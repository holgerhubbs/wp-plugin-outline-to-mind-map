# Outline to Mind Map – WordPress Plugin

Render beautiful, interactive mind maps from plain-text Markdown outlines using the `[outline-to-mind-map]` shortcode, powered by [Markmap.js](https://markmap.js.org/).

---

## Installation

1. Upload the `outline-to-mind-map` folder to `/wp-content/plugins/`.
2. Activate the plugin via **Plugins → Installed Plugins**.
3. Optionally configure defaults at **Settings → Outline to Mind Map**.

---

## Basic Usage

```
[outline-to-mind-map]
# My Project
## Planning
### Goals
### Timeline
## Execution
### Development
### Testing
[/outline-to-mind-map]
```

Write a Markdown-style outline between the shortcode tags. Use `#` for the root node, `##` for first-level branches, and so on.

---

## Shortcode Attributes

| Attribute      | Values                                          | Default  | Description |
|----------------|-------------------------------------------------|----------|-------------|
| `fold`         | `none` / `all` / `1` / `2` / `3`               | `none`   | Initial fold depth. `all` collapses to root; `none` expands everything; a number expands to that depth. |
| `width`        | Any CSS value                                   | `100%`   | Container width, e.g. `800px` or `100%`. |
| `height`       | Any CSS value                                   | `500px`  | Container height, e.g. `400px` or `60vh`. |
| `color_scheme` | `default` / `cool` / `warm` / `forest` / `mono`| `default`| Branch color palette. |
| `zoom`         | `true` / `false`                                | `true`   | Allow mouse-wheel zoom. |
| `pan`          | `true` / `false`                                | `true`   | Allow drag-to-pan. |
| `cache`        | `true` / `false`                                | `true`   | Override global cache setting for this instance. |

### Examples

Collapsed to depth 2 with the forest palette:

```
[outline-to-mind-map fold="2" color_scheme="forest"]
# My Map
## Branch A
### Leaf 1
### Leaf 2
## Branch B
[/outline-to-mind-map]
```

Fixed-size, no interactivity, cache disabled:

```
[outline-to-mind-map width="600px" height="400px" zoom="false" pan="false" cache="false"]
# Static Map
## Item 1
## Item 2
[/outline-to-mind-map]
```

---

## Caching

Rendered HTML is stored as WordPress transients (compatible with Redis / Memcached object-cache drop-ins). The cache key is a hash of the shortcode content **and** its attributes, so edits automatically produce fresh output. You can also flush all cached maps at any time from the settings page.

---

## Requirements

- WordPress 5.8+
- PHP 7.4+
- An internet connection on the front-end (Markmap scripts are loaded from the jsDelivr CDN).

---

## License

GPL v2 or later.
