=== Multisite Cloner ===
Contributors: manuelrazzari, pmtarantino
Tags: multisite, wpmu, clone, copy, copy blog, defaults, new blog, network, default settings
Requires at least: 3.0
Tested up to: 4.2.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

When creating a new blog on WordPress Multisite, copies all the posts, settings and files, from a selected blog into the new one.

== Description ==

In WordPress Multisite, new sites get a boring default template, without any custom settings or starter content.

This plugin allows you to select a specific blog on your network, a "master" one, that will be cloned every time a new blog is created.

In this way, new blogs will contain all posts, uploads, theme settings and plugin options from the master blog. 

= How does it work? =

1. It uses MySQL `INSERT INTO ... SELECT` to copy *every* table from the "master" blog into the new one. This is fast!
1. Then it does a search and replace on the new blog's tables, to replace the old URL with the new one. 
This is done in a way that respects serialized arrays, so your plugin's settings will be preserved.
(We used a heavily trimmed down version of Interconnect/IT's [Search and Replace](https://github.com/interconnectit/Search-Replace-DB) tool for that, so go thank them for this!)
1. It proceeds to copy all files from the `wp-content/uploads` dir of your master blog into the new one's, so that all assets will work as expected.
1. Finally it does some house clean-up, updating the new blog's title, admin email, and user roles as needed.

Go check the screenshots, it's really simple and does what it says. 
Give it a try!

*This plugin was handcrafted with love and ‘yerba mate’ by the team at [Tipit.net](http://www.tipit.net/ "Sustainable Web development since 1996 in Austin, Texas").* 

== Frequently Asked Questions ==

= Can I clone the main site? =

No. The main site in your network (usually the one with ID = 1) contains many DB tables, assets and even sensitive information that shouldn't be replicated to other blogs.

= Can I clone my blog in a single-site WP install? =

No. The whole point of this plugin is to clone blogs within a Multisite network. 

= Really? =

We couldn't come up with more questions. Go ahead and ask us some questions and we'll add the frequent ones here :) 

== Installation ==

1. Upload `multisite-cloner` to the `/wp-content/plugins/` directory
1. Network-activate the plugin through the 'Plugins' menu in your Network admin.
1. In your Network admin, go to Settings > Multisite Cloner

You'll probably want to create a "master" blog to clone from, if you don't have one already. 

== Screenshots ==

1. **Select a default site.** It will be cloned when any new site is created.

2. **Clone any site.** From the Sites list, admins can easily clone any site in the Network.

== Changelog ==

= 0.2.0 =
* New feature: optionally clone users from the master blog. As [suggested by @wppower](https://wordpress.org/support/topic/plugin-works-great-but-doesnt-copy-users-from-master).

= 0.1.13 =
* Disable cloning of the Main blog from the All Sites network admin.

= 0.1.12 =
* This plugin now works on an install [path that includes numbers](https://wordpress.org/support/topic/bug-on-copy-file) (avoid collisions with blogs ids), and [supports HTTPS blogs too](https://wordpress.org/support/topic/https-fails-cloner_db_replacer).

= 0.1.11.1 =
* Fixes minor [problem with the clone recursive copy function](https://wordpress.org/support/topic/error-while-adding-a-new-site)

= 0.1.11 =
* Fixes [problem with non-existents dirs](https://wordpress.org/support/topic/pull-request-error-while-copying-a-dir-while-cloning)

= 0.1.10 =
* This version works with the latest Wordpress release (Wordpress 4.0.0)

= 0.1.9 =
* Bug fix: Images in post were linked to the original blog.

= 0.1.8 =
* Minor fix to avoid PHP warning if target directories already exist (Fixes [warning when creating new site](http://wordpress.org/support/topic/error-when-creating-new-site) reported by mr.gengu and beda69).

= 0.1.7.1 =
* Minor typo fix (Fixes [error on network activation](http://wordpress.org/support/topic/error-on-network-activation) reported by ammienoot)

= 0.1.7 =
* The plugin now works on directory-based installs (Fixes [problem with duplicate file path](http://wordpress.org/support/topic/duplicates-file-path) reported by JigMedia)
* Handle case of new networks that still haven't created any sites to clone from.

= 0.1.6 =
* Fixes user roles error on installs with a non-default db prefix. (Fixes [problem with user roles after cloning](http://wordpress.org/support/topic/problem-with-user-roles-after-cloning-a-subdomain) reported by B_Dark)

= 0.1.5 =
* Independence of the uploads directory structure. (Fixes [warning after creating subdomain site](http://wordpress.org/support/topic/gives-warning-after-creating-subdomain-site) reported by Pradip Nichite)

= 0.1.4 =
* First public release.
* Disabled cloning of the main site in the network. Risky stuff.
* Added "Dolly The Cloner" graphic by rock-star designer Diana Stilinovic.

= 0.1.3 =
* Added settings page.
* Added shortcut to clone any blog in the network.
* Refactored into a plugin class.
* Refactored DB replacer to use wpdb functions instead of obsolete mysql_* calls.

= 0.1.2 =
* Replaced calls to functionality in the underlying OS (mysql, sed) with raw SQL, for greater portability.

= 0.1.1 =
* Initial version, on a client site.

== Upgrade Notice ==

= 0.2.0 =
* New feature: optionally copy users too, into the cloned blog.