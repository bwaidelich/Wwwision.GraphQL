<?php
namespace Wwwision\GraphQL;

use Neos\Flow\Http\Request as HttpRequest;

/**
 * A custom context that will be accessible to all resolvers
 */
final class GraphQLContext
{
    /**
     * @var HttpRequest
     */
    private $httpRequest;

    /**
     * @param HttpRequest $httpRequest
     */
    public function __construct(HttpRequest $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    /**
     * @return HttpRequest
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

}