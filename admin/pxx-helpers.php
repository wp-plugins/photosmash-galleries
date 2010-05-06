<?php

if(!class_exists(PixooxHelpers))
{

class PixooxHelpers {
	
	// Pixoox settings
	var $_settings;
	
	function PixooxHelpers() {

	}
	
	
	function sendCURL($url, $post_data, $display=false){
	
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_POST, 3);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	    $result = curl_exec($curl);
	    curl_close($curl);
	    if($display)
	    {
	      header ("content-type: text/xml");
	      echo $this->result ;
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

}
}
?>