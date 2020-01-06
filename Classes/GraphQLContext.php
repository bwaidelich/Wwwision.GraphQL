<?php
namespace Wwwision\GraphQL;

use Psr\Http\Message\ServerRequestInterface;

/**
 * A custom context that will be accessible to all resolvers
 */
final class GraphQLContext
{
    /**
     * @var ServerRequestInterface
     */
    private $httpRequest;

    /**
     * @param ServerRequestInterface $httpRequest
     */
    public function __construct(ServerRequestInterface $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getHttpRequest(): ServerRequestInterface
    {
        return $this->httpRequest;
    }

}
