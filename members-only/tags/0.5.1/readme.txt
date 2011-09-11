=== Members Only ===

Contributors: hami
Tags: members, user, admin, restrict, posts, access
Requires at least: 2.1
Tested up to: 2.5
Stable tag: 0.5.1

A WordPress plugin that allows you to make your WordPress blog only viewable to visitors that are logged in.

== Description ==

*Members Only* is a simple WordPress plugin that allows you to make your blog only viewable to visitors that are logged in. If a visitor is not logged in, they will be redirected either to the WordPress login page or a page of your choice. Once logged in they can be redirected back to the page that they originally requested.

== Installation ==

This section describes how to install the plugin and get it working.

1. Download the PHP File
2. Upload *members-only.php* file into your *wp-content/plugins/* directory
3. In your *WordPress Administration Area*, go to the *Plugins* page and click *Activate* for *Members Only*

Once you have *Members Only* installed and activated you can change it's settings in *Options > Members Only*.

== Changes ==

**0.5.1**
* Fixed a bug where redirecting to a specific page was causing an endless redirection loop.
* Simplified redirection logic and made it simpler. Using `template_redirect` no longer requires the plugin to exclude `wp-login.php`, `wp-register.php`, `xmlrpc.php` or anywhere in `wp-admin` from being inaccessible, or to check if page is a 404.
* Added `wp-feed.php` to the list of files in the function that restricts access to feeds.
* Removed `sprintf` from the variable that gets the current URL.

**0.5**
* Added functionality make RSS inaccessible. Calling the plugin at `wp_head` in previous versions made the feeds accessible without being logged in.
* Added the ability to toggle whether RSS feeds are accessible to the settings page.
* Changed where the plugin is call from `wp_head` to `template_redirect` which fixes an error where in some situations WordPress would give an error saying `Warning: Cannot modify header information - headers already sent...`
* Rewrote some functions in the plugin to make them tidier. 

**0.4.2**
* Improved security on checking URLs. Replace all `preg_match` and replaced with `strpos` except checking for wp-admin URLs.
* Added checking for 404 pages. They now redirect to the login page too.
* Change where the plugin is called from `init` back to `wp_head` otherwise 404 pages can't be redirected. If this causes problems, like the 'Cannot modify header information' error you can change this back to `init` but a 404 page will be able to be seen as normal.

**0.4.1**
* *Actually* fixed the *critical flaw* in the `preg_match` used to check the url highlighted by [mrgreen](http://wordpress.org/support/topic/164011). The fix in 0.4 didn't work full as you could still add the full url of wp-login.php as a variable and bypass the check. The `preg-match` now uses `parse_url` to only check only the path of the url and nothing else. All users using *Members Only* should upgrade to version 0.4.1 as soon as possible to avoid this flaw being taken advantage of.

**0.4**
* Fixed a *critical flaw* in the `preg_match` used to check the url highlighted by [mrgreen](http://wordpress.org/support/topic/164011). All users using *Members Only* should upgrade to version 0.4 as soon as possible to avoid this simple flaw being taken advantage of.
* Excluded `xmlrpc.php` from being protected by *Members Only*.
* Tweaked Settings Page to suit WordPress 2.5

**0.3**
* Fixed an error where in some situations WordPress would give an error saying `Warning: Cannot modify header information - headers already sent...`
* Excluded `wp-register.php` and `wp-admin/*` from being protected by *Members Only*.
* Exposed the page the visitor original requested so it can be used as a global variable (`$members_only_reqpage`).

**0.2**
* Added the ability to specify the page to redirect to, and the ability to turn off the redirection to the requested page.

**0.1**
* Initial release.

== Settings ==

The settings for *Members Only* are extremely simple. You have a check box that will toggle whether your blog can be access by visitors with or without logging in. The default setting allows visitors to visit your blog as normal.

If you choose to make your blog only accessible to visitors that are logged in, a visitor that isn't logged in will be redirected to either the WordPress login page or a specific page of you choice. This choice can be selected via a drop down menu. You can enter the specific page to redirect to at the bottom of the options page, but if this field is left blank, visitors will be redirected to the login page instead

If you chose to redirect to the WordPress login page, you can also decide whether once the visitor has logged if they will be redirected back to the page that they originally requested. This can be toggled with a check box.



== Screenshots ==

1. Options for *Members Only*

== Known Issues ==

No known issues at this time. 

If you find any bugs or want to request some additional features for future releases, please log them in this plugin's Google Code repository (both repositories are in sync with each other)
<http://code.google.com/p/wordpress-membersonly/>
