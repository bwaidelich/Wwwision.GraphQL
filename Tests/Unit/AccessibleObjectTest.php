<?php
namespace Wwwision\GraphQL\Tests\Unit;

use Neos\Flow\Tests\UnitTestCase;
use Wwwision\GraphQL\AccessibleObject;
use Wwwision\GraphQL\IterableAccessibleObject;
use Wwwision\GraphQL\Tests\Unit\Fixtures\ExampleObject;

require_once __DIR__ . '/Fixtures/ExampleObject.php';

class AccessibleObjectTest extends UnitTestCase
{

    /**
     * @var AccessibleObject (wrapping an instance of ExampleObject)
     */
    protected $accessibleObject;

    public function setUp()
    {
        $this->accessibleObject = new AccessibleObject(new ExampleObject('Foo'));
    }

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
            ['isFoo', true],
            ['hasBar', false],


            // is* and has* can be omitted (ObjectAccess behavior)
            ['foo', true],
            ['bar', false],

            // The following tests show that property resolving works case insensitive
            // like the underlying ObjectAccess. I think it would be less error prone if they didn't
            // But that's the way it currently works, so these tests merely document that behavior
            ['SomeString', 'Foo'],
            ['somestring', 'Foo'],
            ['SoMeStRiNg', 'Foo'],
        ];
    }

    /**
     * @test
     * @dataProvider simpleOffsetGetDataProvider
     * @param string $propertyName
     * @param mixed $expectedValue
     */
    public function simpleOffsetGetTests($propertyName, $expectedValue)
    {
        $this->assertSame($expectedValue, $this->accessibleObject[$propertyName]);
    }

    /**
     * @test
     * @expectedException \Neos\Utility\Exception\PropertyNotAccessibleException
     */
    public function offsetGetThrowsExceptionForUnknownProperties()
    {
        $this->accessibleObject['unknown'];
    }

    /**
     * @test
     */
    public function offsetGetWrapsSimpleArrayProperties()
    {
        /** @var \Iterator $arrayIterator */
        $arrayIterator = $this->accessibleObject['someArray'];
        $this->assertInstanceOf(IterableAccessibleObject::class, $arrayIterator);
        $firstArrayValue = $arrayIterator->current();
        $firstArrayKey = $arrayIterator->key();
        $arrayIterator->next();
        $secondArrayValue = $arrayIterator->current();
        $secondArrayKey = $arrayIterator->key();
        $this->assertSame('string', $firstArrayKey);
        $this->assertSame('neos', $secondArrayKey);
        $this->assertSame('Foo', $firstArrayValue);
        $this->assertSame('rocks', $secondArrayValue);
    }

    /**
     * @test
     */
    public function offsetGetReturnsDateTimeProperties()
    {
        /** @var \DateTimeInterface $date */
        $date = $this->accessibleObject['someDate'];
        $this->assertInstanceOf(\DateTimeInterface::class, $date);
        $this->assertSame('13.12.1980', $date->format('d.m.Y'));
    }

    /**
     * @test
     */
    public function offsetGetWrapsArraySubObjects()
    {
        /** @var \Iterator $subObjectsIterator */
        $subObjectsIterator = $this->accessibleObject['someSubObjectsArray'];
        $this->assertInstanceOf(IterableAccessibleObject::class, $subObjectsIterator);
        $firstSubObject = $subObjectsIterator->current();
        $subObjectsIterator->next();
        $secondSubObject = $subObjectsIterator->current();
        $this->assertInstanceOf(AccessibleObject::class, $firstSubObject);
        $this->assertInstanceOf(AccessibleObject::class, $secondSubObject);
        $this->assertSame('Foo nested a', $firstSubObject['someString']);
        $this->assertSame('Foo nested b', $secondSubObject['someString']);
    }

    /**
     * @test
     */
    public function offsetGetWrapsIterableSubObjects()
    {
        /** @var \Iterator $subObjectsIterator */
        $subObjectsIterator = $this->accessibleObject['someSubObjectsIterator'];
        $this->assertInstanceOf(IterableAccessibleObject::class, $subObjectsIterator);
        $firstSubObject = $subObjectsIterator->current();
        $subObjectsIterator->next();
        $secondSubObject = $subObjectsIterator->current();
        $this->assertInstanceOf(AccessibleObject::class, $firstSubObject);
        $this->assertInstanceOf(AccessibleObject::class, $secondSubObject);
        $this->assertSame('Foo nested a', $firstSubObject['someString']);
        $this->assertSame('Foo nested b', $secondSubObject['someString']);
    }

    /**
     * @test
     */
    public function offsetGetWrapsSubObjects()
    {
        $this->assertInstanceOf(AccessibleObject::class, $this->accessibleObject['someSubObject']);
        $this->assertInstanceOf(AccessibleObject::class, $this->accessibleObject['someSubObject']['someSubObject']);
        $this->assertSame('Foo nested nested', $this->accessibleObject['someSubObject']['someSubObject']['someString']);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function offsetSetThrowsException()
    {
        $this->accessibleObject['someString'] = 'This must not be possible';
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function offsetUnsetThrowsException()
    {
        unset($this->accessibleObject['someString']);
    }

    /**
     * @test
     */
    public function toStringReturnsCastedObject()
    {
        $this->assertSame('ExampleObject (string-casted)', (string)$this->accessibleObject);
    }
}