<?php
/**
 * Object cache functionality
 *
 * @package  easy-cache
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wrap object caching functionality
 */
class WE_Object_Cache {

	/**
	 * Setup hooks/filters
	 *
	 * @since 1.0
	 */
	public function setup() {

		add_action( 'admin_notices', array( $this, 'print_notice' ) );
	}

	/**
	 * Print out a warning if object-cache.php is messed up
	 *
	 * @since 1.0
	 */
	public function print_notice() {

		$cant_write = get_option( 'we_cant_write', false );

		if ( $cant_write ) {
			return;
		}

		$config = WE_Config::factory()->get();

		if ( empty( $config['enable_in_memory_object_caching'] ) || empty( $config['advanced_mode'] ) ) {
			return;
		}

		if ( defined( 'WE_OBJECT_CACHE' ) && WE_OBJECT_CACHE ) {
			return;
		}

		?>
		<div class="error">
			<p>
				<?php esc_html_e( 'wp-content/object-cache.php was edited or deleted. easy Cache is not able to utilize object caching.' ); ?>

				<a href="options-general.php?page=easy-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=we_update&amp;we_settings_nonce=<?php echo wp_create_nonce( 'we_update_settings' ); ?>" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( 'Fix', 'easy-cache' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Delete file for clean up
	 *
	 * @since  1.0
	 * @return bool
	 */
	public function clean_up() {

		global $wp_filesystem;

		$file = untrailingslashit( WP_CONTENT_DIR ) . '/object-cache.php';

		if ( ! $wp_filesystem->delete( $file ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Write object-cache.php
	 *
	 * @since  1.0
	 * @return bool
	 */
	public function write() {

		global $wp_filesystem;

		$file = untrailingslashit( WP_CONTENT_DIR ) . '/object-cache.php';

		$config = WE_Config::factory()->get();

		$file_string = '';

		if ( ! empty( $config['enable_in_memory_object_caching'] ) && ! empty( $config['advanced_mode'] ) ) {
			$cache_file = 'memcached-object-cache.php';

			if ( 'redis' === $config['in_memory_cache'] ) {
				$cache_file = 'redis-object-cache.php';
			}

			/**
			 * Salt to be used with the cache keys.
			 *
			 * We need a random string not long as cache key size is limited and
			 * not with special characters as they cause issues with some caches.
			 *
			 * @var string
			 */
			$cache_key_salt = wp_generate_password( 10, false );

			$file_string = '<?php ' .
			"\n\r" . "defined( 'ABSPATH' ) || exit;" .
			"\n\r" . "define( 'WE_OBJECT_CACHE', true );" .
			"\n\r" . "defined( 'WP_CACHE_KEY_SALT' ) || define( 'WP_CACHE_KEY_SALT', '{$cache_key_salt}' );" .
			"\n\r" . "if ( ! @file_exists( WP_CONTENT_DIR . '/we-config/config-' . \$_SERVER['HTTP_HOST'] . '.php' ) ) { return; }" .
			"\n\r" . "\$GLOBALS['we_config'] = include( WP_CONTENT_DIR . '/we-config/config-' . \$_SERVER['HTTP_HOST'] . '.php' );" .
			"\n\r" . "if ( empty( \$GLOBALS['we_config'] ) || empty( \$GLOBALS['we_config']['enable_in_memory_object_caching'] ) ) { return; }" .
			"\n\r" . "if ( @file_exists( '" . untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/dropins/' . $cache_file . "' ) ) { require_once( '" . untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/dropins/' . $cache_file . "' ); }" . "\n\r";

		}

		if ( ! $wp_filesystem->put_contents( $file, $file_string, FS_CHMOD_FILE ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return an instance of the current class, create one if it doesn't exist
	 *
	 * @since  1.0
	 * @return object
	 */
	public static function factory() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}
