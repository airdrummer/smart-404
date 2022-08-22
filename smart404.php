<?php
/*
Plugin Name: Smart 404
Plugin URI: https://github.com/airdrummer/smart-404

Description: Rescue your viewers from site errors!  When content cannot be found, Smart 404 will use the current URL to attempt to find matching content, and redirect to it automatically. Smart 404 also supplies template tags which provide a list of suggestions, for use on a 404.php template page if matching content can't be immediately discovered.

Version: 0.5.8
Author: airdrummer
Author URI: https://github.com/airdrummer/
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
	$GLOBALS["__smart404"]["search_words"] = array();
	    
	// Extract any GET parameters from URL
	$uri = urldecode( $_SERVER["REQUEST_URI"] );
	$get_params = "";
	if ( preg_match("@/?(\?.*)@", $uri, $matches) ) {
	    $get_params = $matches[1];
	}

	// debug levels 1-5    2dec12
	$debug_smart404 = intval(get_option('debug_smart404' ));
   	if ( $debug_smart404 > 0 ) 
		 error_log("smart404_redirect: uri =" . $uri );
	
	//  replacements	10may20
	$replacements = trim( get_option('replacements' ));
	if (strpos($get_params, 'redir=') === FALSE ) //not already replaced
	{
	    $replacements_array = array();
        $replacements_array = preg_split('/\n|\r/', $replacements, -1, PREG_SPLIT_NO_EMPTY);
   	    if ( $debug_smart404 > 4 ) 
		    error_log("smart404_redirect: replacements_array =" . join($replacements_array,",") );
		foreach ( $replacements_array as $replacement )
		{
			list($old,$new) = explode(",",$replacement);
			$redir = preg_replace("@".$old."@",$new,$uri);
			if ($redir != $uri)
			{
				if ( $debug_smart404 > 1 )
    				error_log("smart404_redirect: redir=/" . $old . "/" . $new);
              	wp_redirect( $redir . (empty($get_params) ? "?" : "&") . "redir=". $old, 301 , "smart-404");
            	exit;
			}
		}
	}
	
    // don't take 1st match air_drummer@verizon.net 15jul12
	$take_1st_match   = get_option('take_1st_match' );
	$take_exact_match = get_option('take_exact_match' );
	$search_whole_uri = get_option('search_whole_uri' );
	$walk_uri         = get_option('walk_uri' );
	$skip_ignored     = get_option('skip_ignored' );
	if ( $debug_smart404 > 4 ) 
	{
		error_log("smart404_redirect: take_1st_match="   . ( $take_1st_match  ? "yes" : "no" ) );
		error_log("smart404_redirect: take_exact_match=" . ( $take_exact_match ? "yes" : "no" ) );
		error_log("smart404_redirect: search_whole_uri=" . ( $search_whole_uri ? "yes" : "no" ) );
		error_log("smart404_redirect: walk_uri="         . ( $walk_uri ? "yes" : "no" ) );
		error_log("smart404_redirect: skip_ignored ="    . ( $skip_ignored ? "yes" : "no" ) );
	}

	// Extract search term from URL
	$patterns_array = array();
	if ( ( $patterns = trim( get_option('ignored_patterns' ) ) ) ) 
	{
        $patterns_array = preg_split('/\n|\r/', $patterns, -1, PREG_SPLIT_NO_EMPTY); /* explode( '\n', $patterns ); */
	}
	if ( $skip_ignored )
	{
        foreach ( $patterns_array as $skipped_pattern ) 
		{
			if (preg_match("@".$skipped_pattern."@", $uri, $matches) != FALSE)
			{
				if ( $debug_smart404 > 4 ) 
					error_log("smart404_redirect: skipped_pattern=" . $skipped_pattern . " matches=" . implode(" ",$matches));
				return;
			}
		}
	}
	$patterns_array[] = "/(trackback|feed|(comment-)?page-?[0-9]*)/?$";
	$patterns_array[] = "\.(html|php)$";
	$patterns_array[] = "/?\?.*";
	$patterns_array = array_map(create_function('$a', '$sep = (strpos($a, "@") === false ? "@" : "%"); return $sep.trim($a).$sep."i";'), $patterns_array);
	
    $search_groups = (array)get_option( 'also_search' );
    if ( !$search_groups ) $search_groups = array("posts","pages","tags","categories");

	if ( $debug_smart404 > 4 ) 
	{
		error_log("smart404_redirect: ignored_patterns_input=" . $patterns );
		error_log("smart404_redirect: ignored_patterns_array=" . join($patterns_array,",") );
     	error_log("smart404_redirect: search_groups=" . join($search_groups,","));
	}
   	
    $matches = array();
    $mct = 0;
    
    if ( $walk_uri ) 
    {
        $GLOBALS["__smart404"]["search_words"] = explode("/", $uri); /* only save starting uri in walk */
    }

    while ( TRUE ) /* loop for walk_uri, exit explicitly */
    {
        if ( $debug_smart404 > 1 )
           error_log("smart404_redirect: uri=" . $uri );
    	$search = preg_replace( $patterns_array, "", $uri );
    	if ( $search_whole_uri ) {
    		$search = str_replace("/", "-", $search);
    	} else {
    		$search = basename(trim($search));
    	}
    	$search = str_replace("_", "-", $search);
    	$search = trim(preg_replace( $patterns_array, "", $search));
    	if ( $debug_smart404 > 2 )
    		error_log("smart404_redirect: search=" . $search);
    	
    	if ( !$search ) break; /* explicitly exit while */
    	
    	$search_words = trim(preg_replace( "@[_-]@", " ", $search));
        if ( ! $walk_uri ) {
    	   $GLOBALS["__smart404"]["search_words"] = explode(" ", $search_words);
    	}
    	if ( $debug_smart404 > 3 )
    		error_log("smart404_redirect: search_words=" . $search_words);
    	
        // Search twice: First looking for exact title match (high priority), then for a general search
        foreach ( $search_groups as $group ) 
        {
            switch ( $group ) 
            {
                case "posts":
                case "pages":
                   // Search for posts with exact name, redirect if one found
                    $group = rtrim($group, "s");
            	    $posts = get_posts( array( "name" => $search, "post_type" => $group ) );
            		if ( count( $posts ) == 1) {
               			if ( $take_1st_match or $take_exact_match ) 
               			{
    					    if ( $debug_smart404 > 1 )
    					    	error_log("smart404_redirect: exact=" . $group );
              			    wp_redirect( get_permalink( $posts[0]->ID ) . $get_params, 301 , "smart-404");
            				exit;
               			}
               		}
                    break;
            		
            	case "tags":
            	    // Search tags
            		$tags = get_tags( array ( "name__like" => $search ) );
             		if ( count( $tags ) == 1) {
               			if ( $take_1st_match or $take_exact_match ) 
               			{
    					   if ( $debug_smart404 > 1 )
    					      error_log("smart404_redirect: exact=tags");
           					wp_redirect(get_tag_link($tags[0]->term_id) . $get_params, 301, "smart-404");
            				exit;
            			}
             		}
            		break;

               case "categories":
                    // Search categories
            		$categories = get_categories( array ( "name__like" => $search ) );
             		if ( count( $categories ) == 1) {
               			if ( $take_1st_match or $take_exact_match ) 
               			{
        					if ( $debug_smart404 > 1 )
        					   error_log("smart404_redirect: exact=categories");
            				wp_redirect(get_category_link($categories[0]->term_id) . $get_params, 301, "smart-404");
            				exit;
               			}
            		}
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
             		if ( $take_1st_match and (count( $posts ) == 1) ) 
             		{
    					if ( $debug_smart404 > 1 )
    					   error_log("smart404_redirect: general=" . $group);
               			wp_redirect( get_permalink( $posts[0]->ID ) . $get_params, 301 , "smart-404");
            			exit;
               		}
    				$matches = array_merge($matches, $posts);
                    break;                
             }
        }
        
        $mct = count( $matches );
        if (( $mct == 1 ) || ( $take_1st_match and ( $mct > 0 ))) 
        {
            if ( $debug_smart404 > 1 )
                error_log("smart404_redirect:1st= " . $matches[0]->post_name );
            wp_redirect( get_permalink( $matches[0]->ID ) . $get_params, 301 , "smart-404");
            exit;
        }
        
        if ( $walk_uri and ( $mct == 0 ) ) 
        {
           	$uri = join("/", array_slice(explode("/", $uri),0,-1)); /* drop last element */
            if ( $uri ) 
            {
           		if ( $debug_smart404 > 1 )
           		   error_log("smart404_redirect:walk_uri=" . $uri );
           	} else {
           	    break; /* explicitly exit while */
           	}
        } else {
            break; /* explicitly exit while */
        }
    }
    
    if ( $debug_smart404 > 0 ) {
	   $uri = urldecode( $_SERVER["REQUEST_URI"] );
       error_log("smart404_redirect: uri=" . $uri . "= #matches=" . $mct);
    }
    $GLOBALS["__smart404"]["suggestions"] =  $matches;
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
			<?php _e("One regex per line to replace & retry. Regular expressions are required."); ?>
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
	return ( isset ( $GLOBALS["__smart404"]["suggestions"] ) 
		&& is_array( $GLOBALS["__smart404"]["suggestions"] ) 
		&&    count( $GLOBALS["__smart404"]["suggestions"] ) > 0 ); 
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

/**
 * Template tag to render HTML list of suggestions
 *
 * @package Smart404
 * @since 0.1
 *
 * @param	format	string	How to display the items: flat (just links, separated by line-breaks), list (li items)
 * @return	boolean	True if some suggestions were rendered, false otherwise
 */
function smart404_suggestions($format = 'flat') 
{
	if ( !smart404_get_suggestions()) 
		return false;
	
	echo '<div id="smart404_suggestions">';
	if ( $format == 'list' )
		echo '<ul>';
		
	foreach ( (array) $GLOBALS["__smart404"]["suggestions"] as $post ) 
	{
	   $post_title = trim($post->post_title);
	   if ( $post_title )
	   {
    		if ( $format == "list" )
    			echo '<li>';	
    		?>
    		<a href="<?php echo get_permalink($post->ID); ?>"><?php echo $post_title; ?></a>
    		<?php
    		
    		if ( $format == "list" )
    			echo '</li>';
    		else
    			echo '<br />';
	   }
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
function smart404_loop() {
	if ( !smart404_get_suggestions() )
		return false;
	
	$postids = array_map(create_function('$a', 'return $a->ID;'), $GLOBALS["__smart404"]["suggestions"]);
	
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
    $rtn = str_replace("?redir=", " ", $GLOBALS["__smart404"]["search_words"]);
	if ($format != 'array')
		$rtn = trim(implode(" ", $rtn));
	return $rtn;
}

// Set up plugin

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

$GLOBALS["__smart404"]["search_words"] = array();

?>