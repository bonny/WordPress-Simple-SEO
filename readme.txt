=== Simple SEO ===
Contributors: eskapism
Donate link: https://eskapism.se/sida/donate/
Tags: seo, meta description, open graph, noindex, simple
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The SEO basics and nothing more: a title, a description and a "don't index" checkbox for every page. No upsells, no settings maze.

== Description ==

Looking for an SEO tool with AI scores, premium upsells and an almost infinite number of settings? Then look elsewhere, because we have none of that.

What we've got: three fields on every post and page. Exactly what you need, and something you can actually manage.

- **SEO title.** The title you give search engines and the browser tab. Your headline can say "About us" while search results say "About us: small batch coffee from Stockholm". Same page, just more to go on.
- **Meta description.** The short summary search engines often show under the title.
- **Discourage search engines.** Ask them not to index thank-you pages, test pages and anything else that doesn't belong in Google.

Fill in a field to use it. Leave it empty, and WordPress does what it always did.

And a few things that just happen:

- **Link previews.** Links shared in Slack, iMessage, LinkedIn, Mastodon, Bluesky and Facebook get the right title, description and image: the featured image, or a default one you pick in Settings → General.
- **A cleaner sitemap.** WordPress's built-in sitemap, minus the pages search engines shouldn't index.
- **Both editors.** A panel in the block editor sidebar, a box in the Classic Editor.
- **Quick Edit.** An SEO column in the Posts and Pages lists, and the fields right in Quick Edit, for fixing many pages fast.
- **Plays nice.** Using Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework? Simple SEO steps aside and says so, so you don't get duplicate tags.

No settings page. No dashboard widgets. No "Go Pro" banners. <del>No nags.</del> Okay, one small grey tip about Simple History. No extra database tables. No "optimized by Simple SEO" comment in your page source either: your HTML is yours. Activate it, edit a page, done.

#### Built for search in 2026

As of 2026, Google's AI Overviews and ChatGPT search use the same basics as normal search: a page they can crawl, a good title and a good description. That's what Simple SEO does. No llms.txt, no "AI optimization" scores, no keyword stuffing. More in the FAQ.

#### Back from the dead!

Simple SEO slept from 2012 to 2026. That's fourteen years, 37 major WordPress releases and one block editor. Now it's awake again, dusted off, and still small on purpose.

One of the earliest SEO plugins for WordPress. Older than Yoast SEO, Rank Math, SEOPress and The SEO Framework, and still alive and kicking (again!).

#### Why does this plugin exist?

Back in 2010 I wanted two small things: a better page title for Google, and a shorter name in the menu. So I wrote a plugin that does exactly that.

Fun fact: Simple SEO arrived on WordPress.org in August 2010, seven weeks before Yoast SEO. Not the first SEO plugin (All in One SEO Pack beat us by three years), but one of the old-timers.

Honest bit: I build Simple SEO for my own sites, like [simple-history.com](https://simple-history.com/). It does what they need and not much more. But sharing is caring, so here it is for you too. Feature requests are welcome, just know that the answer is often "that's a bit much for a plugin called Simple". And hey, things may change: features come and go as my own sites need them. What you've typed into the fields stays put, and the plugin stays simple. Promise.

#### Made by the Simple History guy

No big company or investors behind Simple SEO. Just one developer: me. I also make [Simple History](https://wordpress.org/plugins/simple-history/), which keeps a log of what happens on your site. With both active, every change to your SEO fields shows up in the log: who changed the title, when, and what it said before.

#### Donation and more plugins

* If you like this plugin, [a donation keeps it going](https://eskapism.se/sida/donate/).
* Check out [more WordPress plugins by me](https://profiles.wordpress.org/eskapism/#content-plugins).

== Installation ==

1. In your WordPress admin, go to Plugins → Add New Plugin and search for "Simple SEO".
1. Install and activate it.
1. Edit a post or page. In the block editor, open the "Simple SEO" panel in the sidebar. In the Classic Editor, scroll to the "Simple SEO" box below the editor. To fix many pages at once, use Quick Edit in the Posts or Pages list.
1. Optional: pick a default share image in Settings → General.

== Frequently Asked Questions ==

= What happens if I leave a field empty? =

WordPress does what it always does: the post title in search results and browser tabs, and search engines pick their own description.

= Where does the site name go? =

After your SEO title, like WordPress normally does: "About us: small batch coffee from Stockholm – Tallvik Coffee Roasters". On the front page, your SEO title is the whole title.

= How long should the SEO title be? =

About 50 characters, and close to the page's heading: the same words, just more to go on. WordPress adds " – Site name" after it, and search results cut titles off at around 60 characters. Google also tends to rewrite titles that are long or say something different from the heading. On the front page, the SEO title is the whole title, so about 60 characters works.

= Does "Discourage search engines" hide the page? =

No, it asks search engines not to list it, and it's up to them to honor that. Anyone with the link can still open the page. Simple SEO also leaves the page out of the sitemap.

= What makes a good default share image? =

A wide picture, 1200 × 630 pixels, not your logo or site icon. It shows in link previews in Slack, LinkedIn, Mastodon, Bluesky and others when a post has no featured image. Small or square images get the small preview card.

= My front page shows my latest posts. How do I set its title and description? =

It uses the Site Title and Tagline from Settings → General. The tagline becomes the meta description.

= I use Yoast SEO (or Rank Math, All in One SEO, SEOPress, The SEO Framework). =

Then that plugin is in charge and Simple SEO outputs nothing. The fields stay editable and say which plugin is in charge, so nothing is lost if you switch.

= Do I need llms.txt? =

Not today. As of 2026, Google says it doesn't use it, and hardly any AI search engine fetches it, so Simple SEO doesn't make one. If that changes, so will we.

= What about AI search, like Google AI Overviews and ChatGPT? =

Same rules as normal search: a page that can be crawled, with a good title and description. Simple SEO handles those. Discouraging search engines keeps a page out of AI answers too, with the search engines that honor it (Google does, as of 2026).

= Can I stop AI companies from training on my site? =

Not with Simple SEO. That's a robots.txt job: block the training bots (like GPTBot and ClaudeBot) and keep the search bots, so you still show up in AI search.

= Does it make a sitemap? =

WordPress has one built in, at /wp-sitemap.xml. Simple SEO leaves out the pages that discourage search engines.

= Can I edit the fields with WP-CLI, the REST API or an AI tool? =

Yes. They're normal post meta: `_simple_seo_title`, `_simple_seo_description` and `_simple_seo_noindex`. A title or description is used when it isn't empty. For example: `wp post meta update 123 _simple_seo_title "About us: small batch coffee from Stockholm"`. In the REST API they're under `meta` with `?context=edit`, for users who can edit the post.

= For developers: can I change what Simple SEO outputs? =

Yes, with filters: `simple_seo_title`, `simple_seo_description`, `simple_seo_noindex`, `simple_seo_link_previews`, `simple_seo_link_preview_image`, `simple_seo_link_preview_tags` and `simple_seo_active_seo_plugin`. For example, to add the page's language:

`add_filter( 'simple_seo_link_preview_tags', function ( $tags ) { $tags['og:locale'] = 'sv_SE'; return $tags; } );`

= Is it fast? =

Yes. It reads only the post being shown, which WordPress has already loaded, and its one setting loads with WordPress's own. The only lookup it may add is the featured image for link previews, and most themes load that anyway.

= What happened to the menu label? =

It's still there for pages, in the Classic Editor box, and in Quick Edit on pages that already have one: a shorter name in automatic page lists, like "About" for "About our company".

== Screenshots ==

1. A few SEO fields in the page sidebar of the block editor. That's it.
2. See and edit the SEO of every page right from the Pages list, with Quick Edit.
3. The same column for posts, so you can spot the ones missing a description.
4. Works in the Classic Editor too.
5. One setting: a default share image for link previews, in Settings → General.
6. With Simple History, every SEO change is logged: who, when, and what it said before.

== Changelog ==

= Unreleased =
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
