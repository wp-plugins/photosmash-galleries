<?php

/*  API 
 *	
 *	This file is designed to handle the API Calls.
 *	It also allows you to plug in your own functionality, 
 *	while making the standard functionality available for you to use
 *	in your own code.
 *
*/

/*
	Return codes:
	1001 - invalid action requested
	1002 - invalid api url
	1003 - Invalid User ID or Password
	1004 - invalid key
	1005 - server settings are incomplete
	1006 - 
	1007 - User not authorized to access paid content
	1008 - 
	1010 - timed out
	1020 - invalid data type
	1030 - data too large
	2000 - ok
*/

if(!function_exists("is_admin") || !is_admin() ){ die('So long...'); }

if( !class_exists(PhotoSmash_Mobile_API)){
class PhotoSmash_Mobile_API{

	var $retcode;
	
	var $psUploader;
	
	var $gal_funcs;
	var $g;
	
	var $h; 	// Holds the Pixoox Helpers object - uses class defined in photosmash-galleries/admin/pxx-helpers.php
	
	var $pxxoptions;
	
	var $json; 	// Holds the image settings...gets coalesced back to json in bwbps-wp-uploader
	
	//Instantiate the Class
	function PhotoSmash_Mobile_API(){
	
		global $bwbPS;
		
		$this->h = $bwbPS->h;
		$this->psOptions = $bwbPS->psOptions;	
		$this->gal_funcs = $bwbPS->gal_funcs;
		
		$this->setRetCodes();
		
		if( !isset($_REQUEST['photosmash_action']) ){
			$this->exitAPI(1001);
			return true;
		}
		
		$pp_action = $_REQUEST['photosmash_action'];
		
		$user_time = $_REQUEST['ps_session_time'] . '';
		
		switch ($pp_action){
		
			case 'upload' :
				
				if($this->psOptions['api_disable_uploads']){
					$json['message'] = "Uploads are disabled";
					$this->exitAPI(1010);
				}
				if($this->psOptions['api_logging']){
					$this->h->insertParam('upload', $user_time);
				}
				$this->uploadImage();
				break;
				
			case 'viewsite' :
				
				if($this->psOptions['api_logging']){
					$this->h->insertParam('view site', $user_time);
				}
				$this->viewSite();
				break;
				
			case 'getphotos' :
				if($this->psOptions['api_logging']){
					$this->h->insertParam('get photos', $user_time);
				}
				$this->getPhotos();
				break;
				
			case 'syncsite' :
				if($this->psOptions['api_logging']){
					$this->h->insertParam('sync', $user_time);
				}
				$this->syncSite();
				break;
				
			case 'registeraccount' :
				
				if($this->psOptions['api_logging']){
					$this->h->insertParam('register', $user_time);
				}
				$this->redirToRegistration();
				break;
				
			default :
				$this->exitAPI(1001);
				break;
		}
		
		return true;
		
	}
	
	function redirToRegistration(){
		
		if(is_user_logged_in()){
			wp_redirect(admin_url(), 301);
		} else {
			wp_redirect(site_url('wp-login.php?action=register', 'login'), 301);
		}
		return;
	
	}
	
	function syncSite(){
		
		global $wpdb;
		
		$user_name = $_REQUEST['ps_username'];
		$pass = $_REQUEST['ps_pass'];
		
		$thisuser = $this->h->loginUser($user_name, $pass);
		
		global $current_user;
		
		$current_user = $thisuser;
		
		$data['param_group'] = 'mobile';
		$data['param'] = 'site sync';
		
		
		if( !$thisuser || !(int)$thisuser->ID ){
			$data['val_1'] = '0';
			$json['message'] = "User name or Password is not valid. ";
			$json['status'] = 1003;
		} else {
			$data['val_1'] = $user_name;
		}
		
		if($this->psOptions['api_logging']){
			$wpdb->insert(PSPARAMSTABLE, $data);		
		}
		
		unset($data);
		
		$json['tags'] = $this->psOptions['api_tags'];
		$json['categories'] = $this->psOptions['api_categories'];
		$json['galleries'] = $this->psOptions['api_galleries'];
		$json['custom_fields'] = $this->getSiteCustomFields();
		$json['enable_uploads'] = $this->psOptions['api_disable_uploads'] ? 0 : 1;
		
		$json['max_width'] = (int)$this->psOptions['api_max_width'] ? (int)$this->psOptions['api_max_width'] : 1024;
		
		$json['message'] =  "SYNC Success! Params updated.\n" . $json['message'];
		
		$this->exitAPI(2000, $json);
	}
	
	function getSiteCustomFields(){
		global $wpdb;
		$cflist = $this->psOptions['api_custom_fields'];
		
		if($cflist){
			$cflist = str_replace(" ","",$cflist);
			
			$cfs = explode(",",$cflist);
			
			if(is_array($cfs)){
				foreach($cfs as $cf){
					$cfarr[] = (int)$cf;
				}
				
				$cflist = "(". implode(",", $cfarr) . ")";
				$sql = "SELECT field_id, default_val as value, field_name as param, label, auto_capitalize, keyboard_type, type FROM ".PSFIELDSTABLE." WHERE field_id IN " . $cflist . " ORDER BY seq";
				$cfs = $wpdb->get_results($sql, ARRAY_A);
				
				unset($cfarr);
				
				if(is_array($cfs)){
					
					foreach($cfs as $cfield){
						$sql = "SELECT value, label FROM " . PSLOOKUPTABLE . " WHERE field_id = " . (int)$cfield['field_id'] . " ORDER BY seq";
						$cvals = $wpdb->get_results($sql,ARRAY_A);
						if($cvals){
							foreach($cvals as $cv){
									$cfield['value_array'][] = $cv['value'];
									
									$cfield['value_list'][] = $cv['label'] ? $cv['label'] : $cv['value'];
							}
							
							
							// We need to set Custom Fields up for Single select only...that's the way it is online
							$cfield['single'] = "YES";					
						}
						
						$cfarr[] = $cfield;
						
					}
				
					return $cfarr;
				}
				
			}
			
		}
		
	}
	
	function viewSite(){
		wp_redirect(site_url(), 301);
	}
	
	function getPhotos(){
		global $bwbPS;
		
		// Get Logged in if we can
		$user_name = $_REQUEST['ps_username'];
		$pass = $_REQUEST['ps_pass'];
		
		if($user_name && $pass){
			$thisuser = $this->h->loginUser($user_name, $pass);
		}
		
		global $current_user;
		
		if(!is_array($thisuser->errors)){	
			$current_user = $thisuser;
		}
		
		// Get the List of Galleries that can be viewed
		$r = $this->psOptions['api_view_galleries'];
		
		$r = $r ? str_replace(" ","", $r) : "";
		
		$cnt = (int)$_REQUEST['count'] ? (int)$_REQUEST['count'] : 48;
		
		$page = (int)$_REQUEST['pagenumber'] ? (int)$_REQUEST['pagenumber'] : 1;
		
		$images = $bwbPS->img_funcs->getImagesForMobile($cnt, $r, $page);
		
		if(is_array($images)){
			$json['images'] = $images;
		} else {
			$json['message'] = 'No images found.';
			$json['status'] = 1020;
		}
		
		$this->exitAPI(2000, $json);
		
	}
	
	function setRetCodes(){
	
		$this->retcode[1001] = "Invalid action requested.";
		$this->retcode[1002] = "Invalid API url.";
		$this->retcode[1003] = "Invalid User ID or Password.";
		$this->retcode[1004] = "User not authorized to access paid content.";
		$this->retcode[1005] = "Timed out.";
		$this->retcode[1006] = "Invalid data type.";
		$this->retcode[1007] = "Data too large.";
		$this->retcode[1008] = "Server not configured";
		$this->retcode[1009] = "...";
		$this->retcode[1010] = "Uploads are disabled";
		$this->retcode[1020] = "No images found";
		$this->retcode[2000] = "Ok.";
	}
	
	/*	
	 *	Exit and Echo a JSON status and message
	*/	
	function exitAPI($status, $message = false){
		$json['status'] = $status;
		if( $message ){
			if( is_array($message) ){
				//Merge in the Message array...you can load it up with all sorts of stuff
				$json = PixooxHelpers::mergeArrays($json, $message);
			} else {
				$json['message'] = $message . ' Return code: ' . $this->retcode[$status];
			}
			
		} else {
			
			$json['message'] = $this->retcode[$status];
		}

		die(json_encode($json));
	
	}
	
	function uploadImage() {
		
		global $wpdb;
		global $bwbPS;
		
		$user_name = $_REQUEST['ps_username'];
		$pass = $_REQUEST['ps_pass'];
		
		global $current_user;
		
		if($user_name && $pass){
			$thisuser = $this->h->loginUser($user_name, $pass);
		}
		
		if(!is_array($thisuser->errors)){	
			$current_user = $thisuser;
		} else {
			unset($current_user);
		}
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/bwbps-wp-uploader.php");
		
		$gallery_id = (int)$this->psOptions['api_upload_gallery'];
		
		if( !$gallery_id ){
			
			$this->h->emailAdmin("PhotoSmash API Alert", "Mobile API upload failed:  upload gallery was invalid (id: "
				. (int)$this->psOptions['api_upload_gallery'] 
				. ").  Check your API settings and make sure you have set a valid gallery for the upload gallery.");
				
			$this->exitAPI(1008,"Server settings are incomplete.");
		}
						
		$this->psUploader = new BWBPS_Uploader($this->psOptions, $gallery_id, true);
		
		// prevent any JSON echo messages from using the <textarea></textarea>
		$this->psUploader->jquery_forms_hack = false;	
		
		if(trim($_POST['bwbps_gallery_name'])){
			$gname = trim(stripslashes($_POST['bwbps_gallery_name']));
		} else {
			$gname = false;
		}
		
		$this->g = $this->psUploader->getGallerySettings($gallery_id, $gname );
		
		$this->psUploader->setGallery($this->g);
		
		$this->psUploader->upload_agent = 'mobile';	
		
		// This does the whole upload thing
		$image_id = $this->processUpload();
		
		$json['gallery_id'] = $this->psUploader->g['gallery_id'] . " - " . $gallery_id;
		$json['thumb_url'] = $this->psUploader->json['thumb_fullurl'];
		$json['image_id'] = $image_id;
		$json['message'] = "Image uploaded: " . $image_id;
		
		$this->exitAPI(2000, $json);
	
	}
	
	function processUpload(){
	
		//Step 2 & 3
		$this->prepareUploadStep('', 'image');
		
		
		//Step 4
		$processStatus = $this->processUploadStep('', true);
				
		//Step 5
		if($processStatus){ 
		
			$image_id = $this->saveUploadToDBStep(); 
			
			
						
		}
		
		
		if( $image_id ){
		
			//Step 6 - Add image to Media Library if turned on
			$this->addToWPMediaLibrary();
			
			do_action('bwbps_upload_done', $this->psUploader->imageData);
			
			//Step 7 - Do Action 'bwbps_api_uploaded' - triggers the create new post in PhotoSmash Extend
			//		 - it can also be called by other plugins or themes
			if($this->psOptions['api_post_layout']){
			
				$categories = $this->getNewPostCategories();
			
				do_action( 'bwbps_api_uploaded', $this->psOptions['api_post_layout'], $categories, $this );
			}
		
		}
		
		return $image_id;
	
	}
	
	function getNewPostCategories(){
		
		if(is_array($_POST['bwbps_post_cats'])){
			$cats = $_POST['bwbps_post_cats'];
		} else{
			$cats = explode(",",$_POST['bwbps_post_cats']);
		}
		
		if(is_array($cats)){
			$cats = array_map("trim", $cats);
			foreach($cats as $cat){
				$c[] = get_cat_ID($cat);
			}
			if(is_array($c)){
				$ret = implode(",", $c);
			}
		}
		return $ret;
	}
	
	/*
	 *	Following functions are steps in the upload process.
	 *	They're broken out so that developers can do stuff between them if needed
	 *	Use processUpload() if you want to run all 3 steps automatically
	*/
	
	/*	Steps 2-3:		Prepare Upload - fills up the JSON variable with the image variables
	 *				You should make any adjustments to the JSON variable after this
	*/
	function prepareUploadStep($fileInputNumber="", $file_type = 'image' ){
		
		// at some point we need to learn how to throttle submissions
		$this->psUploader->verifyUserRights($this->psUploader->g);	//will exit if not enough rights.
		
		// Fills up JSON array with image settings
		// $this->getImageSettings($this->psUploader->g);	// replaced with:  $this->getImageData()
		
		$this->getImageSettings($this->psUploader->g);	// this gets the image data that was submitted by the sharing site

		//Set the "handle" object to the uploaded file
		$this->psUploader->getFileType( '', 'image' );  //Takes a param for file field # (blank or 2 are presets)
		
	}
	
	/*	
	 *	Get Image Settings:  Replaces the Standard
	 *
	*/
	function getImageSettings($g)
	{
		$tags = $this->psUploader->getFilterArrays();
		
		$this->psUploader->json['succeed'] = 'false'; 
		$this->psUploader->json['size'] = (int)$_POST['MAX_FILE_SIZE'];
				
		$this->psUploader->json['post_id'] = $g['post_id'];
		
		$this->psUploader->json['file_type'] = 0;
		
		$this->psUploader->json['image_caption'] = $this->psUploader->getImageCaption('bwbps_imgcaption');
		
		if( !empty($_POST['bwbps_post_tags']) ){
			
			if(is_array(  $_POST['bwbps_post_tags'] ) ){
				$bbpost_tags = implode(",", $_POST['bwbps_post_tags']);
			} else {
				$bbpost_tags = $_POST['bwbps_post_tags'];
			}
					
			$this->psUploader->json['post_tags'] = wp_kses($bbpost_tags, $tags[3]);
		}
		
		//Get URL
		$bwbps_url = $this->h->validURL($_POST['bwbps_url']);
				
		if( $bwbps_url ){
			$this->psUploader->json['url'] = $bwbps_url;
		} else {
			$this->psUploader->json['url'] = '';//$bwbps_url;
		}
	
	}
	
	
	/*	Step 4:		Process Upload file & thumb
	*/
	function processUploadStep($fileInputNumber="", $processThumbnail = true)
	{
			
		//Processing the Uploaded file - if file type is set then it's not an
		//image, so you process it using the processDocument 
		
		$ftype = (int)$this->psUploader->json['file_type'];
				
		switch ( true ) {
			
			case ($ftype == 0 || $ftype == 1 ) :	// Image
			
				$ret = $this->psUploader->processUpload($this->psUploader->g
					, "", false, 'bwbps_uploadfile');
						
				break;
				
			default :
				
				break;
		}
		
		return $ret;
	}
	
	
	/*	Step 5:		Save Image/Upload to Database
	 *				You should make any tweaks to database fields through the JSON variable before this
	*/
	function saveUploadToDBStep(){
		
		$ret = $this->psUploader->saveImageToDB($this->psUploader->g, true);
		
		return $ret;
		
	}
	
	
	/*	Step 6:		Add File to WP Media Library
	 *				
	*/
	function addToWPMediaLibrary(){
		
		if( $this->psUploader->psOptions['add_to_wp_media_library'] ){
			return $this->psUploader->addToWPMediaLibrary();
		}
		
		return false;
	}
	
	
}	// Closes class
}	// Closes the if( !class_exists )

?>