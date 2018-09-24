<?php
/**
 * Settings class
 *
 * @package  easy-cache
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class containing settings hooks
 */
class WE_Settings {

	/**
	 * Setup the plugin
	 *
	 * @since 1.0
	 */
	public function setup() {

		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'load-settings_page_easy-cache', array( $this, 'update' ) );
		add_action( 'load-settings_page_easy-cache', array( $this, 'purge_cache' ) );
		add_action( 'admin_notices', array( $this, 'setup_notice' ) );
		add_action( 'admin_notices', array( $this, 'cant_write_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts_styles' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ) );

	}

	/**
	 * Add purge cache button to admin bar
	 *
	 * @since 1,3
	 */
	public function admin_bar_menu() {
		global $wp_admin_bar;

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_menu(
			array(
				'id'     => 'we-purge-cache',
				'parent' => 'top-secondary',
				'href'   => esc_url( admin_url( 'options-general.php?page=easy-cache&amp;wp_http_referer=' . esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . '&amp;action=we_purge_cache&amp;we_cache_nonce=' . wp_create_nonce( 'we_purge_cache' ) ) ),
				'title'  => esc_html__( 'Purge Cache', 'easy-cache' ),
			)
		);
	}

	/**
	 * Output turn on notice
	 *
	 * @since 1.0
	 */
	public function setup_notice() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$cant_write = get_option( 'we_cant_write', false );

		if ( $cant_write ) {
			return;
		}

		$config = WE_Config::factory()->get();

		if ( ! empty( $config['enable_page_caching'] ) || ! empty( $config['advanced_mode'] ) ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( "easy Cache won't work until you turn it on.", 'easy-cache' ); ?>
				<a href="options-general.php?page=easy-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=we_update&amp;we_settings_nonce=<?php echo wp_create_nonce( 'we_update_settings' ); ?>&amp;we_easy_cache[enable_page_caching]=1" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( 'Turn On Caching', 'easy-cache' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Output can't write notice
	 *
	 * @since 1.0
	 */
	public function cant_write_notice() {

		$cant_write = get_option( 'we_cant_write', false );

		if ( ! $cant_write ) {
			return;
		}

		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( "easy Cache can't create or modify needed files on your system. Specifically, easy Cache needs to write to wp-config.php and /wp-content using PHP's fopen() function. Contact your host.", 'easy-cache' ); ?>
				<a href="options-general.php?page=easy-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=we_update&amp;we_settings_nonce=<?php echo wp_create_nonce( 'we_update_settings' ); ?>" class="button button-primary" style="margin-left: 5px;"><?php esc_html_e( 'Try Again', 'easy-cache' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue settings screen js/css
	 *
	 * @since 1.0
	 */
	public function action_admin_enqueue_scripts_styles() {

		global $pagenow;

		if ( 'options-general.php' == $pagenow && ! empty( $_GET['page'] ) && 'easy-cache' == $_GET['page'] ) {

			if ( defined( WP_DEBUG ) && WP_DEBUG ) {
				$js_path = '/assets/js/src/settings.js';
			} else {
				$js_path = '/assets/js/settings.min.js';
			}
			$css_path = '/assets/css/settings.css';

			wp_enqueue_script( 'we-settings', plugins_url( $js_path, dirname( __FILE__ ) ), array( 'jquery' ), WE_VERSION, true );
			wp_enqueue_style( 'we-settings', plugins_url( $css_path, dirname( __FILE__ ) ), array(), WE_VERSION );
		}
	}

	/**
	 * Add options page
	 *
	 * @since 1.0
	 */
	public function action_admin_menu() {

		add_submenu_page( 'options-general.php', esc_html__( 'easy Cache', 'easy-cache' ), esc_html__( 'easy Cache', 'easy-cache' ), 'manage_options', 'easy-cache', array( $this, 'screen_options' ) );
	}

	/**
	 * Purge cache manually
	 *
	 * @since 1.0
	 */
	public function purge_cache() {

		if ( ! empty( $_REQUEST['action'] ) && 'we_purge_cache' === $_REQUEST['action'] ) {
			if ( ! current_user_can( 'manage_options' ) || empty( $_REQUEST['we_cache_nonce'] ) || ! wp_verify_nonce( $_REQUEST['we_cache_nonce'], 'we_purge_cache' ) ) {
				wp_die( esc_html__( 'Cheatin, eh?', 'easy-cache' ) );
			}

			we_cache_flush();

			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				wp_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}
		}
	}

	/**
	 * Handle setting changes
	 *
	 * @since 1.0
	 */
	public function update() {

		if ( ! empty( $_REQUEST['action'] ) && 'we_update' === $_REQUEST['action'] ) {

			if ( ! current_user_can( 'manage_options' ) || empty( $_REQUEST['we_settings_nonce'] ) || ! wp_verify_nonce( $_REQUEST['we_settings_nonce'], 'we_update_settings' ) ) {
				wp_die( esc_html__( 'Cheatin, eh?', 'easy-cache' ) );
			}

			if ( ! WE_Config::factory()->verify_file_access() ) {
				update_option( 'we_cant_write', true );
				wp_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}

			delete_option( 'we_cant_write' );

			$defaults       = WE_Config::factory()->defaults;
			$current_config = WE_Config::factory()->get();

			foreach ( $defaults as $key => $default ) {
				$clean_config[ $key ] = $current_config[ $key ];

				if ( isset( $_REQUEST['we_easy_cache'][ $key ] ) ) {
					$clean_config[ $key ] = call_user_func( $default['sanitizer'], $_REQUEST['we_easy_cache'][ $key ] );
				}
			}

			// Back up configration in options.
			update_option( 'we_easy_cache', $clean_config );

			WP_Filesystem();

			WE_Config::factory()->write( $clean_config );

			WE_Advanced_Cache::factory()->write();
			WE_Object_Cache::factory()->write();

			if ( $clean_config['enable_page_caching'] ) {
				WE_Advanced_Cache::factory()->toggle_caching( true );
			} else {
				WE_Advanced_Cache::factory()->toggle_caching( false );
			}

			// Reschedule cron events.
			WE_Cron::factory()->unschedule_events();
			WE_Cron::factory()->schedule_events();

			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				wp_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}
		}
	}

	/**
	 * Sanitize options
	 *
	 * @param  array $option Array of options to sanitize.
	 * @since  1.0
	 * @return array
	 */
	public function sanitize_options( $option ) {

		$new_option = array();

		if ( ! empty( $option['enable_page_caching'] ) ) {
			$new_option['enable_page_caching'] = true;
		} else {
			$new_option['enable_page_caching'] = false;
		}

		return $new_option;
	}

	/**
	 * Output settings
	 *
	 * @since 1.0
	 */
	public function screen_options() {

		$config = WE_Config::factory()->get();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'easy Cache Settings', 'easy-cache' ); ?></h1>

			<form action="" method="post">
				<?php wp_nonce_field( 'we_update_settings', 'we_settings_nonce' ); ?>
				<input type="hidden" name="action" value="we_update">
				<input type="hidden" name="wp_http_referer" value="<?php echo esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>'" />

				<div class="advanced-mode-wrapper">
					<label for="we_advanced_mode"><?php esc_html_e( 'Enable Advanced Mode', 'easy-cache' ); ?></label>
					<select name="we_easy_cache[advanced_mode]" id="we_advanced_mode">
						<option value="0"><?php esc_html_e( 'No', 'easy-cache' ); ?></option>
						<option <?php selected( $config['advanced_mode'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'easy-cache' ); ?></option>
					</select>
				</div>

				<table class="form-table we-easy-mode-table <?php if ( empty( $config['advanced_mode'] ) ) : ?>show<?php endif; ?>">
					<tbody>
						<tr>
							<th scope="row"><label for="we_enable_page_caching_easy"><span class="setting-highlight">*</span><?php _e( 'Enable Caching', 'easy-cache' ); ?></label></th>
							<td>
								<select <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="we_easy_cache[enable_page_caching]" id="we_enable_page_caching_easy">
									<option value="0"><?php esc_html_e( 'No', 'easy-cache' ); ?></option>
									<option <?php selected( $config['enable_page_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'easy-cache' ); ?></option>
								</select>

								<p class="description"><?php esc_html_e( 'Turn this on to get started. This setting turns on caching and is really all you need.', 'easy-cache' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="we_page_cache_length_easy"><?php esc_html_e( 'Expire the cache after', 'easy-cache' ); ?></label></th>
							<td>
								<input <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> size="5" id="we_page_cache_length_easy" type="text" value="<?php echo (float) $config['page_cache_length']; ?>" name="we_easy_cache[page_cache_length]">
								<select <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="we_easy_cache[page_cache_length_unit]" id="we_page_cache_length_unit_easy">
									<option <?php selected( $config['page_cache_length_unit'], 'minutes' ); ?> value="minutes"><?php esc_html_e( 'minutes', 'easy-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'hours' ); ?> value="hours"><?php esc_html_e( 'hours', 'easy-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'days' ); ?> value="days"><?php esc_html_e( 'days', 'easy-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'weeks' ); ?> value="weeks"><?php esc_html_e( 'weeks', 'easy-cache' ); ?></option>
								</select>
							</td>
						</tr>

						<?php if ( function_exists( 'gzencode' ) ) : ?>
							<tr>
								<th scope="row"><label for="we_enable_gzip_compression_easy"><?php _e( 'Enable Compression', 'easy-cache' ); ?></label></th>
								<td>
									<select <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="we_easy_cache[enable_gzip_compression]" id="we_enable_gzip_compression_easy">
										<option value="0"><?php esc_html_e( 'No', 'easy-cache' ); ?></option>
										<option <?php selected( $config['enable_gzip_compression'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'easy-cache' ); ?></option>
									</select>

									<p class="description"><?php esc_html_e( 'When enabled, pages will be compressed. This is a good thing! This should always be enabled unless it causes issues.', 'easy-cache' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<table class="form-table we-advanced-mode-table <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>show<?php endif; ?>">
					<tbody>
						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Page Cache', 'easy-cache' ); ?></h2>
							</th>
						</tr>

						<tr>
							<th scope="row"><label for="we_enable_page_caching_advanced"><?php _e( 'Enable Page Caching', 'easy-cache' ); ?></label></th>
							<td>
								<select <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="we_easy_cache[enable_page_caching]" id="we_enable_page_caching_advanced">
									<option value="0"><?php esc_html_e( 'No', 'easy-cache' ); ?></option>
									<option <?php selected( $config['enable_page_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'easy-cache' ); ?></option>
								</select>

								<p class="description"><?php esc_html_e( 'When enabled, entire front end pages will be cached.', 'easy-cache' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="we_cache_exception_urls"><?php _e( 'Exception URL(s)', 'easy-cache' ); ?></label></th>
							<td>
								<textarea name="we_easy_cache[cache_exception_urls]" class="widefat" id="we_cache_exception_urls"><?php echo esc_html( $config['cache_exception_urls'] ); ?></textarea>

								<p class="description"><?php esc_html_e( 'Allows you to add URL(s) to be exempt from page caching. One URL per line. URL(s) can be full URLs (http://google.com) or absolute paths (/my/url/). You can also use wildcards like so /url/* (matches any url starting with /url/).', 'easy-cache' ); ?></p>

								<p>
									<select name="we_easy_cache[enable_url_exemption_regex]" id="we_enable_url_exemption_regex">
										<option value="0"><?php esc_html_e( 'No', 'easy-cache' ); ?></option>
										<option <?php selected( $config['enable_url_exemption_regex'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'easy-cache' ); ?></option>
									</select>
									<?php esc_html_e( 'Enable Regex', 'easy-cache' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="we_page_cache_length_advanced"><?php esc_html_e( 'Expire page cache after', 'easy-cache' ); ?></label></th>
							<td>
								<input <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> size="5" id="we_page_cache_length_advanced" type="text" value="<?php echo (float) $config['page_cache_length']; ?>" name="we_easy_cache[page_cache_length]">
								<select
								<?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="we_easy_cache[page_cache_length_unit]" id="we_page_cache_length_unit_advanced">
									<option <?php selected( $config['page_cache_length_unit'], 'minutes' ); ?> value="minutes"><?php esc_html_e( 'minutes', 'easy-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'hours' ); ?> value="hours"><?php esc_html_e( 'hours', 'easy-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'days' ); ?> value="days"><?php esc_html_e( 'days', 'easy-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'weeks' ); ?> value="weeks"><?php esc_html_e( 'weeks', 'easy-cache' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Object Cache (Redis or Memcache)', 'easy-cache' ); ?></h2>
							</th>
						</tr>

						<?php if ( class_exists( 'Memcache' ) || class_exists( 'Redis' ) ) : ?>
							<tr>
								<th scope="row"><label for="we_enable_in_memory_object_caching"><?php _e( 'Enable In-Memory Object Caching', 'easy-cache' ); ?></label></th>
								<td>
									<select name="we_easy_cache[enable_in_memory_object_caching]" id="we_enable_in_memory_object_caching">
										<option value="0"><?php esc_html_e( 'No', 'easy-cache' ); ?></option>
										<option <?php selected( $config['enable_in_memory_object_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'easy-cache' ); ?></option>
									</select>

									<p class="description"><?php _e( "When enabled, things like database query results will be stored in memory. Right now Memcache and Redis are suppported. Note that if the proper <a href='https://pecl.php.net/package/memcache'>Memcache</a> (NOT Memcached) or <a href='https://pecl.php.net/package/redis'>Redis</a> PHP extensions aren't loaded, they won't show as options below.", 'easy-cache' ); ?></p>
								</td>
							</tr>
							<tr>
								<th class="in-memory-cache
								<?php
								if ( ! empty( $config['enable_in_memory_object_caching'] ) ) :
								?>
								show<?php endif; ?>" scope="row"><label for="we_in_memory_cache"><?php _e( 'In Memory Cache', 'easy-cache' ); ?></label></th>
								<td class="in-memory-cache <?php if ( ! empty( $config['enable_in_memory_object_caching'] ) ) : ?>show<?php endif; ?>">
									<select name="we_easy_cache[in_memory_cache]" id="we_in_memory_cache">
										<?php if ( class_exists( 'Memcache' ) ) : ?>
											<option <?php selected( $config['in_memory_cache'], 'memcached' ); ?> value="memcached">Memcache</option>
										<?php endif; ?>
										<?php if ( class_exists( 'Redis' ) ) : ?>
											<option <?php selected( $config['in_memory_cache'], 'redis' ); ?> value="redis">Redis</option>
										<?php endif; ?>
									</select>
								</td>
							</tr>
						<?php else : ?>
							<tr>
								<td colspan="2">
									<?php _e( 'Neither <a href="https://pecl.php.net/package/memcache">Memcache</a> (NOT Memcached) nor <a href="https://pecl.php.net/package/redis">Redis</a> PHP extensions are set up on your server.', 'easy-cache' ); ?>
								</td>
							</tr>
						<?php endif; ?>

						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Compression', 'easy-cache' ); ?></h2>
							</th>
						</tr>

						<?php if ( function_exists( 'gzencode' ) ) : ?>
							<tr>
								<th scope="row"><label for="we_enable_gzip_compression_advanced"><?php _e( 'Enable gzip Compression', 'easy-cache' ); ?></label></th>
								<td>
									<select <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="we_easy_cache[enable_gzip_compression]" id="we_enable_gzip_compression_advanced">
										<option value="0"><?php esc_html_e( 'No', 'easy-cache' ); ?></option>
										<option <?php selected( $config['enable_gzip_compression'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'easy-cache' ); ?></option>
									</select>

									<p class="description"><?php esc_html_e( 'When enabled pages will be gzip compressed at the PHP level. Note many hosts set up gzip compression in Apache or nginx.', 'easy-cache' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'easy-cache' ); ?>">
					<a class="button" style="margin-left: 10px;" href="?page=easy-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=we_purge_cache&amp;we_cache_nonce=<?php echo wp_create_nonce( 'we_purge_cache' ); ?>"><?php esc_html_e( 'Purge Cache', 'easy-cache' ); ?></a>
				</p>
			</form>
		</div>
		<?php
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
