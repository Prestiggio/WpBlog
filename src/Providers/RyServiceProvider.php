<?php

namespace Ry\Wpblog\Providers;

//use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;

class RyServiceProvider extends ServiceProvider
{
	/**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
    	parent::boot($router);
    	/*
    	$this->publishes([    			
    			__DIR__.'/../config/rywpblog.php' => config_path('rywpblog.php')
    	], "config");  
    	$this->mergeConfigFrom(
	        	__DIR__.'/../config/rywpblog.php', 'rywpblog'
	    );
    	$this->publishes([
    			__DIR__.'/../assets' => public_path('vendor/rywpblog'),
    	], "public");    	
    	*/
    	//ressources
    	$this->loadViewsFrom(__DIR__.'/../ressources/views', 'rywpblog');
    	$this->loadTranslationsFrom(__DIR__.'/../ressources/lang', 'rywpblog');
    	/*
    	$this->publishes([
    			__DIR__.'/../ressources/views' => resource_path('views/vendor/rywpblog'),
    			__DIR__.'/../ressources/lang' => resource_path('lang/vendor/rywpblog'),
    	], "ressources");
    	*/
    	$this->publishes([
    			__DIR__.'/../database/factories/' => database_path('factories'),
	        	__DIR__.'/../database/migrations/' => database_path('migrations')
	    ], 'migrations');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }
    public function map(Router $router)
    {    	
    	if (! $this->app->routesAreCached()) {
    		$router->group(['namespace' => 'Ry\Wpblog\Http\Controllers'], function(){
    			require __DIR__.'/../Http/routes.php';
    		});
    	}
    }
}