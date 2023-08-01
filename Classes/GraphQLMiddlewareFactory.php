<?php
declare(strict_types=1);

namespace Wwwision\GraphQL;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Context;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[Flow\Scope('singleton')]
final class GraphQLMiddlewareFactory
{
    public function __construct(
        private readonly bool $debugMode,
        private readonly string $corsOrigin,
        private readonly VariableFrontend $schemaCache,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ThrowableStorageInterface $throwableStorage,
        private readonly Context $securityContext,
        private readonly ObjectManagerInterface $objectManager,
    ) {}

    public function create(
        string $uriPath,
        string $apiObjectName,
        array $typeNamespaces = [],
        string $simulateControllerObjectName = null
    ): GraphQLMiddleware
    {
        return new GraphQLMiddleware(
            $uriPath,
            $apiObjectName,
            $typeNamespaces,
            $simulateControllerObjectName,
            $this->debugMode,
            $this->corsOrigin,
            $this->streamFactory,
            $this->responseFactory,
            $this->schemaCache,
            $this->throwableStorage,
            $this->securityContext,
            $this->objectManager,
        );
    }
}
