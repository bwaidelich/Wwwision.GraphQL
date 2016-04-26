<?php
namespace Wwwision\GraphQL\Tests\Unit;

use TYPO3\Flow\Tests\UnitTestCase;
use Wwwision\GraphQL\AccessibleObject;
use Wwwision\GraphQL\TypeResolver;

class TypeResolverTest extends UnitTestCase
{

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionIfTypeClassNameIsNoString()
    {
        new TypeResolver(123);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getThrowsExceptionIfTypeClassNameIsNoValidTypeDefinition()
    {
        new TypeResolver(new \stdClass());
    }
}