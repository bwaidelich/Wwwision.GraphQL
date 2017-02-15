<?php
namespace Wwwision\GraphQL\View;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Response as HttpResponse;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\View\AbstractView;
use Neos\Flow\Exception as FlowException;

class GraphQlView extends AbstractView
{

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @return string The rendered view
     * @throws FlowException
     */
    public function render()
    {
        if (!isset($this->variables['result'])) {
            throw new FlowException(sprintf('The GraphQlView expects a variable "result" of type "%s", non given!', ExecutionResult::class), 1469545196);
        }
        $result = $this->variables['result'];
        if (!$result instanceof ExecutionResult) {
            throw new FlowException(sprintf('The GraphQlView expects a variable "result" of type "%s", "%s" given!', ExecutionResult::class, is_object($result) ? get_class($result) : gettype($result)), 1469545198);
        }

        /** @var HttpResponse $response */
        $response = $this->controllerContext->getResponse();
        $response->setHeader('Content-Type', 'application/json');

        return json_encode($this->formatResult($result));
    }

    /**
     * Formats the result of the GraphQL execution, converting Flow exceptions by hiding the original exception message
     * and adding status- and referenceCode.
     *
     * @param ExecutionResult $executionResult
     * @return array
     */
    private function formatResult(ExecutionResult $executionResult)
    {
        $convertedResult = [
            'data' => $executionResult->data,
        ];
        if (!empty($executionResult->errors)) {
            $convertedResult['errors'] = array_map(function(Error $error) {
                $errorResult = [
                    'message' => $error->message,
                    'locations' => $error->getLocations()
                ];
                $exception = $error->getPrevious();
                if ($exception instanceof FlowException) {
                    $errorResult['message'] = HttpResponse::getStatusMessageByCode($exception->getStatusCode());
                    $errorResult['_exceptionCode'] = $exception->getCode();
                    $errorResult['_statusCode'] = $exception->getStatusCode();
                    $errorResult['_referenceCode'] = $exception->getReferenceCode();
                }
                if ($exception instanceof \Exception) {
                    $this->systemLogger->logException($exception);
                }
                return $errorResult;
            }, $executionResult->errors);
        }
        if (!empty($executionResult->extensions)) {
            $convertedResult['extensions'] = (array)$executionResult->extensions;
        }
        return $convertedResult;
    }
}