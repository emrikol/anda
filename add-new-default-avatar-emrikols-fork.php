<?php
/**
 * Plugin Name: Add New Default Avatar [Emrikol's Fork]
 * Plugin URI:
 * Description: Add new option to the Default Avatar list.
 * Version: 3.0.0
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
		 * Plugin version.
		 *
		 * @since 2.0.0
		 * @access public
		 * @var string $ver
		 */
		var $ver = '3.0.0';

		/**
		 * Class constructor.
		 *
		 * @since 2.0.0
		 */
		function __construct() {
			add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'action_admin_print_footer_scripts' ) );

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
			$user_capability = apply_filters( 'dws_anda_user_capability', 'manage_options' );
			$page = add_submenu_page( 'themes.php', esc_html__( 'Add New Default Avatar', 'dws' ), esc_html__( 'Add New Avatar', 'dws' ), $user_capability, 'add-new-default-avatar', array( $this, 'plugin_page' ) );
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
		 * Printing inline admin scripts.
		 *
		 * @since 3.0.0
		 */
		function action_admin_print_footer_scripts() {
			?>
			<script type='text/javascript'>
				jQuery( function( $ ) {
					var media_uploader = null;

					function open_media_uploader_image() {
						media_uploader = wp.media( {
							frame: 'post',
							state: 'insert',
							multiple: false
						} );

						media_uploader.on( 'insert', function() {
							var json = media_uploader.state().get( 'selection' ).first().toJSON();

							var image_url = json.url;
							var image_caption = json.caption;
							var image_title = json.title;
							var image_id = json.id;

							$( '#dws_anda_image_url_upload' ).val( image_url );
							$( '#dws_anda_avatar_name' ).val( image_title );
							$( '#dws_anda_avatar_id' ).val( image_id );
						} );

						media_uploader.open();
					}

					$( '#dws_anda_image_url_upload, #dws_anda_image_url' ).click( function( e ) {
						open_media_uploader_image();
					} );
				} );
			</script>
			<style type="text/css">
				#dws_anda_add label {
					display: block;
					float: left;
					width: 100px;
				}
				#dws_anda_add .text {
					width: 200px;
				}
				#dws_anda_add li {
					margin: 10px 0px;
				}
				#dws_anda_add ol, #dws_anda_add li {
					list-style-type: none;
				}
			</style>
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

			if ( false !== $options ) {
				$anda_avatars = array();

				foreach ( $options['avatars'] as $avatar ) {
					$attachment_data = wp_get_attachment_image_src( $avatar['id'] );
					if ( false !== $attachment_data ) {
						$anda_avatars[ $attachment_data[0] ] = esc_html( $avatar['name'] );
					}
				}

				$avatar_defaults = array_merge( $anda_avatars, $avatar_defaults ); // Put our custom avatars on top.
			}

			return $avatar_defaults;
		}

		/**
		 * Plugin Activiation.
		 *
		 * @since 2.0.0
		 */
		function plugin_construct() {
			$options = array();
			$options['avatars'] = array();
			$options['avatar_default'] = get_option( 'avatar_default' );
			update_option( 'dws_anda', $options );
		}

		/**
		 * Plugin Dectiviation.
		 *
		 * @since 2.0.0
		 */
		function plugin_destruct() {
			$options = get_option( 'dws_anda' );

			if ( false !== $options ) {
				// Change default avatar back to what it was before the plugin was activated.
				update_option( 'avatar_default', $options['avatar_default'] );
				delete_option( 'dws_anda' );
			}
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
							'url' => $avatar['url'],
							'name' => $avatar['name'],
							'uid' => $avatar['uid'],
							'id' => $avatar['id'],
						);
					}
				}
				$options['avatars'] = $updated_avatars;
				update_option( 'dws_anda', $options );
			}

			if ( isset( $_POST['new'] ) && isset( $_POST['dws_anda_image_url'] ) ) { // WPCS: input var okay.
				// Safety check.  Did the admin do this?
				check_admin_referer( 'dws_anda_new' );

				$avatar = array();
				$avatar['url'] = esc_url_raw( sanitize_text_field( wp_unslash( $_POST['dws_anda_image_url'] ) ) ); // WPCS: input var okay.
				$avatar['uid'] = 'DWS' . md5( uniqid() );
				$avatar['name'] = isset( $_POST['dws_anda_avatar_name'] ) ? sanitize_text_field( wp_unslash( $_POST['dws_anda_avatar_name'] ) ) : $avatar['uid']; // WPCS: input var okay.
				$avatar['id'] = isset( $_POST['dws_anda_avatar_id'] ) ? absint( $_POST['dws_anda_avatar_id'] ) : 0; // WPCS: input var okay.

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
							</li>
							<li>
								<label for="dws_anda_image_url"><?php esc_html_e( 'Image URL', 'dws' ); ?>: </label>
								<input class='text' name='dws_anda_image_url' id='dws_anda_image_url_upload' type='text' value='' />
								<input type="button" class="button button-primary button-large" id='dws_anda_image_url' value="Select Image" />
								<input type="hidden" id="dws_anda_avatar_id" name="dws_anda_avatar_id" />
							</li>
							<li>
								<label for="dws_anda_avatar_name"><?php esc_html_e( 'Avatar Name', 'dws' ); ?>: </label>
								<input type="text" class="text" id="dws_anda_avatar_name" name="dws_anda_avatar_name" id="dws_anda_avatar_name" value="" />
								<?php wp_nonce_field( 'dws_anda_new' ); ?>
								<?php submit_button( esc_html__( 'Add Avatar', 'dws' ), 'secondary', 'new' ); ?>
							</li>
						</ul>
					</fieldset>
				</form>
				<?php if ( ! empty( $options['avatars'] ) ) : ?>
				<form method="post">
					<fieldset>
						<legend><h3><?php esc_html_e( 'Current Custom Avatars', 'dws' ); ?></h3></legend>
						<?php
						foreach ( $options['avatars'] as $avatar ) {
							?>
							<h4><input type="checkbox" value="<?php echo esc_attr( $avatar['uid'] ); ?>" name="dws_anda_delete[]" /> <?php echo esc_html( $avatar['name'] ); ?></h4>
							<?php echo wp_get_attachment_image( $avatar['id'] ); ?>
							<?php
						}
						?>
						<?php wp_nonce_field( 'dws_anda_update' ); ?>
						<?php submit_button( esc_html__( 'Remove Selected', 'dws' ), 'secondary', 'update' ); ?>
					</fieldset>
				</form>
			<?php endif; ?>
			</div>
			<?php
		}
	}
	$dws_anda = new DWS_ANDA();
}
