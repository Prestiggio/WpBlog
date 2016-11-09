<?php

namespace Ry\Wpblog\Providers;

//use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Ry\Wpblog\Models\Post;
use Ry\Wpblog\Models\Term;

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
    	
    	//ressources
    	$this->loadViewsFrom(__DIR__.'/../ressources/views', 'rywpblog');
    	$this->loadTranslationsFrom(__DIR__.'/../ressources/lang', 'rywpblog');
    	
    	$this->publishes([    			
    			__DIR__.'/../config/rywpblog.php' => config_path('rywpblog.php')
    	], "config");  
    	
    	$router->bind('wp_post', function($href){
    		$post = Post::where(function($query) use ($href){
    			$query->where("post_name", "LIKE", $href);
    			$query->where("post_status", "=", "publish");
    		})->first();
    		if($post) {
    			require_once( config('rywp.dir') . '/wp-load.php' );
    			$wp_post = get_post($post->ID);
    			setup_postdata($wp_post);
    		}
    		else
    			abort(404);
    		return $post;
    	});
    	
    		$router->bind('wp_term', function($href){
    			$term = Term::where(function($query) use ($href){
    				$query->where("slug", "LIKE", $href);
    			})->first();
    			if($term) {
    				require_once( config('rywp.dir') . '/wp-load.php' );
    				$posts = get_posts([
    						'numberposts' => 10,
    						'category' => $term->term_id, 'orderby' => 'date',
    						'order' => 'DESC', 'post_type' => 'post'
    				]);
    				return [
    						"term" => $term,
    						"posts" => $posts
    				];
    			}
    			else
    				abort(404);
    		});
    	
    	/*
    	$this->mergeConfigFrom(
	        	__DIR__.'/../config/rywpblog.php', 'rywpblog'
	    );
    	$this->publishes([
    			__DIR__.'/../assets' => public_path('vendor/rywpblog'),
    	], "public");    	
    	
    	$this->publishes([
    			__DIR__.'/../ressources/views' => resource_path('views/vendor/rywpblog'),
    			__DIR__.'/../ressources/lang' => resource_path('lang/vendor/rywpblog'),
    	], "ressources");
    	
    	$this->publishes([
    			__DIR__.'/../database/factories/' => database_path('factories'),
	        	__DIR__.'/../database/migrations/' => database_path('migrations')
	    ], 'migrations');
	    */
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