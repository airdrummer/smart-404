=== Smart 404 ===

Tags: 404, search, redirection
Requires at least: 2.6
Requires PHP: 5.3
Tested up to: 5.6
Stable tag: 0.6.1
Contributors: michaeltyson,leonstafford,airdrummer
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically redirect to the content the user was most likely after, or show suggestions, instead of showing an unhelpful 404 error.

== Description ==

Save your visitors from unhelpful 404 errors!

Instead of quickly giving up when a visitor reaches content that doesn't exist, make an effort to guess what they were
after in the first place.  This plugin will perform a search of your posts, pages, tags and categories, using keywords from the requested
URL.  If there's a match, redirect to that content instead of showing the error.  If there's more than one match, the
404 template can use some template tags to provide a list of suggestions to the visitor.

See the [Smart 404 homepage](http://michael.tyson.id.au/smart-404) for more information.

== Installation ==

1. Unzip the package, and upload `smart404` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place `<?php smart404_suggestions() ?>` in your 404 template to list suggested posts, or see 'Template tags' for more information.

== Template tags ==

*`smart404_has_suggestions`*

Returns true if there are some suggestions, false otherwise

*`smart404_get_suggestions`*

Retrieve an array of post objects for rendering manually.

*`smart404_suggestions`*

Draw a list of suggested posts.

Pass the parameter "list" to render suggestions as a list.

*`smart404_loop`*

Query posts for use in a Loop. Eg:

`<?php smart404_loop(); ?>
<?php while (have_posts()) : the_post(); ?>
 	<h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>
 	<?php the_excerpt(); ?>
<?php endwhile; ?>`

Note that the loop will not display pages correctly, as it is not built to support them. It is recommended that if you use
a loop like that above, do not enable searching of pages.

*`smart404_get_search_terms`*

Retrieve an array of search terms used to populate the suggestions list, for use with contextual highlighting, etc.

For support and latest source code, please visit https://github.com/leonstafford/smart-404

== Changelog ==

= 0.6.1 =
roll up

= 0.6 =
 * fix deprecation notice
 * add GitHub repo as communications point
 * update plugin's WP readme file
 * bump WP supported version

Version:	0.5.8	added skip_ignored, smart404_get_search_terms($format = 'array') 
Author:		air_drummer@verizon.net 17may20

Version:	0.5.7	added replacements
Author:		air_drummer@verizon.net 10may20

Version:	0.5.6.3	fixed debug output
Author:		air_drummer@verizon.net 20mar13

Version:	0.5.6	improved smart404_suggestions
Author:		air_drummer@verizon.net 4mar13

Version:	0.5.5	improved walk_uri
Author:		air_drummer@verizon.net 3mar13

Version:	0.5.4	walk_uri
Author:		air_drummer@verizon.net 25feb13

Version:	0.5.3	debug levels
Author:		air_drummer@verizon.net 12dec12

Version:	0.5.2	search_whole_uri
Author:		air_drummer@verizon.net 30jul12

Version:	0.5.1	 don't take 1st match 
Author:		air_drummer@verizon.net 15jul12


= 0.5 =

 * Revised search algorithm

= 0.4.4 =

 * Bugfix to avoid clobbering page search results when pages are prioritised over posts (Thanks to Mark Foxwell for the catch)

= 0.4.3 =

 * More robust pattern matching - works with .php URL extensions, and works better with underscores.

= 0.4.2 =

 * Removed errant debug statement

= 0.4.1 =

 * Now actually fixed redirection bug, missed in 0.4

= 0.4 =

 * Added prioritising of pages, posts, tags and categories for search
 * Added a new template tag to retrieve search terms
 * Fixed redirection bug (thanks Emil Janizek!)

= 0.3.5 =

 * Don't get confused by URL parameters
 * Maintain GET parameters

= 0.3.4 =

 * Slightly smarter post matching - now matches against post title, even if post slug differs

= 0.3.3 =

 * Bugfix for when no replacement patterns are specified
 
= 0.3.2 =

 * Better URL filtering to work with URLs including /trackback, /feed, etc
 
= 0.3.1 =

 * Trim whitespace from search in order to obtain more results in some circumstances
 
== Upgrade Notice ==

= 0.6 =

Fix deprecation warning in admin UI. Bump WP support to 5.6.

= 0.5 =
This version revises the search algorithm to provide better results

= 0.4 =
This version introduces prioritising of things to search for, and fixes a redirection bug, causing an infinite redirect.
