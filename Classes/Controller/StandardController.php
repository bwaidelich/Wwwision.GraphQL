<?php
namespace Wwwision\GraphQL\Controller;

use GraphQL\GraphQL;
use GraphQL\Schema;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use Wwwision\GraphQL\TypeResolver;

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
     * @param string $endpoint
     * @return void
     */
    public function indexAction($endpoint)
    {
        $this->verifySettings($endpoint);
        $this->view->assign('endpoint', $endpoint);
    }

    /**
     * @param string $endpoint
     * @param string $query
     * @param string $variables
     * @param string $operationName
     * @return string
     * @Flow\SkipCsrfProtection
     */
    public function queryAction($endpoint, $query, $variables = null, $operationName = null)
    {
        $this->verifySettings($endpoint);
        $decodedVariables = json_decode($variables, true);
        try {
            $querySchema = $this->typeResolver->get($this->settings['endpoints'][$endpoint]['querySchema']);
            $mutationSchema = isset($this->settings['endpoints'][$endpoint]['mutationSchema']) ? $this->typeResolver->get($this->settings['endpoints'][$endpoint]['mutationSchema']) : null;
            $subscriptionSchema = isset($this->settings['endpoints'][$endpoint]['subscriptionSchema']) ? $this->typeResolver->get($this->settings['endpoints'][$endpoint]['subscriptionSchema']) : null;
            $schema = new Schema($querySchema, $mutationSchema, $subscriptionSchema);
            $result = GraphQL::execute($schema, $query, null, $decodedVariables, $operationName);
        } catch (\Exception $exception) {
            $result = ['errors' => [['message' => $exception->getMessage()]]];
        }
        header('Content-Type: application/json');
        return json_encode($result);
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