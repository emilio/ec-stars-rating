=== EC Stars Rating ===
Contributors: ecoal95
Donate link: http://emiliocobos.net/donar/
Tags: stars, rating, posts rating
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweigt, blazing fast star rating plugin for WordPress

== Description ==

A **super fast** **WordPress star rating plugin**, optimized for SEO, and with a really low impact on page load (just CSS + a bit of HTML, plus de strictly required JS for working).

== Installation ==

1. Upload the content of the zip package to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin according to your needs. If you need help you can visit [the plugin page](http://emiliocobos.net/ec-stars-rating-wordpress-plugin/)
4. Place the following snippet where you want the stars to appear (normally `single.php`, `content-single.php` or `index.php`):
```
<?php if(function_exists('ec_stars_rating')) { ec_stars_rating(); } ?>
```

== Frequently asked questions ==

= What rich snippets format should I use, microdata or microformats? =

Actually, microdata is recommended by Google, but with microformats google detects the stars and uses them in your searches ([example](https://www.google.com/search?q=site:emiliocobos.net+ec+stars+rating)).

== Screenshots ==

1. Plugin basic style

== Changelog ==

= 1.0 =
Initial stable release, with some number format fixes.

== Upgrade notice ==

= 1.0 =
If you use a previous version, update now

== How it works ==

Basically we create a new table called `(prefix)ec_stars_votes`, where we store the votes of the people (to prevent duplicate votes).

The number of votes and the sum of the total votes are stored in the `(prefix)options` table in form of custom meta fields, one for the count, and another for the sum. Both fields get updated when someone votes.