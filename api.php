<?php

/*  API UPLOAD Controller file
 *	
 *	This file is designed to handle the uploading of images.
 *	It also allows you to plug in your own functionality, 
 *	while making the standard functionality available for you to use
 *	in your own code.
 *
*/

define('DOING_AJAX', true);
define('WP_ADMIN', true);

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
	require_once('../../../wp-admin/includes/admin.php');
	do_action('admin_init');
} 

if( !$_POST['action'] == 'getkey'){
	check_ajax_referer( "bwbps-sharing" );
}

define("PSPIXOOXAPIURL", "http://pixoox.com/api/");


//
//	Pixoox WordPress Client API	-- Requires PhotoSmash plugin to be installed
//

class PixooxClient_API{
	
	var $sharing_options;
	var $h; // variable to hold the Helpers class
	
	//Instantiate the Class
	function PixooxClient_API(){
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/pxx-helpers.php");
		
		$this->h = new PixooxHelpers();
		
		$this->sharing_options = get_option('bwbps_sharing_options');
		
		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
		}
		
		switch ($action){
		
			case 'request' :
			
				
				break;
			
			case 'posted' :
				
				break;
			
			case 'requestsharing' :
				$this->requestSharing();
				$this->setSharingStatus();
				break;
				
			case 'sendkey' :
				$this->processKey();
				break;
				
			case 'gethublist' :
				$this->getHubList();
				break;
				
			case 'gethubform' :
				$this->getHubForm();
				break;
			
			case 'savehub' :
				$this->saveHub();
				
				$this->setSharingStatus();
				
				break;
			
			case 'deletehub' :
				$this->deleteHub();
				break;
				
			default :
				break;
		
		}
		
		die();
		
	}
	
	function deleteHub(){
		global $wpdb;
		
		if(!current_user_can('level_10')){
			$json['status'] = 0;
			$json['message'] = "security failed in sharing request";
			echo json_encode($json);
			return;
		}
		
		$hub_id = (int)$_POST['hub_id'];
		
		if($hub_id){
			$wpdb->query($wpdb->prepare("DELETE FROM " . PSHUBSTABLE . " WHERE hub_id = %d ", $hub_id));
		}
		
		$json['hub_id'] = $hub_id;
		$json['status'] = 1;
		$json['message'] = "Hub deleted";
		echo json_encode($json);
		return;
		
	}
	
	/*	REQUEST SHARING
	 *	-- kicks off the Request to Share with a hub
	 *
	*/
	function requestSharing(){
	
		if(!current_user_can('level_10')){
			$json['status'] = 0;
			$json['message'] = "security failed in sharing request";
			echo json_encode($json);
			return;
		}
		
		$hub_id = (int)$_REQUEST['hub_id'];
		
		$hub = $this->getHub($hub_id);
		
		$hub_api = $this->h->validURL($hub['api_url']);
				
		if(!$hub_api){
			$json['message'] = "Invalid API URL: " . $hub['api_url'];
			$json['status'] = 0;
			echo json_encode($json);
			return;
		}	
		
		$siteurl = get_bloginfo("url");
		$name = get_bloginfo("name");
		$apiurl = WP_PLUGIN_URL . "/photosmash-galleries/api.php";
		$nonce = wp_create_nonce( 'bwbps-sharing-request' );
		$tags = $this->sharing_options['tags'];
		
		$key = $this->h->decrypt($hub['pixoox_key']);
		
		if(!$this->updateHubNonce($hub_id, $nonce)){
			$json['message'] = "Unable to set nonce security: " . $hub['api_url'];
			$json['status'] = 0;
			echo json_encode($json);
			return;
		};
		
		$post_data = array('action' => 'requestsharing',
			'servername' => $_SERVER['SERVER_NAME'],
			'admin_email' => $this->sharing_options['admin_email'],
			'site_url' => $siteurl,
			'site_name' => $name,
			'hub_id' => $hub_id,
			'_ajax_nonce' => $nonce,
			'pixoox_key' => $key,
			'api_url' => $apiurl,
			'tags' => $tags,
			'agent' => 'photosmash'
		);
		
		
		$json = $this->h->sendCURL($hub_api, $post_data);
		
		echo $json;
		
		// TODO - need to get the User Name and update the Database with it
		
	}
	
	/*	PROCESS KEY
	 *	-- handles the Key verification from the Hub during a Sharing Request
	*/
	function processKey(){
		global $wpdb;
		
		$nonce = $_POST['_ajax_nonce'];
		$hub_id = (int)$_POST['hub_id'];
		$key = $_POST['pixoox_key'];
		
		if(!$nonce || !$hub_id){
			$json['status'] = 1001;
			$json['message'] = "security failed";
			echo json_encode($json);
			return;
		}
		
		if(!$key || strlen($key) < 14){
			$json['status'] = 1005;
			$json['message'] = "invalid key";
			echo json_encode($json);
			return;
		}
		
		$hub = $this->getHub($hub_id, $nonce);
		
		if(!$hub){
			$json['status'] = 1010;
			$json['message'] = "Failed to find hub through nonce.";
			echo json_encode($json);
			return;	
		}
		
		// Return message
		
		$json['status'] = 2000;
		$json['message'] = "key updated";
		
		// echo back and close the connection, but keep processing
		$this->h->backgroundEcho($json);
		
		if($key != $this->h->decrypt($hub['pixoox_key'])){
			$data['pixoox_key'] = $this->h->encrypt($key);			
		}
				
		$data['nonce'] = "";
		$data['hub_status'] = 1;
		$this->updateHub($data, $hub_id, $nonce);
		
		return;
	}
	
	function updateHub($data, $hub_id, $nonce){
		global $wpdb;
		if(is_array($data) && $data['pixoox_key'] && (int)$hub_id && $nonce){
			$where['hub_id'] = $hub_id;
			$where['nonce'] = $nonce;
			return $wpdb->update(PSHUBSTABLE, $data, $where);	
		}
		
		
		return;
		
	}
	
	function saveHub(){
	
		if(!current_user_can('level_10')){
		
			$json['message'] = "you do not have authorization.";
			$json['success'] = 0;
			echo json_encode($json);
			return;
		
		}
		
		global $wpdb;
		$a = array();
		$data['hub_name'] = wp_kses(stripslashes($_POST['hub_name']), $a);
		$data['hub_url'] = esc_url_raw(stripslashes($_POST['hub_url']));
		$data['api_url'] = esc_url_raw(stripslashes($_POST['api_url']));
		$data['hub_status'] = (int)$_POST['hub_status'];
		$data['admin_email'] = wp_kses(stripslashes($_POST['admin_email']), $a);
		$data['tags'] = wp_kses(stripslashes($_POST['tags']), $a);
		
		if($data['tags']){
			$data['tags'] = str_replace(".", " ", $data['tags']);
			$data['tags'] = str_replace(";", ",", $data['tags']);
		}
				
				
			
		if( !(int)$_POST['hub_id'] ){
			$data['created_date'] = current_time('mysql'); //date( 'Y-m-d H:i:s');
			$wpdb->insert(PSHUBSTABLE, $data);
			
			$data['hub_id'] = $wpdb->insert_id;
			$data['isnew'] = 1;
		} else {
			
			$where['hub_id'] = (int)$_POST['hub_id'];
			$wpdb->update(PSHUBSTABLE, $data, $where);
			$data['hub_id'] =  (int)$_POST['hub_id'];
			$data['isnew'] = 0;
		}
		
		$json = $data;
		$json['status'] = 1;
		echo json_encode($json);
		return;

	}
	 
	
	function getHubForm(){
	
		if(!current_user_can('level_10')){
		
			$json['message'] = "you do not have authorization.";
			$json['success'] = 0;
			echo json_encode($json);
			return;
		
		}
	
		$hub_id = (int)$_REQUEST['hub_id'];
		
		if($hub_id){
		
			$hub = $this->getHub($hub_id);
			
			$pxxss = (int)$hub['hub_status'];
			$pxx_hubstatus["p-" . $pxxss] = 'selected=selected';
		
		}
	
		$nonce = wp_create_nonce( 'bwbps-sharing' );
		
		$ret = '
		<form style="text-align: left;" id="pixoox-hub-form" name="pixoox-hub-form" method="post" onsubmit="photosmash.saveHub(); return false;" action="" style="margin:0px; padding: 15px;" class="pixoox-form">
        	<input type="hidden" id="pixoox-hub-nonce" name="_ajax_nonce" value="'.$nonce.'" />
        	<input type="hidden" id="pixoox_hub_id" name="pixoox[hub_id]" value='. $hub_id . ' />
        	<span style="line-height: 23px;">&nbsp;</span><img class="pixoox-wait" style="display: none;" src="' 
        	. WP_PLUGIN_URL . '/pixoox/images/wait.gif" />
        	<br/>
        	
        <table class="form-table">
					<tr valign="middle">
						<th scope="row">
							<label for="pixoox_hub_name">Hub name:</label>
						</th>
						<td>
							<input id="pixoox_hub_name" name="pixoox[hub_name]" type="text" class="" value="'
								. esc_attr($hub['hub_name']) . '" size="60" />
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="pixoox_hub_status">Status:</label>
						</th>
						<td>
							<select id="pixoox_hub_status" name="pixoox[hub_status]">
								<option '  .  $pxx_hubstatus["p-0"] . ' value="0">Not applied</option>
								<option '  .  $pxx_hubstatus["p-1"] . ' value="1">Sharing</option>
								<option '  .  $pxx_hubstatus["p--1"] . ' value="-1">Waiting</option>
								<option '  .  $pxx_hubstatus["p--2"] . ' value="-2">Do Not Share</option>				
								<option '  .  $pxx_hubstatus["p--3"] . ' value="-3">Buried</option>
							</select>
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="pixoox_hub_url">Hub URL:</label>
						</th>
						<td>
							<input id="pixoox_hub_url" name="pixoox[hub_url]" type="text" class="" value="'
								. esc_attr($hub['hub_url']) . '" size="60" />
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="pixoox_api_url">API URL:</label>
						</th>
						<td>
							<input id="pixoox_api_url" name="pixoox[api_url]" type="text" class="" value="'
								. esc_attr($hub['api_url']) . '" size="60" />
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="pixoox_admin_email">Email:</label>
						</th>
						<td>
							<input id="pixoox_admin_email" name="pixoox[admin_email]" type="text" class="" value="'
								. esc_attr($hub['admin_email']) . '" size="60" />
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="pixoox_tags">Tags:</label>
						</th>
						<td>
							<input id="pixoox_tags" name="pixoox[tags]" type="text" class="" value="'
								. esc_attr($hub['tags']) . '" size="60" />
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="pixoox_key">Pixoox key:</label>
						</th>
						<td>
							<input id="pixoox_key" name="pixoox[pixoox_key]" type="text" class="" value="'
								. esc_attr($hub['pixoox_key']) . '" size="60" disabled=true />
						</td>
					</tr>
					
										
        	</table>
		
			<input type="button" onclick=\'photosmash.saveHub(); return false;\' name="save_hub" class="button-primary" value="Save" />			
			<input type="button" onclick="tb_remove();return false;" class="button-primary" value="Done" />
			<img class="pixoox-wait" style="display: none;" src="' 
        	. WP_PLUGIN_URL . '/pixoox/images/wait.gif" /> 
			
			<div id="pixoox-form-message"></div>
		</form>';
		
		echo $ret;
	
	}
	
	function getHub($hub_id=false, $nonce=false){
		global $wpdb;
				
		if(!$nonce){
			$sql = "SELECT * FROM " . PSHUBSTABLE . " WHERE hub_id = " . (int)$hub_id;
		} else {
			if((int)$hub_id){
				$sql = $wpdb->prepare("SELECT * FROM " . PSHUBSTABLE . " WHERE hub_id = %d AND nonce = %s", $hub_id, $nonce);
			}
		}
		if(!$sql){return false;}
		return $wpdb->get_row($sql, ARRAY_A);
	}
	
	function updateHubNonce($hub_id, $nonce){
		global $wpdb;
		
		if(!$nonce){ return false;}
		
		$data['nonce'] = $nonce;
		$where['hub_id'] = (int)$hub_id;
		$wpdb->update(PSHUBSTABLE, $data, $where);
		return true;
	}
	
	function setSharingStatus(){
	
		global $wpdb;
		
		$ret = $wpdb->get_row("SELECT hub_status FROM " . PSHUBSTABLE . " WHERE hub_status > 0 ");
		
		$this->sharing_options['status'] = $ret ? 1 : 0;
		
		update_option('bwbps_sharing_options', $this->sharing_options);
	
	}
	
	
	function getHubList(){
		global $wpdb;
	
		if(!current_user_can('level_10')){
			$json['status'] = 0;
			$json['message'] = "security failed";
			echo json_encode($json);
			return;
		}
			
		$url = PSPIXOOXAPIURL . "hublist";
				
		$ret = $this->h->sendCURL($url);
		
		
		
		$x = json_decode($ret);
		$r = $x->hubs;
		

		if(is_array($r)){
		
			foreach($r as $hub){
				$hub_url = $this->h->validURL($hub->hub_url);
				
				$data['hub_url'] = $hub_url;	
				$data['api_url'] = $this->h->validURL($hub->api_url);
				
				/*
				// Logo URL support never added because you can't trust 'em
				$data['logo_url'] = $this->h->validURL($hub->logo_url);
				$data['logo_url'] = $data['logo_url'] ? $data['logo_url'] : "";
				
				if(!$this->h->compareDomains($hub_url, $data['logo_url'])){
					$data['logo_url'] = "";
				}
				*/
				
				$arr = array();
				
				$data['hub_name'] = wp_kses($hub->hub_name, $arr);
				$data['hub_description'] = wp_kses($hub->hub_description, $arr);
				$data['allows_adult'] = (int)$hub->allows_adult;
				$data['restricts_categories'] = (int)$hub->restricts_categories;
				
				$data['admin_email'] = wp_kses($hub->admin_email, $arr);
				
				$data['tags'] = wp_kses($hub->tags, $arr);
				
				if(!$data['api_url'] || !$hub_url || !$data['hub_name'] ){ 
					$tempname = $data['hub_name'] ? $data['hub_name'] : $hub_url;
					$json['message'] .= "Hub missing data: " . $tempname . "\n";
					//continue; 
				}
				
				if(!$this->h->compareDomains($hub_url, $data['api_url'])){
					$tempname = $data['hub_name'] ? $data['hub_name'] : $hub_url;
					$json['message'] .= "Domain of URL != API: " . $tempname . "\n";
					continue; 
				}
				
				// See if we're updating or inserting
				$hub_id = $wpdb->get_var($wpdb->prepare('SELECT hub_id FROM ' 
					. PSHUBSTABLE . ' WHERE hub_name = %s LIMIT 1', $data['hub_name']));
					
				
				if((int)$hub_id){
					//Update Hub
					$where['hub_id'] = (int)$hub_id;
					$wpdb->update(PSHUBSTABLE, $data, $where);
				} else {
					$wpdb->insert(PSHUBSTABLE, $data);
				}
			
			}
		
		}
		
		$json['message'] = "Hub update complete.\n\nWill reload page automatically.";
		$json['success'] = 1;
		
		echo json_encode($json);
		return;
		
	}
	
	
}

$pixooxClient = new PixooxClient_API();

?>