<?php

namespace Synapse\OAuth2;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;

use Synapse\Controller\OAuthController;
use Synapse\OAuth2\Storage\ZendDb as OAuth2ZendDb;
use Synapse\OAuth2\ResponseType\AccessToken;
use Synapse\OAuth2\Mapper\AccessToken as AccessTokenMapper;
use Synapse\OAuth2\Mapper\RefreshToken as RefreshTokenMapper;
use Synapse\OAuth2\Entity\AccessToken as AccessTokenEntity;
use Synapse\OAuth2\Entity\RefreshToken as RefreshTokenEntity;

use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\Server as OAuth2Server;
use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\UserCredentials;
use OAuth2\ResponseType\AuthorizationCode as AuthorizationCodeResponse;

class ServerServiceProvider implements ServiceProviderInterface
{
    /**
     * Register services
     *
     * @param  Application $app
     */
    public function setup(Application $app)
    {
        $app['oauth.storage'] = $app->share(function () use ($app) {
            // Create the storage object
            $storage = new OAuth2ZendDb($app['db']);
            $storage->setUserMapper($app['user.mapper']);

            return $storage;
        });

        $app['oauth_server'] = $app->share(function () use ($app) {
            $storage = $app['oauth.storage'];

            $grantTypes = [
                'authorization_code' => new AuthorizationCode($storage),
                'user_credentials'   => new UserCredentials($storage),
            ];

            $accessTokenResponseType = new AccessToken($storage, $storage);
            $authCodeResponseType = new AuthorizationCodeResponse($storage);

            return new OAuth2Server(
                $storage,
                [
                    'enforce_state'  => false,
                    'allow_implicit' => true,
                ],
                $grantTypes,
                [
                    'token' => $accessTokenResponseType,
                    'code'  => $authCodeResponseType,
                ]
            );
        });

        $app['oauth.controller'] = $app->share(function () use ($app) {
            return new OAuthController(
                $app['oauth_server'],
                $app['user.service'],
                $app['oauth-access-token.mapper'],
                $app['oauth-refresh-token.mapper'],
                $app['mustache'],
                $app['session']
            );
        });

        $app['oauth-access-token.mapper'] = $app->share(function () use ($app) {
            return new AccessTokenMapper($app['db'], new AccessTokenEntity);
        });

        $app['oauth-refresh-token.mapper'] = $app->share(function () use ($app) {
            return new RefreshTokenMapper($app['db'], new RefreshTokenEntity);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $this->setup($app);
        $this->setFirewalls($app);

        $app->get('/oauth/authorize', 'oauth.controller:authorize');
        $app->get('/oauth/authorize-submit', 'oauth.controller:authorizeFormSubmit')
            ->bind('oauth-authorize-form-submit');

        $app->post('/oauth/token', 'oauth.controller:token');
        $app->post('/oauth/logout', 'oauth.controller:logout');
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        // Noop
    }

    /**
     * Set OAuth related firewalls
     *
     * @param Application $app
     */
    protected function setFirewalls(Application $app)
    {
        $app->extend('security.firewalls', function ($firewalls, $app) {
            $logout = new RequestMatcher('^/oauth/logout', null, ['POST']);
            $oAuth  = new RequestMatcher('^/oauth');

            $breedFirewalls = [
                'oauth-logout' => [
                    'pattern' => $logout,
                    'oauth'   => true,
                ],
                'oauth-public' => [
                    'pattern'   => $oAuth,
                    'anonymous' => true,
                ],
            ];

            return array_merge($breedFirewalls, $firewalls);
        });
    }
}
