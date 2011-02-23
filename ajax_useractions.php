<?php

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}

check_ajax_referer("bwb_upload_photos" );

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
			
	
			case 'savecaption' :
				$this->saveCaption();
				break;
					
			case 'userdelete' :
				$this->userDeleteImage(false);
				break;
			
			case 'userdeletewithpost' :
				$this->userDeleteImage(true);
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
	
	/*
	 *	User Delete Image
	 *	- Allows user to delete his/her own image if it's not approved
	 *	- Provides some checking that the admin doesn't need
	 *	- Also allows you to delete a post that might have been created for that  
	 *	  image in a Custom Upload script
	*/
	function userDeleteImage($deletePost){
		global $wpdb, $user_ID, $bwbPS;
		
		
		$imgid = (int)$_POST['image_id'];
				
		if(current_user_can('level_0')){
			$imgid = (int)$_POST['image_id'];
			$json['image_id'] = $imgid;
			if($imgid){
			
				if( current_user_can('level_10') || (int)$bwbPS->psOptions['can_delete_approved']){
					$status = 2;
				} else {
					$status = 0;
				}
				$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM "
					.PSIMAGESTABLE. " WHERE image_id = %d AND user_id = %d AND status < $status ", $imgid, $user_ID));
					
				if(!$row){
					//Bomb out if no row returned
					$json['message'] = 'Unable to delete approved images.';
					$json['status'] = 0;
					echo json_encode($json);
					return;
				}
				
				if($row->file_name || $row->thumb_url){
					
					// Legacy code - PhotoSmash originally used its own folders for uploads
					if( is_file(PSIMAGESPATH.$row->file_name) ){
						unlink(PSIMAGESPATH.$row->file_name);
					}
					
					if( is_file(PSTHUMBSPATH.$row->file_name) ){
						unlink(PSTHUMBSPATH.$row->file_name);
					}
					
					if(!$row->wp_attach_id){
						// PhotoSmash now uses the WordPress upload folder structure
						$uploads = wp_upload_dir();
						
						if( is_file($uploads['basedir'] . '/' . $row->thumb_url) ){
							unlink($uploads['basedir'] . '/' . $row->thumb_url);
						}
						
						if( is_file($uploads['basedir'] . '/' . $row->medium_url) ){
							unlink($uploads['basedir'] . '/' . $row->medium_url);
						}
						
						if( is_file($uploads['basedir'] . '/' . $row->image_url) ){
							unlink($uploads['basedir'] . '/' . $row->image_url);
						}
					}
				}

				
				
				//Delete the Image Record
				$json['status'] = $wpdb->query($wpdb->prepare('DELETE FROM '.
					PSIMAGESTABLE.' WHERE image_id = %d AND user_ID = %d AND status < '
					. $status, $imgid, $user_ID ));
					
				
				//Delete Tags
				wp_set_object_terms($imgid, '', 'photosmash', false);
				wp_delete_object_term_relationships( $imgid, 'photosmash' );
				
				wp_set_object_terms($imgid, '', 'photosmash_contributors', false);	
				wp_delete_object_term_relationships( $imgid, 'photosmash_contributors' );
					
				//Delete Custom data, Ratings, and Categories
				if($json['status']){
					$wpdb->query($wpdb->prepare('DELETE FROM '. PSCUSTOMDATATABLE
						.' WHERE image_id = %d', $imgid));
					
					$wpdb->query($wpdb->prepare('DELETE FROM '. PSRATINGSTABLE
						.' WHERE image_id = %d', $imgid));
					
					$wpdb->query($wpdb->prepare('DELETE FROM '. PSRATINGSSUMMARYTABLE
						.' WHERE image_id = %d', $imgid));
						
					$wpdb->query($wpdb->prepare('DELETE FROM '. PSCATEGORIESTABLE
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
				
				$json['message'] = "Image deleted.";
				
			} else {
				$json['status'] = 0;
				$json['message'] = "Unable to delete image at this time.";	
			}
		} else {
			$json['status'] = 0;
			$json['message'] = "Must be logged in.";
		}
		
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