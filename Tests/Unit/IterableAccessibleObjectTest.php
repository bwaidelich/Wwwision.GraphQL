<?php
namespace Wwwision\GraphQL\Tests\Unit;

use TYPO3\Flow\Tests\UnitTestCase;
use Wwwision\GraphQL\AccessibleObject;
use Wwwision\GraphQL\Tests\Unit\Fixtures\ExampleObject;

require_once __DIR__ . '/Fixtures/ExampleObject.php';

class IterableAccessibleObjectTest extends UnitTestCase
{

    /**
     * @test
     */
    public function getObjectReturnsTheUnalteredObject()
    {
        $object = new \stdClass();
        $accessibleObject = new AccessibleObject($object);
        $this->assertSame($object, $accessibleObject->getObject());
    }

    /**
     * @test
     */
    public function offsetExistsReturnsFalseIfObjectIsNotSet()
    {
        $accessibleObject = new AccessibleObject(null);
        $this->assertFalse($accessibleObject->offsetExists('foo'));
    }

    public function simpleOffsetGetDataProvider()
    {
        return [
            ['someString', 'Foo'],
            ['someArray', ['string' => 'Foo', 'neos' => 'rocks']],
            ['isFoo', true],
            ['hasBar', false],

            // unresolved:
            ['SomeString', null],
            ['somestring', null],
            ['foo', null],
            ['bar', null],
            ['unknown', null],
        ];
    }

    /**
     * @test
     * @dataProvider simpleOffsetGetDataProvider
     */
    public function simpleOffsetGetTests($propertyName, $expectedValue)
    {
        $accessibleObject = new AccessibleObject(new ExampleObject('Foo'));
        $this->assertSame($expectedValue, $accessibleObject[$propertyName]);
    }

    /**
     * @test
     */
    public function offsetWrapsArraySubObjects()
    {
        //TODO

    }

    /**
     * @test
     */
    public function offsetWrapsIterableSubObjects()
    {
        //TODO

    }

    /**
     * @test
     */
    public function offsetGetReturnsDateTimeProperties()
    {
        $accessibleObject = new AccessibleObject(new ExampleObject('Foo'));
        #$this->assertInstanceOf
        $this->assertSame('13.12.1980', $accessibleObject['someDate']->format('d.m.Y'));
    }

    /**
     * @test
     */
    public function offsetGetWrapsSubObjects()
    {
        $accessibleObject = new AccessibleObject(new ExampleObject('Foo'));
        $this->assertSame('Foo nested nested', $accessibleObject['someSubObject']['someSubObject']['someString']);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function offsetSetThrowsException()
    {
        $accessibleObject = new AccessibleObject(new ExampleObject('Foo'));
        $accessibleObject['someString'] = 'This must not be possible';
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function offsetUnsetThrowsException()
    {
        $accessibleObject = new AccessibleObject(new ExampleObject('Foo'));
        unset($accessibleObject['someString']);
    }
}