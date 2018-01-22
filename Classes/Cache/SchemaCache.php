<?php
namespace Wwwision\GraphQL\Cache;

use Neos\Flow\Annotations as Flow;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Wwwision\GraphQL\Package;
use Wwwision\GraphQL\Resolver;

/**
 * This class is responsible for writing and restoring a graphql schema to and from the
 * PhpFrontend cache.
 * Schemas are also decorated with their respective resolvers
 */
final class SchemaCache
{
    /**
     * @Flow\Inject
     * @var PhpFrontend
     */
    protected $cache;

    /**
     * @Flow\InjectConfiguration("endpoints")
     * @var array
     */
    protected $endpointsConfiguration;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param string $tag
     * @return string
     */
    protected function sanitizeTag(string $tag): string
    {
        $sanitizedTag = preg_replace('/[^a-zA-Z0-9_%\-&]/', '_', $tag);
        return substr($sanitizedTag, 0, 250);
    }

    /**
     * @param string $endpoint
     * @return Schema
     */
    public function getForEndpoint(string $endpoint): Schema
    {
        if (!$this->cache->has($endpoint)) {
            $this->buildForEndpoint($endpoint);
        }

        $configuration = $this->endpointsConfiguration[$endpoint];
        $resolverConfiguration = $configuration['resolvers'] ?? [];
        $resolverPathPattern = $configuration['resolverPathPattern'] ?? null;
        $code = $this->cache->requireOnce($endpoint);
        $document = AST::fromArray($code);

        /** @var Resolver[] $resolvers */
        $resolvers = [];

        return BuildSchema::build($document, function ($config) use ($resolvers, $resolverConfiguration, $resolverPathPattern) {
            $name = $config['name'];

            if (!isset($resolvers[$name])) {
                if (isset($resolverConfiguration[$name])) {
                    $resolvers[$name] = $this->objectManager->get($resolverConfiguration[$name]);
                } else if ($resolverPathPattern !== null) {
                    $possibleResolverName = str_replace('{Type}', $name, $resolverPathPattern);
                    if (class_exists($possibleResolverName)) {
                        $resolvers[$name] = $this->objectManager->get($possibleResolverName);
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
     * @param string $endpoint
     */
    protected function buildForEndpoint(string $endpoint)
    {
        $filename = $this->endpointsConfiguration[$endpoint]['schema'];
        $content = file_get_contents($filename);
        $document = Parser::parse($content);

        $this->cache->set($endpoint, 'return ' . var_export(AST::toArray($document), true) . ';', [
            $this->sanitizeTag($filename)
        ]);
    }

    /**
     * @param string $fileMonitorIdentifier
     * @param array $changedFiles
     */
    public function flushOnFileChanges(string $fileMonitorIdentifier, array $changedFiles)
    {
        if ($fileMonitorIdentifier === Package::FILE_MONITOR_IDENTIFIER) {
            foreach(array_keys($changedFiles) as $changedFile) {
                $this->cache->flushByTag($this->sanitizeTag($changedFile));
            }
        }
    }

    /**
     *
     */
    public function warmup()
    {
        foreach(array_keys($this->endpointsConfiguration) as $endpoint) {
            $this->buildForEndpoint($endpoint);
        }
    }
}
