<?php

declare(strict_types=1);

namespace Wwwision\GraphQL;

use BackedEnum;
use GraphQL\Server\RequestError;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;

use function Wwwision\Types\instantiate;
final class Resolver
{
    /**
     * @param array<class-string> $typeNamespaces
     */
    public function __construct(
        private readonly object $api,
        private readonly array $typeNamespaces,
    ) {
    }

    /**
     * @param array<string, string|bool|int|array<string, mixed>|null> $args
     */
    public function __invoke(object|string|null $objectValue, array $args, mixed $contextValue, ResolveInfo $info): mixed
    {
        $fieldName = $info->fieldName;
        $objectValue ??= $this->api;

        if (method_exists($objectValue, $fieldName)) {
            $objectValue = $objectValue->{$fieldName}(...$this->convertArguments($args, $info->fieldDefinition));
        } elseif (property_exists($objectValue, $fieldName)) {
            $objectValue = $objectValue->{$fieldName};
        } else {
            return null;
        }
        if ($objectValue instanceof BackedEnum) {
            $objectValue = $objectValue->value;
        }
        return $objectValue;
    }

    /**
     * @param array<string, string|bool|int|array<string, mixed>|null> $arguments
     * @return array<string, string|bool|int|array<string, mixed>|object|null>
     */
    private function convertArguments(array $arguments, FieldDefinition $fieldDefinition): array
    {
        $result = [];
        foreach ($arguments as $name => $value) {
            $argumentDefinition = $fieldDefinition->getArg($name);
            $result[$name] = $this->convertArgument($value, $argumentDefinition);
        }
        return $result;
    }

    /**
     * @param string|bool|int|array<string, mixed>|null $argument
     * @return string|bool|int|array<string, mixed>|object|null
     */
    private function convertArgument(string|bool|int|array|null $argument, ?Argument $argumentDefinition): string|bool|int|array|object|null
    {
        if ($argument === null) {
            return null;
        }
        $type = $argumentDefinition?->getType();
        if ($type instanceof NonNull) {
            $type = $type->getWrappedType();
        }
        $argumentType = $type->name;
        if ($type instanceof ListOfType) {
            $type = $type->ofType;
            if ($type instanceof NonNull) {
                $type = $type->getWrappedType();
            }
            $argumentType = $type->name . 's';
        }
        if (str_ends_with($argumentType, 'Input')) {
            $argumentType = substr($argumentType, 0, -5);
        }

        $className = $this->resolveClassName($argumentType);
        if ($className !== null) {
            try {
                return instantiate($className, $argument);
            } catch (InvalidArgumentException $e) {
                throw new RequestError(sprintf('Validation error for %s: %s', $argumentType, $e->getMessage()), 1688654808, $e);
            }
        }
        return $argument;
    }

    /**
     * @param string $argumentType
     * @return class-string|null
     */
    private function resolveClassName(string $argumentType): ?string
    {
        foreach ($this->typeNamespaces as $namespace) {
            $className = rtrim($namespace, '\\') . '\\' . $argumentType;
            if (class_exists($className)) {
                return $className;
            }
        }
        return null;
    }
}
