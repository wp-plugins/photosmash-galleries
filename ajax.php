<?php

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}

check_ajax_referer( "bwbps_moderate_images" );

if(!function_exists('json_encode')){
	require("classes/JSON.php");
}

if(isset($_POST['action']) && $_POST['action']){
	$action = $_POST['action'];
} else {
	die(-1);
}

$bwbpsuploaddir = wp_upload_dir();
define('PSUPLOADPATH', $bwbpsuploaddir['basedir']);
define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");
define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");

switch ($action){
	case 'approve':
		ps_approveImage();
		break;
		
	case 'delete' :
		ps_deleteImage();
		break;
	
	case 'savecaption' :
		ps_saveCaption();
		break;
		
	default :
		break;
}

function ps_saveCaption(){
	global $wpdb;
	if(current_user_can('level_1')){
		
		$data['image_caption'] = stripslashes($_POST['image_caption']);
		$data['url'] = stripslashes($_POST['image_url']);
		$json['image_id'] = (int)$_POST['image_id'];
		$where['image_id'] = $json['image_id'];
		$json['status'] = $wpdb->update(PSIMAGESTABLE, $data, $where);
		$json['action'] = 'saved';
		$json['deleted'] = '';
		
		echo json_encode($json);
		return;
	}else {$json['status'] = -1;}
	echo json_encode($json);

}

function ps_approveImage(){
	global $wpdb;
	if(current_user_can('level_10')){
		
		$data['status'] = 1;
		$json['image_id'] = (int)$_POST['image_id'];
		$where['image_id'] = $json['image_id'];
		$json['status'] = $wpdb->update(PSIMAGESTABLE, $data, $where);
		$json['action'] = 'approved';
		$json['deleted'] = '';
		
		echo json_encode($json);
		return;
	}else {$json['status'] = -1;}
	echo json_encode($json);
}

function ps_deleteImage(){
	global $wpdb;
	if(current_user_can('level_10')){
		$imgid = (int)$_POST['image_id'];
		$json['image_id'] = $imgid;
		if($imgid){
			$filename = $wpdb->get_var($wpdb->prepare("SELECT file_name FROM "
				.PSIMAGESTABLE. " WHERE image_id = %d", $imgid));
			if($filename){
				unlink(PSIMAGESPATH.$filename);
				unlink(PSTHUMBSPATH.$filename);
				
			
				$json['status'] = $wpdb->query($wpdb->prepare('DELETE FROM '.
					PSIMAGESTABLE.' WHERE image_id = %d', $imgid ));
				
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSCUSTOMDATATABLE
					.' WHERE image_id = %d', $imgid));
					
				$json['action'] = 'deleted - '.$filename;
				$json['deleted'] = 'deleted';
			}
		} else {$json['status'] = 0;}
	} else {
		$json['status'] = 0;
	}
		
	echo json_encode($json);
	return;
}

?>