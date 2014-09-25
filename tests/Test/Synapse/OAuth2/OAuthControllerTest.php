<?php

namespace Test\Synapse\OAuth2;

use Synapse\TestHelper\ControllerTestCase;
use Synapse\OAuth2\OAuthController;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

class OAuthControllerTest extends ControllerTestCase
{
    public function setUp()
    {
        $this->setUpMockOAuth2Server();
        $this->setUpMockUserService();
        $this->setUpMockAccessTokenMapper();
        $this->setUpMockRefreshTokenMapper();
        $this->setUpMockMustacheEngine();
        $this->setUpMockSession();
        $this->setUpMockUrlGenerator();

        $this->controller = new OAuthController(
            $this->mockOAuth2Server,
            $this->mockUserService,
            $this->mockAccessTokenMapper,
            $this->mockRefreshTokenMapper,
            $this->mockMustacheEngine,
            $this->mockSession
        );

        $this->controller->setUrlGenerator($this->mockUrlGenerator);
    }

    public function setUpMockOAuth2Server()
    {
        $this->mockOAuth2Server = $this->getMockBuilder('OAuth2\Server')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setUpMockUserService()
    {
        $this->mockUserService = $this->getMockBuilder('Synapse\User\UserService')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setUpMockAccessTokenMapper()
    {
        $this->mockAccessTokenMapper = $this->getMockBuilder('Synapse\OAuth2\AccessTokenMapper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setUpMockRefreshTokenMapper()
    {
        $this->mockRefreshTokenMapper = $this->getMockBuilder('Synapse\OAuth2\RefreshTokenMapper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setUpMockMustacheEngine()
    {
        $this->mockMustacheEngine = $this->getMockBuilder('Mustache_Engine')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setUpMockSession()
    {
        $this->mockSession = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setUpMockUrlGenerator()
    {
        $this->mockUrlGenerator = $this->getMockBuilder('Symfony\Component\Routing\Generator\UrlGeneratorInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function expectingMustacheTemplateRenderedAndReturning($template, $returnValue)
    {
        $this->mockMustacheEngine->expects($this->once())
            ->method('render')
            ->with($this->equalTo($template))
            ->will($this->returnValue($returnValue));
    }

    public function expectingTemplateVarsSet($vars)
    {
        $this->mockMustacheEngine->expects($this->once())
            ->method('render')
            ->with($this->anything(), $this->contains($vars));
    }

    public function expectingTemplateSubmitUrlSetTo($value)
    {
        $this->mockMustacheEngine->expects($this->once())
            ->method('render')
            ->with($this->anything(), $this->contains($value));
    }

    public function expectingUrlGeneratedFromRouteAndReturning($routeName, $returnValue)
    {
        $this->mockUrlGenerator->expects($this->once())
            ->method('generate')
            ->with($this->equalTo($routeName))
            ->will($this->returnValue($returnValue));
    }

    public function testAuthorizeReturnsRenderedOAuthAuthorizeMustacheTemplate()
    {
        $expectedTemplate = 'OAuth/Authorize';

        // Use a class so identity-level equality assertion can be made
        $renderedTemplate = new stdClass();

        $this->expectingMustacheTemplateRenderedAndReturning($expectedTemplate, $renderedTemplate);

        $response = $this->controller->authorize(new Request);

        $this->assertSame($renderedTemplate, $response);
    }

    public function testAuthorizeSetsHttpQueryParamsAsTemplateVars()
    {
        $params = [
            'foo' => 1,
            'bar' => 'baz',
        ];

        $this->expectingTemplateVarsSet([
            [
                'name'  => 'foo',
                'value' => 1,
            ],
            [
                'name'  => 'bar',
                'value' => 'baz',
            ],
        ]);

        $request = $this->createJsonRequest('GET', [
            'getParams' => $params,
        ]);

        $this->controller->authorize($request);
    }

    public function testAuthorizeSetsSubmitUrlToGeneratedAuthorizeFormSubmitUrl()
    {
        // Use a class so identity-level equality assertion can be made
        $generatedUrl = new stdClass();

        $expectedRoute = OAuthController::AUTHORIZE_FORM_SUBMIT_ROUTE_NAME;

        $this->expectingUrlGeneratedFromRouteAndReturning($expectedRoute, $generatedUrl);
        $this->expectingTemplateSubmitUrlSetTo($generatedUrl);

        $this->controller->authorize(new Request);
    }
}
