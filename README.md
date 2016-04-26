# Wwwision.GraphQL

This package allows you to easily provide GraphQL endpoints with Neos and Flow.

## Background


ExampleRootQuery.php

    <?php
    namespace Wwwision\Test;
    
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


Settings.yaml

    Wwwision:
      GraphQL:
        endpoints:
          'neos':
            'querySchema': 'Your\Package\TheRootQueryType'
            'mutationSchema': 'Your\Package\TheRootMutationType'

Settings.yaml

    TYPO3:
      Flow:
        mvc:
          routes:
            'Wwwision.GraphQL':
              variables:
                'endpoint': 'neos'

