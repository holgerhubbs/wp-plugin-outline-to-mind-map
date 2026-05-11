<?php
/**
 * Cache handler for Outline to Mind Map.
 *
 * Uses WordPress transients so it works on any host without
 * an object-cache drop-in, while automatically benefiting from
 * Redis / Memcached when one is present.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Outline_To_Mind_Map_Cache {

    /**
     * Default TTL: 7 days (seconds).
     */
    const DEFAULT_TTL = 604800;

    /**
     * Prefix added to every transient key.
     */
    const KEY_PREFIX = 'hh_mm_';

    /**
     * Build a deterministic cache key from the raw shortcode content + atts.
     *
     * @param string $content Raw markdown / outline text.
     * @param array  $atts    Parsed shortcode attributes.
     * @return string
     */
    public function make_key( string $content, array $atts ): string {
        $payload = $content . wp_json_encode( $atts );
        return self::KEY_PREFIX . md5( $payload );
    }

    /**
     * Retrieve a cached HTML string.
     *
     * @param string $key Cache key from make_key().
     * @return string|false Cached HTML or false on miss.
     */
    public function get( string $key ) {
        return get_transient( $key );
    }

    /**
     * Store rendered HTML.
     *
     * @param string $key  Cache key.
     * @param string $html Rendered output.
     * @param int    $ttl  Time-to-live in seconds (0 = no expiry).
     */
    public function set( string $key, string $html, int $ttl = self::DEFAULT_TTL ): void {
        set_transient( $key, $html, $ttl );

        // Track all keys so we can flush them in bulk.
        $index   = get_option( 'otmm_cache_index', [] );
        $index[] = $key;
        update_option( 'otmm_cache_index', array_unique( $index ), false );
    }

    /**
     * Delete a single cached entry.
     *
     * @param string $key Cache key.
     */
    public function delete( string $key ): void {
        delete_transient( $key );
    }

    /**
     * Remove every cached entry created by this plugin.
     */
    public static function flush_all(): void {
        $index = get_option( 'otmm_cache_index', [] );
        foreach ( $index as $key ) {
            delete_transient( $key );
        }
        update_option( 'otmm_cache_index', [] );
    }
}
