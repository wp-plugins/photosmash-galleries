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
	1002 - 
	1003 - invalid api url
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
		
		switch ($pp_action){
		
			case 'upload' :
				$this->uploadImage();
				break;
				
			case 'viewphotos' :
				$this->viewPhotos();
				break;
				
			default :
				$this->exitAPI(1001);
				break;
		}
		
		return true;
		
	}
	
	function viewPhotos(){
		wp_redirect(site_url(), 301);
	}
	
	function setRetCodes(){
	
		$this->retcode[1001] = "Invalid action requested.";
		$this->retcode[1002] = "Invalid API url.";
		$this->retcode[1003] = "Invalid User ID or Password.";
		$this->retcode[1004] = "User not authorized to access paid content.";
		$this->retcode[1005] = "Timed out.";
		$this->retcode[1006] = "Invalid data type.";
		$this->retcode[1007] = "Data too large.";
		$this->retcode[1008] = "...";
		$this->retcode[1009] = "...";
		$this->retcode[1010] = "...";
		$this->retcode[1020] = "...";
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
				$json = PlumberHelpers::mergeArrays($json, $message);
			} else {
				$json['message'] = $message;
			}
			
		} else {
			
			$json['message'] = $this->retcode[$status];
		}

		die(json_encode($json));
	
	}
	
	function uploadImage() {
		
		global $wpdb;
		global $bwbPS;
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/bwbps-wp-uploader.php");
		
		$user_name = $_REQUEST['ps_username'];
		$pass = $_REQUEST['ps_pass'];
		
		$thisuser = $this->h->loginUser($user_name, $pass);
		
		global $current_user;
		
		$current_user = $thisuser;
		
		if( !$thisuser || !(int)$thisuser->ID ){
			$this->exitAPI(1003, "User name is: " . $user_name);	// invalid username or password
		}
		
		
		$gallery_id = (int)$this->psOptions['api_upload_gallery'];
		
		if( !$gallery_id ){
			
			$this->h->emailAdmin("PhotoSmash API Alert", "Mobile API upload failed:  upload gallery was invalid (id: "
				. (int)$this->psOptions['api_upload_gallery'] 
				. ").  Check your API settings and make sure you have set a valid gallery for the upload gallery.");
				
			$this->exitUpload(1005,"Server settings are incomplete.");
		}
						
		$this->psUploader = new BWBPS_Uploader($this->psOptions, $gallery_id, true);
		
		// prevent any JSON echo messages from using the <textarea></textarea>
		$this->psUploader->jquery_forms_hack = false;	
		
		$this->g = $this->psUploader->getGallerySettings($gallery_id);
		
		$this->psUploader->setGallery($this->g);
		
		$this->psUploader->upload_agent = 'mobile';	
		
		// This does the whole upload thing
		$image_id = $this->processUpload();

	
		
		//$j = json_decode( stripslashes($_REQUEST['ps_json']) );
		
		echo "Image uploaded: " .$image_id;
		die();
		
		$this->exitAPI(2000);
	
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
			if($this->pxxoptions['new_post_layout']){
				do_action( 'bwbps_api_uploaded', $this->pxxoptions['new_post_layout'], $this->pxxoptions['new_post_categories'], $this );
			}
		
		}
		
		return $image_id;
	
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
				
		$this->psUploader->json['post_id'] = $g->post_id;
		
		$this->psUploader->json['file_type'] = 0;
		
		$this->psUploader->json['image_caption'] = $this->psUploader->getImageCaption('caption');
		
		if( !empty($_POST['post_tags']) ){
			
			if(is_array(  $_POST['post_tags'] ) ){
				$bbpost_tags = implode(",", $_POST['post_tags']);
			} else {
				$bbpost_tags = $_POST['post_tags'];
			}
					
			$this->psUploader->json['post_tags'] = wp_kses($bbpost_tags, $tags[3]);
		}
		
		//Get URL
		$bwbps_url = $this->h->validURL($_POST['url']);
				
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