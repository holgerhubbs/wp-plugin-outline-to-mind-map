<?php
/**
 * [outline-to-mind-map] shortcode handler.
 *
 * The Gutenberg shortcode block wraps content in <code> tags:
 *   <code>[outline-to-mind-map]</code> ... <code>[/outline-to-mind-map]</code>
 * so we match both the raw shortcode AND the code-wrapped version.
 *
 * Supported attributes:
 *   fold         - "all" | "none" | 1-3
 *   width        - CSS value, e.g. "100%" or "800px"
 *   height       - CSS value, e.g. "500px" or "60vh"
 *   color_scheme - "default" | "cool" | "warm" | "forest" | "mono"
 *   zoom         - "true" | "false"
 *   pan          - "true" | "false"
 *   cache        - "true" | "false"
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Outline_To_Mind_Map_Shortcode {

    private Outline_To_Mind_Map_Cache $cache;
    private static int $instance_count = 0;
    private static bool $assets_registered = false;
    private static array $token_map = [];

    public function __construct( Outline_To_Mind_Map_Cache $cache ) {
        $this->cache = $cache;
    }

    public function register(): void {
        // Priority 8 = before do_shortcode (11).
        // Priority 99 = after everything, swap tokens for real HTML.
        add_filter( 'the_content', [ $this, 'pass_one_tokenise' ], 8  );
        add_filter( 'the_content', [ $this, 'pass_two_inject'   ], 99 );
        add_filter( 'widget_text', [ $this, 'pass_one_tokenise' ], 8  );
        add_filter( 'widget_text', [ $this, 'pass_two_inject'   ], 99 );
        add_filter( 'the_excerpt', [ $this, 'strip_from_excerpt' ] );
    }

    // -------------------------------------------------------------------------
    // Pass 1: find [outline-to-mind-map] blocks and replace with safe placeholder tokens.
    // Handles three formats Gutenberg/WP may produce:
    //   A) Raw:         [outline-to-mind-map]...[/outline-to-mind-map]
    //   B) Code-tags:   <code>[outline-to-mind-map]</code>...<code>[/outline-to-mind-map]</code>
    //   C) Pre/code:    inside <pre><code> blocks
    // -------------------------------------------------------------------------

    public function pass_one_tokenise( string $content ): string {

        // Pattern A – plain shortcode (our original target).
        $content = preg_replace_callback(
            '/\[outline-to-mind-map([^\]]*)\](.*?)\[\/outline-to-mind-map\]/s',
            [ $this, 'match_to_token' ],
            $content
        );

        // Pattern B – Gutenberg shortcode block wraps tags in <code>...</code>.
        // The opening tag is <code>[outline-to-mind-map ATTS]</code>, content follows
        // as plain text lines, closing is <code>[/outline-to-mind-map]</code>.
        $content = preg_replace_callback(
            '/<code>\[outline-to-mind-map([^\]]*)\]<\/code>(.*?)<code>\[\/outline-to-mind-map\]<\/code>/s',
            [ $this, 'match_to_token' ],
            $content
        );

        // Pattern C – entire shortcode inside one <pre><code>...</code></pre>.
        $content = preg_replace_callback(
            '/<pre[^>]*><code[^>]*>\[outline-to-mind-map([^\]]*)\](.*?)\[\/outline-to-mind-map\]<\/code><\/pre>/s',
            [ $this, 'match_to_token' ],
            $content
        );

        return $content;
    }

    private function match_to_token( array $m ): string {
        $html  = $this->render_map( $m[1], $m[2] );
        $token = 'HHMM_' . md5( $html . count( self::$token_map ) ) . '_END';
        self::$token_map[ $token ] = $html;
        return $token;
    }

    // -------------------------------------------------------------------------
    // Pass 2: swap tokens back to rendered HTML after all WP filters have run.
    // -------------------------------------------------------------------------

    public function pass_two_inject( string $content ): string {
        if ( empty( self::$token_map ) ) {
            return $content;
        }
        foreach ( self::$token_map as $token => $html ) {
            // wpautop may have wrapped the token in <p> tags.
            $content = preg_replace(
                '/<p>\s*' . preg_quote( $token, '/' ) . '\s*<\/p>/i',
                $html,
                $content
            );
            $content = str_replace( $token, $html, $content );
        }
        self::$token_map = [];
        return $content;
    }

    public function strip_from_excerpt( string $content ): string {
        return preg_replace( '/\[outline-to-mind-map[^\]]*\].*?\[\/outline-to-mind-map\]/s', '', $content );
    }

    // -------------------------------------------------------------------------
    // Core renderer
    // -------------------------------------------------------------------------

    private function render_map( string $raw_atts, string $content ): string {
        // Strip any HTML tags the editor added, then decode entities.
        $markdown = wp_strip_all_tags( $content );
        $markdown = html_entity_decode( $markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $markdown = trim( $markdown );

        if ( '' === $markdown ) {
            return '<!-- outline-to-mind-map: empty content -->';
        }

        $opts = get_option( 'otmm_settings', [] );
        $atts = $this->parse_atts( $raw_atts, [
            'fold'         => $opts['default_fold']         ?? 'none',
            'width'        => $opts['default_width']        ?? '100%',
            'height'       => $opts['default_height']       ?? '500px',
            'color_scheme' => $opts['default_color_scheme'] ?? 'default',
            'zoom'         => $opts['default_zoom']         ?? 'true',
            'pan'          => $opts['default_pan']          ?? 'true',
            'cache'        => $opts['enable_cache']         ?? 'true',
        ] );

        $use_cache = filter_var( $atts['cache'], FILTER_VALIDATE_BOOLEAN );

        // Enqueue assets BEFORE the cache check so scripts are registered
        // on every request even when the HTML is served from cache.
        $this->maybe_enqueue_assets();

        if ( $use_cache ) {
            $cache_key = $this->cache->make_key( $markdown, $atts );
            $cached    = $this->cache->get( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }
        }

        self::$instance_count++;
        $map_id  = 'outline-to-mind-map-' . self::$instance_count;
        $js_opts = wp_json_encode( $this->build_js_options( $atts ) );

        // Embed markdown in <script type="text/template"> so the browser does
        // not parse # as headings or < as tags. The autoloader reads this and
        // injects an <svg class="markmap"> into the parent div.
        //
        // The per-instance inline script waits for the autoloader to finish,
        // then applies our custom options and calls fit() to center the view.
        //
        // NOTE: Markmap.create() is a static factory method, not a constructor.
        // Build the container HTML — clean, no inline scripts.
        // The per-instance JS is added via wp_add_inline_script so we never
        // need to return unescaped <script> tags from the shortcode callback.
        $html = '<div id="' . esc_attr( $map_id ) . '-wrap" '
            . 'class="otmm-wrap markmap" '
            . 'style="width:' . esc_attr( $atts['width'] ) . ';height:' . esc_attr( $atts['height'] ) . ';">'
            . '<script type="text/template">' . esc_textarea( $markdown ) . '</script>'
            . '</div>';

        // Add per-instance init script via wp_add_inline_script (properly escaped).
        $inline_js = '(function(){'
            . 'var id="' . esc_js( $map_id ) . '-wrap",opts=' . $js_opts . ',t=0;'
            . 'function run(){'
            .   'var w=document.getElementById(id);if(!w)return;'
            .   'var svg=w.querySelector("svg.markmap");'
            .   'if(!svg||!svg.__markmap){if(++t<200)return setTimeout(run,100);return;}'
            .   'var mm=svg.__markmap;'
            .   'if(mm.setOptions){mm.setOptions(opts);mm.renderData();}'
            .   'setTimeout(function(){if(mm.fit)mm.fit();},300);'
            . '}'
            . 'setTimeout(run,500);'
            . '})();';

        wp_add_inline_script( 'markmap-autoloader', $inline_js );

        if ( $use_cache ) {
            $ttl = (int) ( $opts['cache_ttl'] ?? Outline_To_Mind_Map_Cache::DEFAULT_TTL );
            $this->cache->set( $cache_key, $html, $ttl );
        }

        return wp_kses_post( $html );
    }

    // -------------------------------------------------------------------------
    // Assets
    // -------------------------------------------------------------------------

    private function maybe_enqueue_assets(): void {
        if ( self::$assets_registered ) {
            return;
        }
        self::$assets_registered = true;

        wp_enqueue_script(
            'markmap-autoloader',
            OTMM_PLUGIN_URL . 'assets/vendor/markmap-autoloader.js',
            [],
            OTMM_VERSION,
            true
        );

        wp_enqueue_style(
            'outline-to-mind-map',
            OTMM_PLUGIN_URL . 'assets/outline-to-mind-map.css',
            [],
            OTMM_VERSION
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function parse_atts( string $raw, array $defaults ): array {
        $atts = $defaults;
        preg_match_all( '/(\w+)\s*=\s*["\']([^"\']*)["\']/', $raw, $m, PREG_SET_ORDER );
        foreach ( $m as $match ) {
            $key = strtolower( trim( $match[1] ) );
            if ( array_key_exists( $key, $defaults ) ) {
                $atts[ $key ] = $match[2];
            }
        }
        return $atts;
    }

    private function build_js_options( array $atts ): array {
        $opts = [];
        $fold = $atts['fold'];
        if ( 'all' === $fold ) {
            $opts['initialExpandLevel'] = 1;
        } elseif ( 'none' === $fold ) {
            $opts['initialExpandLevel'] = -1;
        } elseif ( is_numeric( $fold ) ) {
            $opts['initialExpandLevel'] = (int) $fold;
        }
        $opts['zoom']  = filter_var( $atts['zoom'], FILTER_VALIDATE_BOOLEAN );
        $opts['pan']   = filter_var( $atts['pan'],  FILTER_VALIDATE_BOOLEAN );
        $opts['color'] = $this->color_palette( $atts['color_scheme'] );
        return $opts;
    }

    private function color_palette( string $scheme ): array {
        $palettes = [
            'default' => [ '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f', '#edc948', '#b07aa1', '#ff9da7' ],
            'cool'    => [ '#4e79a7', '#76b7b2', '#59a14f', '#499894', '#86bcb6', '#8be04e' ],
            'warm'    => [ '#e15759', '#f28e2b', '#edc948', '#ff9da7', '#d37295', '#b6992d' ],
            'forest'  => [ '#3d6b35', '#5a9d4d', '#7dbf6a', '#a1d492', '#2c5f2e', '#4a8c3f' ],
            'mono'    => [ '#222222', '#444444', '#666666', '#888888', '#aaaaaa', '#333333' ],
        ];
        return $palettes[ $scheme ] ?? $palettes['default'];
    }
}
