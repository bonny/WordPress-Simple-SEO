=== Simple SEO ===
Contributors: eskapism
Donate link: https://eskapism.se/sida/donate/
Tags: seo, meta description, open graph, noindex, simple
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The SEO basics and nothing more: a title, a description and a "don't index" checkbox for every page. No upsells, no settings maze.

== Description ==

Three fields on every post and page:

- **SEO title.** The title in search results and the browser tab. Your headline says "About us", search results say "About us: small batch coffee from Stockholm".
- **Meta description.** The summary search engines often show under the title.
- **Discourage search engines.** For thank-you pages, test pages and anything else that doesn't belong in Google.

Leave a field empty and WordPress does what it always did.

A few things just happen: link previews with the right title, description and image in Slack, Mastodon, Bluesky and friends. Pages you keep out of search are left out of the sitemap too. There's a panel in the block editor, a box in the Classic Editor, and the fields in Quick Edit. Using Yoast SEO or another big SEO plugin? Simple SEO steps aside.

No settings page, no scores, no "Go Pro" banners, no extra database queries. <del>No nags.</del> Okay, one small grey tip about Simple History.

No plugin makes a page rank. Good content does. Simple SEO hands search engines and shared links the title, description and image you chose, and asks search engines to skip the pages you don't want listed. That's also all AI search needs from a plugin: no llms.txt, no "AI optimization".

#### Back from the dead

Simple SEO is old. Like really old. It arrived on WordPress.org in August 2010 (seven weeks before Yoast SEO!). Of the SEO plugins still maintained today, only All in One SEO is older. Then it slept from 2012 to 2026.

I woke it up mostly for myself: I wanted the SEO basics on my own sites without a plugin that asks for attention every time I log in. It stays small on purpose, so feature requests often get "that's a bit much for a plugin called Simple".

No big company or investors, just one developer: me, the guy behind [Simple History](https://simple-history.com/). With both active, every SEO change is logged: who, when, and what it said before. I also make [CMS Tree Page View](https://wordpress.org/plugins/cms-tree-page-view/), and the two work together too. If Simple SEO saves you time, [a donation](https://eskapism.se/sida/donate/) keeps it going.

== Installation ==

1. Go to Plugins → Add New Plugin, search for "Simple SEO", install and activate it.
1. Edit a page and open the "Simple SEO" panel in the sidebar, or the box below the Classic Editor.
1. Optional: pick a default share image in Settings → General.

== Frequently Asked Questions ==

= What happens if I leave a field empty? =

WordPress does what it always does: the post title, and search engines pick their own description. On a front page that shows your latest posts, the Tagline is the description.

= How long should the SEO title be? =

About 50 characters, close to the page's heading: the same words, with more to go on. WordPress adds " – Site name" after it (not on the front page), and Google may rewrite titles that are long or don't match the heading.

= Does "Discourage search engines" hide the page? =

No. It asks search engines not to list it, and it's up to them to honor that. Anyone with the link can still open the page. It's also left out of the sitemap.

= What makes a good default share image? =

A wide picture, 1200 × 630 pixels, not your logo. It's used in link previews when a post has no featured image.

= I use Yoast SEO (or Rank Math, All in One SEO, SEOPress, The SEO Framework). =

Then that plugin is in charge and Simple SEO outputs nothing. The fields stay, so nothing is lost if you switch.

= What about AI search and llms.txt? =

AI search uses the same basics as normal search: a page that can be crawled, with a good title and description. As of 2026, Google doesn't use llms.txt, so Simple SEO doesn't make one.

= Can I use WP-CLI, the REST API or filters? =

Yup. The fields are post meta: `_simple_seo_title`, `_simple_seo_description` and `_simple_seo_noindex`, in the REST API with `?context=edit`. Everything Simple SEO outputs goes through a filter first. See [the hooks](https://github.com/bonny/WordPress-Simple-SEO/blob/main/docs/hooks.md).

= What happened to the menu label? =

It's still there for pages: in the Classic Editor box, and in Quick Edit on pages that have one.

== Screenshots ==

1. A few SEO fields in the page sidebar of the block editor. That's it.
2. See and edit the SEO of every page right from the Pages list, with Quick Edit.
3. The same column for posts, so you can spot the ones missing a description.
4. Works in the Classic Editor too.
5. One setting: a default share image for link previews, in Settings → General.
6. With Simple History, every SEO change is logged: who, when, and what it said before.

== Changelog ==

= 1.3.0 (September 2026) =
- New: the front page tells Google your site's name (a small `WebSite` JSON-LD block), which it shows next to your pages in search results.

= 1.2.0 (September 2026) =
- Changed: the SEO title field in the block editor wraps onto a second line, so you can see the whole title while typing.
- New: with [CMS Tree Page View](https://wordpress.org/plugins/cms-tree-page-view/) active, a page's SEO title, description and menu label show in its page tree card, and Simple History events about a page link to it in the page tree.
- Fixed: one wasted database query each time CMS Tree Page View loaded its page tree.
- New: link previews use the post's excerpt when it has no meta description, if you wrote one. Not an automatic excerpt.
- Changed: a small or square share image, like a site icon, now gets the small preview card instead of being stretched into a large one.
- Changed: shorter help text under the fields. The details moved to the FAQ: how long the SEO title should be, what "Discourage search engines" does, and what makes a good share image.
- New: on a local development site (environment type `local`), HTML comments mark where Simple SEO's tags start and end in the page head, for easier debugging. Never on live sites.

= 1.1.0 (September 2026) =
- Changed: no more checkboxes next to the SEO title, meta description and menu label. Fill in a field to use it, empty it to go back to the default. Only "Discourage search engines" is still a checkbox. Text you had switched off with a checkbox stays off.
- Changed: Quick Edit shows the menu label only on pages that have one.

= 1.0.0 (September 2026) =
- The custom page title, a new meta description and a new "discourage search engines from indexing this page" setting are now stored as post meta that the REST API and WP-CLI can read and write (`_simple_seo_title`, `_simple_seo_description`, `_simple_seo_noindex`). Existing custom page titles keep working and move to the new field the next time the post is saved. Only logged-in users who can edit the post see the fields in the REST API.
- New: the meta description and "discourage search engines" (noindex) are output in the page head. With "Your latest posts" as the front page, the tagline is the meta description.
- Fixed: a custom page title on a static front page was ignored by current themes. It is now the whole title of the front page.
- New: a "Simple SEO" panel in the block editor sidebar, with the same fields as the Classic Editor box. No more Classic Editor needed.
- Changed: in the Classic Editor the fields moved from below the title into a "Simple SEO" box below the editor, with two new ones: a meta description and "Discourage search engines from indexing this page". Each field has a checkbox, so you can switch a value off without losing it.
- New: link previews. Open Graph and Twitter card tags, so links shared in Slack, iMessage, LinkedIn, Mastodon, Bluesky and Facebook get the right title, description and featured image. Developers can turn them off with the `simple_seo_link_previews` filter.
- New: a default share image for link previews, in Settings → General → Simple SEO. Used when a post has no featured image.
- New: with Simple History active, changes to the SEO fields show up in its log, with the old and new values and links to edit or view the page. Changes to the default share image are logged too.
- New: an "SEO" column in the Posts and Pages lists, and the SEO fields in Quick Edit, so you can fix many pages without opening each one.
- New: filters for developers to change the title, description, noindex and link preview tags. See the FAQ.
- New: posts that discourage search engines (noindex) are left out of the WordPress sitemap (`/wp-sitemap.xml`).
- New: when Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework is active, Simple SEO leaves the page head to it, so there are no duplicate tags. The edit screen tells you which plugin is in charge.
- Fixed: the Parent dropdown and the homepage and posts page dropdowns in Settings → Reading showed the custom menu label instead of the page title.
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
