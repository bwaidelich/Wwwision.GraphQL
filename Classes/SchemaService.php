<?php
namespace Wwwision\GraphQL;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\SchemaConfig;
use GraphQL\Utils\BuildSchema;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use GraphQL\Type\Schema;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\Files;
use Neos\Utility\ObjectAccess;

/**
 * @Flow\Scope("singleton")
 */
class SchemaService
{
    /**
     * @Flow\InjectConfiguration(path="endpoints")
     * @var array
     */
    protected $endpointsConfiguration;

    /**
     * @Flow\Inject
     * @var TypeResolver
     */
    protected $typeResolver;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $schemaCache;

    /**
     * Returns the GraphQL Schema for a given $endpoint
     *
     * @param string $endpoint
     * @return Schema
     */
    public function getSchemaForEndpoint(string $endpoint): Schema
    {
        $this->verifySettings($endpoint);
        $endpointConfiguration = $this->endpointsConfiguration[$endpoint];

        if (isset($endpointConfiguration['querySchema'])) {
            $schemaConfig = SchemaConfig::create()
                ->setQuery($this->typeResolver->get($endpointConfiguration['querySchema']));
            if (isset($endpointConfiguration['mutationSchema'])) {
                $schemaConfig->setMutation($this->typeResolver->get($endpointConfiguration['mutationSchema']));
            }
            if (isset($endpointConfiguration['subscriptionSchema'])) {
                $schemaConfig->setSubscription($this->typeResolver->get($endpointConfiguration['subscriptionSchema']));
            }
            return new Schema($schemaConfig);
        }

        if ($this->schemaCache->has($endpoint)) {
            $documentNode = $this->schemaCache->get($endpoint);
        } else {
            $schemaPathAndFilename = $endpointConfiguration['schema'];
            $content = Files::getFileContents($schemaPathAndFilename);
            $documentNode = Parser::parse($content);
            $this->schemaCache->set($endpoint, $documentNode);
        }

        $resolverConfiguration = $endpointConfiguration['resolvers'] ?? [];
        $resolverPathPattern = $endpointConfiguration['resolverPathPattern'] ?? null;
        /** @var Resolver[] $resolvers */
        $resolvers = [];
        return BuildSchema::build($documentNode, function ($config) use (&$resolvers, $resolverConfiguration, $resolverPathPattern) {
            $name = $config['name'];

            if (!isset($resolvers[$name])) {
                if (isset($resolverConfiguration[$name])) {
                    $resolvers[$name] = $this->objectManager->get($resolverConfiguration[$name]);
                } elseif ($resolverPathPattern !== null) {
                    $possibleResolverClassName = str_replace('{Type}', $name, $resolverPathPattern);
                    if ($this->objectManager->isRegistered($possibleResolverClassName)) {
                        $resolvers[$name] = $this->objectManager->get($possibleResolverClassName);
                    }
                }
            }
            if (isset($resolvers[$name])) {
                return $resolvers[$name]->decorateTypeConfig($config);
            }
            return $config;
        });
    }

    /**
     * Verifies the settings for a given $endpoint and throws an exception if they are not valid
     *
     * @param string $endpoint
     * @return void
     * @throws \InvalidArgumentException if the settings are incorrect
     */
    public function verifySettings(string $endpoint)
    {
        if (!isset($this->endpointsConfiguration[$endpoint])) {
            throw new \InvalidArgumentException(sprintf('The endpoint "%s" is not configured.', $endpoint), 1461435428);
        }

        if (!isset($this->endpointsConfiguration[$endpoint]['schema']) && !isset($this->endpointsConfiguration[$endpoint]['querySchema'])) {
            throw new \InvalidArgumentException(sprintf('There is no root query schema configured for endpoint "%s".', $endpoint), 1461435432);
        }

        if (isset($this->endpointsConfiguration[$endpoint]['schema']) && !file_exists($this->endpointsConfiguration[$endpoint]['schema'])) {
            throw new \InvalidArgumentException(sprintf('The Schema file configured for endpoint "%s" does not exist at: "%s".', $endpoint, $this->endpointsConfiguration[$endpoint]['schema']), 1516719329);
        }
    }

    /**
     * @param string|object|array $source
     * @param array $args
     * @param GraphQLContext $context
     * @param ResolveInfo $info
     * @return mixed
     */
    public static function defaultFieldResolver($source, array $args, GraphQLContext $context, ResolveInfo $info)
    {
        $fieldName = $info->fieldName;
        $property = null;

        if (is_array($source) || $source instanceof \ArrayAccess) {
            if (isset($source[$fieldName])) {
                $property = $source[$fieldName];
            }
        } else if (is_object($source)) {
            if (ObjectAccess::isPropertyGettable($source, $fieldName)) {
                $property = ObjectAccess::getProperty($source, $fieldName);
            }
        }

        if ($property instanceof \Closure) {
            return $property($source, $args, $context, $info);
        }

        return $property;
    }


    /**
     * Builds the schema for the given $endpoint and saves it in the cache
     *
     * @param string $endpoint
     * @return DocumentNode
     */
    private function buildSchemaForEndpoint(string $endpoint)
    {
        $schemaPathAndFilename = $this->endpointsConfiguration[$endpoint]['schema'];
        $content = Files::getFileContents($schemaPathAndFilename);
        $documentNode = Parser::parse($content);
        $this->schemaCache->set($endpoint, $documentNode, [md5($schemaPathAndFilename)]);
        return $documentNode;
    }

    /**
     * @return void
     */
    public function warmupCaches()
    {
        foreach($this->endpointsConfiguration as $endpoint => $endpointConfiguration) {
            if (!isset($endpointConfiguration['schema'])) {
                continue;
            }
            $this->buildSchemaForEndpoint($endpoint);
        }
    }
}
