<?php
namespace Wwwision\GraphQL\Controller;

use GraphQL\GraphQL;
use GraphQL\Schema;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use Wwwision\GraphQL\TypeResolver;
use Wwwision\GraphQL\View\GraphQlView;

/**
 * Default controller serving a GraphiQL interface as well as the GraphQL endpoint
 */
class StandardController extends ActionController
{

    /**
     * @Flow\Inject
     * @var TypeResolver
     */
    protected $typeResolver;

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
        $this->verifySettings($endpoint);
        $this->view->assign('endpoint', $endpoint);
    }

    /**
     * @param string $endpoint The GraphQL endpoint, to allow for providing multiple APIs (this value is set from the routing usually)
     * @param string $query The GraphQL query string (see GraphQL::execute())
     * @param string $variables JSON-encoded list of variables (if any, see GraphQL::execute())
     * @param string $operationName The operation to execute (if multiple, see GraphQL::execute())
     * @return string
     * @Flow\SkipCsrfProtection
     */
    public function queryAction($endpoint, $query, $variables = null, $operationName = null)
    {
        $this->verifySettings($endpoint);
        $decodedVariables = json_decode($variables, true);

        $querySchema = $this->typeResolver->get($this->settings['endpoints'][$endpoint]['querySchema']);
        $mutationSchema = isset($this->settings['endpoints'][$endpoint]['mutationSchema']) ? $this->typeResolver->get($this->settings['endpoints'][$endpoint]['mutationSchema']) : null;
        $subscriptionSchema = isset($this->settings['endpoints'][$endpoint]['subscriptionSchema']) ? $this->typeResolver->get($this->settings['endpoints'][$endpoint]['subscriptionSchema']) : null;
        $schema = new Schema($querySchema, $mutationSchema, $subscriptionSchema);
        $result = GraphQL::executeAndReturnResult($schema, $query, null, $decodedVariables, $operationName);
        $this->view->assign('result', $result);
    }

    /**
     * @param string $endpoint
     * @return void
     */
    private function verifySettings($endpoint)
    {
        if (!isset($this->settings['endpoints'][$endpoint])) {
            throw new \InvalidArgumentException(sprintf('The endpoint "%s" is not configured.', $endpoint), 1461435428);
        }
        if (!isset($this->settings['endpoints'][$endpoint]['querySchema'])) {
            throw new \InvalidArgumentException(sprintf('There is no root query schema configured for endpoint "%s".', $endpoint), 1461435432);
        }
    }

}