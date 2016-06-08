=== Add New Default Avatar [Emrikol's Fork] ===
Contributors: emrikol 
Tags: avatars, gravatar, default avatar
Donate link:  http://www.dreamhost.com/donate.cgi?id=13663
Requires at least: 3.3.1
Tested up to: 3.3.1
Stable tag: 2.0.1

This is my version of trepmal's "Add New Default Avatar" plugin.  It has a few more features which I was looking for.

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

== Screenshots ==
1. The plugin page showing a few added avatars.

2. The "Discussion" settings showing the available avatars.

3. Kubrick theme showing the Hellow World post and comment with new avatar

== Frequently Asked Questions ==

None Yet

== Upgrade Notice ==
= 2.0.1 =
* Small bug.  I left a WB_DEBUG on :(

= 2.0.0.1 =
* Fixed small problem in readme.txt

= 2.0 =
* Upgraded to latest TimThumb, 2.8.10
* Fixed "wp_enqueue_script/wp_enqueue_style was called incorrectly" bug.
* A bit late, but thanks to <a href="http://www.honza.info/">Honza</a> for letting me know about a bad CSS bug that killed the bullets on all <li> tags.  Fixed.
* Cleaned up a lot of code, moved some actions to only admin area

= 1.0.1 =
* Upgrade to the more secure TimThumb 2.8

= 1.0 =
* Initial Release

== Changelog ==
= 2.0.1 =
* Small bug.  I left a WB_DEBUG on :(

= 2.0.0.1 =
* Fixed small problem in readme.txt

= 2.0 =
* Upgraded to latest TimThumb, 2.8.10
* Fixed "wp_enqueue_script/wp_enqueue_style was called incorrectly" bug.
* A bit late, but thanks to <a href="http://www.honza.info/">Honza</a> for letting me know about a bad CSS bug that killed the bullets on all li tags.  Fixed.
* Cleaned up a lot of code, moved some actions to only admin area

= 1.0.1 =
* Upgrade to the more secure TimThumb 2.8

= 1.0 =  
* Initial Release

== Description ==

I'd been manually adding custom default avatars in functions.php for a while, and it was always a hassle to edit it to change them.  I decided to see if there was a plugin available to do the job, and stumbled upon "Add New Default Avatar" here: http://wordpress.org/extend/plugins/add-new-default-avatar/
It was nice, but it was missing some features such as the ability to manage avatars, upload images, and automatically resize thumbnails. So I made a few modifications :)

Features:

* Saves current default avatar before activating and restores it when deactivated
* Upload images using AJAX directly from the plugin page
* Uses timthumb to automatically resize and cache thumbnails for better quality and bandwidth performance
* Ability to remove unneeded custom default avatars from the default avatar list

Feel free to contact me, emrikol@gmail.com, if you have any problems or questions.
