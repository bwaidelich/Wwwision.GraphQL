<?php
namespace Wwwision\GraphQL\Http;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * A simple HTTP Component that captures OPTIONS requests and responds with a general "Allow: GET, POST" header if a matching graphQL endpoint is configured
 */
class HttpOptionsMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\InjectConfiguration(path="endpoints")
     * @var array
     */
    protected $endpoints;

    /**
     * @Flow\Inject
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $next
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        // no OPTIONS request => skip
        if ($request->getMethod() !== 'OPTIONS') {
            return $next->handle($request);
        }
        $endpoint = ltrim($request->getUri()->getPath(), '\/');
        // no matching graphQL endpoint configured => skip
        if (!isset($this->endpoints[$endpoint])) {
            return $next->handle($request);
        }
        return $this->responseFactory->createResponse()->withHeader('Allow', 'GET, POST');
    }
}
