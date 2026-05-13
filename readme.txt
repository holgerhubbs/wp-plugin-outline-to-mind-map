=== Outline to Mind Map ===
Contributors: holgerhubbs
Donate link: https://ko-fi.com/holgerhubbs
Support email: outline-to-mind-map@holger.us
Tags: mindmap, mind map, outline, visual thinking, visualization
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform any text outline into a visual, interactive mind map to simplify complex ideas, like a game — no coding required.

== Description ==

Outline to Mind Map lets you turn a plain text outline into a fully interactive, zoomable mind map — right inside your WordPress content.

I love mind maps and hope that this plugin will serve you well. I discovered [Markmap.js](https://markmap.js.org/) and asked Claude.ai to help me build a plugin around it. It takes a universe to make a sandwich; structure supports spontaneity.

**How it works:**

Write a Markdown-style outline between the shortcode tags and the plugin renders it into a smooth, animated SVG mind map — with clickable nodes, zoom, and pan.

`[outline-to-mind-map]`
`# My Topic`
`## Branch One`
`### Leaf`
`## Branch Two`
`[/outline-to-mind-map]`

**Features:**

* Simple shortcode — works in any post, page, or widget
* Interactive: zoom, pan, and click nodes to fold/unfold
* Configurable fold depth, dimensions, and color schemes
* Five built-in color palettes: Default, Cool, Warm, Forest, Mono
* Output caching via WordPress transients (Redis/Memcached compatible)
* Settings page with full parameter reference
* Dark mode aware
* No account or API key required
* Powered by the open-source [Markmap.js](https://markmap.js.org/) library

== Installation ==

1. Upload the `outline-to-mind-map` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins → Installed Plugins**
3. Add the shortcode to any post or page using the **Shortcode block** in Gutenberg
4. Optionally configure global defaults at **Settings → Outline to Mind Map**

**Important:** Always add the shortcode using Gutenberg's **Shortcode block** (the `[/]` block), not a Paragraph or Code block.

== Usage ==

Basic example:

`[outline-to-mind-map]`
`# My Project`
`## Planning`
`### Goals`
`### Timeline`
`## Execution`
`### Development`
`### Testing`
`[/outline-to-mind-map]`

With options:

`[outline-to-mind-map fold="2" color_scheme="forest" height="600px"]`
`# My Topic`
`## Branch A`
`## Branch B`
`[/outline-to-mind-map]`

== Shortcode Parameters ==

* **fold** — Initial fold depth: `none` (fully expanded), `all` (root only), or `1`–`3`. Default: `none`
* **width** — Container width, any CSS value. Default: `100%`
* **height** — Container height, any CSS value. Default: `500px`
* **color_scheme** — Branch palette: `default`, `cool`, `warm`, `forest`, `mono`. Default: `default`
* **zoom** — Enable mouse-wheel zoom: `true` or `false`. Default: `true`
* **pan** — Enable drag-to-pan: `true` or `false`. Default: `true`
* **cache** — Cache this instance: `true` or `false`. Default: `true`

All parameters can be set globally at **Settings → Outline to Mind Map** and overridden per shortcode.

== Frequently Asked Questions ==

= Which block do I use in Gutenberg? =

Always use the **Shortcode block** — search for "Shortcode" in the block inserter (it shows a `[/]` icon). Do not paste the shortcode into a Paragraph or Code block.

= The mind map appears blank after a page reload. =

Go to **Settings → Outline to Mind Map** and click **Flush All Cached Maps**, then reload the page. This clears any stale cached output.

= Can I change the default height and colors? =

Yes — visit **Settings → Outline to Mind Map** to set global defaults for all maps on your site. Individual shortcodes can override any setting.

= Does this work with classic (non-Gutenberg) editor? =

Yes, paste the shortcode directly into the classic editor's text view.

= Does it require an internet connection? =

The Markmap.js library loads from the jsDelivr CDN, so visitors need internet access. There is no server-side dependency.

= Will this slow down pages that don't have a mind map? =

No. The Markmap scripts are only loaded on pages that contain the shortcode.

== Screenshots ==

1. A rendered mind map on the frontend
2. The shortcode in Gutenberg's Shortcode block
3. The Settings page with parameter reference

== Changelog ==

= 1.0.3 =
* Fixed fold parameter (initialExpandLevel) not being applied on initial render
* Fixed color_scheme parameter having no effect
* Fixed zoom and pan parameters not being applied

= 1.0.1 =
* Bundled Markmap library locally (WordPress.org policy compliance)
* Fixed PHP short echo tags
* Fixed nonce verification on settings page
* Light color scheme for mind map container

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade needed.
