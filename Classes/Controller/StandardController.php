<?php
namespace Wwwision\GraphQL\Controller;

use GraphQL\GraphQL;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Wwwision\GraphQL\GraphQLContext;
use Wwwision\GraphQL\SchemaService;
use Wwwision\GraphQL\TypeResolver;
use Wwwision\GraphQL\View\GraphQlView;

/**
 * Default controller serving a GraphQL Playground interface as well as the GraphQL endpoint
 */
class StandardController extends ActionController
{

    /**
     * @Flow\Inject
     * @var TypeResolver
     */
    protected $typeResolver;

    /**
     * @Flow\Inject
     * @var SchemaService
     */
    protected $schemaService;

    /**
     * @var array
     */
    protected $supportedMediaTypes = ['application/json', 'text/html'];

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = ['json' => GraphQlView::class];

    /**
     * @param string $endpoint The GraphQL endpoint, to allow for providing multiple APIs (this value is set from the routing usually)
     * @return void
     */
    public function indexAction($endpoint)
    {
        $this->schemaService->verifySettings($endpoint);
        $this->view->assign('endpoint', $endpoint);
    }

    /**
     * @param string $endpoint The GraphQL endpoint, to allow for providing multiple APIs (this value is set from the routing usually)
     * @param string $query The GraphQL query string (see GraphQL::execute())
     * @param array $variables list of variables (if any, see GraphQL::execute()). Note: The variables can be JSON-serialized to a string or a "real" array
     * @param string $operationName The operation to execute (if multiple, see GraphQL::execute())
     * @return void
     * @Flow\SkipCsrfProtection
     * @throws \Neos\Flow\Mvc\Exception\NoSuchArgumentException
     */
    public function queryAction($endpoint, $query, $variables = null, $operationName = null)
    {
        if ($variables !== null && is_string($this->request->getArgument('variables'))) {
            $variables = json_decode($this->request->getArgument('variables'), true);
        }

        $schema = $this->schemaService->getSchemaForEndpoint($endpoint);
        $context = new GraphQLContext($this->request->getHttpRequest());
        GraphQL::setDefaultFieldResolver([SchemaService::class, 'defaultFieldResolver']);
        $result = GraphQL::executeQuery($schema, $query, null, $context, $variables, $operationName);
        $this->view->assign('result', $result);
    }

}
