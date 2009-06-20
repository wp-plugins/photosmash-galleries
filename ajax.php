<?php

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}

check_ajax_referer( "bwbps_moderate_images" );

if(!function_exists('json_encode')){
	require("classes/JSON.php");
}

$bwbpsuploaddir = wp_upload_dir();
define('PSUPLOADPATH', $bwbpsuploaddir['basedir']);
define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");
define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");


class BWBPS_AJAX{
	
	var $psUploader;
	var $allowNoImg = false;
	
	function BWBPS_AJAX(){

		if(isset($_POST['action']) && $_POST['action']){
			$action = $_POST['action'];
		} else {
			die(-1);
		}


		switch ($action){
			case 'approve':
				$this->approveImage();
				break;
		
			case 'delete' :
				$this->deleteImage();
				break;
	
			case 'savecaption' :
				$this->saveCaption();
				break;
				
			case 'mass_updategalleries' :
				$this->massUpdateGalleries();
				break;
		
			default :
				break;
		}
	}

	function saveCaption(){
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

	function approveImage(){
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

	function deleteImage(){
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
				}
			
				$json['status'] = $wpdb->query($wpdb->prepare('DELETE FROM '.
					PSIMAGESTABLE.' WHERE image_id = %d', $imgid ));
				
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSCUSTOMDATATABLE
					.' WHERE image_id = %d', $imgid));
					
				if( !$filename ){ $filename = ""; } else { $filename = " - ".$filename; }
				$json['action'] = 'deleted'.$filename;
				$json['deleted'] = 'deleted';
				
			} else {$json['status'] = 0;}
		} else {
			$json['status'] = 0;
		}
		
		echo json_encode($json);
		return;
	}
	
	function massUpdateGalleries(){
		global $wpdb;
		
		$json['action'] = 'massupdategalleries';
		$json['count'] = 0;
		$json['message'] = 'Not authorized.';
		
		if(!current_user_can('level_10')){
			echo json_encode($json);
			return;
		}
		
		$gflds = $this->getGallerySettingFields();
		$galflds = $gflds['n'];
		$galtypes = $gflds['t'];
			
		$json['message'] = 'Invalid field name.';
		if(!isset($_POST['field_name']) || strlen($_POST['field_name']) < 3){
			echo json_encode($json);
			return;
		}
		
		$field = substr($_POST['field_name'], 3);
		
		if(!is_array($galflds) || !in_array($field, $galflds)){
			echo json_encode($json);
			return;
		}
		
		$type = $galtypes[array_search($_POST['field_name'], $galflds)];
		
		
		if($type == int){
			if($_POST['field_value'] == 'true'){ 
				$data[$field] = 1;		
			} else {
				if($_POST['field_value'] == 'false'){
					$data[$field] = 0;	
				} else {
					$data[$field] = (int)$_POST['field_value'];	
				}
				
			}
		} else {
			$data[$field] = $_POST['field_value'];
		}
		
		$where['status'] = 1;
		
		$json['count'] = $wpdb->update(PSGALLERIESTABLE, $data, $where);
		
		$json['message'] = "Galleries updated: ". $json['count'];
		echo json_encode($json);
		return;
	}
	
	function getGallerySettingFields(){
		global $wpdb;
		$sql = "SELECT * FROM ".PSGALLERIESTABLE." LIMIT 1";
		
		$ret = $wpdb->get_row($sql);
		
		foreach($wpdb->get_col_info('name') as $name){
			$colname[] = $name;
		}
		foreach($wpdb->get_col_info('type') as $type){
			$coltype[] = $type;
		}
		$c['n'] = $colname;
		$c['t'] = $coltype;
		return $c;
	}

}

$bwbpsAjax = new BWBPS_Ajax();

?>