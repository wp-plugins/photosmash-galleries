<?php

/*  AJAX UPLOAD Controller file
 *	
 *	This file is designed to handle the uploading of images.
 *	It also allows you to plug in your own functionality, 
 *	while making the standard functionality available for you to use
 *	in your own code.
 *
*/


if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
	require_once("../../../wp-admin/includes/image.php");
	require_once("../../../wp-admin/includes/file.php");
	require_once("../../../wp-includes/media.php");
	require_once("../../../wp-includes/post.php");
} 
 
require_once(WP_PLUGIN_DIR . "/photosmash-galleries/bwbps-wp-uploader.php");


//
//	A little class to wrap up the standard upload functionality
//
class BWBPS_AJAXUpload{
	
	var $psUploader;
	var $allowNoImg = false;
	
	function BWBPS_AJAXUpload($psOptions, $_allowNoImg=false){
	
		$this->psUploader = new BWBPS_Uploader($psOptions);	
		$this->psUploader->upload_agent = 'user';	// This tells everybody where this image is coming from
		$this->allowNoImg = $_allowNoImg;
		
	}
	
	/*
	 *	Following 3 functions are steps in the upload process.
	 *	They're broken out so that developers can do stuff between them if needed
	 *	Use processUpload() if you want to run all 3 steps automatically
	*/
	
	/*	Steps 1-3:		Prepare Upload - fills up the JSON variable with the image variables
	 *				You should make any adjustments to the JSON variable after this
	*/
	function prepareUploadStep($fileInputNumber=""){
		
		//Verify User Rights
		$this->psUploader->verifyUserRights($this->psUploader->g);	//will exit if not enough rights.
		
		// Fills up JSON array with image settings
		$this->psUploader->getImageSettings($this->psUploader->g);

		//Set the "handle" object to the uploaded file
		$this->psUploader->getFileType($fileInputNumber);  //Takes a param for file field # (blank or 2 are presets)
	
		
	}
	
	/*	Step 4:		Process Upload file & thumb
	*/
	function processUploadStep($fileInputNumber="", $processThumbnail = true)
	{
			
		//Processing the Uploaded file - if file type is set then it's not an
		//image, so you process it using the processDocument 
		
		$ftype = (int)$this->psUploader->json['file_type'];
		
		//Remove me!!!
		//$this->allowNoImg = true;
		
		switch ( true ) {
			
			case ($ftype == 0 || $ftype == 1 ) :	// Image
			
				$ret = $this->psUploader->processUpload($this->psUploader->g
					, $psNewImageName, $this->allowNoImg);
						
				break;
			
			case ( $ftype == 2 || $ftype == 3 ) : //Direct Link, YouTube
				
				$ret = true;
				
				break;
			
			default :
				
				break;
		}
		
		return $ret;
	}
	
	
	/*	Step 5:		Save Image/Upload to Database
	 *				You should make any tweaks to database fields through the JSON variable before this
	*/
	function saveUploadToDBStep($saveCustomFields = true){
		$saveCustomFields = true;
		$ret = $this->psUploader->saveImageToDB($this->psUploader->g, $saveCustomFields);
		
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
	
	
	/*
	 *  Process Upload File
	 *	All-in-one function for Prepare Upload and Saving Image to Database
	*/
	function processUpload($fileInputNumber="", $processThumbnail = true, 
		$saveCustomFields = true)
	{
		//So, this is legacy...we always want to save custom fields now
		$saveCustomFields = true;
		//Step 1 -3
		$this->prepareUploadStep($fileInputNumber);
		
		//Step 4
		$processStatus = $this->processUploadStep($fileInputNumber, $processThumbnail);
				
		//Step 5
		if($processStatus){ 
		
			$image_id = $this->saveUploadToDBStep($saveCustomFields); 
									
		}
		
		
		if( $image_id ){
		
			//Step 6 - Add image to Media Library if turned on
			$this->addToWPMediaLibrary();
			do_action('bwbps_upload_done', $this->psUploader->imageData);
			
			//Step 7 - Do Action 'bwbps_uploaded' - this triggers the create new post in PhotoSmash Extend
			//		 - it can also be called by other plugins or themes
			do_action('bwbps_uploaded');
		
		}
		
		return $image_id;
	}
	
	
	/*	Send Ajax Result - back to the browser
	 *	echos the JSON variable
	*/	
	function sendAjaxResult(){
		$this->psUploader->echoJSON();
	}
	
	function cleanUpAjax($echoJSON=true){
		$this->psUploader->cleanUpAjax($echoJSON);
	}
}


function getPhotoSmashOptions(){
	global $bwbPS;
	
	return $bwbPS->psOptions;

}

$bwbpsOptions = getPhotoSmashOptions();


//Check to see if Admin wants to use a custom script
if($bwbpsOptions['use_alt_ajaxscript'] ){
		
	if(file_exists(WP_PLUGIN_DIR.'/'.$bwbpsOptions['alt_ajaxscript'])){
		//Use Custom Script is turned on, and the file exist...load it...
		//   Note:  custom script must instantiate itself
		$bCustomScriptInUse = true;
		include_once(WP_PLUGIN_DIR.'/'.$bwbpsOptions['alt_ajaxscript']);

	}
}

if(!$bCustomScriptInUse){
	
	if(isset($_POST['bwbps_allownoimg']) && (int)$_POST['bwbps_allownoimg'] == 1){
		$bwbpsAllowNoImage = true;
	} else { $bwbpsAllowNoImage = false; }
		
	$bwbpsAjaxUpload = new BWBPS_AJAXUpload($bwbpsOptions, $bwbpsAllowNoImage);
	
	$bwbpsAjaxUpload->processUpload();
	$bwbpsAjaxUpload->sendAjaxResult();
	$bwbpsAjaxUpload->cleanUpAjax(false);
}

?>

