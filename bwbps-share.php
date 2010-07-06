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
  
  var $sharing_options;
  
  var $h;	// pixoox helpers
  var $img_funcs;	// Image Functions
 
/*
* @url is the url we are sending the image to
* @post_data is the array of data which is to be uploaded
* @param2 if passed true will display result in the XML format, default is false
* @param3 if passed true will update status twitter,default is false
*/
 
  function BWBPS_Share()
  {
  	
  	global $bwbPS;
  	
  	$this->sharing_options = $bwbPS->sharing_options;
  
  	require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/pxx-helpers.php");
	$this->h = new PixooxHelpers();
	
	$this->img_funcs = $bwbPS->img_funcs;
	
  }
  
  
  /*	getSharingHubs()
   *	Gets the list of Activated Sharing Hubs we need to send image to
   *	If verification is required, it also only gets verified hubs
  */
  function getSharingHubs(){
  	
  	global $wpdb;
  	
  	$sql = "SELECT * FROM ". PSHUBSTABLE . " WHERE hub_status > 0;";
  	
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
  
  	$ret = new BWBPS_Msg();
  	
  	if( !(int)$image['image_id'] || $image['status'] < 1 ){ return; }
  	
  	if( $image['upload_agent'] != 'user' && $image['upload_agent'] != 'approval' ){ return; }
  
  	if(!isset($this->hubs)){
	  	$this->hubs = $this->getSharingHubs();
	}
  	  	
  	if( empty($this->hubs) ){ $this->hubs = false; return; }
  	
  	if(empty($this->post_data)){
	  	$this->post_data = $this->buildPostData($image);
	}
	  	
  	if(is_array($this->hubs)){
  		 	
  		foreach($this->hubs as $hub){
  			$hub->api_url = $this->h->validURL($hub->api_url);
  			if( !$hub->api_url ){

  				$ret->status = 1003;
  				$ret->message = "bad api url";
  				
  				$this->logImageAction($image['image_id'], $hub->hub_id, $hub->hub_name, $ret);
  				continue;
  			
  			}

  			$this->post_data['pixoox_key'] = $this->h->decrypt($hub->pixoox_key);
  			
  			if(!$this->post_data['pixoox_key'] && $this->hub->requires_signup){ 
  				
  				$ret->status = 1004;
  				$ret->message = "bad pixoox key";
  				
  				$this->logImageAction($image['image_id'], $hub->hub_id, $hub->hub_name, $ret);
  				continue;
  				
  			}
  			
  			// And now we send the Image via CURL
  			unset($ret);
	  		$response = $this->h->sendCURL($hub->api_url, $this->post_data);	  			  		
	  			  	
			/*
				Return codes:
				1001 - invalid nonce
				1002 - invalid site url
				1003 - invalid api url
				1004 - invalid pixoox key
				1005 - server settings are incomplete
				1006 - unable to create user name for site url
				1007 - User not authorized to upload to gallery
				1008 - site has not been authorized for sharing
				1010 - image timed out
				1020 - invalid image type
				1030 - image too large
				2000 - ok
			*/
 			
  			if($response){ 
  				$response = json_decode($response); 
				
				if( is_object( $response ) ){
					$ret = $response;
					$ret->status = $response->status;
					$ret->message = $response->message;
				}
				
  			} 
			
			if( empty($ret->status) )
			{
  				
  				$ret->status = 1010;
  				$ret->message = 'timed out';
  			}

  			
  			$ret->hub_id = $hub->hub_id;
  			$ret->hub_name = $hub->hub_name;
  			
  			$this->logImageAction($image['image_id'], $hub->hub_id, $hub->hub_name, $ret);
  		}
  	
  	}
  	
  	return;
 
  }  
  
  function logImageAction($image_id, $hub_id, $hub_name, $ret){
  	
  		global $wpdb;
  		
  		$data['image_id'] = (int)$image_id;
  		$data['hub_id'] = (int)$hub_id;
  		$data['hub_name'] = $hub_name;
  		
  		$data['status'] = (int)$ret->status;
  		$data['url'] = isset($ret->media_url) ? $this->h->validURL($ret->media_url) : "";
  		$data['message'] = isset($ret->message) ? $ret->message : "";
  		
  		$serialized = serialize($ret);
  		
  		$data['serialized'] = $serialized;
  		
  		$data['created_date'] = current_time('mysql',0);
  		
  		$wpdb->insert(PSSHARINGLOGTABLE, $data);
  
  }
  
  function buildPostData($image){
    
  	$p['url'] = $this->getLink($image);	// this is the URL the images will link to
  	
  	if($p['url']){
  	
  		$p['url'] = apply_filters( 'bwbps_sharing_url', $p['url']);
  	
  	}
  	 
  	$p['action'] 			= 'upload'; 	
  	$p['site_name'] 		= get_bloginfo('name');
  	$p['site_url']			= get_bloginfo('url');
  	$p['admin_email']		= get_bloginfo('admin_email');
	
	$p['post_tags']			= $this->getTags($image);
  	
  	$p['caption']			= wp_kses($image['image_caption'], array());
  	  	
  	// Get the Image File
  	$imageurls = $this->img_funcs->getImageDirs($image);
  	
  	switch ($this->sharing_options['send_size']){
  	
  		case 2 : 
  			$file = $imageurls['image_url'];
  			break;
  		case 1 : 
  			$file = $imageurls['thumb_url'];
  			break;
  		default : 
  			$file = $imageurls['medium_url'];
  			break;  	
  	}
  	  	
  	$p['media'] = "@".$file; //Be sure to prefix @, else it wont upload
  	
  	if($p['description'])
  	{	
  		$p['description'] = strip_tags($p['description']);
  		
  		if(strlen($p['description']) > 140){
  			$p['description'] = substr($p['description'],0,137) . "...";
  		}
  	}
  	
  	return $p;
  
  }
  
  function getTags($image){
  
  	// Get Photo Tags if this is an Approval generated share
	if( $image['upload_agent'] ){
  		$image['post_tags'] = $this->getImageTerms( $image['image_id'] );
  	}
  
  	if( !empty($image['post_tags']) ){
	
		$tags = $image['post_tags']; 		
  		
  	} else {
  	  		
  		$pid = (int)$image['post_id'] ? (int)$image['post_id'] : (int)$image['gal_post_id'];
  		
  		if($pid){
  			$gottags = get_the_tags($pid);
  			
  			if ( is_array($gottags) ){
		  		foreach ($gottags as $tag)
				{
					$tags[] = $tag->name;
				}
			}
			
			$gotcats = get_the_category($pid);
			
			if( is_array($gotcats) ){
			foreach ($gotcats as $cat)
				{
					$tags[] = $cat->cat_name;
				}
			}
  		}
  	}
  	
  	if( is_array($tags) ){ 	
		$tags = array_unique($tags);
		$tags = array_map("trim", $tags);
		$tags = wp_kses($tags, array());
		$tags = implode(",", $tags);
	}
  	
  	return $tags;
  
  }
  
  // This is used to get Photo Tags...Approval 'shares' do not contain the tags in the image array, so we go get them
  function getImageTerms( $image_id ){
  	
  	$argsarray = array('name');
  	$terms = wp_get_object_terms( $image_id, 'photosmash', $argsarray);
	$termlist = "";
	$termlist = get_the_term_list( $image_id, 'photosmash', '', ',' );
	return $termlist;

  }
  
  function getLink($image){
  	global $bwbPS;
  	
  	switch ((int)$this->sharing_options['images_url']) {
  	
  		case 3 :	// Specific Post/Page
  			if((int)$this->sharing_options['images_url_post_id']){
	  			$link = get_permalink( $this->sharing_options['images_url_post_id'] );
  			}
  			break;
  		
  		case 2:		// Gallery Viewer
  			
  			if((int)$bwbPS->psOptions['gallery_viewer']){  				
  				$link = get_permalink( (int)$bwbPS->psOptions['gallery_viewer'] );
  				if($link){
  					$link = add_query_arg('psmash-image', $image['image_id'], $link);
  				}
  			}
  			break;
  			
  		case 1:		// Image Posts
  			
  			global $psmashExtend;
  			
  			if( isset($psmashExtend) && $psmashExtend->options['new_posts'] ){
  				if((int)$image['post_id']){
  					$link = get_permalink( (int)$image['post_id'] );
	  			}
  			}
  			
  			break;
  			
  		case 0:	// Attachment / Gallery Post
  			
  			$link = $this->getDefaultLink( $image );
  			
  			break;
  	}
  	
  	if ( !$link ){
  		$link = $this->getDefaultLink( $image );
  	}
  	
  	
  	return $link;
  
  }
  
  function getDefaultLink($image){
  	
  		// Send the Attachment Page link if at all possible
		if((int)$image['wp_attach_id']){
			
			$link = get_attachment_link( (int)$image['wp_attach_id'] );
			
			if( !$link ){
				$link = get_permalink((int)$image['wp_attach_id']);
			}
		}
		
		if( !$link && (int)$image['gal_post_id'] ){
			$link = get_permalink( (int)$image['gal_post_id'] );
		}
		
		if( !$link && (int)$image['post_id'] ){
  			$link = get_permalink( (int)$image['post_id'] );
	  	}
		
		if( !$link ){
			// Just send the plain old Image URL...I don't know what else to do
			$link = $image['image_url'];
		}
		
		return $link;
  	
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
} //class ends here

class BWBPS_Msg {
	
	var $status = 0;
	var $message = "";
	
	function BWBPS_Msg(){
	
	}

}
?>