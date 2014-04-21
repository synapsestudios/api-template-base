<?php

namespace Synapse\Application;

use Synapse\Application;

/**
 * Define services
 */
class Services implements ServicesInterface
{
    /**
     * {@inheritDoc}
     * @param  Application $app
     */
    public function register(Application $app)
    {
        // Register log provider first to catch any exceptions thrown in the others
        $app->register(new \Synapse\Log\ServiceProvider);

        // Register security component before other providers attempt to extend $app['security.firewalls']
        $app->register(new \Silex\Provider\SecurityServiceProvider);
        $this->registerSecurityFirewalls($app);

        $this->registerServiceProviders($app);
    }

    /**
     * Register service providers
     *
     * @param  Application $app
     */
    protected function registerServiceProviders(Application $app)
    {
        $app->register(new \Synapse\Command\CommandServiceProvider);
        $app->register(new \Synapse\Db\DbServiceProvider);
        $app->register(new \Synapse\OAuth2\ServerServiceProvider);
        $app->register(new \Synapse\OAuth2\SecurityServiceProvider);
        $app->register(new \Synapse\Resque\ResqueServiceProvider);
        $app->register(new \Synapse\Controller\ControllerServiceProvider);
        $app->register(new \Synapse\Email\EmailServiceProvider);
        $app->register(new \Synapse\User\UserServiceProvider);
        $app->register(new \Synapse\Migration\MigrationServiceProvider);
        $app->register(new \Synapse\Upgrade\UpgradeServiceProvider);
        $app->register(new \Synapse\Session\SessionServiceProvider);
        $app->register(new \Synapse\SocialLogin\SocialLoginServiceProvider);

        $app->register(new \Synapse\View\ViewServiceProvider, [
            'mustache.paths' => array(
                APPDIR.'/templates'
            ),
            'mustache.options' => [
                'cache' => TMPDIR,
            ],
        ]);

        $app->register(new \Silex\Provider\ValidatorServiceProvider);
        $app->register(new \Silex\Provider\UrlGeneratorServiceProvider);

        // Register the CORS middleware
        $app->register(new \JDesrosiers\Silex\Provider\CorsServiceProvider);
        $app->after($app['cors']);
    }

    /**
     * Register the security firewalls for use with the Security Context in SecurityServiceProvider
     *
     * How to add application-specific firewalls:
     *
     *     $app->extend('security.firewalls', function ($firewalls, $app) {
     *         $newFirewalls = [...];
     *
     *         return array_merge($newFirewalls, $firewalls);
     *     });
     *
     * It's important to return an array with $firewalls at the end, as in the example,
     * so that the catch-all 'base.api' firewall does not preclude more specific firewalls.
     *
     * Application-specific firewalls should only be needed to allow passthrough
     * for public endpoints, since 'base.api' requires authentication.
     *
     * The same can be done with security.access_rules, which are used to restrict
     * sections of the application based on a user's role:
     *
     *     $app->extend('security.access_rules', function ($rules, $app) {
     *         $newRules = [...];
     *
     *         return array_merge($newRules, $rules);
     *     });
     *
     * @link http://silex.sensiolabs.org/doc/providers/security.html#defining-more-than-one-firewall
     * @link http://silex.sensiolabs.org/doc/providers/security.html#defining-access-rules
     *
     * @param  Application $app
     */
    public function registerSecurityFirewalls(Application $app)
    {
        $app['security.firewalls'] = $app->share(function () {
            return [
                'base.api' => [
                    'pattern'   => '^/',
                    /**
                     * Using both oauth and anonymous listeners in this order allows
                     * the endpoint to be accessible even if the user is not logged in.
                     * However, if the user is logged in it authenticates the user
                     * allowing their information to be accessible to the controller.
                     */
                    'oauth'     => true,
                    'anonymous' => true,
                ],
            ];
        });

        $app['security.access_rules'] = $app->share(function () {
            return [];
        });
    }
}
