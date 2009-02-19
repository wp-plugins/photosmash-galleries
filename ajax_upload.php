<?php
//ini_set ('memory_limit','100M');

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}

check_ajax_referer( "bwb_upload_photos" );

// required for Windows & XAMPP
define('WINABSPATH', str_replace("\\", "/", ABSPATH) );

$bwbpsuploaddir = wp_upload_dir();
$_psuploadpath = $bwbpsuploaddir['basedir'];
define('PSUPLOADPATH', $_psuploadpath);

$json['gallery_id'] = (int)$_POST['gallery_id'];

$table_name = $wpdb->prefix . "bwbps_galleries";
$g = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$table_name." WHERE gallery_id = %d", $json['gallery_id']), ARRAY_A);

$json['size'] = $_POST['MAX_FILE_SIZE'];
$json['image_caption'] = escapeJS(stripslashes($_POST['bwbps_imgcaption']));
$json['image_caption'] = htmlentities($json['image_caption']);
$json['url'] = ''; //stripslashes($_POST['bwbps_imgurl']);
$json['img'] = '';
$json['imgrel'] = $g['img_rel'];
$json['show_imgcaption'] = $g['show_imgcaption'];

$json['succeed'] = 'false'; 
if($g['contrib_role'] == -1){
	$user_level = true;
} else {
	$user_level = current_user_can('level_'.$g['contrib_role']);
}

if(!$user_level){
	$json['message'] = "Current user does not have authorization for uploading to this gallery.";
	echo json_encode($json);
	exit();
}


$user_level = current_user_can('level_1');

include('classes/upload/class.upload.php');

$handle->file_max_size = 5000000;
$handle = new upload($_FILES['bwbps_uploadfile']);

$handle->file_auto_rename = true;
$handle->dir_auto_chmod = true;
$handle->auto_create_dir = true;
$handle->jpeg_quality = 100;
$handle->allowed = array('image/*');
$handle->forbidden = array('application/*');
$handle->mime_magic_check = true;

//change image name
$newname = strtotime("now");
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
	
	
	$handle->image_resize = true;
	$handle->image_ratio_crop = true;
	
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
	
	
	//Insert the image into the Images table
	$wpdb->insert($wpdb->prefix . "bwbps_images", $data);
} 

$handle->clean();

function escapeJS($str){
	return str_replace('"',"",$str);
}
?>