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

define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");
define("PSCATEGORIESTABLE", $wpdb->prefix."bwbps_categories");

define('PSUPLOADPATH', $bwbpsuploaddir['basedir']);
define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");

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
				
			case 'review':
				$this->markImageReviewed();
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
				
			case 'userdelete' :
				$this->userDeleteImage(false);
				break;
			
			case 'userdeletewithpost' :
				$this->userDeleteImage(true);
				break;
				
			case 'setgalleryid' :
				$this->setGalleryID();
				break;
		
			default :
				break;
		}
	}
	
	function setGalleryID(){
		global $wpdb;
		
		if(current_user_can('level_10') && isset($_POST['gallery_id'])){
		
			$data['gallery_id'] = (int)$_POST['gallery_id'];
			if(!$data['gallery_id']){
				$json['message'] = "Invalid Gallery ID.";
				$json['action'] = 'failed';
			} else {
				$json['image_id'] = (int)$_POST['image_id'];
				$where['image_id'] = $json['image_id'];
				$json['status'] = $wpdb->update(PSIMAGESTABLE, $data, $where);
				
				$json['message'] = "";				
				$json['action'] = 'galleryset';
			}
			
			$json['deleted'] = '';
		
			echo json_encode($json);
			return;
		}else {$json['status'] = -1;}
		echo json_encode($json);

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
			$data['alerted'] = 1;
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
	
	function markImageReviewed(){
		global $wpdb;
		if(current_user_can('level_10')){
		
			
			$data['alerted'] = 1;
			$json['image_id'] = (int)$_POST['image_id'];
			$where['image_id'] = $json['image_id'];
			$json['status'] = $wpdb->update(PSIMAGESTABLE, $data, $where);
			$json['action'] = 'marked';
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
				
				$row = $wpdb->get_row($wpdb->prepare(
					"SELECT file_name, thumb_url, medium_url, image_url, wp_attach_id FROM "
					.PSIMAGESTABLE. " WHERE image_id = %d", $imgid), ARRAY_A);
				if($row){
				
					if( is_file( PSIMAGESPATH.$row['file_name'] )){
						unlink(PSIMAGESPATH.$row['file_name']);
					}
					
					if( is_file( PSTHUMBSPATH.$row['file_name'] )){
						unlink(PSTHUMBSPATH.$row['file_name']);
					}
					
					// PhotoSmash now uses the WordPress upload folder structure
					$uploads = wp_upload_dir();
					
					if( is_file($uploads['basedir'] . '/' . $row['thumb_url']) ){
						unlink($uploads['basedir'] . '/' . $row['thumb_url']);
					}
					
					if( is_file($uploads['basedir'] . '/' . $row['medium_url']) ){
						unlink($uploads['basedir'] . '/' . $row['medium_url']);
					}
					
					if( is_file($uploads['basedir'] . '/' . $row['image_url']) ){
						unlink($uploads['basedir'] . '/' . $row['image_url']);
					}
					
					//Delete images that may be hanging out in the Meta
					if((int)$row['wp_attach_id']){
						$meta = get_post_meta((int)$row['wp_attach_id'], '_wp_attachment_metadata', true);
																		
						$folders = str_replace(basename($meta['file']), '', $meta['file']);
						
						if( is_file($uploads['basedir'] . '/' . $meta['file']) ){
							unlink($uploads['basedir'] . '/' . $meta['file']);
						}
						
						$url = $uploads['basedir'] . '/' . $folders. $meta['sizes']['thumbnail']['file'];
						if( is_file($url) ){
							unlink($url);
						}		
						
						$url = $uploads['basedir'] . '/' . $folders. $meta['sizes']['medium']['file'];
						if( is_file($url) ){
							unlink($url);
						}			
						
					}
					
				}
			
				$json['status'] = $wpdb->query($wpdb->prepare('DELETE FROM '.
					PSIMAGESTABLE.' WHERE image_id = %d', $imgid ));
				
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSCUSTOMDATATABLE
					.' WHERE image_id = %d', $imgid));
					
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSRATINGSTABLE
					.' WHERE image_id = %d', $imgid));
				
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSRATINGSSUMMARYTABLE
					.' WHERE image_id = %d', $imgid));
					
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSCATEGORIESTABLE
					.' WHERE image_id = %d', $imgid));
					
				if((int)$row['wp_attach_id']){
					
					$wpdb->query($wpdb->prepare('DELETE FROM '. $wpdb->posts
						.' WHERE ID = %d', (int)$row['wp_attach_id']));
				
					$wpdb->query($wpdb->prepare('DELETE FROM '. $wpdb->postmeta
						.' WHERE post_id = %d', (int)$row['wp_attach_id']));	
				
				}
					
					
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
	
	/*
	 *	User Delete Image
	 *	- Allows user to delete his/her own image if it's not approved
	 *	- Provides some checking that the admin doesn't need
	 *	- Also allows you to delete a post that might have been created for that  
	 *	  image in a Custom Upload script
	*/
	function userDeleteImage($deletePost){
		global $wpdb, $user_ID;
				
		if(current_user_can('level_0')){
			$imgid = (int)$_POST['image_id'];
			$json['image_id'] = $imgid;
			if($imgid){
				$row = $wpdb->get_row($wpdb->prepare("SELECT file_name, post_id FROM "
					.PSIMAGESTABLE. " WHERE image_id = %d AND user_id = %d AND status < 0 ", $imgid, $user_ID));
					
				if(!$row){
					//Bomb out if no row returned
					$json['status'] = 0;
					return;
				}
				if($row->file_name){
					unlink(PSIMAGESPATH.$filename);
					unlink(PSTHUMBSPATH.$filename);
				}
			
				$json['status'] = $wpdb->query($wpdb->prepare('DELETE FROM '.
					PSIMAGESTABLE.' WHERE image_id = %d AND user_ID = %d AND status < 0 ', $imgid, $user_ID ));
				if($json['status']){
					$wpdb->query($wpdb->prepare('DELETE FROM '. PSCUSTOMDATATABLE
						.' WHERE image_id = %d', $imgid));
						
					//Delete the related post if directed to
					if( $deletePost && $row->post_id ){
						
						//Check to make sure this person is deleting only his/her own post
						//Also check to make sure that this post is "Pending"
						$postAuthor = $wpdb->get_var($wpdb->prepare("SELECT post_author FROM "
					. $wpdb->posts . " WHERE ID = %d AND post_author = %d AND post_status = 'pending' ", $row->post_id, $user_ID));
					
						if($postAuthor){
							wp_delete_post((int)$row->post_id);
						}
					
					}
				}
					
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