<?php
namespace Wwwision\GraphQL\Tests\Unit\Http;

use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Tests\UnitTestCase;
use Wwwision\GraphQL\Http\HttpOptionsComponent;

class HttpOptionsComponentTest extends UnitTestCase
{
    /**
     * @var HttpOptionsComponent
     */
    private $httpOptionsComponent;

    /**
     * @var ComponentContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockComponentContext;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockHttpRequest;

    /**
     * @var Response|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockHttpResponse;

    public function setUp()
    {
        $this->httpOptionsComponent = new HttpOptionsComponent();

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $this->mockHttpResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->mockHttpResponse));
    }

    /**
     * @test
     */
    public function handleSkipsNonOptionRequests()
    {
        $mockGraphQLEndpoints = [
            'existing-endpoint' => ['querySchema' => 'Foo'],
        ];
        $this->inject($this->httpOptionsComponent, 'endpoints', $mockGraphQLEndpoints);
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('GET'));
        $this->mockHttpRequest->expects($this->never())->method('getRelativePath');

        $this->mockHttpResponse->expects($this->never())->method('setHeader');
        $this->mockComponentContext->expects($this->never())->method('setParameter');
        $this->httpOptionsComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSkipsOptionsRequestsThatDontMatchConfiguredEndpoints()
    {
        $mockGraphQLEndpoints = [
            'existing-endpoint' => ['querySchema' => 'Foo'],
        ];
        $this->inject($this->httpOptionsComponent, 'endpoints', $mockGraphQLEndpoints);
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('OPTIONS'));
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('non-existing-endpoint'));

        $this->mockHttpResponse->expects($this->never())->method('setHeader');
        $this->mockComponentContext->expects($this->never())->method('setParameter');
        $this->httpOptionsComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsAllowHeaderForMatchingOptionsRequests()
    {
        $mockGraphQLEndpoints = [
            'existing-endpoint' => ['querySchema' => 'Foo'],
        ];
        $this->inject($this->httpOptionsComponent, 'endpoints', $mockGraphQLEndpoints);
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('OPTIONS'));
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('existing-endpoint'));

        $this->mockHttpResponse->expects($this->exactly(1))->method('setHeader')->with('Allow', 'GET, POST');
        $this->httpOptionsComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleCancelsComponentChainForMatchingOptionsRequests()
    {
        $mockGraphQLEndpoints = [
            'existing-endpoint' => ['querySchema' => 'Foo'],
        ];
        $this->inject($this->httpOptionsComponent, 'endpoints', $mockGraphQLEndpoints);
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('OPTIONS'));
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('existing-endpoint'));

        $this->mockComponentContext->expects($this->exactly(1))->method('setParameter')->with(ComponentChain::class, 'cancel', true);
        $this->httpOptionsComponent->handle($this->mockComponentContext);
    }
}