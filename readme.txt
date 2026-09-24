=== Simple SEO (Search Engine Optimization) ===
Contributors: eskapism, MarsApril
Donate link: https://eskapism.se/sida/donate/
Tags: seo, page title, title tag, menu label, classic editor
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.5
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Change the page title and menu label output for any page or post.
Useful for SEO and usability reasons and almost a necessarity on a CMS-like website.

== Description ==

**Heads up: the fields only show up in the Classic Editor for now.** If you use the block editor (Gutenberg), install the [Classic Editor](https://wordpress.org/plugins/classic-editor/) plugin, or wait for Simple SEO 1.0, which adds block editor support. Titles you have already set keep working on the front end either way.

Optimize your web site or blog by changing the title and menu output for any page or post.
Thich is very important for SEO (Search Engine Optimization) reasons, but it can also increase
the usability of your website, making it more friendly and understandable for your visitors. 
[Take a look at the screenshots](http://wordpress.org/extend/plugins/simple-seo/screenshots/) to 
see what it looks like and how it works.

#### Features
- **Change the page title of your posts and pages.** This is the title that you see in the title bar of your web browser. It is also the title that shows up in search engines like Google.
- **Change the menu title label of your posts and pages.** This is the title that is shown in your menus/navigation.
- Supports **custom post types**.
- Make your post or page **more attractive** in Google's search results (Google SERP).
- Add additional **keywords synonyms** to your pages or posts.
- Increase **usability**
- **Nicely integrated** into WordPress. Looks like it was built into the core.
- No settings, just activate the plugin and you're ready to go.
- Uses WordPress own custom fields, so **no extra database tables**.
- Works with most themes and plugins. (Well.. at least that's what I hope for... ;)

#### Why yet another SEO-plugin for WordPress?
I felt that the existing plugins for WordPress Search Engine Optimization where a bit to complex 
and difficult to use, especially for new users of WordPress.
Simple SEO aim to be a simple and unobtrusive alternative.

#### Donation and more plugins
* If you like this plugin don't forget to [donate to support further development](http://eskapism.se/sida/donate/).
* Check out some [more WordPress plugins by me](http://wordpress.org/extend/plugins/profile/eskapism).

== Installation ==

1. Upload the folder "simple-seo" to "/wp-content/plugins/"
1. Activate the plugin through the "Plugins" menu in WordPress
1. Done!

Now go and edit a page and you should have two new options: "Custom Page Title" and "Custom Menu Label".

== Screenshots ==

1. Edit Page screen showing an article with modified/optimized Page Title and Menu Label. The next screenshot shows the corresponding web page.
2. Example of a web site after using Simple SEO. Notice that the page title, the menu label and the headline all are different from each other. Hooray for variation!

== Changelog ==

= 0.3.5 =
- Tested with WordPress 7.1 and PHP 7.4 to 8.3.
- Fixed: a page title box that was checked but left empty blanked the page title. It now falls back to the normal title.
- Fixed: the fields are only saved by users who can edit the post, and HTML is stripped from the values.
- Fixed: translations loaded the wrong text domain.
- Now requires WordPress 6.6 and PHP 7.4.

= 0.3.4 =
- Added: if post used on front page has a custom title, that title is prepended to the page title.

= 0.3.3 =
- Fixed: it ran some SQL queries with errors in them when in admin

= 0.3.2 =
- Fixed: No longer depends on get_post_meta to fetch the setting and title for each page. Could take a long time and use a lot of queries on a site/installation with many pages.

= 0.3.1 =
- added POT-file. Please translate! :)
- probably something else that I can't remember...

= 0.3 =
- tried to compress the plugin space a bit.
- prepare for translators.

= 0.2 =
- Some text changes

= 0.1 =
- It's kinda the first version. Works fine for me. Let me know if it works for you!
