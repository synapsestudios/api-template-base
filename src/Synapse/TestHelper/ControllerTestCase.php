<?php

namespace Synapse\TestHelper;

use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolation;
use Synapse\Stdlib\Arr;
use Synapse\User\UserEntity;
use stdClass;

abstract class ControllerTestCase extends PHPUnit_Framework_TestCase
{
    use SecurityAwareTestCaseTrait;

    const LOGGED_IN_USER_ID = 42;

    public function createJsonRequest($method, array $params = [])
    {
        $this->request = new Request(
            Arr::get($params, 'getParams', []),
            [],
            Arr::get($params, 'attributes', []),
            [],
            [],
            [],
            Arr::get($params, 'content') !== null ? json_encode($params['content']) : ''
        );
        $this->request->setMethod($method);
        $this->request->headers->set('CONTENT_TYPE', 'application/json');

        return $this->request;
    }

    public function createNonEmptyConstraintViolationList()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
            ->disableOriginalConstructor();

        $violations = [
            $builder->getMock(),
            $builder->getMock(),
        ];

        return new ConstraintViolationList($violations);
    }
}
