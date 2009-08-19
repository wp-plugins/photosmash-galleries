<?php

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}

$nonce=$_REQUEST['_wpnonce'];
if (! wp_verify_nonce($nonce, 'bwbps-image-rating') ) die('Security check');

if(!function_exists('json_encode')){
	require("classes/JSON.php");
}

$bwbpsuploaddir = wp_upload_dir();

define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");

class BWBPS_AJAXRateImage{
	
	var $psUploader;
	var $allowNoImg = false;
	
	var $psOptions;
	
	function BWBPS_AJAXRateImage(){
		
		$this->psOptions = $this->getPhotoSmashOptions();
		
		if(isset($_REQUEST['action']) && $_REQUEST['action']){
			$action = $_REQUEST['action'];
		
			switch ($action){
				
	
				case 'rateimage';
					$this->saveImageRating();
					break;
			
				default :
					break;
			}
		
		} else {
			die("Bad request");
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
	
	/*
	 * Set IMAGE RATING
	 *
	*/	
	function saveImageRating(){
		
		require_once('bwbps-rating.php');
		
		if(!$this->psOptions['rating_allow_anon'] && !is_user_logged_in()){
		
			echo "Not logged in";
			return;
		}
		
		if(isset($_POST['rating'])){
			$score = (int)$_POST['rating'];
			
			if( is_numeric($score) && $score <=5 && $score >=1 ){
				$rating = new BWBPS_Rating();
				$rating->set_score($score);
								
			} else {
				echo "Invalid score";
			}
		}	
		
	}
		


}

$bwbpsAjax = new BWBPS_AJAXRateImage();

?>