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
// YourApi.php
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
// Objects.yaml
'Your.Package:GraphQLMiddleware':
  className: 'Wwwision\GraphQL\GraphQLMiddleware'
  scope: singleton
  factoryObjectName: Wwwision\GraphQL\GraphQLMiddlewareFactory
  arguments:
    1:
      # GraphQL URL
      value: '/graphql'
    2:
      # PHP Class with the Query/Mutation attributed methods
      value: 'Your\Package\YourApi'
```

And, lastly, register that custom middleware in `Settings.yaml`:

```yaml
// Settings.yaml
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
// YourApi.php
// ...
#[Query]
public function ping(Name $name): Name {
    return strtoupper($name);
}
```
as long as there is a suitable `Name` object in the same namespace (`Your\Package`).
To support types from _different_ namespaces, those can be specified as third argument of the `GraphQLMiddlewareFactory`:

```yaml
// Objects.yaml
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
// Objects.yaml
'Your.Package:GraphQLMiddleware':
  # ...
  arguments:
    # ...
    # Simulate a request to the Neos NodeController in order to initialize the security context and trigger the default Neos backend authentication provider
    4:
      value: 'Neos\Neos\Controller\Frontend\NodeController'
```

> [!IMPORTANT]  
> There must not be any gaps in the argument definitions due to the way Flow parses this configuration
> To only specify the simulated controller, you can pass an empty `value: []`  array for the 3rd argument

### Custom Resolvers

Starting with version [5.2](https://github.com/bwaidelich/Wwwision.GraphQL/releases/tag/5.2.0) custom functions can be registered that extend the behavior of types dynamically:

```yaml
// Objects.yaml
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

> [!NOTE]
> The `resolverMethodName` can be omitted if it is equal to the custom field name

> [!IMPORTANT]  
> There must not be any gaps in the argument definitions due to the way Flow parses this configuration
> To only specify custom resolves, you can pass an empty `value: []`  array for the 3rd argument and `value: null` for the fourth


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

## FAQ

<details>
<summary><b>Q: How can I implement lazy-loading?</b></summary>

The major rewrite with version [5.0](https://github.com/bwaidelich/Wwwision.GraphQL/releases/tag/5.0.0) led to all fields of a type to be loaded and encoded by default.
In my experience, that leads to a better performance due to the reduced i/o and (de)serialization that comes with lazy-loading every field.
However, if you work with highly complex or nested types, the overhead of pre-loading all fields can be a problem.
In that case you can simplify the structures by adding more specific rootlevel queries.
Alternatively you can use [Custom Resolvers](#custom-resolvers).

I would like to make it easier to provide lazily loaded fields (see https://github.com/bwaidelich/types-graphql/issues/6), but currently I don't have the personal need for this feature.
</details>

<details>
<summary><b>Q: I have issues with Neos Flow proxy classes</b></summary>

The [wwwision/types](https://github.com/bwaidelich/types) package, that this library is built on top of, relies on constructors to contain all involved fields (see https://github.com/bwaidelich/types/blob/main/README.md#all-state-fields-in-the-constructor).
Flow Proxy classes (and due to a [bug](https://github.com/neos/flow-development-collection/issues/3060) that is practically every class of a Flow package) override the constructor with one that has no parameters.

As a work-around you can add a `#Flow\Proxy(false)` attribute to the affected classes.

I'm thinking about adding an extension point to the parser to allow proxy classes out of the box (see https://github.com/bwaidelich/types/issues/6), but currently I don't have the personal need for this feature.
</details>

<details>
<summary><b>Q: What about a GraphQL Schema generator from doctrine entities?</b></summary>

Exposing entities directly to an API can be problematic because it increases coupling and can impede maintainance. However, sometimes and especially with smaller API it is the most pragmatic solution to use the same entity classes and value objects in the core as well as "on the edge".
Personally I would avoid exposing (or even using) doctrine entities because they tend to lead to [Anemic Domain Models](https://martinfowler.com/bliki/AnemicDomainModel.html) and couple the core domain to the infrastructure.
Instead, I prefer to use the PHP type system as much as possible (and the [wwwision/types](https://github.com/bwaidelich/types) package for enforcing validation) and [adapters](https://en.wikipedia.org/wiki/Hexagonal_architecture_(software)) to map those from/to database records.

With all that, it should still be possible to derive the GraphQL schema from doctrine entities as long as the constructor contains all fields and the class is not proxied by Flow (see above):

```php
 use Doctrine\ORM\Mapping as ORM;
 use Neos\Flow\Annotations as Flow;

 /**
  * @ORM\Entity
  * @Flow\Proxy(false)
  */
class TestEntity
{

    /**
     * @var string
     * @ORM\Id
     */
    public readonly string $id;

    /**
     * @var string
     * @ORM\Column(length=80)
     */
    public readonly string $title;

    public function __construct(string $id, string $title) {
        $this->id = $id;
        $this->title = $title;
    }
}
```

With https://github.com/bwaidelich/types/issues/6 integration could be improved probably.
</details>

<details>
<summary><b>Q: How to deal with breaking changes in version 5.0?</b></summary>

[Version 5.0](https://github.com/bwaidelich/Wwwision.GraphQL/releases/tag/5.0.0) was a major rewrite of this package with a new foundation and philosophy.
If that approach does not work for you at all, you can still use older versions of this package, I plan to support version 4.x for a while!
</details>

<details>
<summary><b>Q: What about feature x?</b></summary>

I mainly created this package for my own projects and those of my clients, but of course it makes me happy to see it being used elsewhere.
So feel free to provide [feature suggestions](https://github.com/bwaidelich/Wwwision.GraphQL/issues) or even [implementations](https://github.com/bwaidelich/Wwwision.GraphQL/pulls) but please don't expect me to comply as I have to maintain this package in my free time.

If you need a specific feature implemented or bug fixed, you can of course also hire me to do so!
</details>

## Contribution

Contributions in the form of [issues](https://github.com/bwaidelich/Wwwision.GraphQL/issues) or [pull requests](https://github.com/bwaidelich/Wwwision.GraphQL/pulls) are highly appreciated

## License

See [LICENSE](./LICENSE)
