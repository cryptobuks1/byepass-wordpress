=== Byepass ===
Contributors: dommholland
Plugin Name: Byepass
Plugin URI: https://byepass.co/
Tags: login, byepass, remove passwords, passwordless, authentication, security
Author URI: https://byepass.co/
Donate link: https://byepass.co/
Author: Byepass
Requires at least: 3.5
Tested up to: 4.9.8
Stable tag: 1.0.1
Version: 1.0.1
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Login to Wordpress without passwords!

== Description ==

Byepass is an authentication platform that is more secure than passwords and less annoying.

This plugin enables users to login to Wordpress without passwords, using the free Byepass authentication API. 

This plugin will modify your login form to ask for only their email address, then they will be redirected to the secure Byepass authentication platform (Byepass.co), and then be redirected back once they have been authenticated by Byepass.

Byepass is completely backwards compatible and will support all users, you do not need to be pre-registered with Byepass, or have any 3rd-party tools/apps installed.  

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/byepass` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Click on the new 'Byepass Login' menu item in your admin menu on the left
1. Click 'Get API Keys' to register your wordpress site/blog with Byepass.co servers
1. All done, the next time you need to login with Wordpress you only need to enter your email address

== Frequently Asked Questions ==

= Is this secure? =

Byepass is more secure than passwords as there are no passwords to steal or leak.  Plus, Byepass performs identification of the user against their email, rather than just authenticating against a token/password.

== Screenshots ==

1. Login Page
2. Plugin configuration page

== Changelog ==

= 1.0 =
* Initial Release


== Upgrade Notice ==

= 1.0 =
Initial Release
