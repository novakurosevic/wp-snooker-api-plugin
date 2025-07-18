=== Snooker Org API ===
Contributors: novakurosevic
Tags: snooker, sports, api, ajax, cache
Stable tag: 1.0
Tested up to: 6.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fetch data from snooker.org API and cache it for fast display of snooker match results.

== Description ==

Snooker Org API fetches snooker match data from the snooker.org API and caches it to improve performance.

It displays previous, current, and upcoming matches using tabs with AJAX loading for a smooth user experience.

The plugin reduces API calls and speeds up your site by caching data temporarily.

== Author ==

- Novak Urošević — [GitHub](https://github.com/novakurosevic) | [LinkedIn](https://www.linkedin.com/in/novak-urosevic/)

**Important:**
You need to get the Header Value (X-Requested-By) from the snooker.org webmaster by emailing **`webmaster@snooker.org`**
Enter this value in the plugin settings.

== Installation ==

1. Download the plugin zip file or clone the plugin folder to your computer.

2. Log in to your WordPress admin dashboard.

3. Go to **Plugins > Add New**.

4. Click the **Upload Plugin** button at the top.

5. Select the plugin zip file and click **Install Now**.

6. After installation, click **Activate Plugin**.

7. Go to the plugin settings page (found under **Settings > Snooker Org API**) and enter the Header Value (X-Requested-By) you obtained by emailing **`webmaster@snooker.org`**.

8. To display the snooker matches on your site, add the shortcode `[snooker_org_plugin]` into any post, page, or text widget.

== Frequently Asked Questions ==

= How do I get the API Header Value (X-Requested-By)? =

You must contact the snooker.org webmaster at **`webmaster@snooker.org`** and request it.

= How do I show the matches on my site? =

Simply add the shortcode `[snooker_org_plugin]` to any post, page, or widget area.

== Screenshots ==

1. Tabs showing Previous, Current, and Upcoming matches.
2. Smooth AJAX loading of tab content.

== Changelog ==

= 1.0 =
* Initial release with AJAX-loaded tabs for previous, current, and upcoming matches.
* Caching system to improve performance.
* Shortcode support.

== Upgrade Notice ==

= 1.0 =
Initial release.

== License ==

This plugin is licensed under GPLv2 or later. See the LICENSE.md file for full license details.
