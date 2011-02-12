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
}
 
require_once("bwbps-uploader.php");

//
//	A little class to wrap up the standard upload functionality
//
class BWBPS_AJAXUpload{
	
	var $psUploader;
	var $allowNoImg = false;
	
	function BWBPS_AJAXUpload($psOptions, $_allowNoImg=false){
	
		$this->psUploader = new BWBPS_Uploader($psOptions);	
		$this->allowNoImg = $_allowNoImg;
		
	}
	
	/*
	 *	Following 3 functions are steps in the upload process.
	 *	They're broken out so that developers can do stuff between them if needed
	 *	Use processUpload() if you want to run all 3 steps automatically
	*/
	
	/*	Step 1:		Prepare Upload - fills up the JSON variable with the image variables
	 *				You should make any adjustments to the JSON variable after this
	*/
	function prepareUploadStep($fileInputNumber=""){
		
		//Verify User Rights
		$this->psUploader->verifyUserRights($this->psUploader->g);	//will exit if not enough rights.
		
		// Fills up JSON array with image settings
		$this->psUploader->getImageSettings($this->psUploader->g);

		//Set the "handle" object to the uploaded file
		$this->psUploader->getFileHandle($fileInputNumber);  //Takes a param for file field # (blank or 2 are presets)

		//Sets Handle Settings
		$this->psUploader->getHandleSettings();
		
	}
	
	/*	Step 2:		Process Upload file & thumb
	*/
	function processUploadStep($imagename = false, $processThumbnail = true)
	{
	
		//Sets the new Image Name - 
		//Creates name as the time right now - takes a string param to append to name
		
		if($imagename){
			$psNewImageName = $imagename;
		} else {
			$psNewImageName = $this->psUploader->getNewImageName();
		}
		
		//Processing the Uploaded file - if file type is set then it's not an
		//image, so you process it using the processDocument 
		
		$ftype = (int)$this->psUploader->json['file_type'];
		
		//Remove me!!!
		//$this->allowNoImg = true;
		
		switch ( true ) {
			
			case ($ftype == 0 || $ftype == 1 ) :	// Image
			
				$ret = $this->psUploader->processMainImage($this->psUploader->g
					, $psNewImageName, $this->allowNoImg);
				
				if($ret && $processThumbnail){
					$ret = $this->psUploader->processThumbnail(
						$this->psUploader->g, $psNewImageName, $this->allowNoImg);
				}
		
				break;
			
			case ( $ftype == 2 || $ftype == 3 ) : //Direct Link, YouTube
				
				$ret = true;
				
				break;
			
			case ( $ftype == 4 || $ftype == 7 || $ftype == 10) : // Video file
				
				$ret = $this->psUploader->processDocument($this->psUploader->g
					, $psNewImageName, $this->allowNoImg);
				
				break;
			
			default :
				
				break;
		}
		
			
		return $ret;
	}
	
	
	/*	Step 3:		Save Image/Upload to Database
	 *				You should make any tweaks to database fields through the JSON variable before this
	*/
	function saveUploadToDBStep($saveCustomFields = true){
		$ret = $this->psUploader->saveImageToDB($this->psUploader->g, $saveCustomFields);
		
		if($ret){ do_action('bwbps_uploaded'); }
		
		return $ret;
	}
	
	
	/*
	 *  Process Upload File
	 *	All-in-one function for Prepare Upload and Saving Image to Database
	*/
	function processUpload($fileInputNumber="", $imagename = false
		, $processThumbnail = true, $saveCustomFields = true)
	{
		
		//Step 1
		$this->prepareUploadStep($fileInputNumber);
		
		//Step 2
		$processStatus = $this->processUploadStep($imagename, $processThumbnail);
		
		//Step 3
		if($processStatus){ 
			$image_id = $this->saveUploadToDBStep($saveCustomFields); 
			
			do_action('bwbps_upload_done', array( $this->psUploader->imageData) );
			
			return $image_id;
			
		}
		
		
		return false;
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
		$bwbpsOptions = get_option('BWBPhotosmashAdminOptions');
		if($bwbpsOptions && !empty($bwbpsOptions))
		{
			//Options were found..add them to our return variable array
			foreach ( $bwbpsOptions as $key => $option ){
				$opts[$key] = $option;
			}
		} else {
			$opts = false;
		}
		return $opts;
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