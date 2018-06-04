<?php
namespace Wwwision\GraphQL;

interface ResolverInterface
{
    /**
     * @param array $typeConfig
     * @return array
     */
    public function decorateTypeConfig(array $typeConfig);
}
