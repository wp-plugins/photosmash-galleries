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

//Upload Class by Colin Verot
require_once('classes/upload/class.upload.php');

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




//Set SAFE_MODE constant
if ( (gettype( ini_get('safe_mode') ) == 'string') ) {
	// if sever did in in a other way
	if ( ini_get('safe_mode') == 'off' ) define('SAFE_MODE', FALSE);
	else define( 'SAFE_MODE', ini_get('safe_mode') );
} else {
	define( 'SAFE_MODE', ini_get('safe_mode') );
}

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
	
	/* 
	 * Constructor
	 *
	 */
	function BWBPS_Uploader($psOptions, $gallery_id=false){
		
		$this->psOptions = $psOptions;
		
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
			$this->json['message'] = "Current user does not have authorization for uploading to this gallery.";
			$this->echoJSON();
			exit();
		}
		$this->user_level = current_user_can('level_1');
	}
	
	/* 
	 * Get the Temporary File Name of the Uploaded (or URL inserted) File
	 * --- use this filename in the creation of the New Upload Class instance
	 */
	function importImageFromURL($fileFieldNumber){
		if(!file_exists(PSTEMPPATH)){
			if(!mkdir(PSTEMPPATH, 0755)){
				$this->json['message'] = 
					"Unable to create the Temp directory for storing URL files: "
					.PSTEMPPATH.".";
				$this->echoJSON();
				exit();		
			}
		}
		chmod(PSTEMPPATH, 0755);
		
		/* *************  Gets an Image from a URL   *************** */
		$image_url = $_POST['bwbps_uploadurl'.$fileFieldNumber];		
		$basename = basename($image_url);
		$tempname = PSTEMPPATH.$basename;
		
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
	
	function getFileHandle($fileFieldNumber = ""){
		//Clean up old handle if exists
		if($this->handle){ $this->destroyHandle();}
		
		//Determine if using Select From URL
		if(isset($_POST['bwbps_fileorurl'.$fileFieldNumber]) && $_POST['bwbps_fileorurl'.$fileFieldNumber] == 1){
			//Get image from URL
			$tempname = $this->importImageFromURL($fileFieldNumber);
			$this->handle = new upload($tempname);	
		} else {
			//Get uploaded file
			$this->handle = new upload($_FILES['bwbps_uploadfile'.$fileFieldNumber]);
		}		
		return true;
	}
	
	function getHandleSettings(){
		$this->handle->file_max_size = 5000000;

		$this->handle->file_auto_rename = true;
		$this->handle->dir_auto_chmod = true;
		$this->handle->dir_chmod = 0755;
		$this->handle->auto_create_dir = true;
		$this->handle->jpeg_quality = 100;
		$this->handle->allowed = array('image/*');
		$this->handle->forbidden = array('application/*');
		$this->handle->mime_magic_check = true;
		
	}
	
	function getNewImageName($name = ""){
		//Create new name for Image
		if($name){$name = "_".$name;}
		return strtotime("now").$name;
	}
	
	function getImageSettings($g)
	{
		$this->json['succeed'] = 'false'; 
		$this->json['size'] = $_POST['MAX_FILE_SIZE'];
		
		$this->json['form_name'] = $_POST['bwbps_formname'];
		
		$this->json['post_id'] = (int)$_POST['bwbps_post_id'];
		
		$this->json['image_caption'] = $this->getImageCaption();
		
		//$this->json['image_caption'] = htmlentities($this->json['image_caption'], ENT_QUOTES);
		
		//Get URL
		$bwbps_url = trim($this->cleanJS(stripslashes($_POST['bwbps_url'])));
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
	
	
	function getImageCaption(){
		//Get Caption
		//For some reason, img_caption doesn't always carry the value & vice versa
		if(!$_POST['bwbps_imgcaption']){
			$json = $this->stripSlashes($_POST['bwbps_imgcaptionInput']);   
		} else {
			$json = $this->stripSlashes($_POST['bwbps_imgcaption']);   
		}
		
		return $json;
	}
	
	function stripSlashes($val){
		if(get_magic_quotes_gpc()){
				$val = stripslashes($val);
		}
		return $val;
	}
	
	function processMainImage($g, $newname, $allowNoImg=false){
	
		//Verify User rights...and leave if not sufficient
		
		$this->verifyUserRights($g);	//will exit if not enough rights.

		if(get_option('bwbps-use777') == '1' && !SAFE_MODE){
			chmod(PSIMAGESPATH2, 0777);
			chmod(PSTHUMBSPATH2, 0777);
		}
		
		if(!$this->handle->file_is_image){
			$this->json['succeed'] = "true";
			$this->json['img'.$this->imageNumber] = "0";
			return $allowNoImg;
		}
		
		//change image name
		$this->handle->file_new_name_body = $newname;
		sleep(1);
		$this->json['img'.$this->imageNumber] = $newname.".".$this->handle->file_src_name_ext;
		
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
		
		//process and save full sized image
		$this->handle->process(PSUPLOADPATH."/bwbps/");
		
		if($this->handle->processed){
			$this->json['succeed'] = "true";
		} else {
			$this->json['succeed'] = "false";
			$this->json['message'] = "Image processing failed.";
		}

		$this->json['error'] = strip_tags($this->handle->error);
		return $this->handle->processed;
	}
		
	function processThumbnail($g, $newname, $allowNoImg=false){
		$this->verifyUserRights($g);	//will exit if not enough rights.
		
		if(!$this->handle->file_is_image){
			$this->json['succeed'] = "true";
			$this->json['img'.$this->imageNumber] = "0";
			return $allowNoImg;
		}
				
		$this->handle->file_new_name_body = $newname;
		
		if(!$this->json['img'.$this->imageNumber]){
			$this->json['img'.$this->imageNumber] = $newname.".".$this->handle->file_src_name_ext;
		}

		//image sizing
		if($g['thumb_width'] || $g['thumb_height']){
			if(!$g['thumb_width'] || $this->handle->image_src_x < $g['thumb_width']){
				$g['thumb_width'] = $this->handle->image_src_x;
			}
			if(!$g['thumb_height'] || $this->handle->image_src_y < $g['thumb_height']){
				$g['thumb_height'] = $this->handle->image_src_y;
			}
			
			//Figure out whether aspect is to be kept or cropped
			$this->handle->image_resize = true;
			if($g['thumb_aspect'] == 1){
				$this->handle->image_ratio = true;
			} else {
				$this->handle->image_ratio_crop = true;	
			}
			
			if($g['thumb_width']){
				$this->handle->image_x = $g['thumb_width'];
			}
			if($g['thumb_height']){
				$this->handle->image_y = $g['thumb_height'];
			}
		}

		$this->handle->process(PSUPLOADPATH."/bwbps/thumbs/");

		if($this->handle->processed){
			$this->json['succeed'] = "true";
		} else {
			$this->json['succeed'] = "false";
			$this->json['message'] = "Image processing failed.";
		}

		$this->json['error'] = strip_tags($this->handle->error);
		return $this->handle->processed;
	}

	function saveImageToDB($g, $bSaveCustomFields=true){
		global $current_user;
		global $wpdb;
		
		$data['user_id'] = $current_user->ID;
		$data['gallery_id'] = $this->json['gallery_id'];
		$data['comment_id'] = -1;
		$data['post_id'] = $this->json['post_id'];
		
		$data['image_name'] = $this->json['img'.$this->imageNumber];
		$data['image_caption'] = $this->json['image_caption'];
		$data['url'] = $this->json['url'];
		$data['file_name'] = $this->json['img'.$this->imageNumber];
		if($this->user_level){
			$data['status'] = 1;
		}else{
			if($g['img_status'] == 1){
				$data['status'] = 1;
			} else {
				$data['status'] = -1;
			}
		}
		$data['alerted'] = 0;
		$data['updated_by'] = $current_user->ID;
		$data['created_date'] = date( 'Y-m-d H:i:s');
		$data['seq'] = -1;
		$data['avg_rating'] = 0;
		$data['rating_cnt'] = 0;
				
		//Insert the image into the Images table
		$wpdb->insert(PSIMAGESTABLE, $data);
		
		$image_id = $wpdb->insert_id;
		
		$data['image_id'] = $image_id;
		
		//Expose the Image Data to external classes
		$this->imageData = $data;
		
		if($image_id && $bSaveCustomFields){
			$this->saveCustomFields($image_id);
		}
		
		return $image_id;
	}
	
	function saveCustomFields($image_id){
		//If USE_CUSTOMFIELDS is set in PS Options, then Save Custom Field data
		//if($image_id && $this->psOptions['use_customfields']){
		if($this->psOptions['use_customfields']){
			if(!isset($this->bwbpsCF)){
				require_once("bwbps-savecustomfields.php");
			}	
			$this->bwbpsCF = new BWBPS_SaveCustomFields();
			$this->customData = $this->bwbpsCF->saveCustomFields($image_id);
			if(is_array($this->customData) && is_array($this->imageData)){
				$this->imageData = array_merge($this->imageData, $this->customData);
			}
		}	
	}
	
	/*
	 *	Create New Gallery
	 *
	 */
	function createNewGallery($gallery_name, $post_id=0, $image_status=false)
	{
		global $wpdb;
		//This section saves Gallery specific settings
			$d['gallery_name'] = $gallery_name;
			$d['caption'] = $gallery_name;
			$d['post_id'] = (int)$post_id;
			$d['img_perpage'] = (int)$this->psOptions['img_perpage'];
			$d['img_perrow'] = (int)$this->psOptions['img_perrow'];
			$d['thumb_aspect'] = (int)$this->psOptions['thumb_aspect'];
			$d['thumb_width'] = (int)$this->psOptions['thumb_width'];
			$d['thumb_height'] = (int)$this->psOptions['thumb_height'];
			
			$d['image_aspect'] = (int)$this->psOptions['image_aspect'];
			$d['image_width'] = (int)$this->psOptions['image_width'];
			$d['image_height'] = (int)$this->psOptions['image_height'];
			
			$d['img_rel'] = $this->psOptions['img_rel'];
			$d['upload_form_caption'] = $this->psOptions['upload_form_caption'];
			$d['img_class'] = $this->psOptions['img_class'];
			$d['show_imgcaption'] = (int)$this->psOptions['show_imgcaption'];
			$d['nofollow_caption'] = isset($this->psOptions['nofollow_caption']) ? 1 : 0;
			if($image_status === false){
				$d['img_status'] = (int)$this->psOptions['img_status'];
			} else {
				$d['img_status'] = (int)$image_status;
			}
			$d['contrib_role'] = (int)$this->psOptions['contrib_role'];
			
			$d['use_customform'] = isset($this->psOptions['use_customform']) ? 1 : 0;
			$d['use_customfields'] = isset($this->psOptions['use_customfields']) ? 1 : 0;
			$d['custom_formname'] = 'default';
			$d['layout_id'] = (int)$this->psOptions['layout_id'];
						
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
		$this->destroyHandle();
		$this->resetCHMOD();
	}
	
	function echoJSON(){
		
		$this->json['image_caption'] = $this->cleanJS($this->json['image_caption']);
		
		//Echoes back the JSON Array for an Ajax Call
		echo json_encode($this->json);
	}
	
	function cleanJS($str){
		return $str;
		//return str_replace('"','\"',$str);
	}

	function destroyHandle(){
		if($this->handle->processed){
			$this->handle->clean();
		}
		unset($this->handle);
	}
	
	function resetCHMOD(){
		if(get_option('bwbps-use777') == '1' && !SAFE_MODE){
			chmod(PSIMAGESPATH2, 0755);
			chmod(PSTHUMBSPATH2, 0755);
		}
	}
} 







?>