<?php

if(!class_exists('PixooxHelpers'))
{

class PixooxHelpers {
	
	// Pixoox settings
	var $_settings;
	
	function PixooxHelpers() {

	}
	
	function loginUser($user_name, $pass){
		$creds = array();
		$creds['user_login'] = $user_name;
		$creds['user_password'] = $pass;
		
		$user = wp_signon( $creds, false );
		return $user;
	}
	
	
	function sendCURL($url, $post_data= false, $display=false, $timeout = 8){
		
		if(!$post_data){ $post_data = array(); }
				
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_POST, 3);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	    
	    if((int)$timeout){
	    	curl_setopt($curl, CURLOPT_TIMEOUT, (int)$timeout);	//This causes the request to timeout and drive on
	    }
	    
	    $result = curl_exec($curl);
	    curl_close($curl);
	    if($display)
	    {
	      header ("content-type: text/xml");
	      echo $result ;
	    }
	
		return $result;
	}
	

	function get_salt(){
	
		if(defined(AUTH_KEY)){
			$key = AUTH_KEY;
		} else {
			$key = 'MBsTe+G(oQPk<+:48Q!h3:y/gd0`%|&9>!Z90D8^MyLuw#+$$@lj/+n/Y&O3lL,<';
		}
		
		if(strlen($key) > 20){
			$key = substr($key, 0, 20);
		}
		
		return $key;
	
	}
	
	function encrypt($text) 
    { 
    	$salt = $this->get_salt();   	
    
        return trim(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salt, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)))); 
    } 

    function decrypt($text) 
    { 
    	
    	$salt = $this->get_salt();
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND))); 
    }
    
    //Validate URL
	function validURL($url, $raw=true)
	{
		if($raw){
			$url = esc_url_raw($url);
		} else {
			$url = esc_url($url);
		}
		
		if( preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url)){
			return $url;
		} else {
			return false;
		}
	}
	
	function compareDomains($url1, $url2){
	
		if(!$url1 || !$url2){ return false; }
		
		$d1 = $this->getDomainName($url1);
		$d2 = $this->getDomainName($url2);
		
		if(!$d1 || !$d2){ return false; }
		
		return ($d1==$d2);
	
	}
	
	function getDomainName($url){
	
		$url = $this->validURL($url);
		
		if($url){
			$_url = parse_url($url);
			return $_url['host'];
		}
		
		return false;
	
		/*
	
		// get host name from URL
		preg_match('@^(?:http://)?([^/]+)@i',
		    $url, $matches);
		$host = $matches[1];
		
		// get last two segments of host name
		preg_match('/[^.]+\.[^.]+$/', $host, $matches);
		return $matches[0];
		
		*/
		
	}
	
	function backgroundEcho($json){
		// buffer all upcoming output
		ob_start();
		echo json_encode($json);
		
		// get the size of the output
		$size = ob_get_length();
		
		// send headers to tell the browser to close the connection
		header("Content-Length: $size");
		header('Connection: close');
		
		// flush all output
		ob_end_flush();
		ob_flush();
		flush();

		/******** background process starts here ********/
	}
	
	/* 
	 *	Send New Image Alerts
	 *
	*/
	//Send email alerts for new images
	function emailAdmin($subject, $message)
	{
		
		$admin_email = get_bloginfo( "admin_email" );
		
 		$headers = "MIME-Version: 1.0\n" . "From: " . get_bloginfo("site_name" ) ." <{$admin_email}>\n" . "Content-Type: text/html; charset=\"" . get_bloginfo('charset') . "\"\n";
 		
 		wp_mail($admin_email, $subject, $message, $headers );
						
	}
	
	// Removed static modifier to get PHP4 compatibilty back
	function mergeArrays($base, $addon){
		if(is_array($base) && is_array($addon)){
			foreach ( $addon as $key => $option ){
				if(!$base[$key]){
					$base[$key] = $option;
				}
			}
		}
		return $base;
	}
	
	// Removed static modifier to get PHP4 compatibilty back
	function alphaNumeric( $string, $spaces = true, $under = false )
    {
		// Format for element IDs and table names
		if($under){
			return preg_replace('/[^a-zA-Z0-9_]/', '', $string);
		}
				
    	if($spaces){
	        return preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
	    }
				
	    return preg_replace('/[^a-zA-Z0-9]/', '', $string);
    }
    
    /*
	 * Log Actions
	 * Note: this logging provides the ability to spot patterns of use and abuse
	 *		 the PhotoSmash mobile app(s) does not send any identifying information
	 *		 without the user expressly doing so.
	 *		 It does not send the phone number or even the devices unique identifies.
	 *		 It does send the time that the user first started using the app, and that
	 *		 is hardly enough to link back to a phone or a user.
	*/
	function insertParam($param_group, $param, $text_value = '', $num_value = 0){
		global $wpdb;
		$d['param_group'] = wp_kses('mob-' . $param_group, array());
		$d['param'] = wp_kses($param, array());
		$d['text_value'] = wp_kses($text_value, array());
		$d['num_value'] = floatval($num_value);
		$d['user_ip'] = preg_replace( '/[^0-9a-fA-F:., ]/', '',$_SERVER['REMOTE_ADDR'] );
		
		$wpdb->insert(PSPARAMSTABLE, $d);
	}


}
}
?>