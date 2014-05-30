=== Multisite Cloner ===
Contributors: manuelrazzari, pmtarantino
Tags: multisite, wpmu, clone, copy, copy blog, defaults, new blog, network, default settings
Requires at least: 3.0
Tested up to: 3.9.1
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

= 0.1.4 =
This is the first public release.