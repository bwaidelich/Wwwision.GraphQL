<?php
namespace Wwwision\GraphQL\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Wwwision\GraphQL\Tests\Unit\Fixtures\ExampleType;
use Wwwision\GraphQL\TypeResolver;

class TypeResolverTest extends UnitTestCase
{

    /**
     * @var TypeResolver
     */
    protected $typeResolver;

    public function setUp()
    {
        $this->typeResolver = new TypeResolver();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionIfTypeClassNameIsNoString()
    {
        $this->typeResolver->get(123);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionIfTypeClassNameIsNoValidTypeDefinition()
    {
        $this->typeResolver->get('stdClass');
    }

    /**
     * @test
     */
    public function getSupportsRecursiveTypes()
    {
        $exampleType = $this->typeResolver->get(ExampleType::class);
        $this->assertSame('ExampleType', $exampleType->name);
    }

    /**
     * @test
     */
    public function getReturnsTheSameInstancePerType()
    {
        $exampleType1 = $this->typeResolver->get(ExampleType::class);
        $exampleType2 = $this->typeResolver->get(ExampleType::class);
        $this->assertSame($exampleType1, $exampleType2);
    }
}