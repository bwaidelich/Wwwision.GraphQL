<?php
namespace Wwwision\GraphQL;

abstract class AbstractScalarResolver implements ResolverInterface
{

    /**
     * @param $value
     * @return mixed
     */
    abstract public function parseValue($value);

    /**
     * @param $value
     * @return mixed
     */
    abstract public function serialize($value);

    /**
     * @param $valueNode
     * @return mixed
     */
    abstract public function parseLiteral($valueNode);

    /**
     * @param array $typeConfig
     * @return array
     */
    public function decorateTypeConfig(array $typeConfig)
    {
        $typeConfig['parseValue'] = [$this, 'parseValue'];
        $typeConfig['serialize'] = [$this, 'serialize'];
        $typeConfig['parseLiteral'] = [$this, 'parseLiteral'];

        return $typeConfig;
    }
}
