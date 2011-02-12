<?php

define('DOING_AJAX', true);
define('WP_ADMIN', true);

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
	
	require_once('../../../wp-admin/includes/admin.php');
	
	do_action('admin_init');

}

check_ajax_referer( "bwbps_moderate_images" );


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
	
	var $img_funcs; // Image Functions class
	
	function BWBPS_AJAX(){
		//$this->psOptions = $this->getPSOptions();
		
		global $bwbPS;
		
		$this->psOptions = $bwbPS->psOptions;
		$this->img_funcs = $bwbPS->img_funcs;
				
		//$this->img_funcs = new BWBPS_ImageFunc($this->psOptions);
		
		if((isset($_POST['action']) && $_POST['action']) || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'getmapform')){
			$action = $_REQUEST['action'];
		} else {
			die(-1);
		}


		switch ($action){
			case 'getmediagalvideos' :
				$this->getMediaGalleryVideos();
				break;
				
				
			case 'fetchmeta' :
				$this->fetchMeta();
				break;
				
			case 'savecustfields' :
				$this->saveCustomFields();
				break;
				
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
				
			case 'publishpost' :
				$this->publishPost();
				break;
				
			case 'copyimagestogal' :
				$this->copyImagesToGallery(true);
				break;
				
			case 'moveimagestogal' :
				$this->copyImagesToGallery(false);
				break;
				
			case 'togglefileurl' :
				$this->toggleAdminOption('bwbps_show_fileurl');
				break;
				
			case 'togglecustomdata' :
				$this->toggleAdminOption('bwbps_show_customdata');
				break;
				
			case 'toggleshowfields' :
				$this->toggleAdminOption('bwbps_show_fields');
				break;
			
			case 'resizeimage' :
				$this->updateImageSizes();
				break;
				
			case 'getmapform' :
				$this->getMapForm();
				break;
				
			case 'savelatlng' :
				$this->saveLatLng();
				break;
		
			default :
				break;
		}
	}
	
	
	function saveLatLng(){
		global $wpdb;
				
		$image_id = (int)$_POST['image_id'];
		
		if(!current_user_can('level_10')){
			$json['message'] = 'Must be an Admin to set Lat/Lng.';
			$json['status'] = 0;
			echo json_encode($json);
			return;	
		}
		
		if(!$image_id){
			$json['message'] = 'Invalid image id.';
			$json['status'] = 0;
			echo json_encode($json);
			return;	
		}
		
		
		$data['geolat'] = round(floatval($_POST['lat']),10);
		$data['geolong'] = round(floatval($_POST['lng']),10);
		
		$where['image_id'] = $image_id;
		
		$upd = $wpdb->update(PSIMAGESTABLE, $data, $where);
		
		if($upd){
			$json['message'] = 'updated';
			$json['lat'] = $data['geolat'];
			$json['lng'] = $data['geolong'];
			$json['status'] = 1;
		} else {
			$json['message'] = 'update failed';
			$json['status'] = 0;
		}			
			
		echo json_encode($json);
		return;	
	
	}
	
	// Get Form for Update Lat and Lng by clicking Google Map
	function getMapForm(){
			
		$image_id = (int)$_REQUEST["image_id"];
		
		// Output form straight to client
		?>
		<script type="text/javascript">
		//<![CDATA[
		var ps_imgid = <?php echo (int)$image_id; ?>;
		//]]>
		</script>
		
		<script type="text/javascript">
		//<![CDATA[
		<?php
		
		echo '
					
			bwbmap_post_map_249 = bwb_gmap.showMap( "post_map_249", 38.59255, -90.35734);
			
			if( bwbmarkers_post_map ){ bwbmarkers_post_map.setMap(); bwbmarkers_post_map = new Object();}
			
			bwbmarkers_post_map_249 = [
					["bear", 38.59255, -90.35734, "<div style=\"margin: 10px 5px;\"><a href=\'http://pixoox.com/wp-content/uploads/2010/09/bear14.jpg\' rel=\'lightbox[album_24]\' title=\'bear\'  class=\'thickbox\'><img src=\'http://pixoox.com/wp-content/uploads/2010/09/bear14-125x125.jpg\' class=\'ps_images\' alt=\'bear\'  height=\'125\' width=\'125\' /></a><br/>bear</div>"]
				];
			//bwb_gmap.setMarkers(bwbmap_post_map_249, bwbmarkers_post_map_249 );
			
			
			if(bwb_first){
				bwb_bounds = bwbmap_post_map_249.getBounds();
			} else {
				bwbmap_post_map_249.fitBounds(bwb_bounds);
			}
		
		';
		
		?>
		
		
		//]]>
		</script>
		
		<div>
			<input id="address" type="textbox" value="St.Louis, MO" size="80">
			<input type="button" value="Geocode" onclick="bwb_gmap.codeAddress(bwbmap_post_map_249, 'address'); return false;">
		</div>
	
		<div id='post_map_249' class='bwbps_gmap bwbps_gmap_ ' style='width: 500px; height: 370px;'></div>
		<?php
		
		return;	
	
	}
	
	function fetchMeta(){
	
		if(!current_user_can('level_10')){
			$json['status'] = 0;
			$json['message'] = "security failed";
			echo json_encode($json);
			return;
		}
		
		$attach_id = (int)$_POST['attach_id'];
		
		if(!$attach_id){
			$json['status'] = 0;
			$json['message'] = "invalid attachment ID";
			echo json_encode($json);
			return;
		}
		
		$json['status'] = 1;
		$meta = wp_get_attachment_metadata($attach_id);
		$json['meta'] = serialize($meta['image_meta']);
		
		$data['meta_data'] = $json['meta'];
		
		if($data['meta_data'] && (int)$_POST['image_id']){
			$where['image_id'] = (int)$_POST['image_id'];
			global $wpdb;
			$wpdb->update(PSIMAGESTABLE, $data, $where);
		}
		
		echo json_encode($json);
		return;
	
	}
		
	//Toggle whether the file URL field is visible in Photo Manager
	function toggleAdminOption($optionname){
	
		update_option( $optionname, (int)$_POST['adminoption']);
		$json['status'] = 1;
		echo json_encode($json);
		return;
	
	}
	
	//Get Media Gallery videos
	function getMediaGalleryVideos(){
		
		global $wpdb;
		
		if($_POST['search_term']){
			
			$search = esc_sql( stripslashes( $_POST['search_term'] ) );
			
			$sql = "SELECT post_name, guid FROM " . $wpdb->posts 
				. "WHERE post_type LIKE 'attachment video%' AND post_name LIKE '%" . $search
				. "%' ORDER BY post_name";
		} else {
			
			$sql = "SELECT post_name, guid FROM " . $wpdb->posts 
				. "WHERE post_type LIKE 'attachment video%' ORDER BY post_name";
				
		}
			
		$res = $wpdb->get_results($sql);
		
		if($res){
			
			foreach($res as $row){
				$json['images'][] = array($row->post_name, $row->guid);
			}
		
		}
		
		echo json_encode($json);
		return;

	}
	
	
	//Copy or Move to Gallery
	function copyImagesToGallery($copy){
		
		global $wpdb;
	
		$galid = (int)$_POST['newgallery'];
		
		$json['newgallery'] = $galid;
				
		
		if(current_user_can('level_10') && $json['newgallery'] && is_array($_POST['image_ids'])){		
							
			// Check to make sure new gallery is not a virtual gallery type (tag, ranked, contributor, etc)
			$sql = "SELECT gallery_type FROM " . PSGALLERIESTABLE . " WHERE gallery_id = " . (int)$galid 
				. " AND gallery_type < 10";
			
			$res = $wpdb->get_col($sql);
						
			if(empty($res)){
				$json['updated'] = 0;
				$json['message'] = "** Gallery type is not valid for copy/move.  **\n\n" 
					. "Gallery id: " . $galid . "\n\nVirtual galleries such as tag, ranked, contributor, random, etc should not have images assigned directly to them as they will not be displayed as part of the gallery...display is based on the virtual aspects of the gallery.";
				echo json_encode($json);
				return;
			}
			
			unset($res);
			
			//Get list of Image IDs
			foreach($_POST['image_ids'] as $img_id){
				if((int)$img_id){
					$imgs[] = (int)$img_id;
				}
			}
			
			
			//We've got a good gallery...check image id's and perform update/inserts as needed
			if(is_array($imgs)){
			
				$img_ids = implode(",", $imgs);
				
				if(!$copy){
					
					//Code to Move images to a different gallery
					$sql = "UPDATE " . PSIMAGESTABLE . " SET gallery_id = " . (int)$galid
						. " WHERE image_id IN (" . $img_ids . ")";
						
					$json['updated'] = $wpdb->query($sql);		//The database update
					$json['message'] = 'Moved images to gallery';
					
					foreach($imgs as $newid){
						$this->img_funcs->resizeImage($newid);
					}
									
				} else {
					
					//Code to COPY images to a different gallery
				
					//Determine if any of these images are already in the gallery...and ignore them
					
					$skip = array("id", "image_id", "updated_date");
					
					$cols = $this->getCustomDataTableCols(true, $skip);
					
					if(!$cols){
						$json['updated'] = 0;
						$json['message'] = "Error...images table columns not accessible!";
						echo json_encode($json);
						return;
					
					}
					
					$sql = "SELECT * FROM " . PSIMAGESTABLE . " WHERE image_id IN (" 
						. $img_ids . ")";
						
					$imgs = $wpdb->get_results($sql, ARRAY_A);
					
					if($imgs && is_array($imgs)){
						$json['message'] = 'Copied images to gallery';
						foreach($imgs as $img){
							
							if( $galid != $img['gallery_id']){
							
								$data = $img;
								unset( $data['image_id']);
								$data['gallery_id'] = (int)$galid;
								
								$wpdb->insert(PSIMAGESTABLE, $data);	//Insert new iMage record
								
								$newid = $wpdb->insert_id;	//Get new Image id
								
								//Copy the custom data
							
								 
								$sql = "INSERT INTO " . PSCUSTOMDATATABLE . "(image_id, " . $cols 
									. ") SELECT " . (int)$newid . ", " . $cols . " FROM "
									. PSCUSTOMDATATABLE . " WHERE image_id = " . (int)$img['image_id'];
									
								$json['updated'] += $wpdb->query($sql);
								
								//Copy Tags to new Image
								if( isset($terms) ){ unset($terms); }
								$terms = wp_get_object_terms( $img['image_id'], 'photosmash', $argsarray);
								
								if(is_array($terms)){
									if(isset($tags)){unset($tags);}
									foreach($terms as $t){
									
										$tags[] = $t->name;
									
									}
								
									if(is_array($tags)){
										
										$this->saveImageTags($newid, $tags);
									
									}	
																	
								}
								
								//Inset Contributor tag for new image
								$this->saveImageContributorTag($newid, $img['user_id']);
								
								// Perform the resize on the new image
								if($newid){
									$this->img_funcs->resizeImage($newid);							
								}
								
							}
						}
					}
					
				
				}				
				
			}
			
			
			
		} else {
			$json['status'] = 'false';
		}
		
		echo json_encode($json);
		return;
	
	}
	
	function getCustomDataTableCols($ret_sql = true, $skip = false){
		global $wpdb;
		
		$sql = "SHOW COLUMNS FROM ".PSCUSTOMDATATABLE;
	
		$ret = $wpdb->get_results($sql);
		
		if($ret_sql && $ret){
			if(!$skip){$skip = array(); }
			foreach($ret as $row){
				
				if(!in_array($row->Field, $skip)){
					$r[] = $row->Field;
				}
			}
			
			if(is_array($r)){
			
				$cols = implode(", ", $r);
				return $cols;
				
			}
		
		}
		
		return $ret;
	
	}
	
	/*
	 *	Save image tags
	*/
	function saveImageTags($image_id, $tags){
	
		global $wpdb;
		
		if(!(int)$image_id || !$tags){ return; }
		
		$t = is_array($tags) ? $tags : explode( ',', trim($tags, " \n\t\r\0\x0B,") );
		wp_set_object_terms($image_id, $t, 'photosmash', false);
		
		return;
		
		/*
		$data['image_id'] = (int)$image_id;
		$data['category_id'] = 0;
		
		$sql = $wpdb->prepare("DELETE FROM " . PSCATEGORIESTABLE
			. " WHERE image_id = %d AND category_id = 0", $image_id);
			
		$wpdb->query($sql);		 
		 
		
		foreach($t as $tag){
			
			$data['tag_name'] = $tag;
			$wpdb->insert(PSCATEGORIESTABLE, $data);
		
		}
		*/
		
	}
	
	/*
	 *	Save contributor tags
	*/
	function saveImageContributorTag($image_id, $user_id){
	
		global $wpdb;
		
		if(!(int)$image_id || !(int)$user_id){ return; }
		
		$user_info = get_userdata((int)$user_id);
		
		if(!$user_info){ return; }
		
		$t = array($user_info->user_login);
		
		wp_set_object_terms($image_id, $t, 'photosmash_contributors', false);
		
		return;
		
	}
	
	
	function saveCustomFields(){
	
		$json['image_id'] = (int)$_POST['image_id'];
		
		if(current_user_can('level_10') && $json['image_id']){
			
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/bwbps-savecustomfields.php");
						
			$bwbpsCF = new BWBPS_SaveCustomFields();
			$customData = $bwbpsCF->saveCustomFields($json['image_id']);
			if(is_array($customData)){
				$json['status'] = 'saved';	
			} else {
				$json['status'] = 'false';
			}
			
		} else {
			$json['status'] = 'false';
		}
		echo json_encode($json);
		return;
	
	}
	
	function publishPost(){
		
		$json['image_id'] = (int)$_POST['image_id'];
		
		if(current_user_can('level_10')){
			$post_id = (int)$_POST['post_id'];
			wp_publish_post($post_id);
			
			$post_date = get_post_field( 'post_date', $post_id );
			
			$post_date_gmt = get_gmt_from_date($post_date);
			
			$p['ID'] = $post_id;
			$p['post_date_gmt'] = $post_date_gmt;
			
			wp_update_post($p);
			
			$json['action'] = 'published';
		} else {
			$json['action'] = 'failed';
		}
		echo json_encode($json);
		return;
	
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
		if(current_user_can('level_10')){
		
			$data['image_caption'] = stripslashes($_POST['image_caption']);
			$data['url'] = esc_url_raw(stripslashes($_POST['image_url']));
			$data['post_id'] = (int)$_POST['image_post_id'];
			$data['seq'] = (int)$_POST['seq'];
			$data['file_url'] = esc_url_raw(stripslashes($_POST['file_url']));	
			$data['meta_data'] = stripslashes($_POST['meta_data']);
			$data['geolat'] = floatval($_POST['image_geolat']);
			$data['geolong'] = floatval($_POST['image_geolong']);
			
			$where['image_id'] = (int)$_POST['image_id'];
			
			$json = $data;
			$json['image_id'] = $where['image_id'];
			$json['status']	= $data['file_url'];
			
			
			//update now
			$json['status'] = $wpdb->update(PSIMAGESTABLE, $data, $where) + 1;
			$json['action'] = 'saved';
			
			$json['deleted'] = '';
			
			$tags = $_POST['image_tags'];
			$tags = wp_kses( $tags, array() );
			
			$t = is_array($tags) ? $tags : explode( ',', trim($tags, " \n\t\r\0\x0B,") );
			wp_set_object_terms($json['image_id'], $t, 'photosmash', false);
		
			echo json_encode($json);
			return;
		}else {$json['status'] = -1;}
		echo json_encode($json);

	}

	function approveImage(){
		global $wpdb;

		if(current_user_can('level_10') && (int)$_POST['image_id']){
			
			$json['image_id'] = (int)$_POST['image_id'];
			
			//Do this before we update status, so we only do unmoderated images		
			$this->sendMsg($json['image_id'], 1);
		
			$data['status'] = 1;
			$data['alerted'] = 1;
			
			$where['image_id'] = $json['image_id'];
			$json['status'] = $wpdb->update(PSIMAGESTABLE, $data, $where);
			$json['action'] = 'approved';
			$json['deleted'] = '';
			
			$this->img_funcs->updateGalleryImageCount(0, $json['image_id']);
			
			
			$sql = "SELECT * FROM " . PSIMAGESTABLE . " WHERE image_id = " . (int)$json['image_id'];
		
			$image = $wpdb->get_row($sql, ARRAY_A);
			
			if($image){
				$image['upload_agent'] = 'approval';
				do_action('bwbps_image_approved', $image );
			}
								
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
			$this->sendMsg( $json['image_id'], 1 );
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
					"SELECT gallery_id, file_name, thumb_url, medium_url, image_url, wp_attach_id FROM "
					.PSIMAGESTABLE. " WHERE image_id = %d", $imgid), ARRAY_A);
				if($row){
					
					//Get Gallery ID for Image Count Calculation
					$gallery_id = (int)$row['gallery_id'];
					
					//Check to see if this image exists in multiple records
					//If so, do not delete the files...just remove this record
					if($row['thumb_url'] || $row['medium_url'] || $row['image_url'] || $row['mini_url']){
						
						if($row['thumb_url']){ 
							$sqlaa[] = "thumb_url = '" . esc_sql($row['thumb_url']) ."'"; 
						}
						
						if($row['image_url']){ 
							$sqlaa[] = "image_url = '" . esc_sql($row['image_url']) ."'"; 
						}
						
						if($row['medium_url']){ 
							$sqlaa[] = "medium_url = '" . esc_sql($row['medium_url']) ."'"; 
						}
						
						if($row['mini_url']){ 
							$sqlaa[] = "mini_url = '" . esc_sql($row['mini_url']) ."'"; 
						}
						
						if( is_array($sqlaa)){
							$sql = implode(" OR ", $sqlaa); 
						
						
							$sql = "SELECT image_id FROM " .PSIMAGESTABLE. " WHERE image_id <> $imgid AND (" 
								. $sql . ")";
							$imgcnt = $wpdb->get_results($wpdb->prepare( $sql, $row['thumb_url']));
						}
						
						if($imgcnt){ $delete_med_lib = 0; }
					} else {
						$imgcnt = $wpdb->get_var($wpdb->prepare(
							"SELECT count(file_name) FROM "
							.PSIMAGESTABLE. " WHERE file_name = %s", $row['file_name']));
						
						if($imgcnt > 1){ $delete_med_lib = false; }
						
						
					}	
					
					
					
					//Delete the image files							
					if($delete_med_lib){
						
						//Delete the old style files
						if( is_file( PSIMAGESPATH.$row['file_name'] )){
							unlink(PSIMAGESPATH.$row['file_name']);
						}
						
						if( is_file( PSTHUMBSPATH.$row['file_name'] )){
							unlink(PSTHUMBSPATH.$row['file_name']);
						}
						
						
						//Delete the New Style files (media gallery)
						//PhotoSmash now uses the WordPress upload folder structure
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
							
							
							if(isset($meta['file']) && !empty($meta['file'])){
																	
								$folders = str_replace(basename($meta['file']), '', $meta['file']);
								
								if( is_file($uploads['basedir'] . '/' . $meta['file']) ){
									unlink($uploads['basedir'] . '/' . $meta['file']);
								}
								
								if(is_array($meta['sizes']['thumbnail'])){
									$url = $uploads['basedir'] . '/' . $folders. $meta['sizes']['thumbnail']['file'];
									if( is_file($url) ){
										unlink($url);
									}		
								}
								
								
								if(is_array($meta['sizes']['medium'])){
									$url = $uploads['basedir'] . '/' . $folders. $meta['sizes']['medium']['file'];
									if( is_file($url) ){
										unlink($url);
									}	
								}		
							}
							
						}
					}
					
				}
				
				//Do this before we delete it, or we can't get the user ID
				$this->sendMsg( $imgid, 0 );
			
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
					
				
				$this->img_funcs->updateGalleryImageCount($gallery_id);
					
					
				if((int)$row['wp_attach_id'] && $delete_med_lib ){
				
					wp_delete_post((int)$row['wp_attach_id']);
					
				
					$wpdb->query($wpdb->prepare('DELETE FROM '. $wpdb->postmeta
						.' WHERE post_id = %d', (int)$row['wp_attach_id']));
						
					// Delete tagged photos
					
					wp_set_object_terms($imgid, '', 'photosmash', false);
					wp_delete_object_term_relationships( $imgid, 'photosmash' );
					
					wp_set_object_terms($imgid, '', 'photosmash_contributors', false);
					wp_delete_object_term_relationships( $imgid, 'photosmash_contributors' );

					//Delete Post Meta for Attachment
					$wpdb->query($wpdb->prepare('DELETE FROM '. $wpdb->postmeta
						.' WHERE post_id = %d', (int)$row['wp_attach_id']));	
						
				}
					
					
				if( !$filename ){ $filename = ""; } else { $filename = " - ".$filename; }
				$json['action'] = 'deleted'.$filename;
				$json['deleted'] = 'deleted';
				
				// Update the Tag Counts
				$this->img_funcs->updateTagCounts();
				
				
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
				
				$this->img_funcs->updateGalleryImageCount(0, $json['image_id']);
				
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
	function sendMsg($img_id, $approve=false, $unapproved_only=true)
	{
		global $wpdb;
			
		if( !(int)$_POST['send_msg'] ){ return; }
				
		if( $unapproved_only ){
			$status_where = " AND " . PSIMAGESTABLE .".status < 0 ";
		}
						
		$sql = "SELECT ".$wpdb->users.".user_email, ".$wpdb->users.".user_login, "
			. $wpdb->posts . ".post_name as wp_postname, "
			. $wpdb->posts . ".post_title as wp_postitle, "
			. PSIMAGESTABLE
			. ".* FROM ".PSIMAGESTABLE." JOIN "
			. $wpdb->users. " ON " . $wpdb->users . ".ID = "
			. PSIMAGESTABLE . ".user_id LEFT OUTER JOIN " . $wpdb->posts . " ON "
			. $wpdb->posts . ".ID = " . PSIMAGESTABLE . ".post_id WHERE "
			. PSIMAGESTABLE . ".image_id = " . (int)$img_id . $status_where;
															
		$row = $wpdb->get_row($sql);
		
		if(!is_object($row)){ return; }
		
		//Get Post perma-link for [post_link]
		$post_perma = get_permalink((int)$row->post_id);
		
		if($post_perma){
			$perma = "<a href='".$post_perma."' title='View post'>" . $row->wp_postitle . "</a>";
		} else { $perma = " [post not available] "; }
						
		//Get the user's email address
		$email = $row->user_email;
		if(!trim($email)) return;
		
		$post_id = $row->post_id;
		
		//Get a link to the image
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
		
		//Get the Image Caption
		$imgcaption = $row->image_caption ? $row->image_caption : "<em>missing</em>";
		
		//Get Author Link
		$authorlink = get_author_posts_url($row->user_id);					
		if($authorlink){
			$name = 'View your images/posts.';
			$authorlink = "<a href='".$authorlink."' title='View all images by contributor'>".$name."</a";
		}
		
		//Get the message from $_POST
		$msg = $_POST['mod_msg'];
		
		$msg = stripslashes($msg);
		
		$msg = wp_kses( $msg, array() );
		
		$msg = str_replace('[blogname]', get_bloginfo("site_name" ), $msg);
		$msg = str_replace('[post_link]', $perma, $msg);
		$msg = str_replace('[user_name]', $row->user_login, $msg);
		$msg = str_replace('[author_link]', $authorlink, $msg);
		$msg = nl2br($msg);
		
				
		$msg .= "<div style='margin-top: 30px;'><p>Image caption: " . $row->image_caption
			. "</p>" . $imglink . "</div>";
			
		
		$admin_email = get_bloginfo( "admin_email" );
		
 		$headers = "MIME-Version: 1.0\n" . "From: " . get_bloginfo("site_name" ) ." <{$admin_email}>\n" . "Content-Type: text/html; charset=\"" . get_bloginfo('charset') . "\"\n";
 		 		
 		$accepted = $approve ? "Accepted" : "Rejected";
 		
 		$msg = str_replace('[status]', $accepted, $msg);
 		
 		$msgSubject = $_POST['mod_subject'] ? $_POST['mod_subject'] : 'Image moderation notice: ' . $accepted;
 		
 		$msgSubject = stripslashes($msgSubject);
 		
 		$msgSubject = wp_kses( $msgSubject, array() );
 		
 		$msgSubject = str_replace('[blogname]', get_bloginfo("site_name" ), $msgSubject);
 		$msgSubject = str_replace('[status]', $accepted, $msgSubject);
 		$msgSubject = str_replace('\r', "", $msgSubject);
 		$msgSubject = str_replace('\n', "", $msgSubject);
 		
 		wp_mail($email, $msgSubject, $msg, $headers );
				
	}
	
	
	function updateImageSizes(){
		global $wpdb;
	
		if(current_user_can('level_10')  ){
		
			$image_id = (int)$_POST['image_id'];
			
			if($image_id){
							
				$imgFunc = $this->img_funcs;
				
				$json = $imgFunc->resizeImage($image_id);
				
				echo json_encode($json);
				return;
			} else {
			
				$json['message'] = 'Invalid image ID';
				$json['status'] = 0;
				echo json_encode($json);
				
			}
		
		} else {
				
				$json['message'] = "Invalid credentials.";
				$json['status'] = 0;
				echo json_encode($json);
				return;
		
		}		
	
	}
	

}

$bwbpsAjax = new BWBPS_Ajax();

?>