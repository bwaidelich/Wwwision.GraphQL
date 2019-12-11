<?php
namespace Wwwision\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Neos\Flow\Annotations as Flow;

/**
 * A type resolver (aka factory) for GraphQL type definitions.
 * This class is required in order to prevent multiple instantiation of the same type and to allow types to reference themselves
 *
 * Usage:
 *
 * Type::nonNull($typeResolver->get(SomeClass::class))
 *
 * @Flow\Scope("singleton")
 */
class TypeResolver
{
    private const INITIALIZING = '__initializing__';

    /**
     * @var ObjectType[]
     */
    private $types = [];

    /**
     * @param string $typeClassName
     * @return ObjectType
     */
    public function get($typeClassName)
    {
        if (!is_string($typeClassName)) {
            throw new \InvalidArgumentException(sprintf('Expected string, got "%s"', is_object($typeClassName) ? get_class($typeClassName) : gettype($typeClassName)), 1460065671);
        }
        if (!is_subclass_of($typeClassName, Type::class, true)) {
            throw new \InvalidArgumentException(sprintf('The TypeResolver can only resolve types extending "GraphQL\Type\Definition\Type", got "%s"', $typeClassName), 1461436398);
        }
        if (!array_key_exists($typeClassName, $this->types)) {
            // The following code seems weird but it is a way to detect circular dependencies (see
            $this->types[$typeClassName] = self::INITIALIZING;
            $this->types[$typeClassName] = new $typeClassName($this);
        }
        if ($this->types[$typeClassName] === self::INITIALIZING) {
            throw new \RuntimeException(sprintf('The GraphQL Type "%s" seems to have circular dependencies. Please define the fields as callbacks to prevent this error.', $typeClassName), 1554382971);
        }
        return $this->types[$typeClassName];
    }
}
