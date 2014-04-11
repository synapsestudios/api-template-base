<?php

namespace Test\Synapse\User;

use Symfony\Component\HttpFoundation\Request;
use Synapse\TestHelper\ControllerTestCase;
use Synapse\User\ResetPasswordController;
use Synapse\Email\EmailEntity;
use Synapse\User\UserEntity;
use Synapse\View\Email\VerifyRegistrationView;

class ResetPasswordControllerTest extends ControllerTestCase
{
    public function setUp()
    {
        $this->setUpMockUserService();
        $this->setUpMockEmailService();

        $this->controller = new ResetPasswordController($this->mockUserService, $this->mockEmailService);
    }

    public function setUpMockUserService()
    {
        $this->mockUserService = $this->getMockBuilder('Synapse\User\UserService')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setUpMockEmailService()
    {
        $this->mockEmailService = $this->getMockBuilder('Synapse\Email\EmailService')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function createUserEntity()
    {
        $entity = new UserEntity();

        $entity = $entity->exchangeArray([
            'id'         => 213,
            'email'      => 'user@example.com',
            'password'   => 'password',
            'last_login' => 123456789,
            'created'    => 987654321,
            'enabled'    => 1,
            'verified'   => 1,
        ]);

        return $entity;
    }

    public function createEmailEntity()
    {
        $entity = new EmailEntity();

        $entity = $entity->exchangeArray([
            'id'              => 20,
            'subject'         => 'Subject!',
            'recipient_email' => 'recipient@example.com',
            'sender_email'    => 'test@example.com',
            'message'         => 'Message!',
        ]);

        return $entity;
    }

    public function createTokenEntity()
    {
        $entity = new TokenEntity();

        $entity = $entity->exchangeArray([
            'id'      => 10,
            'user_id' => 11,
            'token'   => 'abcdefg1234567',
            'type'    => TokenEntity::TYPE_RESET_PASSWORD,
            'created' => time()-1000,
            'expires' => time()+1000,
        ]);

        return $entity;
    }

    public function createVerifyRegistrationView()
    {
        $view  = new VerifyRegistrationView();
        $token = $this->createTokenEntity();

        $view->setToken($token);

        return $view;
    }

    public function expectingFindByCalledOnUserServiceWithEmail()
    {
        $this->mockUserService->expects($this->once())
            ->method('findBy')
            ->with($this->createEmailEntity());
    }

    public function expectingCreateFromArrayCalledOnEmailService()
    {
        $message = (string) $this->createVerifyRegistrationView();

        $argument = [
            'recipient_email' => $this->createUserEntity()->getEmail(),
            'subject'         => 'Verify Your Account',
            'message'         => $message,
        ];

        $this->mockEmailService->expects($this->once())
            ->method('createFromArray')
            ->with($argument);
    }

    public function performPostRequest()
    {
        $request = new Request();

        $request->setMethod('POST');

        return $this->controller->execute($request);
    }

    public function withUserServiceFindByReturningUser()
    {
        $user = $this->createUserEntity();

        $this->mockUserService->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($user));
    }

    public function withUserServiceCreateUserTokenReturningToken()
    {
        $token = $this->createTokenEntity();

        $this->mockUserService->expects($this->any())
            ->method('createUserToken')
            ->will($this->returnValue($token));
    }

    public function testPostCallsFindByOnUserServiceAndPassesEmail()
    {
        $this->expectingFindByCalledOnUserServiceWithEmail();

        $this->performPostRequest();
    }

    public function testPostCallsCreateUserTokenOnUserServiceIfAccountExists()
    {
        $this->withUserServiceFindByReturningUser();

        $this->expectingCreateUserTokenCalledOnUserService();

        $this->performPostRequest();
    }

    public function testPostCallsCreateFromArrayOnEmailServiceIfAccountExists()
    {
        $this->withUserServiceCreateUserTokenReturningToken();

        $this->expectingCreateFromArrayCalledOnEmailService();

        $this->performPostRequest();
    }

    public function testPostCallsEnqueueSendEmailJobOnEmailServiceIfAccountExists()
    {
    }

    public function testPostReturns204WithoutAnyContentInTheBodyIfAccountExists()
    {
    }

    public function testPostReturns404IfAccountDoesNotExist()
    {
    }

    public function testPutReturns404IfTokenNotFound()
    {
    }

    public function testPutReturns404IfTokenExpired()
    {
    }

    public function testPutReturns422IfRequestDoesNotContainNewPassword()
    {
    }

    public function testPutReturns200AndUserEntityWithoutPasswordInResponseBodyOnSuccess()
    {
    }

    public function testPutCallsResetPasswordOnUserService()
    {
    }

    public function testPutCallsDeleteTokenOnUserService()
    {
    }

}
