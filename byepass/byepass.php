<?php
/**
 Plugin Name: Byepass
 Description: A plugin to enables passwordless login to Wordpress using Byepass.
 Author URI: https://byepass.co/
 Version: 1.0.0
 */

// Prevent direct access

if (!defined('ABSPATH')) exit;


function byepass_get_url($the_url, $max_try) {
	$cur_loop = 0;
	while (FALSE == $url = file_get_contents($the_url)) {
		$cur_loop++;
		if ($cur_loop >= $max_try) {
			break;
		}
	}

	return $url;
}


function byepass_login() {
	$url = site_url() . '/wp-admin';	
	
	if (isset($_POST['challengeId'])) {
		$challengeId = isset($_POST['challengeId']) ? $_POST['challengeId'] : false;
		$oth = isset($_POST['oth']) ? $_POST['oth'] : false;
		$identifier = isset($_POST['identifier']) ? $_POST['identifier'] : false;
		if ($identifier and $challengeId and $oth) {
			$ur = "https://byepass.co/api/verify/?challengeId=$challengeId&oth=$oth&identifier=$identifier&key=" . get_option('byepass_key') . "&secret=" .get_option("byepass_secret");
			$data = byepass_get_url($ur, 5);
			if ($data === false) {
				// error message
				echo '<div id="login_error"><strong>ERROR</strong>.</div>';
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
						echo '<div id="login_error"><strong>ERROR</strong>: Invalid email address.</div>';
					}
				}


			}
		}
	} else if (isset($_GET['challengeId'])) {
		$challengeId = isset($_GET['challengeId']) ? $_GET['challengeId'] : false;
		$oth = isset($_GET['oth']) ? $_GET['oth'] : false;
		$identifier = isset($_GET['identifier']) ? $_GET['identifier'] : false;
		if ($identifier and $challengeId and $oth) {
			$ur = "https://byepass.co/api/verify/?challengeId=$challengeId&oth=$oth&identifier=$identifier&key=" . get_option('byepass_key') . "&secret=" . get_option("byepass_secret");
			$data = byepass_get_url($ur, 5);
			if ($data === false) {

				// error message
				echo '<div id="login_error"><strong>ERROR</strong></div>';
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
						echo '<div id="login_error"><strong>Error</strong>: Invalid email address.</div>';
					}
					
				} else if ($json->success and (time() - strtotime($json->challenge_ts) > 300)) {
					echo '<div id="login_error"><strong>Error: authorisation expired, login again</strong></div>';
				} else {
					echo '<div id="login_error"><strong>Error: ('.$json->status.')</strong></div>';
				}			

			}

			
		}
	}

	wp_enqueue_script('custom.js', plugins_url('custom.js', __FILE__ ), array('jquery'), null, true);
	
	echo '	
	<link rel="stylesheet" type="text/css" href="'.plugins_url('style.css', __FILE__ ).'" />';
	echo '<form method="post" id="bsubmit" style="background:transparent;box-shadow:none;" action="https://byepass.co/redirect" method="post">
	<input type="hidden" name="key" value="' . get_option("byepass_key") . '">
	<input type="hidden" name="identifier" placeholder="Email" required="">	
	</form>
	';
}


function byepass_registerByepass() {
	//if we dont yet have byepass keys then register app with byepass
	if (!get_option('byepass_key') or empty(get_option('byepass_key'))) {	
		$url = site_url()."/wp-login.php";		
		
		$current_user = wp_get_current_user();
		
		if ($current_user->exists() ) {
			$ur = "https://byepass.co/api/wordpressregister/?redirect=".addslashes($url)."&identifier=".$current_user->user_email;
			$data = byepass_get_url($ur, 5);
			if ($data === false) {
							
			} else {
				$json = json_decode($data);
				if ($json->status == "200") {
					update_option( 'byepass_key', $json->key );
					update_option( 'byepass_secret', $json->secret );
					add_action('login_head', 'byepass_login');
					// Plugin logic for adding extra info to posts
					if (!function_exists("byepass_info")) {
						function byepass_extra_post_info($content) {
							$byepass_info = get_option('byepass_info');
							return $content . $byepass_info;
						}
					}
					
					// Apply the extra_post_info function byepass_on our content
					add_filter('the_content', 'byepass_info');
	
				}
			}
	    }	
	} 
}

add_action('admin_init', 'byepass_registerByepass');

if (get_option('byepass_key') and !empty(get_option('byepass_key'))) {	//only show our login form if we have a key alredy
	
	add_action('login_head', 'byepass_login');
	// Plugin logic for adding extra info to posts
	if (!function_exists("byepass_info")) {
		function byepass_extra_post_info($content) {
			$byepass_info = get_option('byepass_info');
			return $content . $byepass_info;
		}
	}
	
	// Apply the extra_post_info function byepass_on our content
	add_filter('the_content', 'byepass_info');
}


?>
