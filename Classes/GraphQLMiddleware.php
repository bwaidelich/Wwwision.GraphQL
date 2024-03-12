<?php
declare(strict_types=1);

namespace Wwwision\GraphQL;

use GraphQL\Error\ClientAware;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GuzzleHttp\Psr7\Response;
use Neos\Cache\Exception;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use Throwable;
use Wwwision\Types\Exception\CoerceException;
use Wwwision\TypesGraphQL\GraphQLGenerator;
use Wwwision\TypesGraphQL\Types\CustomResolvers;
use function array_map;
use function json_decode;
use function md5;
use function sprintf;
use const JSON_THROW_ON_ERROR;

/**
 * HTTP Component to implement the GraphQL Endpoint, see Settings Neos.Flow.http.chain
 */
final class GraphQLMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $uriPath,
        private readonly string $apiObjectName,
        private readonly array $typeNamespaces,
        private readonly ?string $simulateControllerObjectName,
        public readonly bool $debugMode,
        public readonly string $corsOrigin,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly VariableFrontend $schemaCache,
        private readonly ThrowableStorageInterface $throwableStorage,
        private readonly Context $securityContext,
        private readonly ContainerInterface $serviceLocator,
        private readonly CustomResolvers $customResolvers,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // only handle POST and OPTIONS requests to $this->url
        if (!\in_array($request->getMethod(), ['POST', 'OPTIONS'], true) || $request->getUri()->getPath() !== $this->uriPath) {
            return $handler->handle($request);
        }
        if ($this->simulateControllerObjectName !== null) {
            $mockActionRequest = ActionRequest::fromHttpRequest($request);
            // Simulate a request to the specified controller to trigger authentication
            $mockActionRequest->setControllerObjectName($this->simulateControllerObjectName);
            $this->securityContext->setRequest($mockActionRequest);
        }
        $response = $this->responseFactory->createResponse();
        $response = $this->addCorsHeaders($response);
        if ($request->getMethod() === 'POST') {
            $response = $this->handlePostRequest($request, $response);
        }
        return $response;
    }

    private function handlePostRequest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $api = $this->serviceLocator->get($this->apiObjectName);
        $resolver = new Resolver(
            $api,
            $this->typeNamespaces === [] ? [(new ReflectionClass($api))->getNamespaceName()] : $this->typeNamespaces,
            $this->customResolvers,
        );
        $config = ServerConfig::create()
            ->setSchema($this->getSchema($resolver))
            ->setFieldResolver($resolver)
            ->setErrorsHandler($this->handleGraphQLErrors(...));
        if ($this->debugMode) {
            $config->setDebugFlag();
        }
        $server = new StandardServer($config);
        try {
            $request = $this->parseRequestBody($request);
        } catch (\JsonException $_) {
            return new Response(400, [], 'Invalid JSON request');
        }

        $bodyStream = $this->streamFactory->createStream();
        $newResponse = $server->processPsrRequest($request, $response, $bodyStream);
        // For some reason we need to rewind the stream in order to prevent an empty response body
        $bodyStream->rewind();
        return $newResponse;
    }

    /**
     * @throws \JsonException
     */
    private function parseRequestBody(ServerRequestInterface $request): ServerRequestInterface
    {
        if (!empty($request->getParsedBody())) {
            return $request;
        }
        $parsedBody = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        return $request->withParsedBody($parsedBody);
    }

    private function addCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->corsOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'POST,OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Content-Range,Range');
    }

    private function handleGraphQLErrors(array $errors, callable $formatter): array
    {
        return array_map(fn (Throwable $error) => $this->handleGraphQLError($error, $formatter), $errors);
    }

    private function handleGraphQLError(Throwable $error, callable $formatter): array
    {
        if (!$error instanceof ClientAware || !$error->isClientSafe()) {
            $this->throwableStorage->logThrowable($error);
        }
        $formattedError = $formatter($error);
        $originalException = $error->getPrevious();
        if ($originalException instanceof FlowException) {
            $formattedError['extensions']['statusCode'] = $originalException->getStatusCode();
            $formattedError['extensions']['referenceCode'] = $originalException->getReferenceCode();
        }
        if ($originalException?->getPrevious() instanceof CoerceException) {
            $formattedError['extensions']['issues'] = $originalException->getPrevious()->issues;
        }
        return $formattedError;
    }

    private function getSchema(Resolver $resolver): Schema
    {
        $cacheKey = md5($this->apiObjectName);
        if ($this->schemaCache->has($cacheKey)) {
            $documentNode = AST::fromArray($this->schemaCache->get($cacheKey));
        } else {
            /** @var GraphQLGenerator $generator */
            $generator = $this->serviceLocator->get(GraphQLGenerator::class);
            $schema = $generator->generate($this->apiObjectName, $this->customResolvers)->render();
            try {
                $documentNode = Parser::parse($schema);
            } catch (SyntaxError $e) {
                throw new \RuntimeException(sprintf('Failed to parse GraphQL Schema: %s', $e->getMessage()), 1652975280, $e);
            }
            try {
                $this->schemaCache->set($cacheKey, AST::toArray($documentNode));
            } catch (Exception $e) {
                throw new \RuntimeException(sprintf('Failed to store parsed GraphQL Scheme in cache: %s', $e->getMessage()), 1652975323, $e);
            }
        }
        return BuildSchema::build($documentNode, $resolver->typeConfigDecorator(...));
    }
}
