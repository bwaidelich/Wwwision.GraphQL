<?php
namespace Wwwision\GraphQL;

use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Command\CacheCommandController;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Booting\Step;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\Files;
use Neos\Utility\Unicode\Functions;

/**
 * Entry point for the Wwwision.GraphQL Package used to register connect signals and slots
 */
class Package extends BasePackage
{

    const FILE_MONITOR_IDENTIFIER = 'Wwwision_GraphQL_Files';

    /**
     * @param Bootstrap $bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $applicationContext = $bootstrap->getContext();

        $dispatcher->connect(CacheCommandController::class, 'warmupCaches', SchemaService::class, 'warmupCaches');
        if ($applicationContext->isProduction()) {
            return;
        }
        $dispatcher->connect(Sequence::class, 'afterInvokeStep', function (Step $step) use ($bootstrap, $dispatcher) {
            if ($step->getIdentifier() !== 'neos.flow:systemfilemonitor') {
                return;
            }
            $graphQlFileMonitor = FileMonitor::createFileMonitorAtBoot(self::FILE_MONITOR_IDENTIFIER, $bootstrap);
            $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
            $endpointsConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Wwwision.GraphQL.endpoints');
            $packageManager = $bootstrap->getEarlyInstance(PackageManager::class);

            foreach($endpointsConfiguration as $endpointConfiguration) {
                if (!isset($endpointConfiguration['schema'])) {
                    continue;
                }
                $resourceUriParts = Functions::parse_url($endpointConfiguration['schema']);
                if (isset($resourceUriParts['scheme']) && $resourceUriParts['scheme'] === 'resource') {
                    $package = $packageManager->getPackage($resourceUriParts['host']);
                    $schemaPathAndFilename = Files::concatenatePaths([$package->getResourcesPath(), $resourceUriParts['path']]);
                } else {
                    $schemaPathAndFilename = $endpointConfiguration['schema'];
                }
                $graphQlFileMonitor->monitorFile($schemaPathAndFilename);
            }

            $graphQlFileMonitor->detectChanges();
            $graphQlFileMonitor->shutdownObject();
        });

        $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', function(string $fileMonitorIdentifier, array $changedFiles) use ($bootstrap) {
            if ($fileMonitorIdentifier !== self::FILE_MONITOR_IDENTIFIER || $changedFiles === []) {
                return;
            }
            $schemaCache = $bootstrap->getObjectManager()->get(CacheManager::class)->getCache('Wwwision_GraphQL_Schema');
            $schemaCache->flush();
        });
    }
}
