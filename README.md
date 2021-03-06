# Smart 404 for WordPress

*This is an updated continuation of [Michael Tyson's original plugin](http://atastypixel.com/blog/wordpress/plugins/smart-404/). He gave me commit access a looong time ago and I just remembered, so will push a new version with minor fix*

Save visitors to your WordPress site from unhelpful 404 errors!

When a page cannot be found, Smart 404 will use the current URL to attempt to find a matching page, and redirect to it automatically. Smart 404 also supplies template tags which provide a list of suggestions, for use on a 404.php template page if a matching post can’t be immediately discovered.

Instead of quickly giving up when a visitor reaches a page that doesn’t exist, make an effort to guess what they were after in the first place. This plugin will perform a search of your posts, tags and categories, using keywords from the requested URL. If there’s a match, redirect to that page instead of showing the error. If there’s more than one match, the 404 template can use some template tags to provide a list of suggestions to the visitor.

This plugin is also useful if you have recently changed your permalink structure: With minimal or no adjustment, old permalinks will still work.

## Download

Get Smart 404 over at the Smart 404 WordPress Plugin page!

## Installation

 - Unzip the package, and upload smart404 to the /wp-content/plugins/ directory on your WordPress site.
 - Activate the plugin through the ‘Plugins’ menu in WordPress.
 - Optionally, alter your theme’s 404.php template to list suggestions from Smart 404

*Note: If you desire reporting on 404 errors that Smart 404 is unable to remedy, I recommend Joe Hoyle’s JH 404 Logger, which adds an item to your dashboard listing 404 errors. 404 Notifier by Alex King will send emails for 404 errors, but I hear reports that emails are sent for 404 errors that this plugin is able to recover from, not just unrecoverable errors.*

## Configuration

There are two configuration options for Smart 404:

### Search

Turn on or off searching of posts, pages, tags and categories

### Ignored patterns

A newline-separated list of terms or patterns to ignore from the URL. This is particularly useful for supporting old permalinks with an ID number in them. For example, to work with URLs like:

    123-post-title.html

Add the regular expression pattern:

    ^[0-9]+-

This will ignore all numbers, followed by a hyphen, at the start of the URL.

### Template Configuration

To provide a helpful list of suggested posts in your 404 pages, modify the 404.php template in your theme to use a Smart 404 template tag. For example:

```php
    <?php if (smart404_has_suggestions()) : ?>
    Try one of these links:
    <?php smart404_suggestions(); ?>
    <?php endif; ?>
```

Or, for something a little more complicated:

```php
    <?php if (smart404_loop()) : ?>
    <p>Or, try one of these posts:</p>
    <?php while (have_posts()) : the_post(); ?>
    <h4><a href="<?php the_permalink() ?>"
      rel="bookmark"
    title="<?php the_title_attribute(); ?>">
    <?php the_title(); ?></a></h4>
      <small><?php the_excerpt(); ?></small>
    <?php endwhile; ?>
    <?php endif; ?>
```

Note that smart404_loop() will only work for posts, not pages, due to limitations in the loop mechanism. Several template tags are supplied by Smart 404 for use in the 404.php template:
smart404_has_suggestions

Returns true if there are some suggestions, false otherwise
smart404_get_suggestions

Retrieve an array of post objects for rendering manually.
smart404_suggestions

Draw a list of suggested posts.

Pass the parameter “list” to render suggestions as a list.
smart404_loop

Query posts for use in a Loop. See the second example above for usage. Note that smart404_loop() will only work for posts, not pages, due to limitations in the loop mechanism.
