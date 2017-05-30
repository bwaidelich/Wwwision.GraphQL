<?php
namespace Wwwision\GraphQL\Tests\Unit;

use Neos\Flow\Http\Request;
use Neos\Flow\Tests\UnitTestCase;
use Wwwision\GraphQL\GraphQLContext;

class GraphQLContextTest extends UnitTestCase
{

    /**
     * @var GraphQLContext
     */
    private $graphQLContext;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockHttpRequest;

    public function setUp()
    {
        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->graphQLContext = new GraphQLContext($this->mockHttpRequest);
    }

    /**
     * @test
     */
    public function getHttpRequestReturnsTheSpecifiedRequestInstance()
    {
        $this->assertSame($this->mockHttpRequest, $this->graphQLContext->getHttpRequest());
    }
}