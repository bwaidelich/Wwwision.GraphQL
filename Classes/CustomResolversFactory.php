<?php
declare(strict_types=1);

namespace Wwwision\GraphQL;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Webmozart\Assert\Assert;
use Wwwision\TypesGraphQL\Types\CustomResolver;
use Wwwision\TypesGraphQL\Types\CustomResolvers;

#[Flow\Scope('singleton')]
final class CustomResolversFactory
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
    ) {}

    public function create(array $customResolversSettings): CustomResolvers
    {
        $customResolvers = [];
        foreach ($customResolversSettings as $typeName => $settingsForType) {
            Assert::string($typeName);
            Assert::isArray($settingsForType);
            foreach ($settingsForType as $fieldName => $customResolverSettings) {
                Assert::string($fieldName);
                Assert::isArray($customResolverSettings);
                Assert::keyExists($customResolverSettings, 'resolverClassName');
                $resolverClass = $this->objectManager->get($customResolverSettings['resolverClassName']);
                $customResolvers[] = new CustomResolver(
                    $typeName,
                    $fieldName,
                    $resolverClass->{$customResolverSettings['resolverMethodName'] ?? $fieldName}(...),
                    $customResolverSettings['description'] ?? null,
                );
            }
        }
        return CustomResolvers::create(...$customResolvers);
    }
}
