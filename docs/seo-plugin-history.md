# How old the WordPress SEO plugins are

Checked 2026-09-25. "First commit" is the first revision in the plugin's WordPress.org SVN (`svn log -r 1:HEAD --limit 1 https://plugins.svn.wordpress.org/<slug>/`). Status, installs and downloads are from the WordPress.org plugin API (`api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=<slug>&request[fields][active_installs]=1&request[fields][downloaded]=1`). Download counts are all-time.

| Plugin | First commit | Status | Last update | Active installs | Downloads |
|---|---|---|---|---|---|
| SEO Title Tag | 2007-03-17 | Closed 2024-03-07 (security issue) | | | |
| Optimal Title | 2007-03-17 | Closed 2021-03-02 (guideline violation) | | | |
| All in One SEO Pack | 2007-03-30 | Active | 2026-09-23 | 2,000,000+ | 211 million |
| HeadSpace2 | 2007-09-06 | Abandoned, still listed | 2017-11-28 | 3,000+ | 779,000 |
| Robots Meta (Yoast) | 2007-09-08 | Closed 2017-12-03 (author request) | | | |
| Platinum SEO Pack | 2008-06-23 | Closed 2024-02-02 (security issue) | | | |
| SEO Ultimate | 2009-05-22 | Abandoned, still listed | 2016-11-03 | 10,000+ | 2.3 million |
| Add Meta Tags | 2009-10-22 | Closed 2018-05-13 (author request) | | | |
| **Simple SEO** | **2010-08-22** | **Active (revived 2026)** | **2026-09-25** | **300+** | **45,600** |
| Yoast SEO | 2010-10-11 | Active | 2026-09-15 | 10,000,000+ | 1 billion |
| The SEO Framework | 2015-05-29 | Active | 2025-12-10 | 200,000+ | 4.8 million |
| SEOPress | 2016-08-21 | Active | 2026-09-11 | 300,000+ | 20 million |
| Rank Math | 2018-08-01 | Active | 2026-09-22 | 4,000,000+ | 198 million |

Notes:

- Yoast SEO's slug `wordpress-seo` was reserved on 2009-01-02, but the first code ("Initial commit") is from 2010-10-11. The readme's "seven weeks before Yoast SEO" line rests on that.
- Of the plugins older than Simple SEO, only All in One SEO Pack is still maintained. Four are closed, two haven't been updated since 2016–2017.
- The SEO Framework's last update is 2025-12-10, nine months ago.
