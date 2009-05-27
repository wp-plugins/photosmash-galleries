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
	
	function BWBPS_AJAXUpload($psOptions){
		$this->psUploader = new BWBPS_Uploader($psOptions);	
	}
	
	function processUpload($fileInputNumber=""){
		// Fills up JSON array with image settings
		$this->psUploader->getImageSettings($this->psUploader->g);

		//Set the "handle" object to the uploaded file
		$this->psUploader->getFileHandle($fileInputNumber);  //Takes a param for file field # (blank or 2 are presets)

		//Sets Handle Settings
		$this->psUploader->getHandleSettings();

		//Sets the new Image Name - 
		//Creates name as the time right now - takes a string param to append to name
		$psNewImageName = $this->psUploader->getNewImageName();


		if($this->psUploader->processMainImage($this->psUploader->g, $psNewImageName)){
		
			$ret = $this->psUploader->processThumbnail($this->psUploader->g, $psNewImageName);
			if($ret){
				return $this->psUploader->saveImageToDB($this->psUploader->g, true); //Will also call Save Custom Fields
			}
		}
		return false;
	}
	
	function sendAjaxResult(){
		$this->psUploader->echoJSON();
	}
	
	function cleanUpAjax($echoJSON=true){
		$this->psUploader->cleanUpAjax($echoJSON);
	}
}

function getPhotoSmashOptions(){
		$psOptions = get_option('BWBPhotosmashAdminOptions');
		if($psOptions && !empty($psOptions))
		{
			//Options were found..add them to our return variable array
			foreach ( $psOptions as $key => $option ){
				$opts[$key] = $option;
			}
		} else {
			$opts = false;
		}
		return $opts;
}

$psOptions = getPhotoSmashOptions();



//Check to see if Admin wants to use a custom script
if($psOptions['use_alt_ajaxscript'] ){
		
	if(file_exists(WP_PLUGIN_DIR.'/'.$psOptions['alt_ajaxscript'])){
		//Use Custom Script is turned on, and the file exist...load it...
		//   Note:  custom script must instantiate itself
		$bCustomScriptInUse = true;
		include_once(WP_PLUGIN_DIR.'/'.$psOptions['alt_ajaxscript']);

	}
}

if(!$bCustomScriptInUse){
	
	$bwbpsAjaxUpload = new BWBPS_AJAXUpload($psOptions);
	
	$bwbpsAjaxUpload->processUpload();
	$bwbpsAjaxUpload->sendAjaxResult();
	$bwbpsAjaxUpload->cleanUpAjax(false);
}

?>