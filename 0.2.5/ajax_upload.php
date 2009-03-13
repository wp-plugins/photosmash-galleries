<?php
//ini_set ('memory_limit','100M');

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}
 
check_ajax_referer( "bwb_upload_photos" );

require("classes/JSON.php");

//Set the Upload Path
define('PSUPLOADPATH', WP_CONTENT_DIR .'/uploads');
define('PSTEMPPATH', PSUPLOADPATH .'/bwbpstemp/');
define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define('PSIMAGESURL',WP_CONTENT_URL."/uploads/bwbps/");
define('PSTHUMBSURL',PSIMAGESURL."thumbs/");

$json['gallery_id'] = (int)$_POST['gallery_id'];

$table_name = $wpdb->prefix . "bwbps_galleries";
$g = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$table_name." WHERE gallery_id = %d", $json['gallery_id']), ARRAY_A);

$json['size'] = $_POST['MAX_FILE_SIZE'];
if(isset($_POST['bwbps_fileorurl']) && $_POST['bwbps_fileorurl'] == 1){
	$json['image_caption'] = escapeJS(stripslashes($_POST['bwbps_imgcaptionInput']));
} else {
	$json['image_caption'] = escapeJS(stripslashes($_POST['bwbps_imgcaption']));
}
$json['image_caption'] = htmlentities($json['image_caption']);
$json['url'] = ''; //stripslashes($_POST['bwbps_imgcaptionURL']);
$json['img'] = '';
$json['imgrel'] = $g['img_rel'];
$json['show_imgcaption'] = $g['show_imgcaption'];
$json['thumb_width'] = $g['thumb_width'] < 50 ? 50 : $g['thumb_width'] + 4;

//Image per row
if($g['img_perrow'] && $g['img_perrow']>0){
		$json['li_width'] = floor((1/((int)$g['img_perrow']))*100);
} else {
		$json['li_width'] = 0;
}

$json['succeed'] = 'false'; 
if($g['contrib_role'] == -1){
	$user_level = true;
} else {
	$user_level = current_user_can('level_'.$g['contrib_role']) || current_user_can('upload_to_photosmash') 
		|| current_user_can('photosmash_'.$g['gallery_id']) ? true : false;
	if(!user_level){
		if(current_user_can('upload_to_photosmash')){
			$user_level = true;
		}
	}
}

if(!$user_level){
	$json['message'] = "Current user does not have authorization for uploading to this gallery.";
	echo json_encode($json);
	exit();
}


$user_level = current_user_can('level_1');

//Create new name for Image
$newname = strtotime("now");

include('classes/upload/class.upload.php');

//Determine if using Select From URL
if(isset($_POST['bwbps_fileorurl']) && $_POST['bwbps_fileorurl'] == 1){
	
	if(!file_exists(PSTEMPPATH)){
		if(!mkdir(PSTEMPPATH, 0777)){
			$json['message'] = "Unable to create the Temp directory for storing URL files: ".PSTEMPPATH.".";
			echo json_encode($json);
			exit();		
		}
		
	}
	
	chmod(PSTEMPPATH, 0777);
	
	$image_url = $_POST['bwbps_uploadurl'];		
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
	
	$handle = new upload($tempname);	
} else {
	$handle = new upload($_FILES['bwbps_uploadfile']);
}

$handle->file_max_size = 5000000;

$handle->file_auto_rename = true;
$handle->dir_auto_chmod = true;
$handle->auto_create_dir = true;
$handle->jpeg_quality = 100;
$handle->allowed = array('image/*');
$handle->forbidden = array('application/*');
$handle->mime_magic_check = true;

//change image name
$handle->file_new_name_body = $newname;
sleep(3);
$json['img'] = $newname.".".$handle->file_src_name_ext;
//process and save full sized image
$handle->process(PSUPLOADPATH."/bwbps/");



$handle->file_new_name_body = $newname;

//image sizing
if($g['thumb_width'] || $g['thumb_height']){
	if($g['thumb_width'] && $handle->image_src_x < $g['thumb_width']){
		$g['thumb_width'] = $handle->image_src_x;
	}
	if($g['thumb_height'] && $handle->image_src_y < $g['thumb_height']){
		$g['thumb_height'] = $handle->image_src_y;
	}
	
	//Figure out whether aspect is to be kept or cropped
	$handle->image_resize = true;
	if($g['thumb_aspect'] == 1){
		$handle->image_ratio = true;
	} else {
		$handle->image_ratio_crop = true;	
	}
	
	if($g['thumb_width']){
		$handle->image_x = $g['thumb_width'];
	}
	if($g['thumb_height']){
		$handle->image_y = $g['thumb_height'];
	}
}

$handle->process(PSUPLOADPATH."/bwbps/thumbs/");

if($handle->processed){
	$json['succeed'] = "true";
} else {
	$json['succeed'] = "false";
	$json['message'] = "Image processing failed.";
}

$json['error'] = strip_tags($handle->error);
echo json_encode($json);

if ($handle->processed) {

	$date = date( 'Y-m-d H:i:s');
	$data['user_id'] = $current_user->ID;
	$data['gallery_id'] = $json['gallery_id'];
	$data['comment_id'] = -1;
	
	$data['image_name'] = $json['img'];
	$data['image_caption'] = $json['image_caption'];
	$data['url'] = $json['url'];
	$data['file_name'] = $json['img'];
	if($user_level){
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
	$data['created_date'] = $date;
	$data['seq'] = -1;
	$data['avg_rating'] = 0;
	$data['rating_cnt'] = 0;
	
	
	//Insert the image into the Images table
	$wpdb->insert($wpdb->prefix . "bwbps_images", $data);
} 

$handle->clean();

function escapeJS($str){
	return str_replace('"',"",$str);
}
?>