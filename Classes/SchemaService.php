<?php
namespace Wwwision\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use Neos\Flow\Annotations as Flow;
use GraphQL\Type\Schema;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\ObjectAccess;
use Wwwision\GraphQL\Cache\SchemaCache;

/**
 * @Flow\Scope("singleton")
 */
class SchemaService
{
    /**
     * @Flow\InjectConfiguration(path="endpoints")
     * @var array
     */
    protected $endpointConfiguration;

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
     * @var SchemaCache
     */
    protected $schemaCache;

    /**
     * @param string $endpoint
     * @return Schema
     */
    public function getSchemaForEndpoint(string $endpoint): Schema
    {
        $this->verifySettings($endpoint);

        if (isset($this->endpointConfiguration[$endpoint]['querySchema'])) {
            $querySchema = $this->typeResolver->get($this->endpointConfiguration[$endpoint]['querySchema']);
            $mutationSchema = isset($this->endpointConfiguration[$endpoint]['mutationSchema']) ? $this->typeResolver->get($this->endpointConfiguration[$endpoint]['mutationSchema']) : null;
            $subscriptionSchema = isset($this->endpointConfiguration[$endpoint]['subscriptionSchema']) ? $this->typeResolver->get($this->endpointConfiguration[$endpoint]['subscriptionSchema']) : null;

            return new Schema([
                'query' => $querySchema,
                'mutation' => $mutationSchema,
                'subscription' => $subscriptionSchema
            ]);
        }

        return $this->schemaCache->getForEndpoint($endpoint);
    }

    public function verifySettings(string $endpoint)
    {
        if (!isset($this->endpointConfiguration[$endpoint])) {
            throw new \InvalidArgumentException(sprintf('The endpoint "%s" is not configured.', $endpoint), 1461435428);
        }

        if (!isset($this->endpointConfiguration[$endpoint]['schema']) && !isset($this->endpointConfiguration[$endpoint]['querySchema'])) {
            throw new \InvalidArgumentException(sprintf('There is no root query schema configured for endpoint "%s".', $endpoint), 1461435432);
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
}
