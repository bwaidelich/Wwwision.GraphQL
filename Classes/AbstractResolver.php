<?php
namespace Wwwision\GraphQL;

use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

abstract class AbstractResolver implements ResolverInterface
{
    /**
     * @param $value
     * @param GraphQLContext $context
     * @param ResolveInfo $info
     * @return string
     * @throws Error
     */
    public function resolveType($value, GraphQLContext $context, ResolveInfo $info): string
    {
        throw new Error(static::class . ' is missing a resolveType implementation');
    }

    /**
     * @param array $typeConfig
     * @return array
     */
    public function decorateTypeConfig(array $typeConfig)
    {
        $typeConfig['resolveType'] = [$this, 'resolveType'];
        $fields = $typeConfig['fields'];

        $typeConfig['fields'] = function () use ($fields) {
            $resolvedFields = [];
            foreach($fields() as $name => $config) {
                $resolveMethod = [$this, $name];
                if (is_callable($resolveMethod)) {
                    $config['resolve'] = $resolveMethod;
                }

                $resolvedFields[$name] = $config;
            }

            return $resolvedFields;
        };

        return $typeConfig;
    }
}
