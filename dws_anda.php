<?php
/*
Plugin Name: Add New Default Avatar [Emrikol's Fork]
Plugin URI: 
Description: Add new option to the Default Avatar list. 
Version: 2.0
Author: Decarbonated Web Services
Author URI: http://www.decarbonated.com/
License: GPL2
*/

if (!class_exists('DWS_ANDA')) {
	class DWS_ANDA {
		var $slug = 'anda';
		var $name = 'Add New Default Avatar';
		var $access = 'manage_options';
		var $installdir;
		var $ver = "2.0";

		function __construct() {
			//define('WP_DEBUG', true);
			$this->installdir = WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__),"",plugin_basename(__FILE__));
			$this->name = __($this->name,'dws');
			
			add_action('admin_menu', array(&$this, 'action_admin_menu'));
			add_action('admin_print_scripts', array(&$this, 'action_admin_print_scripts'));
			add_action('wp_ajax_dws_anda_ajax_callback', array(&$this, 'action_wp_ajax_dws_anda_ajax_callback'));
			add_action('wp_enqueue_scripts', array(&$this, 'action_wp_enqueue_scripts'));
			add_action('admin_enqueue_scripts', array(&$this, 'action_admin_enqueue_scripts'));
			
			add_filter('avatar_defaults', array(&$this, 'filter_avatar_defaults'));

			register_activation_hook(__FILE__, array(&$this, 'plugin_construct'));	// Register construct
			register_deactivation_hook(__FILE__, array(&$this, 'plugin_destruct'));	// Register plugin destruct
		}
		
		function action_admin_menu() { // Add "Add New Avatar" link under the "Appearance" menu
			$page = add_submenu_page('themes.php',__('Add New Default Avatar','dws'),__('Add New Avatar','dws'),$this->access, __FILE__ , array(&$this, 'plugin_page'));
		}
		
		function action_admin_enqueue_scripts() {
			wp_enqueue_script('jquery-ui-core'); // Make sure jQuery UI is loaded
			wp_enqueue_script('dws_ajaxupload',$this->installdir.'js/ajaxupload.js',array('jquery'),$this->ver);  // Add AjaxUpload
			wp_enqueue_script('dws_anda_js',$this->installdir.'js/dws_anda.js',array('jquery','dws_ajaxupload'),$this->ver);  // Add JS
		}
		
		function action_wp_enqueue_scripts() {
			wp_enqueue_style('dws_anda_style',$this->installdir.'css/style.css',array(),$this->ver,'all');  // Add CSS
		}
		
		function action_admin_print_scripts() { // Set necessary 'variable' JavaScript options
			echo "<script type='text/javascript'>var dws_anda_admin_url = '" . admin_url("admin-ajax.php") . "';</script>";
		}
		
		function action_wp_ajax_dws_anda_ajax_callback() { // Add Ajax callback
			$ajax_action = $_POST['type'];
			switch ($ajax_action) {
				case 'upload':
					$clickedID = $_POST['data']; // Acts as the name
					$filename = $_FILES[$clickedID];
					$filename['name'] = preg_replace('/[^a-zA-Z0-9._\-]/', '', $filename['name']); 
					
					$override['test_form'] = false;
					$override['action'] = 'wp_handle_upload';    
					$uploaded_file = wp_handle_upload($filename,$override);
							$upload_tracking[] = $clickedID;
							update_option( $clickedID , $uploaded_file['url'] );
							
					if(!empty($uploaded_file['error'])) {
						echo __('Upload Error: ','dws') . $uploaded_file['error'];
					} else { echo json_encode($uploaded_file); } // Is the Response
					die();  // Always have to end with a die, thanks to the "die('0');" in admin-ajax.php
				case 'image_reset':
					$id = $_POST['data']; // Acts as the name
					global $wpdb;
					$query = "DELETE FROM $wpdb->options WHERE option_name LIKE '$id'";
					$wpdb->query($query);
					die();  // Always have to end with a die, thanks to the "die('0');" in admin-ajax.php
				default:
					die();
			}
		}
		
		function filter_avatar_defaults($avatar_defaults) { // Add plugin to avatar settings
			$options = get_option('dws_anda');
			
			if ($options) {
				$thumb_url = $this->installdir."includes/timthumb.php";
				$anda_avatars = array();

				foreach ($options['avatars'] as $avatar) {
					$image_url = $thumb_url."?src=".$avatar['local'];
					$anda_avatars[$image_url] = $avatar['name'];
				}

				return array_merge($anda_avatars,$avatar_defaults); // Put our custom avatars on top
			} else {
				return $avatar_defaults;
			}
		}

		function plugin_construct() {
			$options = array();											// Set up options array
			$options['avatars'] = array();								// Set up avatars array
			$options['avatar_default'] = get_option('avatar_default');	// Be sure to save original avatar in case user removes plugin
			update_option('dws_anda',$options);							// Save plugin options
		}

		function plugin_destruct() {
			$options = get_option('dws_anda');	// Get Plugin Prefrences
			
			// Change default avatar back to what it was before the plugin was activated
			update_option('avatar_default',$options['avatar_default']);
			delete_option('dws_anda');			// Delete the plugin prefrences
		}
		
		function plugin_page() {
			$options = get_option('dws_anda');

			if (isset($_POST['update'])) {
				// Safety check.  Did the admin do this?
				check_admin_referer('dws_anda_update');
				
				$updated_avatars = array();
				foreach ($options['avatars'] as $avatar) {
					if (!in_array($avatar['uid'],$_POST['dws_anda_delete'])) {
						$updated_avatars[] = array(
							'local' => $avatar['local'],
							'url' => $avatar['url'],
							'name' => $avatar['name'],
							'uid' => $avatar['uid']
						);
					}
				}
				$options['avatars'] = $updated_avatars;
				update_option('dws_anda',$options);
			}

			if (isset($_POST['new'])) {
				// Safety check.  Did the admin do this?
				check_admin_referer('dws_anda_new');
				
				$avatar = array();
				$avatar['local'] = $_POST['dws_anda_localfile'];
				$avatar['url'] = $_POST['dws_anda_image_url'];
				$avatar['uid'] = "DWS".md5(uniqid());
				$avatar['name'] = $_POST['dws_anda_avatar_name'] ? $_POST['dws_anda_avatar_name'] : $avatar['uid'];
				
				$options['avatars'][] = $avatar;
				
				update_option('dws_anda',$options);
				
				if (get_option('dws_anda') == $options) {
					echo '<h3>'.__('Saved!','dws').'</h3>';
				}

				else {
					echo '<h3>'.__('Something may have gone wrong.','dws').'</h3>';
					echo '<p>'.__('If the problem persists, please contact the author','dws').': <a href="mailto:emrikol@gmail.com">emrikol@gmail.com</a></p>';
				}
			}

			// Output page HTML
			?>
			<div class="wrap">
				<div id="icon-upload" class="icon32"></div>
				<h2><?php _e('Custom Default Avatars','dws'); ?></h2>
				
				<form id="dws_anda_add" method="post">
					<fieldset>
						<ul>
							<li>
								<legend><h3><?php _e("Add a new Avatar","dws"); ?></h3></legend>
								<?php wp_nonce_field('dws_anda_new'); ?>
								<input type="hidden" name="new" />
							</li>
							<li>
								<label for="dws_anda_image_url"><?php _e('Image URL','dws'); ?>: </label>
								<input class='text' name='dws_anda_image_url' id='dws_anda_image_url_upload' type='text' value='' />
								<div class='upload_button_div'>
									<span class='button image_upload_button' id='dws_anda_image_url'><?php _e('Upload Image','dws'); ?></span>
									<span class='button image_reset_button hidden' id='reset_dws_anda_image_url"' title='dws_anda_image_url'><?php _e('Remove','dws'); ?></span>
								</div>
							</li>
							<li class='nothidden'>
								<label for="dws_anda_avatar_name"><?php _e('Avatar Name','dws'); ?>: </label>
								<input type="text" class="text" id="dws_anda_avatar_name" name="dws_anda_avatar_name" id="dws_anda_avatar_name" value="" />
							</li>
							<li class='nothidden'>
								<input type="submit" class="save" value="<?php _e('Add Avatar','dws'); ?>" />
							</li>
						</ul>
					</fieldset>
				</form>
				<?php $this->show_avatars(); ?>
			</div>
			<?php
		}
		function show_avatars() {
			$options = get_option('dws_anda');

			if (!empty($options['avatars'])) {
			?>
				<form method="post">
					<fieldset>
						<legend><h3><?php _e("Current Custom Avatars","dws"); ?></h3></legend>
						<?php wp_nonce_field('dws_anda_update'); ?>
						<input type="hidden" name="update" />
		<?php
			$thumb_url = $this->installdir."includes/timthumb.php";
			foreach ($options['avatars'] as $avatar) {
				$dws_anda_name = $avatar['name'];
				$dws_anda_local = $avatar['local'];
				$dws_anda_url = $avatar['url'];
				$dws_anda_uid = $avatar['uid'];
				// $image_url = $thumb_url."?src=".$dws_anda_url;
				$image_url = $thumb_url."?src=".$dws_anda_local;
				?>
						<h4><input type="checkbox" value="<?php echo $dws_anda_uid; ?>" name="dws_anda_delete[]" /> <?php echo $dws_anda_name; ?></h4>
						<p><img rel="<?php echo $dws_anda_local; ?>" class='new-default-avatar' src='<?php echo $image_url; ?>' alt='' /></p>
				<?php
			}
		?>
						<input type="submit" class="save" value="<?php _e('Remove Selected','dws'); ?>" />
					</fieldset>
				</form>
			<?php
			}
		}

	}
	$dws_anda = new DWS_ANDA();
}