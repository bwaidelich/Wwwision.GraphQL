<?php
namespace Wwwision\GraphQL;


use Neos\Flow\Package\Package as BasePackage;
use Neos\Flow\Command\CacheCommandController;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Booting\Sequence;
use Neos\Flow\Core\Booting\Step;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Monitor\FileMonitor;
use Wwwision\GraphQL\Cache\SchemaCache;

class Package extends BasePackage
{

    const FILE_MONITOR_IDENTIFIER = 'GraphQL_Files';

    /**
     * @param Bootstrap $bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $context = $bootstrap->getContext();


        if (!$context->isProduction()) {
            $dispatcher->connect(Sequence::class, 'afterInvokeStep', function (Step $step) use ($bootstrap, $dispatcher) {
                if ($step->getIdentifier() === 'neos.flow:resources') {
                    $dispatcher->connect(FileMonitor::class, 'filesHaveChanged', SchemaCache::class, 'flushOnFileChanges');$dispatcher->connect(FileMonitor::class, 'filesHaveChanged', SchemaCache::class, 'flushOnFileChanges');
                    $graphQlFileMonitor = FileMonitor::createFileMonitorAtBoot(self::FILE_MONITOR_IDENTIFIER, $bootstrap);
                    $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
                    $endpoints = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Wwwision.GraphQL.endpoints');

                    foreach($endpoints as $endpoint) {
                        if (isset($endpoint['schema'])) {
                            $graphQlFileMonitor->monitorFile($endpoint['schema']);
                        }
                    }

                    $graphQlFileMonitor->detectChanges();
                    $graphQlFileMonitor->shutdownObject();
                }
            });
        }

        $dispatcher->connect(CacheCommandController::class, 'warmupCaches', SchemaCache::class, 'warmup');
    }
}
