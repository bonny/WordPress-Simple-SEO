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

One of the earliest SEO plugins for WordPress. Older than Yoast SEO, Rank Math, SEOPress and The SEO Framework, and still alive and kicking (again!).

**Heads up: the fields only show up in the Classic Editor for now.** If you use the block editor (Gutenberg), install the [Classic Editor](https://wordpress.org/plugins/classic-editor/) plugin, or wait for Simple SEO 1.0, which adds block editor support. Titles you have already set keep working on the front end either way.

Change the title and menu label of any page or post, without touching the headline on the page itself.

#### Features
- **Custom page title.** The title shown in the browser tab and in search engines like Google. Leave it empty and the normal title is used.
- **Custom menu label.** A shorter name for the page in page lists, like "About" for a page called "About our company". Used by `wp_list_pages()` and the Page List block, which is the default navigation in block themes. Menu items you add by hand keep their own labels.
- Supports **custom post types**.
- No settings, just activate the plugin and you're ready to go.
- Uses WordPress own custom fields, so **no extra database tables**.
- Works with most themes and plugins. (Well.. at least that's what I hope for... ;)

#### Why does this plugin exist?
Back in 2010 I wanted two small things: a better page title for Google, and a shorter name in the menu. So I wrote a plugin that does exactly that.

Fun fact: Simple SEO arrived on WordPress.org in August 2010, seven weeks before Yoast SEO. Not the first SEO plugin (All in One SEO Pack beat us by three years), but one of the old-timers.

Honest bit: I build Simple SEO for my own sites, like [simple-history.com](https://simple-history.com/). It does what they need and not much more. But sharing is caring, so here it is for you too. Feature requests are welcome, just know that the answer is often "that's a bit much for a plugin called Simple".

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

= Unreleased =
- The custom page title, a new meta description and a new "discourage search engines from indexing this page" setting are now stored as post meta that the REST API and WP-CLI can read and write (`_simple_seo_title`, `_simple_seo_description`, `_simple_seo_noindex`). Existing custom page titles keep working and move to the new field the next time the post is saved. Only logged-in users who can edit the post see the fields in the REST API.
- New: the meta description and "discourage search engines" (noindex) are output in the page head. With "Your latest posts" as the front page, the tagline is the meta description.
- Fixed: a custom page title on a static front page was ignored by current themes. It is now the whole title of the front page.
- New: an "SEO" panel in the block editor sidebar, with the same fields as the Classic Editor box. No more Classic Editor needed.
- Changed: in the Classic Editor the fields moved from below the title into a "Simple SEO" box below the editor, with two new ones: a meta description and "Discourage search engines from indexing this page". Each field has a checkbox, so you can switch a value off without losing it.
- New: link previews. Open Graph and Twitter card tags, so links shared in Slack, iMessage, LinkedIn, Facebook and X show the right title, description and featured image. Developers can turn them off with the `simple_seo_link_previews` filter.
- New: a default share image for link previews, in Settings → General → Simple SEO. Used when a post has no featured image.
- New: with Simple History active, changes to the SEO fields show up in its log, with the old and new values and links to edit or view the page.
- New: an "SEO" column in the Posts and Pages lists, and the SEO fields in Quick Edit, so you can fix many pages without opening each one.
- New: filters for developers to change the title, description, noindex and link preview tags. See the FAQ.
- New: posts that discourage search engines (noindex) are left out of the WordPress sitemap (`/wp-sitemap.xml`).
- New: when Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework is active, Simple SEO leaves the page head to it, so there are no duplicate tags. The edit screen tells you which plugin is in charge.
- On a site older than WordPress 6.6 or PHP 7.4, Simple SEO now pauses and shows a notice with a link to 0.3.5, instead of possibly breaking the site.

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
