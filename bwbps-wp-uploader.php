<?php
/*  FUNCTIONS FOR UPLOADING AND SAVING IMAGES TO GALLERIES */

if(!function_exists('json_encode')){
	require("classes/JSON.php");
}

if(function_exists('check_ajax_referer') && !check_ajax_referer( "bwb_upload_photos" )){
	$json['message']= "Invalid authorization...nonce field missing.";
	$json['succeed'] = 'false'; 
	echo json_encode($json);
	exit();
};


//Set Database Table Constants
define("PSGALLERIESTABLE", $wpdb->prefix."bwbps_galleries");
define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSLAYOUTSTABLE", $wpdb->prefix."bwbps_layouts");
define("PSFIELDSTABLE", $wpdb->prefix."bwbps_fields");
define("PSLOOKUPTABLE", $wpdb->prefix."bwbps_lookup");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");

//Set the Upload Path
define('PSUPLOADPATH', WP_CONTENT_DIR .'/uploads');
define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSIMAGESPATH2',PSUPLOADPATH."/bwbps");
define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define('PSTHUMBSPATH2',PSUPLOADPATH."/bwbps/thumbs");
define('PSIMAGESURL',WP_CONTENT_URL."/uploads/bwbps/");
define('PSTHUMBSURL',PSIMAGESURL."thumbs/");
define("PSTABLEPREFIX", $wpdb->prefix."bwbps_");
define("PSTEMPPATH",PSUPLOADPATH."/bwbpstemp/");

require_once('admin/image-functions.php');

class BWBPS_Uploader{
	var $bwbpsCF;	//var to hold Save Custom Fields Class
	var $psOptions;	//var for Standard PS Options
	var $g;			//var for Gallery settings
	var $json;		//var for JSON that gets returned to browser
	var $user_level; //Does user have authorization to insert without moderation?
	var $handle;	//The magical object to handle uploads - the upload class
	var $imageNumber = "";	//
	var $imageData; //This gets populated with Image data on Image Save
	var $customData; //This gets populated with the custom fields data on Custom Field Save
	var $badImage; //Set to true if allowNoImage == false and file mime != image and file type = 0
	
	var $file;
	
	var $psImageFunctions; // Image Functions class
	
	/* 
	 * Constructor
	 *
	 */
	function BWBPS_Uploader($psOptions, $gallery_id=false){
		
		$this->psOptions = $psOptions;
		
		$this->psImageFunctions = new BWBPS_ImageFunc();
		
		if(!$gallery_id === false){
			$this->json['gallery_id'] = (int)$gallery_id;
		} else {
			$this->json['gallery_id'] = (int)$_POST['gallery_id'];
			if($this->json['gallery_id']){
				$this->g = $this->getGallerySettings($this->json['gallery_id']);
			}
			
		}
		
		$this->json['custom_callback'] = 0;
	}
	
	
	/*
	 *	Step 1:	Verify User rights
	 *
	*/	
	function verifyUserRights($g){
		
		if($g['contrib_role'] == -1){
			$this->user_level = true;
		} else {
			$this->user_level = current_user_can('level_'
				.$g['contrib_role']) || current_user_can('upload_to_photosmash') 
				|| current_user_can('photosmash_'.$g['gallery_id']) ? true : false;
			if(!user_level){
				if(current_user_can('upload_to_photosmash')){
					$this->user_level = true;
				}
			}
		}
		
		if(!$this->user_level){
			$this->json['message'] = "You do not have authorization for uploading to this gallery.";
			$this->json['succeed'] = "false";
			$this->echoJSON();
			exit();
		}
		//Determine if user is author above...if so, no moderation
		$this->user_level = current_user_can('level_2');
	}
	
	
	/*	
	 *	STEP 2:   Get Image Settings
	 *
	*/
	
	function getImageSettings($g)
	{
		$tags = $this->getFilterArrays();
		
		$this->json['succeed'] = 'false'; 
		$this->json['size'] = (int)$_POST['MAX_FILE_SIZE'];
		
		$this->json['form_name'] = esc_attr(wp_kses($_POST['bwbps_formname'], $tags[3]));
		
		$this->json['post_id'] = (int)$_POST['bwbps_post_id'];
		
		$this->json['file_type'] = (int)$_POST['bwbps_file_type'];
		
		$this->json['image_caption'] = $this->getImageCaption();
		
		if(isset($_POST['bwbps_post_tags'])){
		
			if(is_array($_POST['bwbps_post_tags'])){
				$bbpost_tags = implode(",", $_POST['bwbps_post_tags']);
			} else {
				$bbpost_tags =  $_POST['bwbps_post_tags'];
			}
		
			$this->json['post_tags'] = wp_kses($bbpost_tags, $tags[3]);
		}
		
		if(isset($_POST['bwbps_img_attribution'])){
			$this->json['img_attribution'] = wp_kses($_POST['bwbps_img_attribution'], $tags[3]);
		}
		
		$this->json['img_license'] = (int)$_POST['bwbps_img_license'];
		
		//$this->json['image_caption'] = htmlentities($this->json['image_caption'], ENT_QUOTES);
		
		//Get URL
		$bwbps_url = esc_url_raw($_POST['bwbps_url']);
				
		if($this->psValidateURL($bwbps_url)){
			$this->json['url'] = $bwbps_url;
		} else {
			$this->json['url'] = '';//$bwbps_url;
		}
		
		//Get Image/Thumbnail information for JSON results
		$this->json['img'.$this->imageNumber] = '';
		$this->json['imgrel'] = $g['img_rel'];
		$this->json['show_imgcaption'] = $g['show_imgcaption'];
		$this->json['thumb_width'] = $g['thumb_width'] < 12 ? 12 : $g['thumb_width'] + 4;
		$this->json['thumb_height'] = $g['thumb_height'] < 12 ? 12 : $g['thumb_height'] +4;
		//Image per row
		if($g['img_perrow'] && $g['img_perrow']>0){
				$this->json['li_width'] = floor((1/((int)$g['img_perrow']))*100);
		} else {
				$this->json['li_width'] = 0;
		}
	
	}
	
	/*	
	 *	STEP 3:   Get File Handle
	 *
	*/
	function getFileType($fileFieldNumber = ""){
				
		//Figure out the File Type that was uploaded
		if(isset($_POST['bwbps_filetype'.$fileFieldNumber])){
			$filetype = (int)$_POST['bwbps_filetype'.$fileFieldNumber];
		} else { 
			$filetype = 0; 
		}
		
		switch ($filetype){
			
			case 0 :	//Image upload
				$this->json['file_type'] = 0;
				break;
			
			case 1 :	//URL
				$this->json['file_type'] = 1;
				break;
			
			case 2 :	//Direct Link
				$this->json['img'.$this->imageNumber] = "0";
				$this->json['file_type'] = 2;
				$image_url = esc_url_raw($_POST['bwbps_uploaddl'.$fileFieldNumber]);
				
				if(!$this->psValidateURL($image_url)){
					$this->json['file_url'] = "";		
					$this->json['message'] = "Invalid URL.";
				} else {
					$this->json['file_url'] = $image_url;
				}
				$this->json['succeed'] = 'true';
				break;
				
			case 3 :	//YouTube
				$this->json['img'.$this->imageNumber] = "0";
				$this->json['file_type'] = 3;
				$image_url = $_POST['bwbps_uploadyt'];
				
				if(!$this->psValidateURL($image_url)){
					$this->json['file_url'] = "";
					$this->json['message'] = "Invalid URL.";
				} else {
					$this->json['file_url'] = $this->extractYouTubeKey($image_url);
					
					if( !$this->json['file_url'] ){
						$this->json['file_url'] = "";
						$this->json['message'] = "Invalid YouTube URL.";
						$this->json['succeed'] = 'false';
					} else {
					
						$this->json['succeed'] = 'true';
						
					}
					
				}
				
				break;
				
			case 4 :	//Video File
				$this->json['file_type'] = 4;
				break;
			
			case 5 :	//Image upload for File 2
				$this->json['file_type'] = 0;
				break;
				
			case 6 :	//URL for File 2
				$this->json['file_type'] = 0;
				$tempname = $this->importImageFromURL($fileFieldNumber);
				
				break;
			
			case 7 :	//General Document
				$this->json['file_type'] = 7;
				
				break;
		}
			
		return true;
	}

	
	/*	
	 *	STEP 4:   Process File Upload
	 *
	*/
	function processUpload($g, $fileFieldNumber = "", $allowNoImg=false){
			
		$uploads = wp_upload_dir();
	
		switch ( (int)$this->json['file_type'] ){
		
			case 0 :	// File is from a File Upload field
				$file = $this->processFileFromUpload($g, $uploads
					, $fileFieldNumber, $allowNoImg);
				break;
		
			case 1 :	// File is from URL
				$file = $this->processFileFromURL($fileFieldNumber, $allowNoImg);
				$this->json['file_type'] = 0;
				break;
		}
		
		if( !$file ){
			if(!$allowNoImg){
				$this->exitUpload("Invalid image file.");
				return false;
			}
			
			$this->json['succeed'] = "true";
			return true;
			
		}
		
		$relpath = $this->get_relative_path( $file['file'], $uploads );
		$basename = basename($file['file']);
				
		$this->json['image_url'] = $relpath . $basename;
		$this->json['image_name'.$this->imageNumber] = $basename;
		
		// Create Mini & Thumbnail & Medium sizes
		
		//Create THumb size
		$this->createResized($g, 'thumb', $file, $uploads, $relpath );
		
		//Set thumb_fullurl for JSON in callback back on the page
		$this->json['thumb_fullurl'] = $uploads['baseurl'] . '/'. $this->json['thumb_url'];
		
		//Create Mini Size
		$this->createResized($g, 'mini', $file, $uploads, $relpath );
		
		//Create Medium Size
		$this->createResized($g, 'medium', $file, $uploads, $relpath );
		
		$this->createResized($g, 'image', $file, $uploads, $relpath );
		$this->json['image_fullurl'] = $uploads['baseurl'] . '/' . $this->json['image_url'];
		
		$this->json['succeed'] = "true";
				
		$this->file = $file;
		
		return true;
	
	
	}
	
	/**
	 * Adapted from wp-includes/post.php
	 * 
	 * Used to update the file path of the attachment, which uses post meta name
	 * '_wp_attached_file' to store the path of the attachment.
	 *
	 * @since 2.1.0
	 * @uses apply_filters() Calls 'update_attached_file' on file path and attachment ID.
	 *
	 * @param int $attachment_id Attachment ID
	 * @param string $file File path for the attachment
	 * @return bool False on failure, true on success.
	 */
	function get_relative_path( $filepath, $uploads ) {
			
	
		// Make the file path relative to the upload dir
		if ( false === $uploads['error'] ) { // Get upload directory
			if ( 0 === strpos($filepath, $uploads['basedir']) ) {// Check that the upload base exists in the file path
					$ret = str_replace($uploads['basedir'], '', $filepath); // Remove upload dir from the file path
					$ret = ltrim($ret, '/');
					
					$ret = str_replace(basename($ret), '', $ret);
			}
		}
	
		return $ret;
	}
	
	/** 
	 * Get the Image File from a Uploaded File
	 * 
	 */
	function processFileFromUpload($g, $uploads, $fileFieldNumber = "", $allowNoImg=false){
				
		if(! is_writable($uploads['path']) ){
			$this->exitUpload("Uploads path is not writable: " . $uploads['path']);
			return;
		}
		
		
		$upload = $_FILES['bwbps_uploadfile'.$fileFieldNumber];
		
		if( empty($upload['tmp_name'] )){
			if(!$allowNoImg){
				$this->exitUpload("No file uploaded.");
			}
			return false;
		}
		
		//Deal with File Size too Large
		if((int)$this->psOptions['max_file_size'] > 0 && 
			(int)$this->psOptions['max_file_size'] < (int)$upload['size']){
			
			$maxfilesize = (int)$this->psOptions['max_file_size']/1000;
			$uploadedsize = (int)$upload['size']/1000;
		
			$this->exitUpload(" ***  File too Large  *** 
				Maximum allowed file size is: " 
				. round($maxfilesize,1) . "kB --- Uploaded file size: "
				.round($uploadedsize,1) . "kB");
		
		}
		
		// Handle the uploaded file
		$file = $this->handle_image_upload($upload);
	
		return $file;
	
	}
	
	
	/** 
	 * Get the Image File from a URL
	 * 
	 */
	function processFileFromURL($fileFieldNumber, $allowNoImg=false){
					
		$file_url = esc_url_raw( $_POST['bwbps_uploadurl'.$fileFieldNumber] );	
		
		if(!$this->psValidateURL($file_url)){
			if(!$allowNoImg){
				$this->exitUpload("Invalid URL.");
			}
			return false;		
		}
		
		if (!empty($file_url) ) {
		
			$file_array['name'] = basename($file_url);
		
			//Download the file using the Snoopy HTTP class
			$tmp = $this->importImageFromURL($file_url); //download_url($file_url);
			$file_array['tmp_name'] = $tmp;
		
			if ( is_wp_error($tmp) ) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
				$this->exitUpload("Downloading the URL failed.");
			}
			
			$overrides = array('test_form'=>false);
			$file = wp_handle_sideload($file_array, $overrides);
			
			
			
			if ( isset($file['error']) ) {
				$this->exitUpload("<span style='color: red;'>File upload_error:</span><p>" . $file['error'] ."</p>");
			}
	
		} else {
		
			if(!$allowNoImg){
				$this->exitUpload("Invalid URL.");
			}
			return false;
		
		}
	
		return $file;
	}
	
	
	/* 
	 * Get the Temporary File Name of the Uploaded (or URL inserted) File
	 * --- use this filename in the creation of the New Upload Class instance
	 */
	function importImageFromURL($image_url){	
					
		$tempname = wp_tempnam($url);

		
		/* *************  Gets an Image from a URL   *************** */
		
		$ch = curl_init();
		$timeout = 0;
		curl_setopt ($ch, CURLOPT_URL, $image_url);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);	
		
		// Getting binary data
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);	
		
		$image = curl_exec($ch);
		curl_close($ch);
		
		$fp = fopen($tempname,'w');
		fwrite($fp, $image);
		fclose($fp);
		
		return $tempname;
	}
	
	
	function createResized( $g, $size, $file, $uploads, $relpath ){
		$resized = image_make_intermediate_size( $file['file'],
			$g[$size.'_width'], $g[$size.'_height'], !$g[$size.'_aspect']  );
			
		if( $resized ){
		
			$this->json[$size.'_url'] = $relpath . $resized['file'];
		
		} else {
			
			//We didn't need to resize it, so just use the same image
			$this->json[$size.'_url'] = $this->json['image_url'];
		
		}
	
	}
	
	function handle_image_upload($upload){
		
		
		//Check if is image
		if ( file_is_displayable_image( $upload['tmp_name'] ) ){
			
			//handle the uploaded file
			$overrides = array(
				'test_form' => false,
			);
			
			$file = wp_handle_upload($upload, $overrides);
		
		}
		
		return $file;
	
	}
	
		
	
	function exitUpload($msg){
	
		$this->json['message'] = $msg;
		$this->json['succeed'] = "false";
		$this->echoJSON();
		exit();
	
	}
	
	/*	
	 *	STEP 5:   Save Image to the Database
	 *
	*/
	function saveImageToDB($g, $bSaveCustomFields=true){
		global $current_user;
		global $wpdb;
		
			
		$data['user_id'] = (int)$current_user->ID;
		$data['gallery_id'] = (int)$this->json['gallery_id'];
		$data['comment_id'] = -1;
		$data['post_id'] = (int)$this->json['post_id'];
		
		$data['image_name'] = $this->json['image_name'.$this->imageNumber];
		$data['image_caption'] = $this->json['image_caption'];
		$data['img_attribution'] = $this->json['img_attribution'];
		$data['img_license'] = (int)$this->json['img_license'];
		
		$data['url'] = $this->json['url'];
		$data['file_name'] = $this->json['file_name'.$this->imageNumber];
		
		$data['file_type'] = (int)$this->json['file_type'];
		
		$data['file_url'] = $this->json['file_url'];
		
		// Add the 3 image URLs
		$data['mini_url'] = $this->json['mini_url'];
		$data['thumb_url'] = $this->json['thumb_url'];
		$data['medium_url'] = $this->json['medium_url'];
		$data['image_url'] = $this->json['image_url'];
		
		if($this->user_level){
			$data['status'] = 1;
		}else{
			if($g['img_status'] == 1){
				$data['status'] = 1;
			} else {
				$data['status'] = -1;
				$this->json['message'] = "<span style='color:red;'>Submission is awaiting moderation.</span>";
			}
		}
		
		if( $this->psOptions['alert_all_uploads'] == 1 || $data['status'] == -1 ) {
			$data['alerted'] = 0;
		} else {
			$data['alerted'] = 1;
		}
		$data['updated_by'] = $current_user->ID;
		$data['created_date'] = date( 'Y-m-d H:i:s');
		
		//Meta/Exif
		$data['meta_data'] = '';
		$data['geolong'] = 0;
		$data['geolat'] = 0;
		
		$data['seq'] = -1;
		$data['avg_rating'] = 0;
		$data['rating_cnt'] = 0;
			
		//Insert the image into the Images table
		$this->json['db_saved'] = (int)$wpdb->insert(PSIMAGESTABLE, $data);
		
		if( !$this->json['db_saved'] )
		{
			$this->json['message'] = "<span class='error'>Failed to save image to Database.</span>";
			$this->json['succeed'] = "false";
		}
		
		$image_id = $wpdb->insert_id;
		
		$data['image_id'] = $image_id;		
		$this->json['image_id'] = $image_id;
		
		//Save image tags
		if($this->json['post_tags']){
			$this->json['post_tags'] = $this->cleanImageTags($this->json['post_tags']);
			$data['post_tags'] = $this->json['post_tags'];
			$this->saveImageTags($image_id, $data['post_tags']);
		}
		
		//Expose the Image Data to external classes
		$this->imageData = $data;
		
		if($image_id && $bSaveCustomFields){
			$this->saveCustomFields($image_id);
		}
		
		$this->imageData['user_login'] = $current_user->user_login;
		$this->imageData['display_name'] = $current_user->display_name;
		$this->imageData['user_nicename'] = $current_user->user_nicename;
		$this->imageData['user_url'] = $current_user->user_url;
		
		//Trigger for up the Upload Alert Email
		if($image_id){
			if( $this->psOptions['img_alerts'] == -1 ) {
				$this->sendNewImageAlerts();
			} else {
				update_option('BWBPhotosmashNeedAlert', 1);
			}
		}
		
		$this->psImageFunctions->updateGalleryImageCount($data['gallery_id']);
		
		return $image_id;
	}
	
	
	function cleanImageTags($tags){
	
		$ret = stripslashes($tags);
		$ret = str_replace("\\n", ",", $ret);
		$ret = str_replace("\\t", ",", $ret);
		$ret = str_replace(";", ",", $ret);
		$ret = str_replace("'", "", $ret);
		$ret = str_replace('"', "", $ret);
		$ret = str_replace(">", "", $ret);
		$ret = str_replace("<", "", $ret);
		$ret = esc_attr($ret);
		return $ret;
	}
	
		
	/*
	 *	Save image tags
	*/
	function saveImageTags($image_id, $tags){
	
		global $wpdb;
		
		if(!(int)$image_id || !$tags){ return; }
		
		$t = is_array($tags) ? $tags : explode( ',', trim($tags, " \n\t\r\0\x0B,") );
		wp_set_object_terms($image_id, $t, 'photosmash', false);
		
		return;
		
		$data['image_id'] = (int)$image_id;
		$data['category_id'] = 0;
		
		$sql = $wpdb->prepare("DELETE FROM " . PSCATEGORIESTABLE
			. " WHERE image_id = %d AND category_id = 0", $image_id);
			
		$wpdb->query($sql);		 
		 
		
		foreach($t as $tag){
			
			$data['tag_name'] = $tag;
			$wpdb->insert(PSCATEGORIESTABLE, $data);
		
		}
		
	}
	
	
	/*	
	 *	STEP 6:   Add Image to WP Media Library
	 *
	*/
	function addToWPMediaLibrary(){
	
		if(!$this->file || !$this->json['image_id'] ){ return false; }
		
		global $wpdb;
		
		$attachment = array
		(
			'post_mime_type' => $this->file['type'],
			'guid' => $this->file['url'],
			'post_parent' => (int)$this->json['post_id'],
			'post_title' => $wpdb->escape( $this->json['image_caption'] ),
			'post_content' => $wpdb->escape( $this->json['image_caption'] )
		);
				
		//insert post attachment
		$attach_id = wp_insert_attachment( $attachment, $this->file['file'], 
			(int)$this->json['post_id'] 
		);
		
		//update meta data
		if( !is_wp_error($attach_id) ){
		
			$attach_data = wp_generate_attachment_metadata( $attach_id, $this->file['file'] );
			wp_update_attachment_metadata( $attach_id,  $attach_data );
			
						
			// Add the Attachment ID to the PS Image record
			$meta = wp_get_attachment_metadata($attach_id);		//Get the Exif data
			
			$data = array( 'wp_attach_id' => $attach_id, 'meta_data' => serialize($meta['image_meta']) );
			$where = array( 'image_id' => $this->json['image_id'] );
						
			$wpdb->update( PSIMAGESTABLE, $data, $where );
			
			$this->imageData['wp_attach_id'] = $attach_id;
					
		} else {
			return false;
		}
		
		return true;
		
	}
	

		
	function extractYouTubeKey($ytURL){
		
		//preg borrowed from SmartYouTube plugin by Vladimir Prelovac
		preg_match_all("/http:\/\/([a-zA-Z0-9\-\_]+\.|)youtube\.com\/watch(\?v\=|\/v\/)([a-zA-Z0-9\-\_]{11})([^<\s]*)/", $ytURL, $matches, PREG_SET_ORDER);
		
		if(is_array($matches)){
			$ret = $matches[0][3];
		}
		
		return $ret;
	}
	
	
	/* 
	 * Set Custom Callback in JSON - 
	 * @param $useCustomCallback - true or false
	 */
	 function setCustomCallback($useCustomCallback){
	 	
	 	if($useCustomCallback){
	 		$this->json['custom_callback'] = 1;	
	 	} else {
	 		$this->json['custom_callback'] = 0;
	 	}
	 }
	
	
	function getImageSizeOptions($g){
		
		//image sizing
		if($g['image_width'] || $g['image_height']){
			if(!$g['image_width'] || $this->handle->image_src_x < $g['image_width']){
				$g['image_width'] = $this->handle->image_src_x;
			}
			if(!$g['image_height'] || $this->handle->image_src_y < $g['image_height']){
				$g['image_height'] = $this->handle->image_src_y;
			}
			
			//Figure out whether aspect is to be kept or cropped
			$this->handle->image_resize = true;
			if($g['image_aspect'] == 1){
				$this->handle->image_ratio = true;
			} else {
				$this->handle->image_ratio_crop = true;	
			}
			
			if($g['image_width']){
				$this->handle->image_x = $g['image_width'];
			}
			
			if($g['image_height']){
				$this->handle->image_y = $g['image_height'];
			}
			
		}
	
	
	}
	
	
	
	
	
	
	
	/* 
	 * Set Gallery Variable - 
	 * @param $g - a gallery array
	 */
	function setGallery($g){
		$this->g = $g;
		$this->json['gallery_id'] = (int)$this->g['gallery_id'];
	}
		
	function getGallerySettings($gallery_id){
		global $wpdb;
		
		if(!$gallery_id){
			$gallery_id = (int)$this->json['gallery_id'];
		}
		
		if($gallery_id){
			$g = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".PSGALLERIESTABLE
					." WHERE gallery_id = %d", $gallery_id), ARRAY_A);
		}

		return $g;
	}

	function psValidateURL($url)
	{
			return ( ! preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url)) ? FALSE : TRUE;
	}
	
	
	
	
	
	
	function getHandleSettings(){
		$this->handle->file_max_size = 5000000;

		$this->handle->file_auto_rename = true;
		$this->handle->dir_auto_chmod = true;
		$this->handle->dir_chmod = 0755;
		$this->handle->auto_create_dir = true;
		$this->handle->jpeg_quality = 100;
		
		//deal with different file types
		switch ((int)$this->json['file_type']){
			case 0 : 	//Image
				$this->handle->allowed = array('image/*');
				$this->handle->forbidden = array('application/*');
				break;
				
			case 1 :	//Image imported by URL
				$this->json['file_type'] = 0;
				$this->handle->allowed = array('image/*');
				$this->handle->forbidden = array('application/*');
				break;
				
			case 2 :	//Direct Link
				$this->handle->forbidden = array('*/*');
				break;
				
			case 3 :	//YouTube Link
				$this->handle->forbidden = array('*/*');
				break;
				
			case 4 :	//Video files
				$this->handle->allowed = 
					array( 'video/*'
					, 'application/x-shockwave-flash'
					, 'application/x-msmetafile'
					, 'audio/*'
				);
				break;
			
			case 5 :	//Image2 - Should have been changed to 0 in Get Handle
				$this->json['file_type'] = 0;
							
			case 6 :	//Image 2 URL - Should have been changed to 0 in GetHandle
				$this->json['file_type'] = 0;
				
			case 7 :	//General documents
				$this->handle->allowed = 
					array( 'application/pdf' );
				break;
			
			case 10 :	//PDF
				$this->handle->allowed = 
					array( 'application/pdf' );
				break;
				
			default :
				$this->handle->allowed = array('image/*');
				$this->handle->forbidden = array('application/*');
				break;				
		}
		
		$this->handle->mime_magic_check = true;
		
	}
	
	function getNewImageName($name = ""){
		//Create new name for Image
		if($name){$name = "_".$name;}
		return strtotime("now").$name;
	}
	
		
	
	function getImageCaption(){
		//Get Caption
		//For some reason, img_caption doesn't always carry the value & vice versa
		if(!$_POST['bwbps_imgcaption']){
			$json = $this->stripSlashes($_POST['bwbps_imgcaptionInput']);   
		} else {
			$json = $this->stripSlashes($_POST['bwbps_imgcaption']);   
		}
		
		$tags = $this->getFilterArrays();
		
		return wp_kses($json, $tags[0]);
	}
	
	function stripSlashes($val){
		if(get_magic_quotes_gpc()){
				$val = stripslashes($val);
		}
		return $val;
	}
		
	
	function saveCustomFields($image_id){
		//If USE_CUSTOMFIELDS is set in PS Options, then Save Custom Field data
		//if($image_id && $this->psOptions['use_customfields']){
		//if($this->psOptions['use_customfields']){
			if(!isset($this->bwbpsCF)){
				require_once("bwbps-savecustomfields.php");
			}	
			$this->bwbpsCF = new BWBPS_SaveCustomFields();
			$this->customData = $this->bwbpsCF->saveCustomFields($image_id);
			if(is_array($this->customData) && is_array($this->imageData)){
				$this->imageData = array_merge($this->imageData, $this->customData);
			}
		//}	
	}
	
	//Send email alerts for new images
	function sendNewImageAlerts()
	{
		global $wpdb;
				
		if( !$this->psOptions['alert_all_uploads'] ){
			
			$sqlStatus = " AND status = -1 " ;
			$msgStatus = " awaiting moderation.";
		
		}
		
		$sql = "SELECT * FROM ".PSIMAGESTABLE." WHERE alerted = 0 $sqlStatus ;";
		$results = $wpdb->get_results($sql);
		if(!$results) return;
		
		$ret = get_bloginfo('name')." has ". $results->num_rows. " new photos". $msgStatus. ".  Select the appropriate gallery or click image below.<p><a href='".get_bloginfo('url')
		."/wp-admin/admin.php?page=managePhotoSmashImages'>".get_bloginfo('name')." - PhotoSmash Photo Manager</a></p>";
		
		
		$ret .= "<table><tr>";
		$i = 0;
		
		$uploads = wp_upload_dir();
		
		foreach($results as $row)
		{
		
			if( !$row->thumb_url ){
				
					$row->thumb_url = PSTHUMBSURL.$row->file_name;
			
			} else {
				$row->thumb_url = $uploads['baseurl'] . '/' . $row->thumb_url;
			
			}
		
			$ret .= "<td><a href='".get_bloginfo('url')
		."/wp-admin/admin.php?page=managePhotoSmashImages&psget_gallery_id=".$row->gallery_id."'><img src='".$row->thumb_url."' /><br/>gallery id: ".$row->gallery_id."</a></td>";
			$i++;
			if($i==4){
				$ret .="</tr><tr>";
				$i=0;
			}
		}
		$ret .="</tr></table>";
		$admin_email = get_bloginfo( "admin_email" );
		
 		$headers = "MIME-Version: 1.0\n" . "From: " . get_bloginfo("site_name" ) ." <{$admin_email}>\n" . "Content-Type: text/html; charset=\"" . get_bloginfo('charset') . "\"\n";
 		
 		wp_mail($admin_email, "New images for moderation", $ret, $headers );
		$this->psOptions['last_alert'] = time();
		
		update_option($this->adminOptionsName, $this->psOptions);
		update_option('BWBPhotosmashNeedAlert',0);
		
		$data['alerted'] = -1;
		$where['alerted'] = 0;
		$wpdb->update(PSIMAGESTABLE, $data, $where);
		
	}
	
	
	/*
	 *	Create New Gallery
	 *
	 */
	//function createNewGallery($gallery_name, $post_id=0, $image_status=false)
	function createNewGallery($data = false)
	{
		global $wpdb;
		
		if(!is_array($data)){
			unset($data);
			$data = array('empty'=> true);
		}
		
		//This section saves Gallery specific settings
			$d['gallery_name'] = $data['gallery_name'] ? $data['gallery_name'] : "";
			$d['gallery_type'] = isset($data['gallery_type']) ? (int)$data['gallery_type'] : 0;
			$d['caption'] = $data['gallery_name'] ? $data['gallery_name'] : "";
			$d['post_id'] = $data['post_id'] ? (int)$data['post_id'] : 0;
			
			$d['img_perpage'] = $data['img_perpage'] ? (int)$data['img_perpage'] : (int)$this->psOptions['img_perpage'];
			$d['img_perrow'] = isset($data['img_perrow']) ? (int)$data['img_perrow'] : (int)$this->psOptions['img_perrow'];
			
			$d['thumb_aspect'] = isset($data['thumb_aspect']) ? (int)$data['thumb_aspect'] : (int)$this->psOptions['thumb_aspect'];
			$d['thumb_width'] = isset($data['thumb_width']) ? (int)$data['thumb_width'] : (int)$this->psOptions['thumb_width'];
			$d['thumb_height'] = isset($data['thumb_height']) ? (int)$data['thumb_height'] : (int)$this->psOptions['thumb_height'];
			
			$d['medium_aspect'] = isset($data['medium_aspect']) ? (int)$data['medium_aspect'] : (int)$this->psOptions['medium_aspect'];
			$d['medium_width'] = isset($data['medium_width']) ? (int)$data['medium_width'] : (int)$this->psOptions['medium_width'];
			$d['medium_height'] = isset($data['medium_height']) ? (int)$data['medium_height'] : (int)$this->psOptions['medium_height'];
			
			$d['image_aspect'] = isset($data['image_aspect']) ? (int)$data['image_aspect'] : (int)$this->psOptions['image_aspect'];
						
			$d['image_width'] = isset($data['image_width']) ? (int)$data['image_width'] : (int)$this->psOptions['image_width'];
			
			$d['image_height'] = isset($data['image_height']) ? (int)$data['image_height'] : (int)$this->psOptions['image_height'];
			
			$d['img_rel'] = $data['img_rel'] ? $data['img_rel'] : $this->psOptions['img_rel'];
			
			$d['add_text'] = $data['add_text'] ? $data['add_text'] : 
				( $this->psOptions['add_text'] ? $this->psOptions['add_text'] : "Add Photos" );
			
			$d['upload_form_caption'] = $data['upload_form_caption'] ? $data['upload_form_caption'] : $this->psOptions['upload_form_caption'];
			
			$d['img_class'] = $data['img_class'] ? $data['img_class'] : $this->psOptions['img_class'];
			
			$d['anchor_class'] = $data['anchor_class'] ? $data['anchor_class'] : $this->psOptions['anchor_class'];
			
			$d['show_imgcaption'] = $data['show_imgcaption'] ? (int)$data['show_imgcaption'] : (int)$this->psOptions['show_imgcaption'];
			
			if(isset($data['nofollow_caption'])){
				$d['nofollow_caption'] = (int)$data['nofollow_caption'];
			} else {
				$d['nofollow_caption'] = isset($this->psOptions['nofollow_caption']) ? 1 : 0;
			}
			
			$d['img_status'] = isset($data['img_status']) ? (int)$data['img_status'] : (int)$this->psOptions['img_status'];
			
			$d['contrib_role'] = isset($data['contrib_role']) ? (int)$data['contrib_role'] : (int)$this->psOptions['contrib_role'];
			
			$d['use_customform'] = isset($data['use_customform']) ? (int)$data['use_customform'] : (isset($this->psOptions['use_customform']) ? 1 : 0);
			
			$d['use_customfields'] = isset($data['use_customfields']) ? $data['use_customfields'] : (isset($this->psOptions['use_customfields']) ? 1 : 0);
			
			$d['custom_formid'] = $data['custom_formid'] ? (int)$data['custom_formid'] : (int)$this->psOptions['custom_formid'];
			
			$d['layout_id'] = isset($data['layout_id']) ? (int)$data['layout_id'] : (int)$this->psOptions['layout_id'];
			
			$d['sort_field'] = isset($data['sort_field']) ? (int)$data['sort_field'] : (int)$this->psOptions['sort_field'];
			
			$d['sort_order'] = isset($data['sort_order']) ? (int)$data['sort_order'] : (int)$this->psOptions['sort_order'];
			
			$d['poll_id'] = isset($data['poll_id']) ? (int)$data['poll_id'] : (int)$this->psOptions['poll_id'];
			
			$d['rating_position'] = isset($data['rating_position']) ? (int)$data['rating_position'] : (int)$this->psOptions['rating_position'];
			
			//PhotoSmash Extend settings
			$d['pext_insert_setid'] = isset($data['pext_insert_setid']) ? (int)$data['pext_insert_setid'] : ((isset($psmashExtend->insertSets)) ? 
				$psmashExtend->insertSets->options['default_set'] : 0);
			
			//Save the gallery			
			$tablename = $wpdb->prefix.'bwbps_galleries';
			
			//Create new Gallery Record
			$d['created_date'] = date('Y-m-d H:i:s');
			$d['status'] = 1;
			if( $wpdb->insert($tablename,$d)){
				$d['gallery_id']= $wpdb->insert_id;
				return $d;
			} else {
				return false;
			}
	}
	
	//Update Gallery
	function updateGallery($gallery_id, $data){
		global $wpdb;
		
		$tablename = $wpdb->prefix.'bwbps_galleries';
		
		$where['gallery_id'] = $gallery_id;
		return $wpdb->update($tablename, $data, $where);
	}
	
	//Update Gallery
	function updateImage($image_id, $data){
		global $wpdb;
		
		$tablename = $wpdb->prefix.'bwbps_images';
		
		$where['image_id'] = $image_id;
		return $wpdb->update($tablename, $data, $where);
	}
	
	function cleanUpAjax($echojson=false){
		if($echojson){
			$this->echoJSON();
		}
	}
	
	function echoJSON(){
		global $bwbps_special_msg;
		global $bwbps_preview_id;
				
		
		
		if($bwbps_special_msg){
			$this->json['special_msg'] = ($bwbps_special_msg);
			$this->json['preview_id'] = $bwbps_preview_id;
		}
		
		$this->json = $this->cleanJS($this->json);
		
		//Echoes back the JSON Array for an Ajax Call
		echo json_encode($this->json);
	}
	
	function cleanJS($arr){
	
		if(is_array($arr)){
			foreach($arr as $key => $val){
				$newarr[$key] = strip_tags($val);
			}
		}
		
		return $newarr;
	}

		
	function getFilterArrays(){
	
		//Allowable tag arrays for use with wp_kses
	 	//Allow formatting
	 	 $tags[0] = array('b' => array());
		 $tags[1] = array(
			'abbr' => array(
				'title' => array()
				),
			'acronym' => array(
				'title' => array()
				),
			'code' => array(),
			'em' => array(),
			'strong' => array(),
			'b' => array(),
			'p' => array()
		);
		
		//Allow links and lists + formatting
	  	$tags[2] = array(
			'a' => array( 
				'href' => array(), 
				'title' => array(), 
				'rel' => array(),
				'target' => array(),
				'id'  => array(),
				'class' => array()
				),
			'ul' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
				), 
			'ol' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
				),
			'li' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
				), 
			'abbr' => array(
				'title' => array()
				),
			'acronym' => array(
				'title' => array()
				),
			'code' => array(),
			'em' => array(),
			'strong' => array(),
			'b' => array(),
			'div' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'p' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
				),
			'br' => array(),
			'hr' => array()
			
		);
		
		$tags[3] = array();
		return $tags;
	}
} 







?>