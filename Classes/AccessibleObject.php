<?php
namespace Wwwision\GraphQL;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;

/**
 * A proxy that exposes all getters of a given object through an ArrayAccess interface
 *
 * Usage:
 *
 *
 * class Customer
 * {
 *     public function getName()
 *     {
 *         return 'John Doe';
 *     }
 *
 *     public function isActive()
 *     {
 *         return true;
 *     }
 * }
 *
 * $customer = new AccessibleObject(new Customer());
 *
 * $customer['name']; // "John Doe"
 * $customer['isActive']; // true
 *
 *
 * @Flow\Proxy(false)
 */
class AccessibleObject implements \ArrayAccess
{

    /**
     * @var object
     */
    protected $object;

    /**
     * @param object $object Arbitrary object to be wrapped
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $propertyName
     * @return bool
     */
    public function offsetExists($propertyName)
    {
        if ($this->object === null) {
            return false;
        }
        if (preg_match('/^(is|has)([A-Z])/', $propertyName) === 1) {
            return is_callable([$this->object, $propertyName]);
        }
        return ObjectAccess::isPropertyGettable($this->object, $propertyName);
    }

    /**
     * @param string $propertyName
     * @return mixed
     */
    public function offsetGet($propertyName)
    {
        if ($this->object === null) {
            return null;
        }
        if (preg_match('/^(is|has)([A-Z])/', $propertyName) === 1) {
            return (boolean)call_user_func([$this->object, $propertyName]);
        }
        $result = ObjectAccess::getProperty($this->object, $propertyName);
        if (is_array($result) || $result instanceof \Iterator) {
            return new IterableAccessibleObject($result);
        }
        if ($result instanceof \DateTimeInterface) {
            return $result;
        }
        if (is_object($result)) {
            return new self($result);
        }
        return $result;
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('The AccessibleObject wrapper does not allow for mutation!', 1460895624);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        throw new \RuntimeException('The AccessibleObject wrapper does not allow for mutation!', 1460895625);
    }

    /**
     * This is required in order to implicitly cast wrapped string types for example
     *
     * @return string
     */
    function __toString()
    {
        return (string)$this->object;
    }


}