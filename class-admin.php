<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package AAS\Admin
 */

namespace AAS\Admin;

// Guard against direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 * Handles the admin menu registration, page rendering, notices, and enqueuing admin assets.
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'admin_init', [ $this, 'handle_log_actions' ] );
		add_action( 'wp_ajax_aas_clear_logs', [ $this, 'ajax_clear_logs' ] );
		add_action( 'admin_footer', [ $this, 'render_global_modals' ] );
	}

	/**
	 * Handle admin-specific actions for downloading logs.
	 */
	public function handle_log_actions() {
		if ( ! isset( $_GET['page'] ) || 'aas-debug-logs' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		if ( empty( $action ) ) {
			return;
		}

		if ( ! check_admin_referer( 'aas_log_action_nonce', 'nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ai-auto-seo' ) );
		}

		$upload_dir = wp_upload_dir();
		$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'aas-logs';
		$log_file   = trailingslashit( $log_dir ) . 'plugin.log';

		if ( 'download_logs' === $action ) {
			if ( ! file_exists( $log_file ) || 0 === filesize( $log_file ) ) {
				wp_die( esc_html__( 'Log file is empty or does not exist.', 'ai-auto-seo' ) );
			}
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: text/plain' );
			header( 'Content-Disposition: attachment; filename="ai-auto-seo-debug.log"' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . filesize( $log_file ) );
			readfile( $log_file );
			exit;
		}
	}

	/**
	 * Register the menu and submenu pages under the Top Level Menu.
	 */
	public function add_menu_pages() {
		// Define parent top-level menu page.
		add_menu_page(
			__( 'AI Automatic SEO Dashboard', 'ai-auto-seo' ),
			__( 'AI Automatic SEO', 'ai-auto-seo' ),
			'manage_options',
			'aas-dashboard',
			[ $this, 'render_dashboard_page' ],
			'fa-solid fa-robot',
			30
		);

		// Sub-menu items (parent slug set to null to hide from the default WP admin sidebar menu list):
		// 2. Site SEO Score
		add_submenu_page(
			null,
			__( 'Site SEO Score', 'ai-auto-seo' ),
			__( 'Site SEO Score', 'ai-auto-seo' ),
			'manage_options',
			'aas-site-seo-score',
			[ $this, 'render_site_seo_score_page' ]
		);

		// 3. Posts
		add_submenu_page(
			null,
			__( 'Posts SEO Management', 'ai-auto-seo' ),
			__( 'Posts', 'ai-auto-seo' ),
			'manage_options',
			'aas-posts',
			[ $this, 'render_posts_page' ]
		);

		// 4. Pages
		add_submenu_page(
			null,
			__( 'Pages SEO Management', 'ai-auto-seo' ),
			__( 'Pages', 'ai-auto-seo' ),
			'manage_options',
			'aas-pages',
			[ $this, 'render_pages_page' ]
		);

		// 5. Categories
		add_submenu_page(
			null,
			__( 'Categories SEO Management', 'ai-auto-seo' ),
			__( 'Categories', 'ai-auto-seo' ),
			'manage_options',
			'aas-categories',
			[ $this, 'render_categories_page' ]
		);

		// WooCommerce Pages (Always Registered / Unconditional)
		// 6. Products
		add_submenu_page(
			null,
			__( 'WooCommerce Products SEO', 'ai-auto-seo' ),
			__( 'Products', 'ai-auto-seo' ),
			'manage_options',
			'aas-products',
			[ $this, 'render_products_page' ]
		);

		// 7. Product Categories
		add_submenu_page(
			null,
			__( 'WooCommerce Product Categories SEO', 'ai-auto-seo' ),
			__( 'Product Categories', 'ai-auto-seo' ),
			'manage_options',
			'aas-product-categories',
			[ $this, 'render_product_categories_page' ]
		);

		// 9. Settings
		add_submenu_page(
			null,
			__( 'AI Automatic SEO Settings', 'ai-auto-seo' ),
			__( 'Settings', 'ai-auto-seo' ),
			'manage_options',
			'aas-settings',
			[ $this, 'render_settings_page' ]
		);

		// 13. Debug & Logs
		add_submenu_page(
			null,
			__( 'Debug & Logs', 'ai-auto-seo' ),
			__( 'Debug & Logs', 'ai-auto-seo' ),
			'manage_options',
			'aas-debug-logs',
			[ $this, 'render_debug_logs_page' ]
		);

		// 14. About Developer
		add_submenu_page(
			null,
			__( 'About Developer', 'ai-auto-seo' ),
			__( 'About Developer', 'ai-auto-seo' ),
			'manage_options',
			'aas-about-developer',
			[ $this, 'render_about_developer_page' ]
		);

		// 15. Other Plugins
		add_submenu_page(
			null,
			__( 'Other Plugins', 'ai-auto-seo' ),
			__( 'Other Plugins', 'ai-auto-seo' ),
			'manage_options',
			'aas-other-plugins',
			[ $this, 'render_other_plugins_page' ]
		);

		// 16. License
		add_submenu_page(
			null,
			__( 'License Manager', 'ai-auto-seo' ),
			__( 'License', 'ai-auto-seo' ),
			'manage_options',
			'aas-license',
			[ $this, 'render_license_page' ]
		);
	}

	/**
	 * Check if the current screen belongs to our plugin to scope scripts/styles.
	 *
	 * @return bool
	 */
	private function is_plugin_screen(): bool {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		return strpos( $screen->id, 'aas-' ) !== false || strpos( $screen->id, 'ai-auto-seo' ) !== false;
	}

	/**
	/**
	 * Register and enqueue styles.
	 */
	public function enqueue_styles() {
		if ( ! wp_style_is( 'aas-font-awesome', 'registered' ) ) {
			wp_register_style(
				'aas-font-awesome',
				'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
				[],
				$this->version,
				'all'
			);
		}

		$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$our_pages = [
			'aas-dashboard',
			'aas-site-seo-score',
			'aas-posts',
			'aas-pages',
			'aas-categories',
			'aas-products',
			'aas-product-categories',
			'aas-settings',
			'aas-other-plugins',
			'aas-debug-logs',
			'aas-license',
			'aas-onboarding',
			'aas-about-developer'
		];

		if ( in_array( $current_page, $our_pages, true ) ) {
			if ( ! wp_style_is( 'aas-font-awesome', 'enqueued' ) ) {
				wp_enqueue_style( 'aas-font-awesome' );
			}
		}
	}

	/**
	 * Output early preconnect hints for Font Awesome CDN.
	 */
	public function early_preconnect_font_awesome() {
		$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$our_pages = [
			'aas-dashboard',
			'aas-site-seo-score',
			'aas-posts',
			'aas-pages',
			'aas-categories',
			'aas-products',
			'aas-product-categories',
			'aas-settings',
			'aas-other-plugins',
			'aas-debug-logs',
			'aas-license',
			'aas-onboarding',
			'aas-about-developer'
		];

		if ( in_array( $current_page, $our_pages, true ) ) {
			echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>' . "\n";
			echo '<link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">' . "\n";
		}
	}

	/**
	 * Register and enqueue scripts.
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		$current_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		$our_pages = [
			'aas-dashboard',
			'aas-site-seo-score',
			'aas-posts',
			'aas-pages',
			'aas-categories',
			'aas-products',
			'aas-product-categories',
			'aas-settings',
			'aas-other-plugins',
			'aas-debug-logs',
			'aas-license',
			'aas-onboarding',
			'aas-about-developer'
		];

		if ( ! in_array( $current_page, $our_pages, true ) ) {
			return;
		}

		$ver = $this->version . '-' . time();

		wp_enqueue_script(
			$this->plugin_name . '-ajax',
			AAS_URL . 'assets/js/admin-ajax.js',
			[ 'jquery' ],
			$ver,
			false
		);

		wp_enqueue_script(
			$this->plugin_name,
			AAS_URL . 'assets/js/admin.js',
			[ 'jquery', $this->plugin_name . '-ajax' ],
			$ver,
			false
		);

		if ( 'aas-settings' === $current_page ) {
			wp_enqueue_script( $this->plugin_name . '-settings', AAS_URL . 'assets/js/settings.js', [ 'jquery', $this->plugin_name ], $ver, false );
			wp_enqueue_script( $this->plugin_name . '-languages', AAS_URL . 'assets/js/languages.js', [ 'jquery', $this->plugin_name ], $ver, false );
			wp_enqueue_script( $this->plugin_name . '-internal-linking', AAS_URL . 'assets/js/internal-linking.js', [ 'jquery', $this->plugin_name ], $ver, false );
		} elseif ( 'aas-site-seo-score' === $current_page ) {
			wp_enqueue_script( $this->plugin_name . '-seo-audit', AAS_URL . 'assets/js/seo-audit.js', [ 'jquery', $this->plugin_name ], $ver, false );
		} elseif ( 'aas-dashboard' === $current_page ) {
			wp_enqueue_script( $this->plugin_name . '-dashboard', AAS_URL . 'assets/js/dashboard.js', [ 'jquery', $this->plugin_name ], $ver, false );
		} elseif ( 'aas-license' === $current_page ) {
			wp_enqueue_script( $this->plugin_name . '-license', AAS_URL . 'assets/js/license.js', [ 'jquery', $this->plugin_name ], $ver, false );
		}

		// Content list screens
		$content_screens = [ 'aas-posts', 'aas-pages', 'aas-categories', 'aas-products', 'aas-product-categories' ];
		if ( in_array( $current_page, $content_screens, true ) ) {
			wp_enqueue_script( $this->plugin_name . '-content-list', AAS_URL . 'assets/js/content-list.js', [ 'jquery', $this->plugin_name ], $ver, false );
		}

		// Localize standard data like nonces and global variables.
		wp_localize_script(
			$this->plugin_name . '-ajax',
			'aasGlobals',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'aas_admin_nonce' ),
				'emptyKeyMsg' => __( 'License key cannot be empty.', 'ai-auto-seo' ),
			]
		);
	}

	/**
	 * Helper method to render custom admin pages with shared headers/footers.
	 *
	 * @param string $view_file Path to the view file to render.
	 * @param string $title     Title of the page to display in the header.
	 */
	public function render_page( string $view_file, string $title ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ai-auto-seo' ) );
		}

		// Ensure we load header partial.
		$header_path = AAS_PATH . 'admin/partials/header.php';
		$footer_path = AAS_PATH . 'admin/partials/footer.php';

		if ( file_exists( $header_path ) ) {
			include $header_path;
		}

		if ( file_exists( $view_file ) ) {
			include $view_file;
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Template file not found.', 'ai-auto-seo' ) . '</p></div>';
		}

		if ( file_exists( $footer_path ) ) {
			include $footer_path;
		}
	}

	/**
	 * Callback rendering the Dashboard page.
	 */
	public function render_dashboard_page() {
		$this->render_page( AAS_PATH . 'dashboard/views/dashboard-page.php', __( 'Dashboard', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the Site SEO Score page.
	 */
	public function render_site_seo_score_page() {
		$this->render_page( AAS_PATH . 'seo-audit/views/site-seo-score-page.php', __( 'Site SEO Score', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the Posts list page.
	 */
	public function render_posts_page() {
		$this->render_page( AAS_PATH . 'content-modules/views/posts-page.php', __( 'Posts SEO Management', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the Pages list page.
	 */
	public function render_pages_page() {
		$this->render_page( AAS_PATH . 'content-modules/views/pages-page.php', __( 'Pages SEO Management', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the Categories list page.
	 */
	public function render_categories_page() {
		$this->render_page( AAS_PATH . 'content-modules/views/categories-page.php', __( 'Categories SEO Management', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the Products list page (WooCommerce only).
	 */
	public function render_products_page() {
		$this->render_page( AAS_PATH . 'content-modules/views/products-page.php', __( 'WooCommerce Products SEO', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the Product Categories list page (WooCommerce only).
	 */
	public function render_product_categories_page() {
		$this->render_page( AAS_PATH . 'content-modules/views/product-categories-page.php', __( 'WooCommerce Product Categories SEO', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the Bulk SEO Queue page.
	 */
	public function render_bulk_queue_page() {
		$this->render_page( AAS_PATH . 'queue/views/queue-page.php', __( 'Bulk SEO Queue Manager', 'ai-auto-seo' ) );
	}



	/**
	 * Callback rendering the Languages page.
	 */
	public function render_languages_page() {
		$this->render_page( AAS_PATH . 'languages/views/languages-page.php', __( 'Multi Language SEO Settings', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the Settings page.
	 */
	public function render_settings_page() {
		$this->render_page( AAS_PATH . 'settings/views/settings-page.php', __( 'AI Automatic SEO Settings', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the License page.
	 */
	public function render_license_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ai-auto-seo' ) );
		}
		include AAS_PATH . 'includes/license/license-page.php';
	}

	/**
	 * Callback rendering the Debug & Logs page.
	 */
	public function render_debug_logs_page() {
		$this->render_page( AAS_PATH . 'admin/views/debug-logs-page.php', __( 'Debug & Logs', 'ai-auto-seo' ) );
	}

	/**
	 * Callback rendering the About Developer page.
	 */
	public function render_about_developer_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ai-auto-seo' ) );
		}
		include AAS_PATH . 'includes/about-author.php';
	}

	/**
	 * Callback rendering the Other Plugins page.
	 */
	public function render_other_plugins_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ai-auto-seo' ) );
		}
		include AAS_PATH . 'includes/other-plugins.php';
	}

	/**
	 * AJAX endpoint to clear the debug logs.
	 */
	public function ajax_clear_logs() {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'aas_admin_nonce', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Security check failed.', 'ai-auto-seo' ) ] );
		}

		$upload_dir = wp_upload_dir();
		$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'aas-logs';
		$log_file   = trailingslashit( $log_dir ) . 'plugin.log';

		if ( file_exists( $log_file ) ) {
			@file_put_contents( $log_file, '' ); // Clear file content.
		}

		wp_send_json_success( [ 'message' => __( 'Logs cleared successfully.', 'ai-auto-seo' ) ] );
	}

	/**
	 * Render global modal popups on all admin pages.
	 */
	public function render_global_modals() {
		?>
		<!-- Free Version Limit Reached Modal Popup -->
		<div id="aas_limit_reached_modal" style="display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
			<div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 32px; width: 480px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); box-sizing: border-box; font-family: 'IBM Plex Sans', sans-serif;">
				
				<div style="text-align: center; margin-bottom: 24px;">
					<div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 50%; background: #FEE2E2; color: #EF4444; margin-bottom: 16px;">
						<span class="dashicons dashicons-warning" style="font-size: 28px; width: 28px; height: 28px; line-height: 28px; margin: 0;"></span>
					</div>
					<h3 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 700; color: #0F172A;"><?php esc_html_e( 'Free Version Limit Reached', 'ai-auto-seo' ); ?></h3>
					<p style="margin: 0; font-size: 14.5px; color: #475569; line-height: 1.6; text-align: left; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px;">
						<?php esc_html_e( 'You have reached the SEO Generation limit available in the Free Version.', 'ai-auto-seo' ); ?>
						<br><br>
						<strong><?php esc_html_e( 'Upgrade to AI Automatic SEO Pro to unlock:', 'ai-auto-seo' ); ?></strong>
						<span style="display: block; margin-top: 8px; padding-left: 10px;">
							• <?php esc_html_e( 'Unlimited SEO Generation', 'ai-auto-seo' ); ?><br>
							• <?php esc_html_e( 'Unlimited Bulk SEO', 'ai-auto-seo' ); ?><br>
							• <?php esc_html_e( 'Advanced Website Optimization', 'ai-auto-seo' ); ?><br>
							• <?php esc_html_e( 'Premium Features', 'ai-auto-seo' ); ?>
						</span>
					</p>
				</div>

				<div style="display: flex; gap: 12px; justify-content: flex-end;">
					<button type="button" id="aas_limit_close_btn" style="background: #FFFFFF; border: 1px solid #CBD5E1; color: #475569; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 13.5px; transition: all 0.15s ease;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='#FFFFFF'">
						<?php esc_html_e( 'Close', 'ai-auto-seo' ); ?>
					</button>
					<a href="https://webpresshub.net/product/ai-automatic-seo-one-click-ai-seo-optimization-website-audit-wordpress-plugin/" target="_blank" style="background: #E11D48; border: 1px solid #E11D48; color: #FFFFFF; padding: 10px 20px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 13.5px; text-align: center; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.2); transition: all 0.15s ease;" onmouseover="this.style.background='#BE123C'; this.style.borderColor='#BE123C';" onmouseout="this.style.background='#E11D48'; this.style.borderColor='#E11D48';">
						<?php esc_html_e( 'Upgrade to Pro', 'ai-auto-seo' ); ?>
					</a>
				</div>

			</div>
		</div>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(document).on('click', '#aas_limit_close_btn', function() {
				$('#aas_limit_reached_modal').fadeOut(200);
			});
			$(document).on('click', function(event) {
				if ($(event.target).is('#aas_limit_reached_modal')) {
					$('#aas_limit_reached_modal').fadeOut(200);
				}
			});
		});
		</script>
		<?php
	}
}
