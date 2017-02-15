<?php
namespace Wwwision\GraphQL;

use Neos\Flow\Annotations as Flow;

/**
 * A proxy that recursively exposes all getters of objects within a collection (\Iterator or array) through an ArrayAccess interface
 * @see AccessibleObject
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
 * $customers = new IterableAccessibleObject([
 *  new Customer()),
 *  new Customer())
 * ]);
 *
 * foreach ($customers as $customer) {
 *     $wrappedType['name']; // "John Doe"
 *     $wrappedType['isActive']; // true
 * }
 *
 *
 * @Flow\Proxy(false)
 */
class IterableAccessibleObject implements \Iterator
{

    /**
     * @var \Iterator
     */
    protected $innerIterator;

    /**
     * @param \Iterator|array $object
     */
    public function __construct($object)
    {
        if ($object instanceof \Iterator) {
            $this->innerIterator = $object;
        } elseif (is_array($object)) {
            $this->innerIterator = new \ArrayIterator($object);
        } else {
            throw new \InvalidArgumentException('The IterableAccessibleObject only works on arrays or objects implementing the Iterator interface', 1460895979);
        }
    }

    /**
     * @return \Iterator
     */
    public function getIterator()
    {
        return $this->innerIterator;
    }

    /**
     * @return AccessibleObject
     */
    public function current()
    {
        $currentElement = $this->innerIterator->current();
        if (is_object($currentElement)) {
            return new AccessibleObject($currentElement);
        }
        return $currentElement;
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->innerIterator->next();
    }

    /**
     * @return string
     */
    public function key()
    {
        return $this->innerIterator->key();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->innerIterator->valid();
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->innerIterator->rewind();
    }
}