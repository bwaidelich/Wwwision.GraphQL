<?php
namespace Wwwision\GraphQL\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Wwwision\GraphQL\AccessibleObject;
use Wwwision\GraphQL\IterableAccessibleObject;
use Wwwision\GraphQL\Tests\Unit\Fixtures\ExampleObject;

require_once __DIR__ . '/Fixtures/ExampleObject.php';

class IterableAccessibleObjectTest extends UnitTestCase
{

    /**
     * @var IterableAccessibleObject (wrapping instances of ExampleObject)
     */
    protected $iterableAccessibleObject;

    public function setUp()
    {
        $this->iterableAccessibleObject = new IterableAccessibleObject([
            new ExampleObject('Foo'),
            new ExampleObject('Bar')
        ]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructorThrowsExceptionWhenPassedNull()
    {
        new IterableAccessibleObject(null);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function constructorThrowsExceptionWhenPassedNonIterableObject()
    {
        /** @noinspection PhpParamsInspection */
        new IterableAccessibleObject(new \stdClass());
    }

    /**
     * @test
     */
    public function constructorConvertsArraysToArrayIterator()
    {
        $someArray = ['foo' => 'Foo', 'bar' => 'Bar'];
        $iterableAccessibleObject = new IterableAccessibleObject($someArray);
        $this->assertInstanceOf(\ArrayIterator::class, $iterableAccessibleObject->getIterator());
        $this->assertSame($someArray, iterator_to_array($iterableAccessibleObject->getIterator()));
    }

    /**
     * @test
     */
    public function getIteratorReturnsTheUnalteredInnerIterator()
    {
        $someIterator = new \ArrayIterator(['foo' => 'Foo', 'bar' => 'Bar']);
        $iterableAccessibleObject = new IterableAccessibleObject($someIterator);
        $this->assertSame($someIterator, $iterableAccessibleObject->getIterator());
    }

    /**
     * @test
     */
    public function currentObjectElementsAreWrapped()
    {
        $this->assertInstanceOf(AccessibleObject::class, $this->iterableAccessibleObject->current());
        $this->assertSame('Foo', $this->iterableAccessibleObject->current()['someString']);
    }

   /**
     * @test
     */
    public function currentScalarElementsAreNotWrapped()
    {
        $arrayProperty = ['foo' => 'Foo', 'bar' => 'Bar'];
        $iterableAccessibleObject = new IterableAccessibleObject([$arrayProperty]);

        $this->assertSame($arrayProperty, $iterableAccessibleObject->current());
    }
}