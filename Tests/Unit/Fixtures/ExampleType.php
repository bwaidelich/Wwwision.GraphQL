<?php
namespace Wwwision\GraphQL\Tests\Unit\Fixtures;


use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wwwision\GraphQL\TypeResolver;

/**
 * Dummy type for the TypeResolver tests
 */
class ExampleType extends ObjectType
{
    /**
     * @param TypeResolver $typeResolver
     */
    public function __construct(TypeResolver $typeResolver)
    {
        return parent::__construct([
            'name' => 'ExampleType',
            'fields' => [
                'someString' => ['type' => Type::string()],
                'selfReference' => ['type' => $typeResolver->get(ExampleType::class)],
            ],
        ]);
    }

}