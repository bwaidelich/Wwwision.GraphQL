# Wwwision.GraphQL

Easily create GraphQL APIs with [https://www.neos.io/](Neos) and [https://flow.neos.io/](Flow).

## Background

This package is a small collection of tools that'll make it easier to provide [GraphQL](http://graphql.org/) endpoints
with Neos and Flow.
It is a wrapper for the [PHP port of webonyx](https://github.com/webonyx/graphql-php) that comes with automatic Schema generation from PHP code (using [wwwision/types](https://github.com/bwaidelich/types))
and an easy-to-configure [PSR-15](https://www.php-fig.org/psr/psr-15/) compatible HTTP middleware.

## Usage

Install via  [composer](https://getcomposer.org/doc/):

```
composer require wwwision/graphql
```

### Simple tutorial

Create a class containing at least one public method with a `Query` attribute (see [wwwision/types-graphql](https://github.com/bwaidelich/types-graphql) for more details):

```php
<?php
namespace Your\Package;

use Neos\Flow\Annotations as Flow;
use Wwwision\TypesGraphQL\Attributes\Query;

#[Flow\Scope('singleton')]
final class YourApi
{
    #[Query]
    public function ping(string $name): string {
        return strtoupper($name);
    }
}
```

Now define a [virtual object](https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/ObjectManagement.html#sect-virtual-objects) for the [HTTP middleware](https://flowframework.readthedocs.io/en/stable/TheDefinitiveGuide/PartIII/Http.html#middlewares-chain)
in some `Objects.yaml` configuration:

```yaml
'Your.Package:GraphQLMiddleware':
  className: 'Wwwision\GraphQL\GraphQLMiddleware'
  scope: singleton
  factoryObjectName: Wwwision\GraphQL\GraphQLMiddlewareFactory
  arguments:
    1:
      value: '/graphql'
    2:
      value: 'Your\Package\YourApi'
```

And, lastly, register that custom middleware in `Settings.yaml`:

```yaml
Neos:
  Flow:
    http:
      middlewares:
        'Your.Package:GraphQL':
          position: 'before routing'
          middleware: 'Your.Package:GraphQLMiddleware'
```

And with that, a working GraphQL API is accessible underneath `/graphql`.

### Complex types

By default, all types with the *same namespace* as the specified API class will be resolved automatically, so you could do:

```php
// ...
#[Query]
public function ping(Name $name): Name {
    return strtoupper($name);
}
```
as long as there is a suitable `Name` object in the same namespace (`Your\Package`).
To support types from _different_ namespaces, those can be specified as third argument of the `GraphQLMiddlewareFactory`:

```yaml
'Your.Package:GraphQLMiddleware':
  # ...
  arguments:
    # ...
    # Look for classes in the following namespaces when resolving types:
    3:
      value:
        - 'Your\Package\Types'
        - 'SomeOther\Package\Commands'
```

### Authentication

Commonly the GraphQL middleware is executed before the routing middleware. So the `Security\Context` is not yet initialized.
This package allows you to "simulate" an MVC request  though in order to initialize security.
This is done with the fourth argument of the `GraphQLMiddlewareFactory`:

```yaml
'Your.Package:GraphQLMiddleware':
  # ...
  arguments:
    # ...
    # Simulate a request to the Neos NodeController in order to initialize the security context and trigger the default Neos backend authentication provider
    4:
      value: 'Neos\Neos\Controller\Frontend\NodeController'
```

### Custom Resolvers

Starting with version [5.2](https://github.com/bwaidelich/Wwwision.GraphQL/releases/tag/5.2.0) custom functions can be registered that extend the behavior of types dynamically:

```yaml
'Your.Package:GraphQLMiddleware':
  # ...
  arguments:
    # ...
    # custom resolvers
    5:
      value:
        'User':
          'fullName':
            description: 'Custom resolver for User.fullName'
            resolverClassName: Some\Package\SomeCustomResolvers
            resolverMethodName: 'getFullName'
          'isAllowed':
            resolverClassName: Some\Package\SomeCustomResolvers
```

**Note:** The `resolverMethodName` can be omitted if it is equal to the custom field name

All custom resolvers have to be public functions with the extended type as first argument (and optionally additional arguments) and a specified return type

For the example above, the corresponding resolver class could look like this:

```php
final class SomeCustomResolvers {

    public function __construct(private readonly SomeDependency $incjection) {}
    
    public function getFullName(User $user): string {
        return $user->givenName . ' ' . $user->familyName;
    }
    
    public function isAllowed(User $user, Privilege $privilege): bool {
        return $this->incjection->isUserPrivilegeAllowed($user->id, $privilege);
    }
}
```

### More

See [wwwision/types](https://github.com/bwaidelich/types) and [wwwision/types-graphql](https://github.com/bwaidelich/types-graphql) for more examples and how to use more complex types.

## Contribution

Contributions in the form of [issues](https://github.com/bwaidelich/Wwwision.GraphQL/issues) or [pull requests](https://github.com/bwaidelich/Wwwision.GraphQL/pulls) are highly appreciated

## License

See [LICENSE](./LICENSE)
