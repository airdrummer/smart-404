<?php
/*
Plugin Name: Smart 404
Plugin URI: https://github.com/airdrummer/smart-404

Description: Rescue your viewers from site errors!  When content cannot be found, Smart 404 will use the current URL to attempt to find matching content, and redirect to it automatically. Smart 404 also supplies template tags which provide a list of suggestions, for use on a 404.php template page if matching content can't be immediately discovered.

Version:	0.7 airdrummer
Version: 0.5
Author: Michael Tyson

Author URI: http://atastypixel.com/blog/
*/

/*  Copyright 2008 Michael Tyson <mike@tyson.id.au>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 * Main action handler
 *
 * @package Smart404
 * @since 0.1
 *
 * Searches through posts to see if any matches the REQUEST_URI.
 * Also searches tags
 */
function smart404_redirect() 
{
	if ( !is_404() )
		return;

	// Extract any GET parameters from URL
	$uri = urldecode( $_SERVER["REQUEST_URI"] );
	$urlParts = parse_url($uri);	
	$uri = $urlParts['path'];

	parse_str((isset($urlParts['query']) ? $urlParts['query'] : ""), $getParams);
	if( ! isset($getParams['walkup']))
		$getParams['walkup'] = [];
	if( ! isset($getParams['replaced']))
		$getParams['replaced']   = [];

	smart404_set_search_words(
		trim(
			preg_replace( "@[-_/]@", " ", $uri)
			. " " . implode(" ", array_reverse($getParams['walkup']))
//			. " " . implode(" ", $getParams['redir'])
		));
	smart404_set_suggestions(array());

	// debug levels 1-5    2dec12
	$debug_smart404 = intval(get_option('debug_smart404' ));
	if ( $debug_smart404 > 0 )
    	error_log("smart404_redirect -------- "
    				. get_the_user_ip() . " -------- ". $uri);

	$take_1st_match   = get_option('take_1st_match' );
	$take_exact_match = get_option('take_exact_match' );
	$search_whole_uri = get_option('search_whole_uri' );
	$walk_uri         = get_option('walk_uri' );
	$skip_ignored     = get_option('skip_ignored' );
	if ( $debug_smart404 > 1 ) 
	{
		error_log("smart404_redirect: take_1st_match="   . ( $take_1st_match  ? "yes" : "no" ) );
		error_log("smart404_redirect: take_exact_match=" . ( $take_exact_match ? "yes" : "no" ) );
		error_log("smart404_redirect: search_whole_uri=" . ( $search_whole_uri ? "yes" : "no" ) );
		error_log("smart404_redirect: walk_uri=" . ( $walk_uri ? "yes" : "no" ) );
		error_log("smart404_redirect: skip_ignored=" . ( $skip_ignored ? "yes" : "no" ) );
	}

	$replacements = trim( get_option('replacements' ));
//	if ( empty($getParams['redir']) ) //not already replaced
	$replacements_array = preg_split('/\n|\r/', $replacements, -1, PREG_SPLIT_NO_EMPTY);
	if (count($getParams['replaced']) < count($replacements_array))
	{
   	    if ( $debug_smart404 > 1 ) 
		    error_log("smart404_redirect: replacements_array: "
		    		. join($replacements_array," : ") );
		$redir = $uri;
		foreach ( $replacements_array as $replacement )
		{
			list($old,$new) = explode(",",$replacement);
			$count=0;
			$redir = preg_replace("@".$old."@", $new, $redir, 1, $count);
			if ($count == 0)
				continue;
			if ( $debug_smart404 > 0 )
    			error_log("smart404_redirect: replaced:" . $old . "->" . $new);
    		$getParams['replaced'][] = $old;
            wp_redirect( $redir . '?' . http_build_query($getParams), 301, "smart-404");
            exit;
		}
	}
	
	$patterns = trim( get_option('ignored_patterns' ) ); 
    $patterns_array = ( ! empty( $patterns ) 
    					? preg_split('/\n|\r/', $patterns, -1, PREG_SPLIT_NO_EMPTY)
						: array());

	if ( $skip_ignored )
	{
       foreach ( $patterns_array as $skipped_pattern ) 
		{
			if (preg_match("@".$skipped_pattern."@", $uri, $matches))
			{
				if ( $debug_smart404 > 0 ) 
					error_log("smart404_redirect: skipped_pattern=" 
							 . $skipped_pattern . " matches="
							 . implode(" ",$matches));
				return;
			}
		}
	}

	$patterns_array[] = "/(trackback|feed|(comment-)?page-?[0-9]*)/?$";
	$patterns_array[] = "\.(html|php)$";
	$patterns_array[] = "/?\?.*";
	$patterns_array = array_map(
						create_function(
							'$a', '$sep = 
							(strpos($a, "@") === false ? "@" : "%"); 
							return $sep.trim($a).$sep."i";'),
						$patterns_array);
	
	$search_groups = (array)get_option( 'also_search' );
	if ( !$search_groups ) 
		$search_groups = array("posts","pages","tags","categories");

	if ( $debug_smart404 > 2 ) 
	{
		error_log("smart404_redirect: ignored_patterns_input=" . $patterns );
		error_log("smart404_redirect: ignored_patterns_array=" . join($patterns_array,",") );
     	error_log("smart404_redirect: search_groups=" . join($search_groups,","));
	}

	$search = preg_replace( $patterns_array, "", $uri );
	if ( $search_whole_uri )
		$search = ltrim(str_replace("/", "-", $search),"-");
	else 
		$search = basename(trim($search));
	$search = str_replace("_", "-", $search);
	
	if ( ! $search ) 
		return;
	
   	if ( $debug_smart404 > 1 )
   		error_log("smart404_redirect: search=" . $search);
	smart404_set_search_words(
		trim(
			 preg_replace( "@[-_]@", " ", $search)
			. " " . implode(" ", array_reverse($getParams['walkup']))
//			. " " . implode(" ", $getParams['redir'])
		));

	$search_groups = (array)get_option( 'also_search' );
	if ( !$search_groups ) 
		$search_groups = array("posts","pages","tags","categories");

	$matches = array();
	$mct = 0;

    // Search twice: First looking for exact title match (high priority), 
    //					then for a general search
	foreach ( $search_groups as $group ) 
	{
	   switch ( $group ) 
	   {
	       case "posts":
	       case "pages":
	          // Search for posts with exact name, redirect if one found
				$group = rtrim($group, "s");
				$posts = get_posts( array( "name" => $search, "post_type" => $group ) );
				$mct = count( $posts );
				if ( $mct > 0) 
				{
					$matches = array_unique(array_merge($matches, $posts), SORT_REGULAR);
					if ( $take_1st_match or ($take_exact_match and $mct = 1)) 
					{
						if ( $debug_smart404 > 0 )
							error_log("smart404_redirect:exact:"
								. ($take_1st_match? "take_1st_match":"") 
								. ($take_exact_match? "take_exact_match":"") 
								. $group . ":" . $posts[0]->title );
						wp_redirect( 
							get_permalink( $posts[0]->ID )
							. '?' . http_build_query($getParams), 
							301 , "smart-404");
						exit;
					}
				}
				if ( $debug_smart404 > 1 )
					error_log("smart404_redirect:exact:"
							. $group . ": #matches=". $mct
							. ( $debug_smart404 > 4 
								? ":" . print_r($posts,true) : ""));
				break;
		}
	}
	
	// Now perform general search
	foreach ( $search_groups as $group ) 
	{
	   switch ( $group ) 
	   {
	       case "posts":
	       case "pages":
				$group = rtrim($group, "s");
				$posts = smart404_search($search, $group);
				$mct = count( $posts );
				if ($mct > 0)
				{
		      		if ( $take_1st_match or ($take_exact_match and $mct = 1)) 
		        	{
						if ( $debug_smart404 > 0 )
						   	error_log("smart404_redirect:general:" 
								. ($take_1st_match? "take_1st_match":"") 
								. ($take_exact_match? "take_exact_match":"") 
								. $group . ": " . $posts[0]->title);
		          		wp_redirect( 
		          			get_permalink( $posts[0]->ID )
		          			. '?' . http_build_query($getParams), 
		          			301, "smart-404");
		   	    		exit;
					}
				}
				break;                
	
	       case "tags":
				$posts = get_tags( array ( "name__like" => $search ) );
				$mct = count( $posts );
				if ( $mct > 0 ) 
				{
		     		if ( $take_1st_match ) 
		      		{
					   	if ( $debug_smart404 > 0 )
					      	error_log("smart404_redirect:general:" 
								. ($take_1st_match ? "take_1st_match":"") 
								. ":tag:" . $posts[0]->name);
				  		$redir = get_tag_link($posts[0]->term_id)
		          					. '?' . http_build_query($getParams);
				        if ( $debug_smart404 > 1 )
					        error_log("smart404_redirect:redir:". $group . ": " . $redir);
		          		wp_redirect($redir, 301, "smart-404");
				   		exit;
		   			}
		    	}
		   		break;
	
	      case "categories":
				$posts = get_categories( array ( "name__like" => $search ) );
				$mct = count( $posts );
				if ( $mct > 0 ) 
				{
					if ( $take_1st_match  ) 
					{
			     		if ( $debug_smart404 > 0 )
			     			error_log("smart404_redirect:exact:take_1st_match" 
			     			. ":category:" . $posts[0]->name);
			         	$redir = get_category_link($posts[0]->term_id)
		          			        . '?' . http_build_query($getParams);
				        if ( $debug_smart404 > 1 )
					        error_log("smart404_redirect:redir:". $group . ": " . $redir);
			         	wp_redirect($redir, 301, "smart-404");
			         	exit;
					}
			    }
			    break;
		}
	   	if ( $debug_smart404 > 1 )
			error_log("smart404_redirect: general: ". $group . ": #matches: ". $mct
				. ( $debug_smart404 > 4 ? ":" . print_r($posts,true) : ""));
		$matches = array_unique(array_merge($matches, $posts), SORT_REGULAR);
	}

	$mct = count( $matches );
	if ( $walk_uri and ( $mct == 0 ) ) 
	{
		$tail = basename($uri);
		$uri = join("/", array_slice(explode("/", $uri),0,-1)); /* drop last element */
		if ( $uri )
		{
			if ( $debug_smart404 > 1 )
	  		   error_log("smart404_redirect:walk_uri=" . $uri . "= tail=" . $tail);
	  		$getParams['walkup'][]= $tail;
			wp_redirect($uri . '?' . http_build_query($getParams), 301, "smart-404");
			exit;
		}
	}

	smart404_set_suggestions($matches);
	if ( $debug_smart404 > 0 )
		error_log("smart404_redirect: uri=" . $uri . "= #matches=" . $mct);
}

/**
 * Helper function for searching
 *
 * @package Smart404
 * @since 0.5
 * @param   query   Search query
 * @param   type    Entity type (page or post)
 * @return  Array of results
 */
function smart404_search($search, $type) {
    $search_words = trim(preg_replace( "@[_-]@", " ", $search));
	$posts = get_posts( array( "s" => $search_words, "post_type" => $type ) );
	if ( count( $posts ) > 1 ) {
	    // See if search phrase exists in title, and prioritise any single match
	    $titlematches = array();
	    foreach ( $posts as $post ) {
	        if ( strpos(strtolower($post->post_title), strtolower($search_words)) !== false ) {
	            $titlematches[] = $post;
	        }
	    }
	    if ( count($titlematches) == 1 ) {
	        return $titlematches;
	    }
	}
	
	return $posts;
}
 
 
/**
 * Filter to keep the inbuilt 404 handlers at bay
 *
 * @package Smart404
 * @since 0.3
 *
 */
function smart404_redirect_canonical_filter($redirect, $request) {
	
	if ( is_404() ) {
		// 404s are our domain now - keep redirect_canonical out of it!
		return false;
	}
	
	// redirect_canonical is good to go
	return $redirect;
}

/**
 * Set up administration
 *
 * @package Smart404
 * @since 0.1
 */
function smart404_setup_admin() {
	add_options_page( 'Smart 404', 'Smart 404', 'manage_options', __FILE__, 'smart404_options_page' );
	wp_enqueue_script('jquery-ui-sortable');
}

/**
 * Options page
 *
 * @package Smart404
 * @since 0.1
 */
function smart404_options_page() {
	?>
	<div class="wrap">
	<h2>Smart 404</h2>
	
	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>
	
	<table class="form-table">
	
	<tr valign="top">
		<th scope="row"><?php _e('Search:') ?><br/><small><?php _e('(Drag up/down to change priority)') ?></small></th>
		<td>
		<ul id="also_search_group">
		    <?php foreach ( array_unique(array_merge((array)get_option('also_search'), array('posts','pages','tags','categories'))) as $group ) : ?>
			<li><input type="checkbox" name="also_search[]" value="<?php echo $group ?>" <?php echo (in_array($group, (array)get_option('also_search')) ? "checked" : ""); ?> /> <?php _e(ucwords($group)) ?></li>
		    <?php endforeach; ?>
		</ul>
		</div>
		</td>
	</tr>
	
	<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery('#also_search_group').sortable();
        jQuery('#also_search_group').disableSelection();
    });
	</script>
	
	<tr valign="top">
		<th scope="row"><?php _e('Replacements:') ?></th>
		<td>
			<textarea name="replacements" cols="44" rows="5"><?php echo htmlspecialchars(get_option('replacements')); ?></textarea><br />
			<?php _e("One regex per line to replace & retry. Regular expressions are required, no commas. useful for searching alternate/relocated directories"); ?>
		</td>
	</tr>
	
	<tr valign="top">
		<th scope="row"><?php _e('Ignored patterns:') ?></th>
		<td>
			<textarea name="ignored_patterns" cols="44" rows="5"><?php echo htmlspecialchars(get_option('ignored_patterns')); ?></textarea><br />
			<?php _e("One term per line to ignore while searching. Regular expressions are permitted."); ?>
		<br/>
		<input type="checkbox" name="skip_ignored" value="skip_ignored" 
		<?php echo get_option('skip_ignored') ? "checked" : ""; ?> /> skip redirection of ignored patterns
		</td>
	</tr>
	
	<tr valign="top">
		<th scope="row">Redirection:</th>
		<td>
		<input type="checkbox" name="take_1st_match" value="take_1st_match" 
		<?php echo get_option('take_1st_match') ? "checked" : ""; ?> /> redirect to 1st match found, otherwise show all matches
		<br/>
		<input type="checkbox" name="take_exact_match" value="take_exact_match" 
		<?php echo get_option('take_exact_match') ? "checked" : ""; ?> /> redirect to exact match if found
		<br/>
		<input type="checkbox" name="search_whole_uri" value="search_whole_uri" 
		<?php echo get_option('search_whole_uri') ? "checked" : ""; ?> /> try to match on whole uri; default is tail only
		<br/>
		<input type="checkbox" name="walk_uri" value="walk_uri" 
		<?php echo get_option('walk_uri') ? "checked" : ""; ?> /> try walking up uri
		</td>
	</tr>
	
	<tr valign="top">
		<th scope="row">Debug:</th>
		<td>
		<input type="text" size=1 name="debug_smart404" value="<?php echo get_option('debug_smart404'); ?>" />&gt;0 to send debug to error log (1-5)
		</td>
	</tr>

	</table>
	
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="also_search, replacements, skip_ignored, ignored_patterns,take_1st_match,take_exact_match,search_whole_uri,debug_smart404,walk_uri" />
	
	<p class="submit">
	<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
	</p>
	
	</form>
	</div>
	<?php
}

/**
 * Template tag to determine if there any suggested posts
 *
 * @package Smart404
 * @since 0.1
 *
 * @return	boolean	True if there are some suggestions, false otherwise
 */
function smart404_has_suggestions() {
	$suggestions = smart404_get_suggestions();
	return ( isset ( $suggestions )
		&& is_array( $suggestions )
		&&    count( $suggestions) > 0 ); 
}

/**
 * Template tag to obtain suggested posts
 *
 * @package Smart404
 * @since 0.1
 *
 * @return	array	Array of posts
 */
function smart404_get_suggestions() {
	return $GLOBALS["__smart404"]["suggestions"];
}

function smart404_set_suggestions($suggestions) 
{
	$GLOBALS["__smart404"]["suggestions"] = $suggestions;
}

/**
 * Template tag to render HTML list of suggestions
 *
 * @package Smart404
 * @since 0.1
 *
 * @param	format	string	How to display the items: 
 	- flat (just links, separated by line-breaks), 
 	- list (ul/li items)
 * @return	boolean	True if some suggestions were rendered, false otherwise
 */
function smart404_suggestions($format = 'list') 
{
	$suggestions = smart404_get_suggestions();
	if ( ! $suggestions )
		return false;
	
	echo '<div id="smart404_suggestions">';
	if ( $format == 'list' )
		echo '<ul>';
		
	foreach ( (array) $suggestions as $post ) 
	{
       if ( $format == "list" )
    		echo '<li>';

       if (is_a($post, "WP_Post"))
       {
    	    $post_title = trim($post->post_title);
    	    $postLink   = get_permalink($post->ID);
		}
		else if (is_a($post, "WP_Term"))
		{
			if ( $post->taxonomy == "post_tag" )
				$post_title = "tag: " . $post->name;
			else
				$post_title = $post->taxonomy . ": " . trim($post->category_nicename);
			$postLink = get_term_link($post->term_id, $post->taxonomy);            
	    }
	    else
	    {
    		error_log("smart404_suggestions -------- unhandled post type: ". get_class($post));
	    	$post_title = null;
	    }

       if ( $post_title )
    	     echo "<a href='".  $postLink . "'>" . $post_title . "</a>";
    	echo ( $format == "list" ? '</li>' : '<br />');
	}
	
	if ( $format == 'list')
		echo '</ul>';
	echo '</div>';
	
	return true;
}

/**
 * Template tag to initiate 'The Loop' with suggested posts
 *
 * @package Smart404
 * @since 0.1
 *
 * @return	boolean	True if there are some posts to loop over, false otherwise
 */
function smart404_loop() 
{
	$suggestions = smart404_get_suggestions();
	if ( ! $suggestions)
		return false;

	$postids = array_map(create_function('$a', 'return $a->ID;'), $suggestions);
	query_posts( array( "post__in" => $postids ) );
	
	return have_posts();
}

/**
 * Template tag to retrieve array of search terms used
 *
 * @package Smart 404
 * @since 0.4
 *
 * @return Array or string of search terms
 */
function smart404_get_search_terms($format = 'array')
{
    $rtn = $GLOBALS["__smart404"]["search_words"];
	if ($format != 'array')
		$rtn = trim(implode(" ", $rtn));
	return $rtn;
}
function smart404_set_search_words($search_words, $delim = " ") 
{
	$GLOBALS["__smart404"]["search_words"] = (
			is_array($search_words)
				?  $search_words
				:  explode($delim, trim($search_words)));
}
/**
 * Template tag for 404-page
 */

function smart404_display_suggestions($themename = "smart-404") 
{ 
	echo "<div class=smart-404>";

	$sts = smart404_get_search_terms('string');
	if (smart404_has_suggestions())
	{
		echo "<br/>";
		_e( 'Perhaps one of these is what you are looking for:',$themename);
		smart404_suggestions("list");
		echo "<br> or";
	}
	else
		echo "<br>Please";
_e( ' try searching our webpages:&nbsp;',$themename); 
?>
 <form role="search" method="get" id="searchform"
    class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <div>
        <label class="screen-reader-text" for="s"><?php _x( 'Search for:', 'label' ); ?></label>
        <input type="text" value="<?php echo smart404_get_search_terms('string') ?>" 
			style="width:40%;background-color:#e6ddcc!important;" name="s" id="s" />
        <input type="submit" id="searchsubmit"
 style="background-color:#e6ddcc!important;"
			   value="<?php echo esc_attr_x( 'Search', 'submit button' ); ?>" />
    </div>
 </form>
	<script type="text/javascript">
		// load, focus on search field
        sf = document.getElementById('s');
        sf.focus();
	</script>
 </div>
<?php
}
// Set up plugin
smart404_set_search_words(array());

add_action( 'template_redirect', 'smart404_redirect' );
add_filter( 'redirect_canonical', 'smart404_redirect_canonical_filter', 10, 2 );
add_action( 'admin_menu', 'smart404_setup_admin' );
add_option( 'also_search', array ( 'posts', 'pages', 'tags', 'categories' ) );
add_option( 'replacements', '' );
add_option( 'ignored_patterns', '' );
add_option( 'ignore_patterns', 'ignore_patterns' );
add_option( 'take_1st_match', 'take_1st_match' );
add_option( 'take_exact_match', 'take_exact_match' );
add_option( 'search_whole_uri', 'search_whole_uri' );
add_option( 'debug_smart404', '0' );
add_option( 'walk_uri', 'walk_uri' );


function get_the_user_ip() 
{
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )
	{
	//check ip from share internet	
		$ip = $_SERVER['HTTP_CLIENT_IP'];	
	}
	elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) 
	{
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} 
	else 
	{
		$ip = $_SERVER['REMOTE_ADDR'];
	}
		
	return apply_filters( 'wpb_get_ip', $ip );	
}
?>