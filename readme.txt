=== Simple SEO ===
Contributors: eskapism
Donate link: https://eskapism.se/sida/donate/
Tags: seo, page title, title tag, menu label, classic editor
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.3.5
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Set a custom page title and menu label for any page or post. Two fields, no settings.

== Description ==

#### Back from the dead!
Simple SEO slept from 2012 to 2026. That's fourteen years, 37 major WordPress releases and one block editor. Now it's awake again, dusted off, and tested with WordPress 7.1. Still small on purpose.

**Heads up: the fields only show up in the Classic Editor for now.** If you use the block editor (Gutenberg), install the [Classic Editor](https://wordpress.org/plugins/classic-editor/) plugin, or wait for Simple SEO 1.0, which adds block editor support. Titles you have already set keep working on the front end either way.

Change the title and menu label of any page or post, without touching the headline on the page itself.

#### Features
- **Custom page title.** The title shown in the browser tab and in search engines like Google. Leave it empty and the normal title is used.
- **Custom menu label.** A shorter name for the page in page lists, like "About" for a page called "About our company". Used by `wp_list_pages()` and the Page List block, which is the default navigation in block themes. Menu items you add by hand keep their own labels.
- Supports **custom post types**.
- No settings, just activate the plugin and you're ready to go.
- Uses WordPress own custom fields, so **no extra database tables**.
- Works with most themes and plugins. (Well.. at least that's what I hope for... ;)

#### Why yet another SEO-plugin for WordPress?
I felt that the existing plugins for WordPress Search Engine Optimization were a bit too complex
and difficult to use, especially for new users of WordPress.
Simple SEO aims to be a simple and unobtrusive alternative.

#### Donation and more plugins
* If you like this plugin don't forget to [donate to support further development](https://eskapism.se/sida/donate/).
* Check out some [more WordPress plugins by me](https://profiles.wordpress.org/eskapism/#content-plugins).

== Installation ==

1. In your WordPress admin, go to Plugins → Add New Plugin and search for "Simple SEO"
1. Install and activate it
1. Done!

Now go and edit a page and you should have two new options: "Custom Page Title" and "Custom Menu Label".

== Screenshots ==

1. The Edit Page screen with a custom page title and a custom menu label, right below the title.
2. The same page on the site. The menu says "About", the headline says "About us", and the browser tab and Google get the custom page title. Hooray for variation!

== Changelog ==

= 0.3.5 (September 2026) =
- Back from the dead! The first update in fourteen years. Everything below is what the plugin needed after its long nap.
- Tested with WordPress 7.1 and PHP 7.4 to 8.3.
- Fixed: a page title or menu label box that was checked but left empty blanked the title or menu link. It now falls back to the normal title.
- Fixed: the blog page could get the page title of the newest post.
- Fixed: with "Your latest posts" on the front page, older themes could show the newest post's custom title on the home page.
- Fixed: when another plugin saved extra posts at the same time, they could get this page's title and menu label.
- Fixed: the fields are only saved by users who can edit the post, and HTML is stripped from the values.
- Fixed: translations loaded the wrong text domain. Translations now come from translate.wordpress.org, so the old bundled translation template is gone.
- Fixed: the fields were misaligned and cut off in the current WordPress admin.
- Now requires WordPress 6.6 and PHP 7.4. On an older site? WordPress won't install this update there, and 0.3.4 keeps working just like before.

= 0.3.4 (October 2012) =
- Added: if post used on front page has a custom title, that title is prepended to the page title.

= 0.3.3 (June 2012) =
- Fixed: it ran some SQL queries with errors in them when in admin

= 0.3.2 (June 2011) =
- Fixed: No longer depends on get_post_meta to fetch the setting and title for each page. Could take a long time and use a lot of queries on a site/installation with many pages.

= 0.3.1 (October 2010) =
- added POT-file. Please translate! :)
- probably something else that I can't remember...

= 0.3 (September 2010) =
- tried to compress the plugin space a bit.
- prepare for translators.

= 0.2 (September 2010) =
- Some text changes

= 0.1 (August 2010) =
- It's kinda the first version. Works fine for me. Let me know if it works for you!

ysaetf7ruhjnm3e2x4tbtletpc35ckeb
