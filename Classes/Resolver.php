<?php
namespace Wwwision\GraphQL;

class Resolver
{
    /**
     * @param array $typeConfig
     * @return array
     */
    public function decorateTypeConfig(array $typeConfig)
    {
        $fields = $typeConfig['fields']();

        $typeConfig['fields'] = &$fields;
        foreach($fields as $name => &$config) {
            $resolveMethod = [$this, $name];
            if (is_callable($resolveMethod)) {
                $config['resolve'] = $resolveMethod;
            }
        }

        return $typeConfig;
    }
}
