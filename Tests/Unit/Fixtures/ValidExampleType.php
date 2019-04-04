<?php
namespace Wwwision\GraphQL\Tests\Unit\Fixtures;


use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Wwwision\GraphQL\TypeResolver;

/**
 * Dummy type for the TypeResolver tests
 */
class ValidExampleType extends ObjectType
{
    /**
     * @param TypeResolver $typeResolver
     */
    public function __construct(TypeResolver $typeResolver)
    {
        parent::__construct([
            'name' => 'ValidExampleType',
            'fields' => function() use ($typeResolver) {
                return [
                    'someString' => ['type' => Type::string()],
                    'selfReference' => ['type' => $typeResolver->get(self::class)],
                ];
            }
        ]);
    }

}
