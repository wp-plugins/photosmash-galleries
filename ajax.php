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

//Set the Upload Path
define('PSBLOGURL', get_bloginfo('wpurl')."/");
define('PSUPLOADPATH', $bwbpsuploaddir['basedir']);

define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSIMAGESPATH2',PSUPLOADPATH."/bwbps");
define('PSIMAGESURL',WP_CONTENT_URL."/uploads/bwbps/");

define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define('PSTHUMBSPATH2',PSUPLOADPATH."/bwbps/thumbs");
define('PSTHUMBSURL',PSIMAGESURL."thumbs/");

define('PSDOCSPATH',PSUPLOADPATH."/bwbps/docs/");
define('PSDOCSPATH2',PSUPLOADPATH."/bwbps/docs");
define('PSDOCSURL',PSIMAGESURL."docs/");

define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");
define("PSCATEGORIESTABLE", $wpdb->prefix."bwbps_categories");


class BWBPS_AJAX{
	
	var $psUploader;
	var $allowNoImg = false;
	var $psOptions;
	
	function BWBPS_AJAX(){
		$this->psOptions = $this->getPSOptions();
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
				$this->deleteImage(true);
				break;
				
			case 'remove' :
				$this->deleteImage(false);
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
			
			$json['image_id'] = (int)$_POST['image_id'];
			
			//Do this before we update status, so we only do unmoderated images
			$this->alertContributor( 1, $json['image_id'] );
		
			$data['status'] = 1;
			$data['alerted'] = 1;
			
			$where['image_id'] = $json['image_id'];
			$json['status'] = $wpdb->update(PSIMAGESTABLE, $data, $where);
			$json['action'] = 'approved';
			$json['deleted'] = '';
								
			echo json_encode($json);
			return;
		}else {$json['status'] = -1;}
		echo json_encode($json);
	}
	
	function alertContributor($approved, $img_id){
			
		if($this->psOptions['mod_send_msg']){
			if($approved){
				$msg = $this->psOptions['mod_approve_msg'];
			} else {
				$msg = $this->psOptions['mod_reject_msg'];
			}
			
			$this->sendMsg($msg, $img_id, $approved);
		}	
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

	function deleteImage($delete_med_lib=true){
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
					
					if($delete_med_lib){
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
					
				}
				
				//Do this before we delete it, or we can't get the user ID
				$this->alertContributor( 0, $json['image_id'] );
			
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
					
				if((int)$row['wp_attach_id'] && $delete_med_lib ){
					
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
		
		
			
		$json['message'] = 'Invalid field name.';
		if(!isset($_POST['field_name']) || strlen($_POST['field_name']) < 3){
			echo json_encode($json);
			return;
		}
		
		$field = substr($_POST['field_name'], 3);
		
		$fld = $this->getGallerySettingFields($field);
				
		if(!$fld){
			echo json_encode($json);
			return;
		}
		
		$galflds = $fld['type'];
		$isint = strpos($galflds, 'int');
		
		if($isint === false){ } else { $isint = true; }
		
		
		if($isint){
			
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
	
	function getGallerySettingFields($fld){
		global $wpdb;
		
		$sql = "SHOW COLUMNS FROM ".PSGALLERIESTABLE . " LIKE '". $fld ."'";
			
		$ret = $wpdb->get_results($sql);
	
		return $ret;
	}
	
	//Returns the PhotoSmash Defaults
	function getPSOptions()
	{
		$psOptions = get_option("BWBPhotosmashAdminOptions");
		
		return $psOptions;
	}
	
	//Send email alerts for new images
	function sendMsg($msg, $img_id, $approve=false, $unapproved_only=true)
	{
		global $wpdb;
				
		if( $unapproved_only ){
			$status_where = " AND " . PSIMAGESTABLE .".status < 0 ";
		}
						
		$sql = "SELECT ".$wpdb->users.".user_email, ".PSIMAGESTABLE
			. ".* FROM ".PSIMAGESTABLE." JOIN "
			. $wpdb->users. " ON " . $wpdb->users . ".ID = "
			. PSIMAGESTABLE . ".user_id WHERE "
			. PSIMAGESTABLE . ".image_id = " . (int)$img_id . $status_where;
												
		$row = $wpdb->get_row($sql);
		
		
		if(!is_object($row)){ return; }
				
		$email = $row->user_email;
		if(!trim($email)) return;
		
		$post_id = $row->post_id;
		
		if( $approve && $row->file_type === "0" ){
		
			$uploads = wp_upload_dir();	
			
			if( !$row->thumb_url ){
				$row->thumb_url = PSTHUMBSURL.$row->file_name;
			} else {
				$row->thumb_url = $uploads['baseurl'] . '/' . $row->thumb_url;
			}		
						
			$imglink = "<img src='" . $row->thumb_url . "' />";
			if($row->post_id){			
				$plink = get_permalink($row->post_id);
				
				$imglink = "<a href='". $plink . "' title='View post'>"
					. $imglink . "</a>";
					
			}
			
		}
		
		$imgcaption = $row->image_caption ? $row->image_caption : "<em>missing</em>";
		
		$msg = str_replace('[blogname]', get_bloginfo("site_name" ), $msg);
		
		$msg .= "<div style='margin-top: 30px;'><p>Image caption: " . $row->image_caption
			. "</p>" . $imglink . "</div>";
			
		
		$admin_email = get_bloginfo( "admin_email" );
		
 		$headers = "MIME-Version: 1.0\n" . "From: " . get_bloginfo("site_name" ) ." <{$admin_email}>\n" . "Content-Type: text/html; charset=\"" . get_bloginfo('charset') . "\"\n";
 		 		
 		$accepted = $approve ? "Accepted" : "Rejected";
 		wp_mail($email, "Image has been ". $accepted, $msg, $headers );
				
	}

}

$bwbpsAjax = new BWBPS_Ajax();

?>