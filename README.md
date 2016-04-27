# Wwwision.GraphQL

Easily create GraphQL APIs with Neos and Flow.

## Background

This package is a small collection of tools that'll make it easier to provide [GraphQL](http://graphql.org/) endpoints
with Neos and Flow.
It is a wrapper for the [PHP port of webonyx](https://github.com/webonyx/graphql-php) that comes with following additions:

* A `TypeResolver` that allows for easy interdependency between complex GraphQL type definitions
* The `AccessibleObject` and `IterableAccessibleObject` wrappers that make it possible to expose arbitrary objects to
  the GraphQL API
* A `StandardController` that renders the [GraphiQL IDE](https://github.com/graphql/graphiql) and acts as dispatcher
  for API calls

## Installation

```
composer require wwwision/graphql
```

(Refer to the [composer documentation](https://getcomposer.org/doc/) for more details)

## Simple tutorial

Create a simple Root Query definition within any Flow package:

`ExampleRootQuery.php`:

    <?php
    namespace Your\Package;
    
    use GraphQL\Type\Definition\ObjectType;
    use GraphQL\Type\Definition\Type;
    use Wwwision\GraphQL\TypeResolver;
    
    class ExampleRootQuery extends ObjectType
    {
        /**
         * @param TypeResolver $typeResolver
         */
        public function __construct(TypeResolver $typeResolver)
        {
            return parent::__construct([
                'name' => 'ExampleRootQuery',
                'fields' => [
                    'ping' => [
                        'type' => Type::string(),
                        'resolve' => function () {
                            return 'pong';
                        },
                    ],
                ],
            ]);
        }
    }

Now register this endpoint like so:

`Settings.yaml`:

    Wwwision:
      GraphQL:
        endpoints:
          'test':
            'querySchema': 'Your\Package\ExampleRootQuery'

And, lastly, activate the corresponding routes:

`Settings.yaml`:

    TYPO3:
      Flow:
        mvc:
          routes:
            'Wwwision.GraphQL':
              variables:
                'endpoint': 'test'

This will make the endpoint "test" available under `/test`.

Note: If you already have more specific routes in place, or want to provide multiple GraphQL endpoints you can as well
activate routes in your global `Routes.yaml` file:

    -
      name: 'GraphQL API'
      uriPattern: '<GraphQLSubroutes>'
      subRoutes:
        'GraphQLSubroutes':
          package: 'Wwwision.GraphQL'
          variables:
            'endpoint': 'test'

Congratulations, your first GraphQL API is done and you should be able to invoke the GraphiQL IDE by browsing to `/test`:

![](graphiql.png)


## More advanced tutorial

Again, start with the Root Query definition

`ExampleRootQuery.php`:

    <?php
    namespace Your\Package;
    
    use GraphQL\Type\Definition\ObjectType;
    use GraphQL\Type\Definition\Type;
    use Wwwision\GraphQL\TypeResolver;
    
    class ExampleRootQuery extends ObjectType
    {
        /**
         * @param TypeResolver $typeResolver
         */
        public function __construct(TypeResolver $typeResolver)
        {
            return parent::__construct([
                'name' => 'ExampleRootQuery',
                'fields' => [
                    'ping' => [
                        'type' => Type::string(),
                        'resolve' => function () {
                            return 'pong';
                        },
                    ],
                ],
            ]);
        }
    }

Now register this endpoint like so:

`Settings.yaml`:

    Wwwision:
      GraphQL:
        endpoints:
          'test':
            'querySchema': 'Your\Package\ExampleRootQuery'

And, lastly, activate the corresponding routes:

`Settings.yaml`:

    TYPO3:
      Flow:
        mvc:
          routes:
            'Wwwision.GraphQL':
              variables:
                'endpoint': 'test'

This will make the endpoint "test" available under `/test`.

Note: If you already have more specific routes in place, or want to provide multiple GraphQL endpoints you can as well
activate routes in your global `Routes.yaml` file:

    -
      name: 'GraphQL API'
      uriPattern: '<GraphQLSubroutes>'
      subRoutes:
        'GraphQLSubroutes':
          package: 'Wwwision.GraphQL'
          variables:
            'endpoint': 'test'

Congratulations, your first GraphQL API is done and you should be able to invoke the GraphiQL IDE by browsing to `/test`:

![](graphiql_01.png)