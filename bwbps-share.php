<?php
// Class adapted from TwitPic class by Sachin Khosla from DigiMantra:  
// http://www.digimantra.com/technology/php/post-pictures-on-twitpic-api-using-php/

class BWBPS_Share
{
  /* 
   * variable declarations
   */
  var $url='';
  var $post_data='';
  var $result='';
  var $tweet='';
  var $return='';
  var $display = false;
  
  var $hubs;
 
/*
* @url is the url we are sending the image to
* @post_data is the array of data which is to be uploaded
* @param2 if passed true will display result in the XML format, default is false
* @param3 if passed true will update status twitter,default is false
*/
 
  function BWBPS_Share()
  {
  	$this->hubs = $this->getSharingHubs();
  	
  	if(!$this->hubs || !is_array($this->hubs)){ $this->hubs = false; return; }
  	
  }
  
  
  /*	getSharingHubs()
   *	Gets the list of Activated Sharing Hubs we need to send image to
   *	If verification is required, it also only gets verified hubs
  */
  	
  function getSharingHubs(){
  	
  	global $wpdb;
  	
  	$sql = "SELECT * FROM ". PSSHARINGHUBS . " WHERE hub_status > 0 AND (verification_status = 1 OR requires_verification = 0);";
  	
  	$res = $wpdb->get_results($sql);
  	
  	return $res;
  
  }
  
 /**
 * shareImage($image) - return a gallery  
 * 
 * @param object $image - the Newly uploaded or newly approved Image Object that we want to share
 * @return 
 */
  function shareImage($image)
  {
  
  	$this->post_data = $this->buildPostData($image);
  	
  	if(!empty($this->hubs) && is_array($this->hubs)){
  	
  		foreach($this->hubs as $hub){
  		
  			if(!$hub->api_url || !$this->verifyURL($hub->api_url) ){
  			
  				$this->logImageAction( $image->image_id . ": " . $hub->name . ' - bad URL: ' . $hub->api_url);
  			
  			}

  			if(!$hub->active){ continue; }

  			$this->post_data['pixoox_key'] = $this->getPixooxKey($hub->name);
  			if(!$this->post_data['pixoox_key']){ 
  				$this->logImageAction($image->image_id . ": " .$hub->name . ' missing pixoox key.');
  				continue; 
  			}
  			
  			$this->post($hub->api_url, $this->post_data);
  		
  		}
  	
  	}
    
    
    if(empty($this->post_data) || !is_array($this->post_data)) //validates the data
      $this->throw_error(0);
      
    $this->display = $displayXML;
 
  }
  
  function getPixooxKey($hub){
  
  	return get_option('pixoox_hub_' . $hub);
  
  }
  
  function logImageAction($msg){
  
  }
  
  function buildPostData($image){
  
  	$p['url'] = get_permalink($image['post_id']);	// this is the URL the images will link to...refine this
  	// Gallery Viewer is a possibility
  	// Allow Sharers to set up where they want each gallery to point...add to the Galleries table
  	
  	
  	$p['site_name'] 		= get_bloginfo('name');
  	$p['site_url']			= get_bloginfo('url');
  	$p['admin_email']		= get_bloginfo('admin_email');
  	$p['description']		= get_bloginfo('description');
  	
  	if($p['description'])
  	{
  		
  		$p['description'] = strip_tags($p['description']);
  		
  		if(strlen($p['description']) > 140){
  			$p['description'] = substr($p['description'],0,137) . "...";
  		}
  	
  	}
  	
  	$p['twitter_id'] = "";
  
  }
  
  function postToTwitPic($attach){
	
		// In Media upload, user can elect not to send a twitpic
		//...other uploads that add attachments go through always
		if(isset($_REQUEST['twitpicit_verified']) ){
			
			if(!isset($_REQUEST['twitpicit_post_image'])){
				return;
			} 
			
		}
		
		if(!isset($this->tpPost)){
			require_once('twitpic-it-post.php');
		}
			
		$tweet =  $this->_settings['twitpicit']['update_twitter'] ? true : false;
		
		$file='file_to_be_uploaded.gif';
		
		
		$postfields = array();
		 
		$postfields['username'] = $this->_settings['twitpicit']['twitter_username'];
		 
		$postfields['password'] = $this->_settings['twitpicit']['twitter_password'];
		
		
		//Get File Path and Post Permalink, etc
		$imgdata = $this->getImagePathFromAttachment($attach);
		
		$file = $imgdata['media'];
		
		if(!$file){ return false; }
		
		if(isset($_REQUEST['twitpicit_msg']) ){
			
			$postfields['message'] = wp_filter_kses( $_REQUEST['twitpicit_msg'] );
		
		} else {
		
			$postfields['message'] = stripslashes($this->_settings['twitpicit']['twitter_msg']);
		
		}
		
		$postfields['message'] = str_replace("[post_url]", $imgdata['permalink'], $postfields['message']);		
		$postfields['media'] = "@$file"; //Be sure to prefix @, else it wont upload
				 
		$t=new TwitPicItPost($postfields,false,$tweet);
		$xml = $t->post();
		
	}
 
  function post($url, $post_data)
  {
	return $this->makeCurl($url, $post_data);
  }
  
  function makeCurl($url, $post_data)
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 3);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);	//This causes the request to timeout and drive on
    
    $this->result = curl_exec($curl);
    
    curl_close($curl);
    
    /*
    
    // undo this comment and the timeout above if you want to sit around and wait for a response.
    // trouble is that with opening this up to everybody to be a sharing hub, 
    // we don't know if their servers will be fast or if they'll just upset users with slow/hung servers
    
    if($this->display)
    {
      header ("content-type: text/xml");
      echo $this->result ;
    }
    
	$res->mediaurl = $this->extractTaggedText($this->result, '<mediaurl>', '</mediaurl>' ) ;
	$res->mediaid = $this->extractTaggedText($this->result, '<mediaid>', '</mediaid>') ;
    //Requires PHP5
    if(function_exists('simplexml_load_string')){
    	//$res = simplexml_load_string($this->result);
    } else {
    	$res->mediaurl = $this->extractTaggedText($this->result, '<mediaurl>', '</mediaurl>' ) ;
    	$res->mediaid = $this->extractTaggedText($this->result, '<mediaid>', '</mediaid>') ;
    }
    
    return $res;
    */
    
    return true;
    
  }
  
  
  // NOT USED unless we remove the cURL timeout
  function extractTaggedText($xml,  $start, $end){
 
	$pos_start = strpos($xml,$start);
	$pos_end = strpos($xml, $end, ($pos_start + strlen($start)));
	if ( ($pos_start !== false) && ($pos_end !== false) )
	{
	$pos1 = $pos_start + strlen($start);
	$pos2 = $pos_end - $pos1;
	return substr($xml, $pos1, $pos2);
	}

  
  }
  
  function throw_error($code) //handles few errors, you can add more
  {
    switch($code)
    {
      case 0:
        echo 'Think, you forgot to pass the data';
        break;
      default:
        echo 'Something just broke !!';
        break;
    }
    exit;
  }
} //class ends here
 
?>