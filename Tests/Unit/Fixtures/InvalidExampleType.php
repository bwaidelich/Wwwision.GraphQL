<?php
namespace Wwwision\GraphQL\Tests\Unit\Fixtures;


use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wwwision\GraphQL\TypeResolver;

/**
 * Dummy type for the TypeResolver tests
 */
class InvalidExampleType extends ObjectType
{
    /**
     * @param TypeResolver $typeResolver
     */
    public function __construct(TypeResolver $typeResolver)
    {
        parent::__construct([
            'name' => 'InvalidExampleType',
            'fields' => [
                'someString' => ['type' => Type::string()],
                // for circular dependencies fields must be declared as callback, see https://webonyx.github.io/graphql-php/type-system/object-types/#recurring-and-circular-types
                'selfReference' => ['type' => $typeResolver->get(self::class)],
            ],
        ]);
    }

}
