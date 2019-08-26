<?php
namespace Wwwision\GraphQL;

use Psr\Http\Message\ServerRequestInterface as HttpRequestInterface;

/**
 * A custom context that will be accessible to all resolvers
 */
final class GraphQLContext
{
    /**
     * @var HttpRequestInterface
     */
    private $httpRequest;

    /**
     * @param HttpRequestInterface $httpRequest
     */
    public function __construct(HttpRequestInterface $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    /**
     * @return HttpRequestInterface
     */
    public function getHttpRequest(): HttpRequestInterface
    {
        return $this->httpRequest;
    }

}
