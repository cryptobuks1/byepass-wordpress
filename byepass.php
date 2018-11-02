<?php
/**
 Plugin Name: Byepass
 Plugin URI: https://byepass.co/
 Description: A plugin to enables passwordless login to Wordpress using Byepass.
 Author URI: https://byepass.co/
 Version: 1.0.0
 */


class byepass_plugin {
	
	public function __construct() {
		
		add_action( 'admin_init', array($this, 'register_mysettings') );
		add_action( 'admin_menu', array($this, 'byepass_info_menu') );					  
								
		//add_action('admin_init', 'registerByepass');
				
		//only show our login form if we have a key alredy	
		if (get_option('byepass_key') and !empty(get_option('byepass_key'))) {	
			add_action('login_head', array($this, 'byepass_login'));	
		}	
		
	}
	
	public function register_mysettings() {
		register_setting( 'byepass-settings', 'byepass_email' );	
		register_setting( 'byepass-settings', 'byepass_key' );
		register_setting( 'byepass-settings', 'byepass_secret' );
	}
	
	public function byepass_info_menu() {
	
	  $page_title = 'Byepass Login';
	  $menu_title = 'Byepass Login';
	  $capability = 'manage_options';
	  $menu_slug  = 'byepass-setup';
	  $function   = 'byepass_info_page';
	  $icon_url   = 'dashicons-media-code';
	  $position   = 4;
	
	  add_menu_page( $page_title,
	                 $menu_title,
	                 $capability,
	                 $menu_slug,
	                 array($this, $function),
	                 $icon_url,
	                 $position );	                 		
	
	}
		


	public function byepass_get_url($the_url, $max_try) {
		$cur_loop = 0;
		while (FALSE == $url = file_get_contents($the_url)) {
			$cur_loop++;
			if ($cur_loop >= $max_try) {
				break;
			}
		}
	
		return $url;
	}



	public function update_byepass_info($email,$callback) {
		
		$email = sanitize_email($email);
		$callback = esc_url_raw($callback);
		
		if (!is_email($email)) { echo "bad email"; die; } 
		if (!wp_http_validate_url($callback)) { echo "bad url"; die; }		
		
		$ur = 
			"https://byepass.co/api/enroll/".
			"?platform=wordpress&version=".get_bloginfo( 'version' ).
			"&redirect=".addslashes($callback).
			"&identifier=".$email;
			
		$data = $this->byepass_get_url($ur, 5);
		
		if ($data) {
			$json = json_decode($data);
			if ($json->status == "200") {
				update_option( 'byepass_email', $email );
				update_option( 'byepass_key', $json->key );
				update_option( 'byepass_secret', $json->secret );
				
				add_action('login_head', array($this, 'byepass_login'));								

			}
		}
				
	}



	public function byepass_info_page() { 
		$current_user = wp_get_current_user(); 
		if (isset($_POST['byepass_email']) and isset($_POST['byepass_callback'])) {
			$this->update_byepass_info($_POST['byepass_email'],$_POST['byepass_callback']);
		}		
	?>
	  <h1>Byepass - Passwordless Logins</h1>
	  <form method="post" action="#">	  

	    <table class="form-table">			     
	      <?php if (!empty(get_option('byepass_email')) and !empty(get_option('byepass_key')) and !empty(get_option('byepass_secret'))) { ?>		      
	      <tr valign="top"><td>Byepass is configured!</td></tr>
	      <tr valign="top"><td>To login use your Wordpress email: <?php echo sanitize_email(get_option('byepass_email')); ?>!</td></tr>
	      <tr valign="top"><td><a href="https://byepass.co" target="_blank">Login to Byepass.co to check stats</a></td></tr>
	      <tr valign="top">
	      <th scope="row">Your email (registered with Byepass):</th>
	      <td><input type="text" name="email" value="<?php echo sanitize_email(get_option('byepass_email')); ?>" disabled="disabled"/></td>
	      </tr>
	      <tr valign="top">
	      <th scope="row">Key:</th>
	      <td><input type="text" name="" value="<?php echo sanitize_text_field(get_option('byepass_key')); ?>" disabled="disabled"/></td>
	      </tr>
		   <tr valign="top">
	      <th scope="row">Secret:</th>
	      <td><input type="text" name="" value="<?php echo sanitize_text_field(get_option('byepass_secret')); ?>" disabled="disabled"/></td>
	      </tr>
			</table>  
		  <?php } else { ?>
		  <tr valign="top"><th>Let's get your API keys!</th></tr>
		  <tr valign="top">
	      <th scope="row">Your email (registered with Byepass):</th>
	      <td><input type="text" name="byepass_email" value="<?php echo $current_user->user_email; ?>"/></td>
	      </tr>
	      <tr valign="top">
	      <th scope="row">Redirect URL (Don't change unless you know what you are doing.</th>
	      <td><input type="text" name="byepass_callback" value="<?php echo site_url()."/wp-login.php"; ?>"/></td>
	      </tr>  
	      </table>
	      <?php submit_button("Get API Keys"); ?>
		  <?php } ?>	    
	  
	  </form>
	<?php
	}


	public function byepass_login() {
	

		$url = site_url() . '/wp-admin';	
		
		if (isset($_REQUEST['challengeId'])) {
			
			$challengeId = isset($_REQUEST['challengeId']) ? $_REQUEST['challengeId'] : false;
			$oth = isset($_REQUEST['oth']) ? $_REQUEST['oth'] : false;
			$identifier = isset($_REQUEST['identifier']) ? $_REQUEST['identifier'] : false;					
			
			if ($identifier and $challengeId and $oth) {
				
				$challengeId = sanitize_text_field($challengeId);
				$oth = sanitize_text_field($oth);
				$identifier = sanitize_email($identifier);
				
				$user = get_user_by('email', $identifier);
				
				//ensure user exists as a wordpress user before attempting to login with Byepass
				if (empty($user->ID)) {											
					echo '<div id="login_error"><strong>Error:</strong>: Invalid email address, please use your Wordpress user email address.</div>';
				
				//user exists let's authenticate with Byepass
				} else {
		
					$ur = 
						"https://byepass.co/api/verify/".
						"?challengeId=$challengeId".
						"&oth=$oth".
						"&identifier=$identifier".
						"&key=" . sanitize_text_field(get_option('byepass_key')).
						"&secret=" .sanitize_text_field(get_option("byepass_secret"));
						
					$data = $this->byepass_get_url($ur, 5);
					
					if ($data === false) {
						// error message
						echo '<div id="login_error"><strong>Error communicating with Byepass</strong>.</div>';
						exit;
					} else {
						$json = json_decode($data);
						if ($json->success and (time() - strtotime($json->challenge_ts) < 300)) {
							$user = get_user_by('email', $identifier);
							if (!empty($user->ID)) {						
								wp_set_auth_cookie($user->ID, 0);
								if (wp_redirect($url)) {
									exit;
								}
							} else {
								echo '<div id="login_error"><strong>Error:</strong>: Invalid email address, please use your Wordpress user email address.</div>';
							}
						} else if ($json->success and (time() - strtotime($json->challenge_ts) > 300)) {
							echo '<div id="login_error"><strong>Error: authorisation expired, login again</strong></div>';
						} else {
							echo '<div id="login_error"><strong>Error: ('.esc_html($json->status).')</strong></div>';
						}
		
		
					}
				}
			}
		}
	
		
		wp_enqueue_style( 'byepass-custom-css', plugins_url() . '/byepass/style.css', __FILE__ ) ;				
		echo 
			'<form method="post" id="bsubmit" style="background:transparent;box-shadow:none;" action="https://byepass.co/redirect" method="post">'.
				'<input type="hidden" name="key" value="' . get_option("byepass_key") . '">'.
				'<input type="hidden" name="identifier" placeholder="Email" required="">'.		
			'</form>';
		wp_enqueue_script('byepass-custom-script', plugins_url() . '/byepass/custom.js', array('jquery'), null, true);
	}


}

new byepass_plugin();

?>
