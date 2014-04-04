<?php

namespace Synapse\Db;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

/**
 * Provider for Zend database services
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the database adapter
     *
     * @param  Application $app Silex application
     */
    public function register(Application $app)
    {
        $app['db'] = $app->share(function () use ($app) {
            return new Adapter($app['config']->load('db'));
        });
    }

    /**
     * Perform extra chores on boot (none needed here)
     *
     * @param  Application $app
     */
    public function boot(Application $app)
    {
        // noop
    }
}
