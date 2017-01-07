<?php

namespace Ry\Wpblog\Providers;

//use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Ry\Wpblog\Models\Post;
use Ry\Wpblog\Models\Term;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

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
    	
    	if(!defined("RYWPBLOG"))
    		define("RYWPBLOG", "RYWPBLOG");
    	
    	require_once( env("wp_dir") . "/wp-load.php" );
    	
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
    		
    	Blade::extend(function($view, $compiler){
    		$pattern = $compiler->createMatcher('paginatePretty');
    		$code =
    				'$1<?php
		        echo "banana" . $2;
		    ?>';
    		return preg_replace($pattern, $code, $view);
    	});
    	
    	Blade::extend(function($view, $compiler){
    		$pattern = $compiler->createMatcher('wpmenu');
    				preg_match_all($pattern, $view, $reg);
    				$expression = $reg[2][0];    				
    				
    				$v = trim($expression, '()');
    				$ar = explode(" as ", $v);
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
    				
    				return preg_replace($pattern, $s, $view);
    			});
    			
    				Blade::extend(function($view, $compiler){
    					$pattern = $compiler->createPlainMatcher('endwpmenu');
    					$s = '<?php endforeach; ?>';
    					return preg_replace($pattern, $s, $view);
    				});
    					Blade::extend(function($view, $compiler){
				    		$pattern = $compiler->createMatcher('wpposts');
				    				preg_match_all($pattern, $view, $reg);
				    		
				    		$expression = $reg[2][0];
    						$v = trim($expression, '()');
    						$ar = explode(",", $v);
    						$ar = array_map(function($item){
    							return trim($item, " ");
    						}, $ar);
    						$s = "";
    						if(count($ar)>0) {
    							$varname = trim($ar[0], '"');
    						}
    							
    						$catname = "v" . Str::quickRandom();
    						if(count($ar)>1) {
    							$ar = explode(",", $v, 2);
    							$p_args = array(
    									"post_type" => 'post',
    									"posts_per_page" => -1,
    									"orderby"=> "id",
    									"order" => "ASC"
    							);
    							$json = array_merge($p_args, json_decode($ar[1], true));
    							//si plugin translator axtivé
    							/*$locale = get_locale();
    							 if(isset($json["category_name"]) && $locale) {
    							 $json["category_name"] = $json["category_name"]."-".$locale{0}.$locale{1};
    							}*/
    							$s = '$'.$catname.' = new WP_Query(json_decode(\''.json_encode($json).'\'));
						while ( $'.$catname.'->have_posts() ) : $'.$catname.'->the_post();';
    						}
    						else {
    							$s = 'while ( have_posts() ) : the_post();';
    						}
    						
    						return preg_replace($pattern, '<?php
									wp_reset_postdata();
									'.$s.'
					                $'. $varname .' = get_post();
					                    ?>', $view);
    					});
    					
    						Blade::extend(function($view, $compiler){
    							$pattern = $compiler->createPlainMatcher('endwpposts');
    							$s = '<?php endwhile; ?>';
    							return preg_replace($pattern, $s, $view);
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