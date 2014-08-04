<?php

namespace Synapse\User;

use Symfony\Component\HttpFoundation\Request;
use Synapse\Controller\AbstractRestController;
use Synapse\Security\SecurityAwareInterface;
use Synapse\Security\SecurityAwareTrait;
use OutOfBoundsException;

/**
 * Controller for user related actions
 */
class UserController extends AbstractRestController implements SecurityAwareInterface
{
    use SecurityAwareTrait;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var UserValidator
     */
    protected $userValidator;

    /**
     * @var UserRegistrationValidator
     */
    protected $userRegistrationValidator;

    /**
     * Return a user entity
     *
     * @param  Request $request
     * @return array
     */
    public function get(Request $request)
    {
        $userEntity = $request->attributes->get('user');

        if ($userEntity === null) {
            $userEntity = $this->user();
        } elseif ($userEntity === false) {
            return $this->createNotFoundResponse();
        }

        return $this->userArrayWithoutPassword($userEntity);
    }

    /**
     * Create a user
     *
     * @param  Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function post(Request $request)
    {
        $userData = $this->getContentAsArray($request);

        $errors = $this->userRegistrationValidator->validate($userData ?: []);

        if (count($errors) > 0) {
            return $this->createConstraintViolationResponse($errors);
        }

        try {
            $newUser = $this->userService->register($userData);
        } catch (OutOfBoundsException $e) {
            $httpCodes = [
                UserService::EMAIL_NOT_UNIQUE => 409,
            ];

            return $this->createSimpleResponse($httpCodes[$e->getCode()], $e->getMessage());
        }

        $newUser = $this->userArrayWithoutPassword($newUser);

        $newUser['_href'] = $this->url('user-entity', array('user' => $newUser['id']));

        return $this->createJsonResponse($newUser, 201);
    }

    /**
     * Edit a user; requires the user to be logged in and the current password provided
     *
     * @param  Request $request
     * @return array
     */
    public function put(Request $request)
    {
        $user = $this->user();

        $userValidationCopy = clone $user;

        // Validate the modified fields
        $content = $this->getContentAsArray($request);

        $errors = $this->userValidator->validate($content);

        if (count($errors) > 0) {
            return $this->createConstraintViolationResponse($errors);
        }

        $userValidationCopy->exchangeArray($content ?: [])->getArrayCopy();

        try {
            $user = $this->userService->update($user, $this->getContentAsArray($request));
        } catch (OutOfBoundsException $e) {
            $httpCodes = [
                UserService::CURRENT_PASSWORD_REQUIRED => 403,
                UserService::FIELD_CANNOT_BE_EMPTY     => 422,
                UserService::EMAIL_NOT_UNIQUE          => 409,
            ];

            return $this->createSimpleResponse($httpCodes[$e->getCode()], $e->getMessage());
        }

        return $this->userArrayWithoutPassword($user);
    }

    /**
     * @param UserService $service
     */
    public function setUserService(UserService $service)
    {
        $this->userService = $service;
        return $this;
    }

    /**
     * @param UserValidator $validator
     */
    public function setUserValidator(UserValidator $validator)
    {
        $this->userValidator = $validator;
        return $this;
    }

    /**
     * @param UserRegistrationValidator $validator
     */
    public function setUserRegistrationValidator(UserRegistrationValidator $validator)
    {
        $this->userRegistrationValidator = $validator;
        return $this;
    }

    /**
     * Transform the User entity into an array and remove the password element
     *
     * @param  User   $user
     * @return array
     */
    protected function userArrayWithoutPassword(UserEntity $user)
    {
        $user = $user->getArrayCopy();

        unset($user['password']);

        return $user;
    }
}
