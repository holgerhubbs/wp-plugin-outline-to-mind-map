<?php
/**
 * Admin settings page for Outline to Mind Map.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Outline_To_Mind_Map_Settings {

    const OPTION_KEY = 'otmm_settings';
    const MENU_SLUG  = 'outline-to-mind-map-settings';

    public function register(): void {
        add_action( 'admin_menu',       [ $this, 'add_menu_page' ] );
        add_action( 'admin_init',       [ $this, 'register_settings' ] );
        add_action( 'admin_post_otmm_flush_cache', [ $this, 'handle_flush_cache' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
    }

    // -------------------------------------------------------------------------
    // Menu
    // -------------------------------------------------------------------------

    public function add_menu_page(): void {
        add_options_page(
            __( 'Outline to Mind Map Settings', 'outline-to-mind-map' ),
            __( 'Outline to Mind Map', 'outline-to-mind-map' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    // -------------------------------------------------------------------------
    // Settings API
    // -------------------------------------------------------------------------

    public function register_settings(): void {
        register_setting(
            'otmm_settings_group',
            self::OPTION_KEY,
            [ 'sanitize_callback' => [ $this, 'sanitize_settings' ] ]
        );

        // ---- Section: Defaults ----
        add_settings_section(
            'otmm_defaults',
            __( 'Default Shortcode Options', 'outline-to-mind-map' ),
            '__return_false',
            self::MENU_SLUG
        );

        $defaults_fields = [
            [ 'default_fold',         __( 'Default Fold Level', 'outline-to-mind-map' ),        [ $this, 'field_fold' ] ],
            [ 'default_width',        __( 'Default Width', 'outline-to-mind-map' ),              [ $this, 'field_width' ] ],
            [ 'default_height',       __( 'Default Height', 'outline-to-mind-map' ),             [ $this, 'field_height' ] ],
            [ 'default_color_scheme', __( 'Default Color Scheme', 'outline-to-mind-map' ),       [ $this, 'field_color_scheme' ] ],
            [ 'default_zoom',         __( 'Enable Zoom by Default', 'outline-to-mind-map' ),     [ $this, 'field_zoom' ] ],
            [ 'default_pan',          __( 'Enable Pan by Default', 'outline-to-mind-map' ),      [ $this, 'field_pan' ] ],
        ];
        foreach ( $defaults_fields as [ $id, $label, $cb ] ) {
            add_settings_field( $id, $label, $cb, self::MENU_SLUG, 'otmm_defaults' );
        }

        // ---- Section: Cache ----
        add_settings_section(
            'otmm_cache_section',
            __( 'Cache Settings', 'outline-to-mind-map' ),
            '__return_false',
            self::MENU_SLUG
        );

        add_settings_field( 'enable_cache', __( 'Enable Output Cache', 'outline-to-mind-map' ), [ $this, 'field_enable_cache' ], self::MENU_SLUG, 'otmm_cache_section' );
        add_settings_field( 'cache_ttl',    __( 'Cache Duration (seconds)', 'outline-to-mind-map' ),  [ $this, 'field_cache_ttl' ],    self::MENU_SLUG, 'otmm_cache_section' );
    }

    // -------------------------------------------------------------------------
    // Field renderers
    // -------------------------------------------------------------------------

    private function get( string $key, $default = '' ) {
        $opts = get_option( self::OPTION_KEY, [] );
        return $opts[ $key ] ?? $default;
    }

    public function field_fold(): void {
        $v = $this->get( 'default_fold', 'none' );
        ?>
        <select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_fold]">
            <option value="none"  <?php selected( $v, 'none' ); ?>><?php echo esc_html__( 'None (fully expanded)', 'outline-to-mind-map' ); ?></option>
            <option value="all"   <?php selected( $v, 'all' ); ?>><?php echo esc_html__( 'All (collapse to root only)', 'outline-to-mind-map' ); ?></option>
            <option value="1"     <?php selected( $v, '1' ); ?>><?php echo esc_html__( 'Depth 1', 'outline-to-mind-map' ); ?></option>
            <option value="2"     <?php selected( $v, '2' ); ?>><?php echo esc_html__( 'Depth 2', 'outline-to-mind-map' ); ?></option>
            <option value="3"     <?php selected( $v, '3' ); ?>><?php echo esc_html__( 'Depth 3', 'outline-to-mind-map' ); ?></option>
        </select>
        <p class="description"><?php echo esc_html__( 'Controls how many levels are open when the map first renders. Override per-instance with the fold="" attribute.', 'outline-to-mind-map' ); ?></p>
        <?php
    }

    public function field_width(): void {
        $v = $this->get( 'default_width', '100%' );
        echo '<input type="text" name="' . esc_attr( self::OPTION_KEY ) . '[default_width]" value="' . esc_attr( $v ) . '" class="regular-text">';
        echo '<p class="description">' . esc_html__( 'Any valid CSS value, e.g. 100%, 800px. Override with width="" attribute.', 'outline-to-mind-map' ) . '</p>';
    }

    public function field_height(): void {
        $v = $this->get( 'default_height', '500px' );
        echo '<input type="text" name="' . esc_attr( self::OPTION_KEY ) . '[default_height]" value="' . esc_attr( $v ) . '" class="regular-text">';
        echo '<p class="description">' . esc_html__( 'Any valid CSS value, e.g. 500px, 60vh. Override with height="" attribute.', 'outline-to-mind-map' ) . '</p>';
    }

    public function field_color_scheme(): void {
        $v = $this->get( 'default_color_scheme', 'default' );
        $schemes = [
            'default' => 'Default (Tableau)',
            'cool'    => 'Cool (Blues & Greens)',
            'warm'    => 'Warm (Reds & Oranges)',
            'forest'  => 'Forest (Greens)',
            'mono'    => 'Mono (Greyscale)',
        ];
        echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[default_color_scheme]">';
        foreach ( $schemes as $key => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $key ),
                selected( $v, $key, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Branch color palette. Override with color_scheme="" attribute.', 'outline-to-mind-map' ) . '</p>';
    }

    public function field_zoom(): void {
        $v = $this->get( 'default_zoom', 'true' );
        echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY ) . '[default_zoom]" value="true" ' . checked( $v, 'true', false ) . '> ' . esc_html__( 'Allow visitors to zoom with the mouse wheel', 'outline-to-mind-map' ) . '</label>';
        echo '<p class="description">' . esc_html__( 'Override per-instance with zoom="false".', 'outline-to-mind-map' ) . '</p>';
    }

    public function field_pan(): void {
        $v = $this->get( 'default_pan', 'true' );
        echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY ) . '[default_pan]" value="true" ' . checked( $v, 'true', false ) . '> ' . esc_html__( 'Allow visitors to drag / pan the map', 'outline-to-mind-map' ) . '</label>';
        echo '<p class="description">' . esc_html__( 'Override per-instance with pan="false".', 'outline-to-mind-map' ) . '</p>';
    }

    public function field_enable_cache(): void {
        $v = $this->get( 'enable_cache', 'true' );
        echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY ) . '[enable_cache]" value="true" ' . checked( $v, 'true', false ) . '> ' . esc_html__( 'Cache rendered HTML output (recommended)', 'outline-to-mind-map' ) . '</label>';
    }

    public function field_cache_ttl(): void {
        $v = $this->get( 'cache_ttl', 604800 );
        echo '<input type="number" min="60" step="60" name="' . esc_attr( self::OPTION_KEY ) . '[cache_ttl]" value="' . esc_attr( $v ) . '" class="small-text"> ' . esc_html__( 'seconds', 'outline-to-mind-map' );
        echo '<p class="description">' . esc_html__( 'Default: 604800 (7 days). Cache is keyed on shortcode content + attributes, so edits always produce fresh output.', 'outline-to-mind-map' ) . '</p>';
    }

    // -------------------------------------------------------------------------
    // Sanitisation
    // -------------------------------------------------------------------------

    public function sanitize_settings( $input ): array {
        $clean = [];

        $clean['default_fold']         = in_array( $input['default_fold'] ?? 'none', [ 'none', 'all', '1', '2', '3' ], true )
                                            ? $input['default_fold']
                                            : 'none';

        $clean['default_width']        = sanitize_text_field( $input['default_width'] ?? '100%' );
        $clean['default_height']       = sanitize_text_field( $input['default_height'] ?? '500px' );

        $clean['default_color_scheme'] = in_array( $input['default_color_scheme'] ?? 'default', [ 'default', 'cool', 'warm', 'forest', 'mono' ], true )
                                            ? $input['default_color_scheme']
                                            : 'default';

        $clean['default_zoom']         = isset( $input['default_zoom'] ) ? 'true' : 'false';
        $clean['default_pan']          = isset( $input['default_pan']  ) ? 'true' : 'false';
        $clean['enable_cache']         = isset( $input['enable_cache'] ) ? 'true' : 'false';
        $clean['cache_ttl']            = max( 60, (int) ( $input['cache_ttl'] ?? 604800 ) );

        return $clean;
    }

    // -------------------------------------------------------------------------
    // Cache flush action
    // -------------------------------------------------------------------------

    public function handle_flush_cache(): void {
        if (
            ! current_user_can( 'manage_options' )
            || ! check_admin_referer( 'otmm_flush_cache' )
        ) {
            wp_die( esc_html__( 'Unauthorised.', 'outline-to-mind-map' ) );
        }
        Outline_To_Mind_Map_Cache::flush_all();
        $redirect = add_query_arg( [ 'page' => self::MENU_SLUG, 'cache_flushed' => '1', '_wpnonce' => wp_create_nonce( 'otmm_flushed' ) ], admin_url( 'options-general.php' ) );
        wp_safe_redirect( $redirect );
        exit;
    }

    // -------------------------------------------------------------------------
    // Admin styles
    // -------------------------------------------------------------------------

    public function enqueue_admin_styles( string $hook ): void {
        if ( 'settings_page_' . self::MENU_SLUG !== $hook ) {
            return;
        }
        wp_enqueue_style( 'outline-to-mind-map-admin', OTMM_PLUGIN_URL . 'assets/admin.css', [], OTMM_VERSION );
    }

    // -------------------------------------------------------------------------
    // Page render
    // -------------------------------------------------------------------------

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $flushed = isset( $_GET['cache_flushed'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'otmm_flushed' );
        ?>
        <div class="wrap outline-to-mind-map-admin">

            <div class="hh-mm-header">
                <div class="hh-mm-logo">
                    <span class="hh-mm-icon">🧠</span>
                    <div>
                        <h1><?php echo esc_html__( 'Outline to Mind Map', 'outline-to-mind-map' ); ?></h1>
                        <span class="hh-mm-version">v<?php echo esc_html( OTMM_VERSION ); ?></span>
                    </div>
                </div>
            </div>

            <?php if ( $flushed ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html__( '✓ Mindmap cache has been cleared.', 'outline-to-mind-map' ); ?></p></div>
            <?php endif; ?>

            <div class="hh-mm-grid">

                <!-- ========== LEFT: settings form ========== -->
                <div class="hh-mm-main">
                    <form method="post" action="options.php">
                        <?php settings_fields( 'otmm_settings_group' ); ?>
                        <?php do_settings_sections( self::MENU_SLUG ); ?>
                        <?php submit_button( __( 'Save Settings', 'outline-to-mind-map' ) ); ?>
                    </form>

                    <hr>

                    <h2><?php echo esc_html__( 'Cache Management', 'outline-to-mind-map' ); ?></h2>
                    <p><?php echo esc_html__( 'Rendered mind map HTML is cached in WordPress transients. The cache is automatically invalidated whenever you change a shortcode\'s content or attributes. Use the button below to manually flush all cached maps.', 'outline-to-mind-map' ); ?></p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="otmm_flush_cache">
                        <?php wp_nonce_field( 'otmm_flush_cache' ); ?>
                        <?php submit_button( __( '🗑 Flush All Cached Maps', 'outline-to-mind-map' ), 'secondary' ); ?>
                    </form>
                </div>

                <!-- ========== RIGHT: docs sidebar ========== -->
                <div class="hh-mm-sidebar">

                    <div class="hh-mm-card">
                        <h3>📌 <?php echo esc_html__( 'About', 'outline-to-mind-map' ); ?></h3>
                        <p><?php echo esc_html__( 'Outline to Mind Map lets you embed interactive, zoomable mind maps anywhere in your content using a simple shortcode. Just write a Markdown-style outline between the tags and the plugin renders it into a fully interactive SVG map powered by ', 'outline-to-mind-map' ); ?><a href="https://markmap.js.org/" target="_blank" rel="noopener">Markmap.js</a>.</p>
                        <p><?php echo esc_html__( 'Rendered output is cached so repeat page loads are instant — no JavaScript transformation happens on the server.', 'outline-to-mind-map' ); ?></p>
                    </div>

                    <div class="hh-mm-card">
                        <h3>🚀 <?php echo esc_html__( 'Quick Start', 'outline-to-mind-map' ); ?></h3>
                        <pre><code>[outline-to-mind-map]
# My Project
## Planning
### Goals
### Timeline
## Execution
### Development
### Testing
[/outline-to-mind-map]</code></pre>
                    </div>

                    <div class="hh-mm-card">
                        <h3>⚙️ <?php echo esc_html__( 'Shortcode Parameters', 'outline-to-mind-map' ); ?></h3>
                        <table class="hh-mm-params">
                            <thead>
                                <tr><th><?php echo esc_html__( 'Attribute', 'outline-to-mind-map' ); ?></th><th><?php echo esc_html__( 'Values', 'outline-to-mind-map' ); ?></th><th><?php echo esc_html__( 'Description', 'outline-to-mind-map' ); ?></th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>fold</code></td>
                                    <td><code>none</code> / <code>all</code> / <code>1</code>–<code>3</code></td>
                                    <td><?php echo esc_html__( 'Initial fold depth. "all" collapses to root; "none" expands everything; a number expands to that depth only.', 'outline-to-mind-map' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>width</code></td>
                                    <td><?php echo esc_html__( 'CSS value', 'outline-to-mind-map' ); ?></td>
                                    <td><?php echo esc_html__( 'Container width, e.g. 100% or 800px.', 'outline-to-mind-map' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>height</code></td>
                                    <td><?php echo esc_html__( 'CSS value', 'outline-to-mind-map' ); ?></td>
                                    <td><?php echo esc_html__( 'Container height, e.g. 500px or 60vh.', 'outline-to-mind-map' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>color_scheme</code></td>
                                    <td><code>default</code> / <code>cool</code> / <code>warm</code> / <code>forest</code> / <code>mono</code></td>
                                    <td><?php echo esc_html__( 'Branch color palette.', 'outline-to-mind-map' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>zoom</code></td>
                                    <td><code>true</code> / <code>false</code></td>
                                    <td><?php echo esc_html__( 'Allow mouse-wheel zoom.', 'outline-to-mind-map' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>pan</code></td>
                                    <td><code>true</code> / <code>false</code></td>
                                    <td><?php echo esc_html__( 'Allow drag-to-pan.', 'outline-to-mind-map' ); ?></td>
                                </tr>
                                <tr>
                                    <td><code>cache</code></td>
                                    <td><code>true</code> / <code>false</code></td>
                                    <td><?php echo esc_html__( 'Override the global cache toggle for this instance.', 'outline-to-mind-map' ); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="hh-mm-card">
                        <h3>💡 <?php echo esc_html__( 'Examples', 'outline-to-mind-map' ); ?></h3>
                        <p><?php echo esc_html__( 'Collapsed to depth 2, forest palette:', 'outline-to-mind-map' ); ?></p>
                        <pre><code>[outline-to-mind-map fold="2" color_scheme="forest"]
# My Map
## Branch A
### Leaf 1
### Leaf 2
## Branch B
[/outline-to-mind-map]</code></pre>
                        <p><?php echo esc_html__( 'Fixed size, no interactivity, no cache:', 'outline-to-mind-map' ); ?></p>
                        <pre><code>[outline-to-mind-map width="600px" height="400px"
 zoom="false" pan="false" cache="false"]
# Static Map
## Item 1
## Item 2
[/outline-to-mind-map]</code></pre>
                    </div>

                </div><!-- .hh-mm-sidebar -->
            </div><!-- .hh-mm-grid -->
        </div><!-- .wrap -->
        <?php
    }
}
