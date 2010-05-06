<?php

/*  API UPLOAD Controller file
 *	
 *	This file is designed to handle the uploading of images.
 *	It also allows you to plug in your own functionality, 
 *	while making the standard functionality available for you to use
 *	in your own code.
 *
*/

die(-1);

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
} 

//
//	A little class to wrap up the standard upload functionality
//



class Pixoox_API{
	
	var $psUploader;
	var $h; // variable to hold the Helpers class
	
	//Instantiate the Class
	function Pixoox_API(){
		global $bwbPS;
		
		require_once(WP_PLUGIN_DIR . "/photosmash/admin/pxx-helpers.php");
		
		$this->h = new PixooxHelpers();
		
		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
		}
		
		switch ($action){
		
			case 'request' :
			
				
				break;
			
			case 'posted' :
								break;
			
			case 'sendrequest' :
				$this->sendRequest();
				break;
				
			case 'getkey' :
				$this->getKey();
				break;
				
			default :
				break;
		
		}
		
		die();
		
	}
	
	function getKey(){
	
		
	
	}
	
	function sendRequest(){
	
		$url = "http://pixoox.com/api/request/";
		$post_data = array('action' => 'posted',
			'servername' => $_SERVER['SERVER_NAME']
		);
		
		
		echo $this->h->sendCURL($url, $post_data);
		
	}
	
}

$pixooxAPI = new Pixoox_API();

?>