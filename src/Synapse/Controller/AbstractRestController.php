<?php

namespace Synapse\Controller;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Synapse\Response\EntityResponse;
use Synapse\Rest\Exception\MethodNotImplementedException;
use Zend\Stdlib\ArraySerializableInterface;

/**
 * Abstract rest controller. Allows children to simply set get(), post(),
 * put(), and/or delete() methods.
 */
abstract class AbstractRestController extends AbstractController
{
    /**
     * Request body content decoded from JSON
     *
     * @var mixed
     */
    protected $content;

    /**
     * Silex hooks into REST controllers here
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function execute(Request $request)
    {
        $method = $request->getMethod();

        if (!method_exists($this, $method)) {
            throw new MethodNotImplementedException(
                sprintf(
                    'HTTP method "%s" has not been implemented in class "%s"',
                    $method,
                    get_class($this)
                )
            );
        }

        $result = $this->{$method}($request);

        if ($result instanceof ArraySerializableInterface) {
            return new EntityResponse($result);
        } elseif (is_array($result)) {
            return new JsonResponse($result);
        } elseif ($result instanceof Response) {
            return $result;
        } else {
            throw new RuntimeException(
                sprintf(
                    'Unhandled response type %s from controller',
                    gettype($result)
                )
            );
        }
    }

    /**
     * Get a JSON decoded array from the request content
     *
     * @param  Request $request
     * @return array
     */
    protected function getContentAsArray(Request $request)
    {
        $content = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestException();
        }

        if ($content === null) {
            return [];
        }

        return $content;
    }
}
