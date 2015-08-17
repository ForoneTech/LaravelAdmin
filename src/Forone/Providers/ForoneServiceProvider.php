<?php
/**
 * User: Mani Wang
 * Date: 8/13/15
 * Time: 9:16 PM
 * Email: mani@forone.co
 */

namespace Forone\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class ForoneServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            require __DIR__ . '/../routes.php';
        }
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'admin');
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'admin');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'admin');
        $this->publishResources();
        $this->setLocale();
        $this->app['events']->fire('admin.ready');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
        $this->registerProvider();
        $this->registerAlias();
        $this->registerMiddleware();
        $this->app->bind(\Illuminate\Contracts\Auth\Registrar::class, \Forone\Admin\Services\Registrar::class);
    }

    private function registerCommands()
    {
        $this->commands([
            \Forone\Admin\Console\ClearDatabase::class,
            \Forone\Admin\Console\InitCommand::class,
            \Forone\Admin\Console\Upgrade::class,
            \Forone\Admin\Console\Backup::class,
        ]);
    }

    private function registerProvider()
    {
        $this->app->register(\Zizaco\Entrust\EntrustServiceProvider::class);
        $this->app->register(\Forone\Admin\Providers\ForoneValidatorProvider::class);
        $this->app->register(\Illuminate\Html\HtmlServiceProvider::class);
        $this->app->register(\Forone\Admin\Providers\ForoneFormServiceProvider::class);
        $this->app->register(\Forone\Admin\Providers\ForoneHtmlServiceProvider::class);
        $this->app->register(\Forone\Admin\Providers\QiniuUploadProvider::class);
        $this->app->register(\Orangehill\Iseed\IseedServiceProvider::class);
    }

    private function registerAlias()
    {
        $this->app->alias('Form',\Illuminate\Html\FormFacade::class);
        $this->app->alias('Html',\Illuminate\Html\HtmlFacade::class);
        $this->app->alias('Entrust', \Zizaco\Entrust\EntrustFacade::class);
    }

    /**
     * Publish configuration file.
     */
    private function publishResources()
    {
        // publish views
//        $this->publishes([__DIR__ . '/../../resources/views' => base_path('resources/views/vendor/foreone'),]);

        // publish config
        $this->publishes([__DIR__ . '/../../config/config.php' => config_path('forone.php'),]);

        // publish assets
        $this->publishes([__DIR__ . '/../../../public' => public_path('vendor/forone'),], 'public');
    }

    /**
     * Publish migration file.
     */
    private function publishMigrations()
    {
        $this->publishes([__DIR__ . '/../../migrations/' => base_path('database/migrations')], 'migrations');
    }

    /**
     * Sets the locale if it exists in the session and also exists in the locales option
     *
     * @return void
     */
    public function setLocale()
    {
        if ($locale = $this->app->session->get('admin_locale'))
        {
            $this->app->setLocale($locale);
        }
    }

    private function registerMiddleware()
    {
        $this->app['router']->middleware('admin.permission', \Forone\Admin\Middleware\EntrustPermission::class);
        $this->app['router']->middleware('admin.auth', \Forone\Admin\Middleware\Authenticate::class);
        $this->app['router']->middleware('admin.guest', \Forone\Admin\Middleware\RedirectIfAuthenticated::class);
    }
}
