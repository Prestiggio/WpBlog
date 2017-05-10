<?php
namespace Ry\Wpblog;

use Ry\Wpblog\Models\Post;
use Ry\Wpblog\Models\Term;
use Illuminate\Support\Facades\Blade;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Wpblog
{
	public function __construct() {
		require_once( env("wp_dir") . "/wp-load.php" );
		 
		app("router")->bind('wp_post', function($href){
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
			 
		app("router")->bind('wp_term', function($href){
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
				 
		Blade::directive('wpmenu', function($expression){
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
				
			return $s;
		});
					 
		Blade::directive("endwpmenu", function($expression){
			return '<?php endforeach; ?>';
		});
				
		Blade::directive("wpposts", function($expression){
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
				$s = '$'.$catname.' = new WP_Query(["meta_key" => $meta_key, "meta_value" => $meta_value]);
				while ( $'.$catname.'->have_posts() ) : $'.$catname.'->the_post();';
			}
			
			return '<?php
						wp_reset_postdata();
						'.$s.'
						$'. $varname .' = get_post();
								?>';
		});
    	
		Blade::directive('endwpposts', function($expression){
			return '<?php endwhile; ?>';
		});
	}
	
	public function view($template, $href, $params = []) {
		list($meta_key, $meta_value) = explode("://", $href);
		$wp_query = new \WP_Query(["post_type" => "any", "meta_key" => $meta_key, "meta_value" => $meta_value]);
		if($wp_query->is_404())
			abort(404);
		$params["post"] = $wp_query->get_posts()[0];
		$params["meta_key"] = $meta_key;
		$params["meta_value"] = $meta_value;
		return view($template, $params);
	}
}