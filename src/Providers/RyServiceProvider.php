<?php

namespace Ry\Wpblog\Providers;

//use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Ry\Wpblog\Models\Post;
use Ry\Wpblog\Models\Term;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

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
    	
    	require_once( config("rywpblog.dir") . "/wp-load.php" );
    	
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
    			require_once( config('rywpblog.dir') . '/wp-load.php' );
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
    				require_once( config('rywpblog.dir') . '/wp-load.php' );
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
    		Blade::directive('wpmenu', function($expression) {
    				$v = trim($expression, '()');
    				$ar = explode(" as ", $v);
    				require_once( config('rywpblog.dir') . '/wp-load.php' );
    				$locations = get_nav_menu_locations();
    				$location = trim($ar[0]);
    				$menuname = "m" . Str::quickRandom();
    				$locvar = "l" . Str::quickRandom();
    				$item = $location;
    				if(count($ar)>1)
    					$item = trim($ar[1]);
    				$s = '';
    				if(isset($locations[$location])) {
    					$loc = $locations[$location];
    					$s .= '<?php	
					$'.$locvar.' = get_nav_menu_locations();
					$'.$menuname.' = false;
					if(isset($'.$locvar.'["'.$location.'"]))
						$'.$menuname.' = wp_get_nav_menu_items($'.$locvar.'["'.$location.'"]);
			
					if(!$'.$menuname.')
						$'.$menuname.' = wp_get_nav_menu_items("'.$location.'");
								
					if(!is_array($'.$menuname.'))
						$'.$menuname.' = [];
								
					foreach($'.$menuname.' as $'.$item.'):
                    ?>';
    				}
    				else {
    					$s .= '<?php
					$'.$menuname.' = wp_get_nav_menu_items("'.$location.'");
							
					if(!is_array($'.$menuname.'))
						$'.$menuname.' = [];
					
					foreach($'.$menuname.' as $'.$item.'):
                    ?>';
    				}
    					
    				return $s;
    			});
    			
    				Blade::directive('endwpmenu', function($expression) {
    					$s = '<?php endforeach; ?>';
    					return $s;
    				});
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