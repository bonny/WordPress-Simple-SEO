=== Simple SEO (Search Engine Optimization) ===
Contributors: eskapism, MarsApril
Donate link: http://eskapism.se/sida/donate/
Tags: page, post, pages, posts, page title, menu title, menu label, title, seo, Search engine optimization, optimize, usability
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 0.3.4

Change the page title and menu label output for any page or post.
Useful for SEO and usability reasons and almost a necessarity on a CMS-like website.

== Description ==

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
