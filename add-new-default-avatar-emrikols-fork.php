<?php
/**
 * Plugin Name: Add New Default Avatar [Emrikol's Fork]
 * Plugin URI:
 * Description: Add new option to the Default Avatar list.
 * Version: 2.0.0
 * Author: Decarbonated Web Services
 * Author URI: http://www.decarbonated.com/
 * License: GPL2
 *
 * @package WordPress
 * @subpackage dws-anda
 */

if ( ! class_exists( 'DWS_ANDA' ) ) {

	/**
	 * Primary plugin class
	 *
	 * @since 2.0.0
	 */
	class DWS_ANDA {

		/**
		 * Plugin Slug.
		 *
		 * @since 2.0.0
		 * @access public
		 * @var string $slug
		 */
		var $slug = 'anda';

		/**
		 * Plugin Name.
		 *
		 * @since 2.0.0
		 * @access public
		 * @var string $name
		 */
		var $name = 'Add New Default Avatar';

		/**
		 * Plugin access level.
		 *
		 * @since 2.0.0
		 * @access public
		 * @var string $access
		 */
		var $access = 'manage_options';

		/**
		 * Plugin version.
		 *
		 * @since 2.0.0
		 * @access public
		 * @var string $ver
		 */
		var $ver = '2.0';

		/**
		 * Class constructor.
		 *
		 * @since 2.0.0
		 */
		function __construct() {
			add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'action_admin_print_footer_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

			add_filter( 'avatar_defaults', array( $this, 'filter_avatar_defaults' ) );

			register_activation_hook( __FILE__, array( $this, 'plugin_construct' ) );	// Register construct.
			register_deactivation_hook( __FILE__, array( $this, 'plugin_destruct' ) );	// Register plugin destruct.
		}

		/**
		 * Adding admin menu.
		 *
		 * @since 2.0.0
		 */
		function action_admin_menu() {
			// Add "Add New Avatar" link under the "Appearance" menu.
			$page = add_submenu_page( 'themes.php', esc_html__( 'Add New Default Avatar', 'dws' ), esc_html__( 'Add New Avatar', 'dws' ), $this->access, 'add-new-default-avatar', array( $this, 'plugin_page' ) );
		}

		/**
		 * Enqueueing admin scripts.
		 *
		 * @since 2.0.0
		 */
		function action_admin_enqueue_scripts() {
			// Enqueue core media uploader.
			wp_enqueue_media();
		}

		/**
		 * Enqueueing admin styles.
		 *
		 * @since 2.0.0
		 */
		function action_wp_enqueue_scripts() {
			wp_enqueue_style( 'dws_anda_style', plugins_url( 'css/style.css', __FILE__ ), array(), $this->ver, 'all' );  // Add CSS.
		}

		/**
		 * Printing inline admin scripts.
		 *
		 * @since 2.0.0
		 */
		function action_admin_print_footer_scripts() {
			?>
			<script type='text/javascript'>
				jQuery( function( $ ) {
					var media_uploader = null;

					function open_media_uploader_image() {
						media_uploader = wp.media({
						frame:    "post",
						state:    "insert",
						multiple: false
						} );

						media_uploader.on( "insert", function() {
							var json = media_uploader.state().get("selection").first().toJSON();

							console.log( json );
							var image_url = json.url;
							var image_caption = json.caption;
							var image_title = json.title;

							$( '#dws_anda_image_url_upload' ).val( image_url );
							$( '#dws_anda_avatar_name' ).val( image_title );
						} );

						media_uploader.open();
					}

					$( '#dws_anda_image_url_upload, #dws_anda_image_url' ).click( function( e ) {
						open_media_uploader_image();
					} );
				} );
			</script>
			<?php
		}

		/**
		 * Default Avatar filter.
		 *
		 * @param array $avatar_defaults Default Core Avatars.
		 * @since 2.0.0
		 */
		function filter_avatar_defaults( $avatar_defaults ) {
			// Add plugin to avatar settings.
			$options = get_option( 'dws_anda' );

			if ( $options ) {
				$thumb_url = plugins_url( 'includes/timthumb.php', __FILE__ );
				$anda_avatars = array();

				foreach ( $options['avatars'] as $avatar ) {
					$image_url = $thumb_url . '?src=' . $avatar['local'];
					$anda_avatars[ $image_url ] = $avatar['name'];
				}

				return array_merge( $anda_avatars, $avatar_defaults ); // Put our custom avatars on top.
			} else {
				return $avatar_defaults;
			}
		}

		/**
		 * Plugin Activiation.
		 *
		 * @since 2.0.0
		 */
		function plugin_construct() {
			$options = array();											// Set up options array.
			$options['avatars'] = array();								// Set up avatars array.
			$options['avatar_default'] = get_option( 'avatar_default' );	// Be sure to save original avatar in case user removes plugin.
			update_option( 'dws_anda', $options );							// Save plugin options.
		}

		/**
		 * Plugin Dectiviation.
		 *
		 * @since 2.0.0
		 */
		function plugin_destruct() {
			$options = get_option( 'dws_anda' );	// Get Plugin Prefrences.

			// Change default avatar back to what it was before the plugin was activated.
			update_option( 'avatar_default', $options['avatar_default'] );
			delete_option( 'dws_anda' );			// Delete the plugin prefrences.
		}

		/**
		 * Generate Admin page.
		 *
		 * @since 2.0.0
		 */
		function plugin_page() {
			$options = get_option( 'dws_anda' );

			if ( isset( $_POST['update'] ) ) { // WPCS: input var okay.
				// Safety check.  Did the admin do this?
				check_admin_referer( 'dws_anda_update' );

				$updated_avatars = array();
				foreach ( $options['avatars'] as $avatar ) {
					if ( isset( $_POST['dws_anda_delete'] ) && ! in_array( $avatar['uid'], wp_unslash( $_POST['dws_anda_delete'] ), true ) ) { // WPCS: input var okay.
						$updated_avatars[] = array(
							'local' => $avatar['local'],
							'url' => $avatar['url'],
							'name' => $avatar['name'],
							'uid' => $avatar['uid'],
						);
					}
				}
				$options['avatars'] = $updated_avatars;
				update_option( 'dws_anda', $options );
			}

			if ( isset( $_POST['new'] ) && isset( $_POST['dws_anda_localfile'] ) && isset( $_POST['dws_anda_image_url'] ) ) { // WPCS: input var okay.
				// Safety check.  Did the admin do this?
				check_admin_referer( 'dws_anda_new' );

				$avatar = array();
				$avatar['local'] = sanitize_text_field( wp_unslash( $_POST['dws_anda_localfile'] ) ); // WPCS: input var okay.
				$avatar['url'] = esc_url_raw( sanitize_text_field( wp_unslash( $_POST['dws_anda_image_url'] ) ) ); // WPCS: input var okay.
				$avatar['uid'] = 'DWS' . md5( uniqid() );
				$avatar['name'] = isset( $_POST['dws_anda_avatar_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dws_anda_avatar_name'] ) ) : $avatar['uid']; // WPCS: input var okay.

				$options['avatars'][] = $avatar;

				update_option( 'dws_anda', $options );

				if ( get_option( 'dws_anda' ) === $options ) {
					echo '<h3>' . esc_html__( 'Saved!', 'dws' ) . '</h3>';
				} else {
					echo '<h3>' . esc_html__( 'Something may have gone wrong.', 'dws' ) . '</h3>';
				}
			}

			// Output page HTML.
			?>
			<div class="wrap">
				<div id="icon-upload" class="icon32"></div>
				<h2><?php esc_html_e( 'Custom Default Avatars', 'dws' ); ?></h2>
				
				<form id="dws_anda_add" method="post">
					<fieldset>
						<ul>
							<li>
								<legend><h3><?php esc_html_e( 'Add a new Avatar', 'dws' ); ?></h3></legend>
								<?php wp_nonce_field( 'dws_anda_new' ); ?>
								<input type="hidden" name="new" />
							</li>
							<li>
								<label for="dws_anda_image_url"><?php esc_html_e( 'Image URL', 'dws' ); ?>: </label>
								<input class='text' name='dws_anda_image_url' id='dws_anda_image_url_upload' type='text' value='' />
								<input type="button" class="button button-primary button-large" id='dws_anda_image_url' value="Select Image" />
							</li>
							<li class='nothidden'>
								<label for="dws_anda_avatar_name"><?php esc_html_e( 'Avatar Name', 'dws' ); ?>: </label>
								<input type="text" class="text" id="dws_anda_avatar_name" name="dws_anda_avatar_name" id="dws_anda_avatar_name" value="" />
							</li>
							<li class='nothidden'>
								<input type="submit" class="save" value="<?php esc_html_e( 'Add Avatar', 'dws' ); ?>" />
							</li>
						</ul>
					</fieldset>
				</form>
				<?php $this->show_avatars(); ?>
			</div>
			<?php
		}

		/**
		 * Show Avatars.
		 *
		 * @since 2.0.0
		 */
		function show_avatars() {
			$options = get_option( 'dws_anda' );

			if ( ! empty( $options['avatars'] ) ) {
			?>
				<form method="post">
					<fieldset>
						<legend><h3><?php esc_html_e( 'Current Custom Avatars', 'dws' ); ?></h3></legend>
						<?php wp_nonce_field( 'dws_anda_update' ); ?>
						<input type="hidden" name="update" />
						<?php
						$thumb_url = plugins_url( 'includes/timthumb.php', __FILE__ );
						foreach ( $options['avatars'] as $avatar ) {
							$dws_anda_name = $avatar['name'];
							$dws_anda_local = $avatar['local'];
							$dws_anda_url = $avatar['url'];
							$dws_anda_uid = $avatar['uid'];
							$image_url = $thumb_url . '?src=' . $dws_anda_local;
							?>
									<h4><input type="checkbox" value="<?php echo esc_attr( $dws_anda_uid ); ?>" name="dws_anda_delete[]" /> <?php echo esc_html( $dws_anda_name ); ?></h4>
									<p><img rel="<?php echo esc_attr( $dws_anda_local ); ?>" class='new-default-avatar' src='<?php echo esc_url( $image_url ); ?>' alt='' /></p>
							<?php
						}
						?>
						<input type="submit" class="save" value="<?php esc_html_e( 'Remove Selected', 'dws' ); ?>" />
					</fieldset>
				</form>
			<?php
			}
		}
	}
	$dws_anda = new DWS_ANDA();
}
