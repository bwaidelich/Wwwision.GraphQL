<?php
namespace Wwwision\GraphQL\Http;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;

/**
 * A simple HTTP Component to configure CORS for the GraphQL endpoint
 */
class HttpCorsComponent implements ComponentInterface
{
    /**
     * @Flow\InjectConfiguration(path="endpoints")
     * @var array
     */
    protected $endpoints;

    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param ComponentContext $componentContext
     * @return void
     */
    public function handle(ComponentContext $componentContext)
    {
        $httpRequest = $componentContext->getHttpRequest();
        // no matching graphQL endpoint configured => skip
        if (!isset($this->endpoints[$httpRequest->getRelativePath()])) {
            return;
        }
        $httpResponse = $componentContext->getHttpResponse();
        $headers = $this->options['headers'] ?? [];
        foreach ($headers as $headerName => $headerValue) {
            $httpResponse->setHeader($headerName, $headerValue);
        }
    }
}
