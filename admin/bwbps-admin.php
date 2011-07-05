<?php
//Admin Pages for BWB-PhotoSmash plugin

class BWBPS_Admin{
	
	var $psOptions;
	var $message = false;
	var $msgclass = "updated fade";
	
	var $gallery_id;
	var $galleryQuery;
	var $psForm;
	
	//Constructor
	function BWBPS_Admin(){
		//Get PS Defaults
		
		if(!current_user_can('level_10')){ return; }
		
		$this->psOptions = $this->getPSOptions();
		
		$this->gallery_id = (int)$_POST['gal_gallery_id'];
		
		if($_GET['ps-discon-msg']){ update_option('photosmash_discontinued_msg', 'true'); }
				
		//Save PS General Settings
		if(isset($_POST['update_bwbPSDefaults'])){
			check_admin_referer( 'update-gallery');
			$this->saveGeneralSettings($this->psOptions);
			//Refresh options
			$this->psOptions = $this->getPSOptions();
			
			$label = $this->psOptions['tag_label'] ? esc_attr($this->psOptions['tag_label']) : "Photo tags";
		 	$slug = $this->psOptions['tag_slug'] ? $this->psOptions['tag_slug'] : "photo-tag";
	 	
		 	register_taxonomy( 'photosmash', 'post', array( 'hierarchical' => false, 'label' => __($label, 'series'), 'query_var' => 'bwbps_wp_tag', 'rewrite' => array( 'slug' => $slug ) ) );
		 	
		 	$label = $this->psOptions['contributor_label'] ? esc_attr($this->psOptions['contributor_label']) : "Photo Contributors";
		 	
		 	$slug = $this->psOptions['contributor_slug'] ? $this->psOptions['contributor_slug'] : "contributor";
		 	
		 	$labels = array(
			    'name' => $label
			  ); 	
	 	
		 	register_taxonomy( 'photosmash_contributorios', 'post', array( 'hierarchical' => false, 'labels' => $labels, 'query_var' => 'bwbps_contributor', 'rewrite' => array( 'slug' => $slug ) ) );
			
		 	global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}
		
		//Reset PS General Settings to Default
		if(isset($_POST['reset_bwbPSDefaults'])){
			check_admin_referer( 'update-gallery');
			$this->psOptions = $this->getPSDefaultOptions();
			update_option('BWBPhotosmashAdminOptions', $this->psOptions);
		}
		
		//Save Gallery Settings
		if(isset($_POST['save_bwbPSGallery'])){
			check_admin_referer( 'update-gallery');
			$this->saveGallerySettings($this->psOptions);
		}
		
		//Delete Gallery
		if(isset($_POST['deletePhotoSmashGallery'])){
			check_admin_referer( 'delete-gallery');
			$this->deleteGallery($this->options, $this->gallery_id);
			
			$this->gallery_id = 0;
		}
		
		//Delete Multiple Galleries
		if(isset($_POST['deletePSGMultipleGalleries'])){
			check_admin_referer( 'delete-gallery');			
			$this->deleteMultiGalleries($this->options);
		}
		
		if(isset($_POST['ps_update_contribtags']) && $_POST['ps_update_contribtags'] == 'true'){
			check_admin_referer( 'update-gallery');
			$this->updateContributorTags();
		}
	}
	
	function updateContributorTags(){
		global $wpdb;
		
		$sql = "SELECT image_id, user_id FROM " . PSIMAGESTABLE;
		
		$images = $wpdb->get_results($sql, ARRAY_A);
		
		foreach( $images as $img){
			
			$this->saveImageContributorTag( $img['image_id'], $img['user_id'] );
		
		}
	
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

	
	
	function cleanSlashes($val){
		if(get_magic_quotes_gpc()){
			return $val = stripslashes($val);
		}
		return $val;
	}
	
	//Returns the PhotoSmash Defaults
	function getPSOptions()
	{
		$psOptions = get_option("BWBPhotosmashAdminOptions");
		if(!empty($psOptions))
		{
			//Options were found..add them to our return variable array
			foreach ( $psOptions as $key => $option ){
				$psAdminOptions[$key] = $option;
			}
		}
			
		if (!array_key_exists('use_thickbox', $psAdminOptions)) {
			$psAdminOptions['use_thickbox']=1;
		}
		
		return $psAdminOptions;
	}
	
	
	function getPSDefaultOptions()
	{
	
		global $bwbPS;
		
		return $bwbPS->getPSDefaultOptions();
	
	}
	
	
	function deleteMultiGalleries($options)
	{
	
		if(isset($_POST['gal_gallery_ids'] ) && is_array($_POST['gal_gallery_ids'] ) ){
		
			foreach( $_POST['gal_gallery_ids'] as $delGal){
				
				$tempret = $this->deleteGallery($options, $delGal);
				$ret['gal_deleted'] += $tempret['gal_deleted'];
				$ret['deleted_image_cnt'] += $tempret['deleted_image_cnt'];
			}
			
			if($ret['gal_deleted']){
				$this->message = "Deleted Galleries: ".$ret['gal_deleted']. "...Deleted Images: ".$ret['deleted_image_cnt'] ;
			} else {
				$this->message = "No Galleries deleted.";
			}
		
		} else {
			$this->message = "No Galleries selected for deletion.";
		}
	}

	function deleteGallery($options, $gal_id)
	{
		global $wpdb;
		
		//This section deletes a Gallery
		
		if($gal_id){
			
			$ret['gal_deleted'] = $wpdb->query("DELETE FROM ".PSGALLERIESTABLE." WHERE gallery_id="
				. (int)$gal_id ." LIMIT 1" );
			
		}
		if($ret['gal_deleted']){
		
			$ret['deleted_image_cnt'] = $this->deleteGalleryImages($gal_id);
			
			$this->message = "Gallery deleted...Deleted Images: ".$ret['deleted_image_cnt'] ;
		}
				
		return $ret;
	}
	
	function deleteGalleryImages($gal_id){
	
		global $wpdb;
				
		$sql = $wpdb->prepare("SELECT image_id FROM " . PSIMAGESTABLE 
			. " WHERE gallery_id = %d", $gal_id);
						
		$imgs = $wpdb->get_col($sql);
		
		$img_cnt =0;
				
		if($imgs){
			foreach($imgs as $img){
				$img_cnt += $this->deleteImage($img);
			}
		}
		
		return $img_cnt;
	
	}
	
	function deleteImage($imgid){
		global $wpdb;
		if(current_user_can('level_10')){
			if($imgid){
				$row = $wpdb->get_row($wpdb->prepare(
					"SELECT file_name, thumb_url, medium_url, image_url, wp_attach_id FROM "
					.PSIMAGESTABLE. " WHERE image_id = %d", $imgid), ARRAY_A);
				if($row){
				
					if( $row['file_name'] && is_file( PSIMAGESPATH.$row['file_name'] )){
						unlink(PSIMAGESPATH.$row['file_name']);
					}
					
					if( $row['file_name'] && is_file( PSTHUMBSPATH.$row['file_name'] )){
						unlink(PSTHUMBSPATH.$row['file_name']);
					}
					
					// PhotoSmash now uses the WordPress upload folder structure
					
					if( $row['thumb_url'] && !$row['wp_attach_id'] ){
					
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
						
						/*
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
						*/
					
					}
					
				}
			
				$ret = $wpdb->query($wpdb->prepare('DELETE FROM '.
					PSIMAGESTABLE.' WHERE image_id = %d', $imgid ));
				
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSCUSTOMDATATABLE
					.' WHERE image_id = %d', $imgid));
					
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSRATINGSTABLE
					.' WHERE image_id = %d', $imgid));
				
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSRATINGSSUMMARYTABLE
					.' WHERE image_id = %d', $imgid));
					
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSCATEGORIESTABLE
					.' WHERE image_id = %d', $imgid));
				
				/*		We'e not going to ZAP Media Library images
				
				if((int)$row['wp_attach_id']){
					
					$wpdb->query($wpdb->prepare('DELETE FROM '. $wpdb->posts
						.' WHERE ID = %d', (int)$row['wp_attach_id']));
				
					$wpdb->query($wpdb->prepare('DELETE FROM '. $wpdb->postmeta
						.' WHERE post_id = %d', (int)$row['wp_attach_id']));	
				
				}
				*/	
				
			} else {$ret = 0;}
		} else {
			$ret = 0;
		}
		
		return $ret;
	}

	
	//Checks to see if we're saving options
	function saveGeneralSettings($ps){
		global $wpdb;
		global $bwbPS;
		
		//This section Saves the overall PhotoSmash defaults
		
			$ps['gallery_viewer'] = (int)$_POST['ps_gallery_viewer'];
			
			if(isset($_POST['ps_auto_add'])){
				$ps['auto_add'] = (int)$_POST['ps_auto_add'];
			}	
			
			$ps['version'] = PHOTOSMASHVERSION;
					
			$ps['use_wp_upload_functions'] = isset($_POST['ps_use_wp_upload_functions']) ? 1 : 0;
			$ps['add_to_wp_media_library'] = isset($_POST['ps_add_to_wp_media_library']) ? 1 : 0;

			$ps['exclude_piclens_js'] = isset($_POST['ps_exclude_piclens_js']) ? 1 : 0;
			
			$ps['max_file_size'] = (int)$_POST['ps_max_file_size'];
			
			$ps['mini_aspect'] = isset($_POST['ps_mini_aspect']) ? 0 : 1;
			$ps['mini_width'] = (int)$_POST['ps_mini_width'];
			$ps['mini_height'] = (int)$_POST['ps_mini_height'];

			$ps['thumb_aspect'] = isset($_POST['ps_thumb_aspect']) ? 0 : 1;
			$ps['thumb_width'] = (int)$_POST['ps_thumb_width'];
			$ps['thumb_height'] = (int)$_POST['ps_thumb_height'];
			
			$ps['medium_aspect'] = isset($_POST['ps_medium_aspect']) ? 0 : 1;
			$ps['medium_width'] = (int)$_POST['ps_medium_width'];
			$ps['medium_height'] = (int)$_POST['ps_medium_height'];
			
			$ps['image_aspect'] = isset($_POST['ps_image_aspect']) ? 0 : 1;
			$ps['image_width'] = (int)$_POST['ps_image_width'];
			$ps['image_height'] = (int)$_POST['ps_image_height'];

			
			$ps['img_perpage'] = (int)$_POST['ps_img_perpage'];
			$ps['img_perrow'] = (int)$_POST['ps_img_perrow'];
			
			$ps['anchor_class'] = esc_attr($_POST['ps_anchor_class']);
			
			if(isset($_POST['ps_img_rel'])){
				$ps['img_rel'] = esc_attr($_POST['ps_img_rel']);
			}
			if(isset($_POST['ps_add_text'])){
				$ps['add_text'] = esc_attr($_POST['ps_add_text']);
			}
			if(isset($_POST['ps_upload_form_caption'])){
				$ps['upload_form_caption'] = esc_attr($_POST['ps_upload_form_caption']);
			}
			if(isset($_POST['ps_img_class'])){
				$ps['img_class'] = esc_attr($_POST['ps_img_class']);
			}
			if(isset($_POST['ps_show_imgcaption'])){
				$ps['show_imgcaption'] = (int)$_POST['ps_show_imgcaption'];
			} else {
				$ps['show_imgcaption'] = 0;
			}
			
			$ps['tag_label'] = esc_attr( $_POST['ps_tag_label'] );
			$ps['tag_slug'] = sanitize_title( $_POST['ps_tag_slug'] );
			$ps['contributor_label'] = esc_attr( $_POST['ps_contributor_label'] );
			$ps['contributor_slug'] = sanitize_title( $_POST['ps_contributor_slug'] );
			
			$ps['gallery_viewer_slug'] = trim(sanitize_title( $_POST['ps_gallery_viewer_slug'] ));
			
			if(!$ps['gallery_viewer_slug']){ $ps['gallery_viewer_slug'] = 'psmash-gallery'; }
			
			$ps['can_delete_approved'] = isset($_POST['ps_can_delete_approved']) ? 1 : 0;
			
			$ps['nofollow_caption'] = isset($_POST['ps_nofollow_caption']) ? 1 : 0;
			
			//Alert on All Uploads
			$ps['alert_all_uploads'] = isset($_POST['ps_alert_all_uploads']) ? 1 : 0;
			
			if(isset($_POST['ps_image_alert_schedule'])){
				$ps['img_alerts'] = (int)$_POST['ps_image_alert_schedule'];
			}
			if(isset($_POST['ps_contrib_role'])){
				$ps['contrib_role'] = (int)$_POST['ps_contrib_role'];
			}
			if(isset($_POST['ps_img_status'])){
				$ps['img_status'] = (int)$_POST['ps_img_status'];
			}
			if(isset($_POST['ps_last_alert'])){
				$ps['last_alert'] = (int)$_POST['ps_last_alert'];
			}
			
			if(isset($_POST['ps_layout_id'])){
				$ps['layout_id'] = (int)$_POST['ps_layout_id'];
			} else {
				$ps['layout_id'] = -1;
			}
			
			$ps['use_advanced'] = isset($_POST['ps_use_advanced']) ? 1 : 0;
			$ps['use_urlfield'] = isset($_POST['ps_use_urlfield']) ? 1 : 0;
			$ps['use_attribution'] = isset($_POST['ps_use_attribution']) ? 1 : 0;
			$ps['custom_formid'] = (int)$_POST['ps_custom_formid'];
			$ps['use_customfields'] = isset($_POST['ps_use_customfields']) ? 1 : 0;
			$ps['use_thickbox'] = isset($_POST['ps_use_thickbox']) ? 1 : 0;
			$ps['tb_height'] = (int)$_POST['ps_tb_height'];
			$ps['tb_width'] = (int)$_POST['ps_tb_width'];
			$ps['caption_targetnew'] = isset($_POST['ps_caption_targetnew']) ? 1 : 0;
			$ps['img_targetnew'] = isset($_POST['ps_img_targetnew']) ? 1 : 0;
			
			$ps['imglinks_postpages_only'] = isset($_POST['ps_imglinks_postpages_only']) ? 1 : 0;
						
			if(isset($_POST['ps_use_alt_ajaxscript']) ){
				if(!file_exists(WP_PLUGIN_DIR.'/'.esc_attr($_POST['ps_alt_ajaxscript']))){
					if($this->message){
						$this->message .= "<br/>";
					}
					$this->message .= "<span style='color:red'>WARNING - Alternate Ajax Upload File does not exist:<br/>".WP_PLUGIN_DIR.'/'.esc_attr($_POST['ps_alt_ajaxscript'])."
					</span>";
				}
			}
			$ps['use_alt_ajaxscript'] = 
				isset($_POST['ps_use_alt_ajaxscript']) ? 1 : 0;	
						
			$ps['alt_ajaxscript'] = 
				esc_attr($_POST['ps_alt_ajaxscript']);
				
			$ps['alt_javascript'] = 
				$this->cleanSlashes($_POST['ps_alt_javascript']);
				
			$ps['alt_paging'] = 
				trim($this->cleanSlashes($_POST['ps_alt_paging']));
				
			$ps['uni_paging'] = isset($_POST['ps_uni_paging']) ? 1 : 0;	
			
			
			if($ps['use_thickbox']){
				$ps['uploadform_visible'] = 0;
			}else{
				$ps['uploadform_visible'] = isset($_POST['ps_uploadform_visible']) ? 1 : 0;
			}
			
			$ps['use_manualform'] = isset($_POST['ps_use_manualform']) ? 1 : 0;
			
			$ps['use_donelink'] = isset($_POST['ps_use_donelink']) ? 1 : 0;
			$ps['exclude_default_css'] = isset($_POST['ps_exclude_default_css']) ? 1 : 0;
			
			$ps['css_file'] = trim($_POST['ps_css_file']);
			$ps['date_format'] = trim($_POST['ps_date_format']);
			$ps['upload_authmessage'] = esc_attr(stripslashes(trim($_POST['ps_upload_authmessage'])));
			
			$ps['sort_field'] = (int)$_POST['ps_sort_field'];
			
			$ps['sort_order'] = (int)$_POST['ps_sort_order'];
			
			$ps['poll_id'] = (int)$_POST['ps_poll_id'];
			$ps['rating_position'] = (int)$_POST['ps_rating_position'];
			$ps['rating_allow_anon'] = isset($_POST['ps_rating_allow_anon']) ? 1 : 0;
			
			$ps['favorites'] = (int)$_POST['ps_favorites'];
			$ps['favorites_page'] = (int)$_POST['ps_favorites_page'];
			
			/* Contributor Gallery */
			$ps['contrib_gal_on'] = isset($_POST['ps_contrib_gal_on']) ? 1 : 0;
			$ps['suppress_contrib_posts'] = isset($_POST['ps_suppress_contrib_posts']) ? 1 : 0;
			
			/* Google Map Config */
			$ps['gmap_width'] = (int)$_POST['ps_gmap_width'] ? (int)$_POST['ps_gmap_width'] : 450;
			$ps['gmap_height'] = (int)$_POST['ps_gmap_height'] ? (int)$_POST['ps_gmap_height'] : 350;
			$ps['gmap_js'] = stripslashes(trim($_POST['ps_gmap_js']));
			$ps['gmap_layout'] = stripslashes(trim($_POST['ps_gmap_layout']));
			$ps['auto_maptowidget'] = isset($_POST['ps_auto_maptowidget']);
			$ps['tags_mapid'] = stripslashes(trim($_POST['ps_tags_mapid']));

			$ps['geocode_label'] = stripslashes(trim($_POST['ps_geocode_label']));
			$ps['geocode_description'] = stripslashes(trim($_POST['ps_geocode_description']));
			$ps['latitude_label'] = stripslashes(trim($_POST['ps_latitude_label']));
			$ps['longitude_label'] = stripslashes(trim($_POST['ps_longitude_label']));

			
			/* Moderation */
			$ps['mod_msg_subject'] = esc_attr(stripslashes(trim($_POST['ps_mod_msg_subject'])));
			if(!$ps['mod_msg_subject']){ $ps['mod_msg_subject'] = "Your Uploaded Image has been [status]";}
			$ps['mod_approve_msg'] = esc_attr(stripslashes(trim($_POST['ps_mod_approve_msg'])));
			$ps['mod_reject_msg'] = esc_attr(stripslashes(trim($_POST['ps_mod_reject_msg'])));
			$ps['mod_send_msg'] = isset($_POST['ps_mod_send_msg']) ? 1 : 0;
			
			$ps['api_enabled'] = isset($_POST['ps_api_enabled']) ? 1 : 0;
			$ps['api_disable_uploads'] = isset($_POST['ps_api_disable_uploads']) ? 1 : 0;
			
			$ps['api_upload_gallery'] = (int)$_POST['ps_api_upload_gallery'];
			$ps['api_post_layout'] = $_POST['ps_api_post_layout'];
			
			$ps['api_url'] = $bwbPS->h->validURL( $_POST['ps_api_url'] );
			$ps['api_logging'] = isset($_POST['ps_api_logging']) ? 1 : 0;
			
			if( !$ps['api_url'] ){
				$ps['api_url'] = admin_url('admin-ajax.php');
			}
			
			if(is_array($_POST['ps_api_categories'])){
				$catstemp = array_map("trim", $_POST['ps_api_categories']);
				$ps['api_categories'] = implode(",",$catstemp);
			} else {
				$ps['api_categories'] = trim($_POST['ps_api_categories']);
			}
			
			$ps['api_categories'] = stripslashes(trim($ps['api_categories']));
			
			$ps['api_tags'] = stripslashes(trim($_POST['ps_api_tags']));
			$ps['api_galleries'] = esc_attr(stripslashes(trim($_POST['ps_api_galleries'])));
			$ps['api_view_galleries'] = esc_attr(stripslashes(trim($_POST['ps_api_view_galleries'])));
			
			$ps['api_link_toattachments'] = isset($_POST['ps_api_link_toattachments']) ? 1 : 0;
			
			$ps['api_big_url'] = (int)$_POST['ps_api_big_url'];
			
			$ps['api_max_width'] = (int)$_POST['ps_api_max_width'] ? (int)$_POST['ps_api_max_width'] : 1024;
			
			if(isset($_POST['ps_api_custom_fields']) && is_array($_POST['ps_api_custom_fields'])){
			
				foreach($_POST['ps_api_custom_fields'] as $cfld){
					$cfarr[] = (int)$cfld;
				}
				
				if(is_array($cfarr)){
					$ps['api_custom_fields'] = implode(",", $cfarr);
				} else {
					$ps['api_custom_fields'] = "";
				}
			
			}else {
					$ps['api_custom_fields'] = "";
			}
			

			//Update the PS Defaults
			update_option('BWBPhotosmashAdminOptions', $ps);
			if($this->message){
						$this->message .= "<br/><br/>";
					}
			$this->message .= "PhotoSmash defaults updated...";
			return true;
	}
	
	function checkName($text)
	{
		$regex = "/^([A-Za-z0-9_\/]+)$/";
		if (preg_match($regex, $text)) {
			return TRUE;
		} 
		else {
			return FALSE;
		}
	}
	
	function pickGalleryCoverImage($gal_id){
	
		global $wpdb;
		
		$sql = "SELECT image_id FROM " . PSIMAGESTABLE 
			. " WHERE gallery_id = " . (int)$gal_id 
			. " AND status = 1 AND thumb_url <> '' ORDER BY RAND() LIMIT 1;";
		
		return $wpdb->get_var($sql);
	
	}
	
	function saveGallerySettings()
	{
		global $wpdb;
		// GLOBAL for $bwbPS is added in Cover Image row...move back here if you need elsewhere
		//This section saves Gallery specific settings
			$gallery_id = (int)$this->gallery_id;
			$d['status'] = isset($_POST['gal_status']) ? 1 : 0;
			$d['gallery_name'] = $_POST['gal_gallery_name'];
			
			$d['gallery_description'] = stripslashes($_POST['gal_gallery_description']);
			
			$d['post_id'] = (int)$_POST['gal_post_id'];
			
			if(!(int)$_POST['gal_cover_imageid'] && $gallery_id){
				$coverimg = $this->pickGalleryCoverImage($gallery_id);
			} else {
				$coverimg = (int)$_POST['gal_cover_imageid'];
			}
			
			$d['cover_imageid'] = $coverimg;
			
			
			$d['gallery_type'] = (int)$_POST['gal_gallery_type'];
			$d['img_perpage'] = (int)$_POST['gal_img_perpage'];
			$d['img_perrow'] = (int)$_POST['gal_img_perrow'];
			
			$d['mini_aspect'] = isset($_POST['gal_mini_aspect']) ? 0 : 1;
			$d['mini_width'] = (int)$_POST['gal_mini_width'];
			$d['mini_height'] = (int)$_POST['gal_mini_height'];
			
			$d['thumb_aspect'] = isset($_POST['gal_thumb_aspect']) ? 0 : 1;
			$d['thumb_width'] = (int)$_POST['gal_thumb_width'];
			$d['thumb_height'] = (int)$_POST['gal_thumb_height'];
			
			$d['medium_aspect'] = isset($_POST['gal_medium_aspect']) ? 0 : 1;
			$d['medium_width'] = (int)$_POST['gal_medium_width'];
			$d['medium_height'] = (int)$_POST['gal_medium_height'];
			
			$d['image_aspect'] = isset($_POST['gal_image_aspect']) ? 0 : 1;
			$d['image_width'] = (int)$_POST['gal_image_width'];
			$d['image_height'] = (int)$_POST['gal_image_height'];
			
			$d['anchor_class'] = esc_attr($_POST['gal_anchor_class']);
			$d['img_rel'] = esc_attr($_POST['gal_img_rel']);
			$d['add_text'] = esc_attr($_POST['gal_add_text']);
			$d['upload_form_caption'] = $_POST['gal_upload_form_caption'];
			$d['img_class'] = $_POST['gal_img_class'];
			$d['show_imgcaption'] = (int)$_POST['gal_show_imgcaption'];
			$d['nofollow_caption'] = isset($_POST['gal_nofollow_caption']) ? 1 : 0;
			$d['img_status'] = (int)$_POST['gal_img_status'];
			$d['contrib_role'] = (int)$_POST['gal_contrib_role'];
			$d['allow_no_image'] = isset($_POST['gal_allow_no_image']) ? 1 : 0;
			$d['suppress_no_image'] = isset($_POST['gal_suppress_no_image']) ? 1 : 0;
			$d['default_image'] = $_POST['gal_default_image'];
			
			$d['use_customform'] = isset($_POST['gal_use_customform']) ? 1 : 0;
			
			$d['custom_formid'] = (int)$_POST['gal_custom_formid'];
			$d['use_customfields'] = isset($_POST['gal_use_customfields']) ? 1 : 0;
			$d['layout_id'] = (int)$_POST['gal_layout_id'];
			
			$d['sort_field'] = (int)$_POST['gal_sort_field'];
			$d['sort_order'] = (int)$_POST['gal_sort_order'];
			
			$d['poll_id'] = (int)$_POST['gal_poll_id'];
			$d['rating_position'] = (int)$_POST['gal_rating_position'];
			$d['hide_toggle_ratings'] = isset($_POST['gal_hide_toggle_ratings']) ? 1 : 0;
			
			if($d['thumb_width']==0) $d['thumb_width'] = $psOptions['thumb_width'];
			if($d['thumb_height']==0) $d['thumb_height'] = $psOptions['thumb_height'];
			
			$d['max_user_uploads'] = (int)$_POST['gal_max_user_uploads'];
			$d['uploads_period'] = (int)$_POST['gal_uploads_period'];
			
			
			
			
			$tablename = $wpdb->prefix.'bwbps_galleries';
			
			if($gallery_id == 0){
				//Create new Gallery Record
				$d['created_date'] = date('Y-m-d H:i:s');
				$d['status'] = 1;
				if( $wpdb->insert($tablename,$d)){
					$this->message = "New Gallery Created: ".$d['gallery_name'];
					$this->gallery_id = $wpdb->insert_id;
				} else {
					$this->message = "Failed to create new gallery: ".$d['gallery_name']
						."<br/>Possibly a database error.  Go to Plugin Info and execute 'Update DB' button.";
					$this->msgclass = 'error';
				}
			}else{
				$where['gallery_id'] = $gallery_id;
				$updated = $wpdb->update($tablename, $d, $where);
				
				$this->message .= "<p>Gallery Updated: ".$d['gallery_name']. "</p>";
				
				if($updated){
					//Recalc Image Ratings
					$ratesumtable = PSRATINGSSUMMARYTABLE;
					$imagetable = PSIMAGESTABLE;
					
					$sql = "UPDATE $imagetable LEFT JOIN $ratesumtable ON  
						$imagetable.image_id = $ratesumtable.image_id 
						AND $imagetable.gallery_id = $ratesumtable.gallery_id
						AND $ratesumtable.poll_id = " . $d['poll_id'] ."
						SET $imagetable.votes_cnt = $ratesumtable.rating_cnt
						, $imagetable.votes_sum = $ratesumtable.avg_rating
						, $imagetable.rating_cnt = $ratesumtable.rating_cnt
						, $imagetable.avg_rating = $ratesumtable.avg_rating
						WHERE $imagetable.gallery_id = $gallery_id";
												
					$updated = $wpdb->query($sql);
					
					$this->message .= "<p>Image ratings updated: ".$updated."</p>";
				
				}

			}
	}
	
	function getGalleryDefaults(){
		//Get Defaults for New Galleries
		return $this->psOptions;
	}
	
	
	//Disply the General Settings Page
	function printGallerySettings(){
		
		if(isset($_POST['massGalleryEdit']) || isset($_POST['deletePSGMultipleGalleries']) ){
			$this->printMassGalleryEdit();
			return;
		}
	
		global $wpdb;
		$psOptions = $this->psOptions;		
		
		if($this->gallery_id){
			$galleryID = (int)$this->gallery_id;
		} else { $galleryID = 0; }
		
		$galleryDDL = $this->getGalleryDDL($galleryID);
		if($galleryID){
			$galOptions = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.PSGALLERIESTABLE.' WHERE gallery_id = %d',$galleryID), ARRAY_A);
			
			$imageCount = $wpdb->get_var("SELECT COUNT(image_id) as imgcnt FROM ".PSIMAGESTABLE
				." WHERE gallery_id = ".(int)$galleryID);
			
		} else {
			$galOptions = $this->getGalleryDefaults();
		}
		
		$layoutsDDL = $this->getLayoutsDDL((int)$galOptions['layout_id'], false, 0);
		
		?>
		<div class=wrap>
		
		<h2>PhotoSmash Galleries</h2>
		
		<?php 
		if(!get_option('photosmash_discontinued_msg')){ ?>
		<h3 style='color: red !important;'>ATTENTION! PhotoSmash is being discontinued...read about it <a href='http://smashly.net/blog/farewell-to-photosmash/'>here</a>. (<a href='admin.php?page=bwb-photosmash.php&ps-discon-msg=true'>Hide</a> this message.)</h3>
		<?php
		}
		
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">	
		<?php bwbps_nonce_field('delete-gallery'); ?>
<h3>Gallery Settings</h3>
<?php if($psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>
<p>
Select gallery: <?php echo $galleryDDL;?>&nbsp;<input type="submit" name="show_bwbPSSettings" value="<?php _e('Edit', 'bwbPS') ?>" />
<input type="submit" name="deletePhotoSmashGallery" onclick='return bwbpsConfirmDeleteGallery();' value="<?php _e('Delete', 'photosmash') ?>" /> 

<input type="submit" name="massGalleryEdit"  value="<?php _e('Mass Edit', 'photosmash') ?>" />
</p>
</form>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" onsubmit='return photosmash.verifyCreateGallery();'>
	<input type="hidden" id="bwbps_gallery_id" name="gal_gallery_id" value="<?php echo $galleryID;?>" />

<div id="bwbpsslider" class="wrap">
<ul id="bwbpstabs">

			<li><a href="#bwbps_galleryoptions">Gallery Options</a></li>
			<li><a href="#bwbps_uploading">Uploading</a></li>
			<li><a href="#bwbps_thumbnails">Images</a></li>

</ul>

<div id='bwbps_galleryoptions'>
		<?php bwbps_nonce_field('update-gallery'); ?>
	<table class="form-table">
	<?php if($galleryID){
	?>
	
	<tr>
		<th><b>Number of images:</b></th>
		<td style='font-size: 14px;'>
		<?php echo $imageCount;?> - <a href='admin.php?page=managePhotoSmashImages&psget_gallery_id=<?php echo $galOptions['gallery_id']; ?>' title='Photo Manager'>Manage images</a>
		</td>
	</tr>
	
	<tr>
		<th><b>Display code:</b></th>
		<td>[photosmash id=<?php echo $galleryID;?>]
		<br/>Copy/paste this code into Post or Page content <br/>where you want gallery to display...(include the []'s)<?php  echo "<br/>Associated with post: ".$galOptions['post_id']; ?>
		</td>
	</tr>
	
	<?php }?>
	
	<tr>
				<th>Activated:</th>
				<td>
					<input type="checkbox" name="gal_status" <?php if($galOptions['status'] == 1) echo 'checked'; ?> />
				</td>
	</tr>
	<tr>
				<th>Gallery Name:</th>
				<td>
					<input type='text' id='gal_gallery_name' name="gal_gallery_name" value='<?php echo $galOptions['gallery_name'];?>' style="width: 300px;"/>
				</td>
	</tr>
	
	<tr>
				<th>Gallery Description:</th>
				<td>
					<textarea id='gal_gallery_description' name="gal_gallery_description" cols="43" rows="2"><?php echo htmlentities( $galOptions['gallery_description']);?></textarea>
					<br/>Use standard HTML.  Show in Custom Layouts via Wrapper field by using the [gallery_description] tag
				</td>
	</tr>
	
	<tr>
				<th>Related Post ID:</th>
				<td>
					<input type='text' name="gal_post_id" value='<?php echo $galOptions['post_id'];?>' size="5"/> <a style='color: #cc0000 !important; font-size: 15px; font-weight: bold;' href='javascript: void(0);' onclick='alert("Attention! This is a very important setting to your Gallery.  It controls which Post this gallery will show up on when the photosmash shortcode is used without an ID. It also controls the Post ID that images loaded to this gallery will receive."); return false;'>?</a> 
					<?php if( (int)$galOptions['post_id'] ){ 
						$postpermalink = get_permalink($galOptions['post_id']);
						
						echo "<a target='_blank' href='" .$postpermalink ."'>" . $postpermalink . "</a>";
						
						}
					?>
				</td>
	</tr>
	
	
	<tr>
				<th>Gallery Image ID:</th>
				<td>
					<input type='text' name="gal_cover_imageid" value='<?php echo $galOptions['cover_imageid'];?>' size="5"/> (Blank for a Random image. Visit <a href='admin.php?page=managePhotoSmashImages&psget_gallery_id=<?php echo $galOptions['gallery_id']; ?>' title='Photo Manager' target='_blank'>Photo Manager</a> to find an image ID.)
					<?php
					
					if((int)$galOptions['cover_imageid']){
						global $bwbPS;
						$img = $bwbPS->getImage((int)$galOptions['cover_imageid']);
						$uploads = wp_upload_dir();
						echo "<br/><img src='" . $uploads['baseurl'] . '/' . $img['thumb_url'] . "' height='80' width='80' />
						";
					
					}					
					?>
				</td>
	</tr>
	
	<tr>
				<th>Gallery type:</th>
				<td>
					<?php
				
					/* ******** NOTE   ***********
						
						Any gallery above type = 9 should be a VIRTUAL GALLERY that does not
						get images posted to it.
						
						If this needs to change, you need to change ajax.php copyImagesToGallery()
						where it checks to see if a gallery_type < 10 to be eligible for move/copy operation
						
					*/
					?>
					<select name="gal_gallery_type">
						<option value="0" <?php if($galOptions['gallery_type'] == 0) echo 'selected=selected'; ?>>Photo gallery</option>
						
						<option value="97" <?php if($galOptions['gallery_type'] == 97) echo 'selected=selected'; ?>>WordPress Gallery</option>
						
						<option value="10" <?php if($galOptions['gallery_type'] == 10) echo 'selected=selected'; ?>>Contributor Gallery</option>
						
						<option value="9" <?php if($galOptions['gallery_type'] == 9) echo 'selected=selected'; ?>>Post-Author Uploads</option>
						
						<option value="20" <?php if($galOptions['gallery_type'] == 20) echo 'selected=selected'; ?>>Random Images</option>
						<option value="30" <?php if($galOptions['gallery_type'] == 30) echo 'selected=selected'; ?>>Recent Images</option>
						<option value="40" <?php if($galOptions['gallery_type'] == 40) echo 'selected=selected'; ?>>Tags Gallery</option>
						
						<option value="70" <?php if($galOptions['gallery_type'] == 70) echo 'selected=selected'; ?>>User Favorites</option>
						
						<option value="71" <?php if($galOptions['gallery_type'] == 71) echo 'selected=selected'; ?>>Most Favorited</option>
						
						<option value="99" <?php if($galOptions['gallery_type'] == 99) echo 'selected=selected'; ?>>Highest Ranked</option>
						<option value="100" <?php if($galOptions['gallery_type'] == 100) echo 'selected=selected'; ?>>Gallery Viewer</option>
						
						<option value="3" <?php if($galOptions['gallery_type'] == 3) echo 'selected=selected'; ?>>YouTube gallery (deprecated)</option>
						
						<?php
						 /*  We're blocking out the Video options right now
						<option value="4" <?php if($galOptions['gallery_type'] == 4) echo 'selected=selected'; ?>>Video - YouTube + Upload</option>
						<option value="5" <?php if($galOptions['gallery_type'] == 5) echo 'selected=selected'; ?>>Video - Uploads only</option>
						
						*/ 
						?>
						<option value="6" <?php if($galOptions['gallery_type'] == 6) echo 'selected=selected'; ?>>Mixed - Images + YouTube (deprecated)</option>

						
					</select>
				</td>
	</tr>
	
	<tr>
				<th>Display using Layout:</th>
				<td>
					<?php  echo $layoutsDDL;?>
				</td>
	</tr>	
	
	<tr>
				<th>Sort Images by:</th>
				<td>
					<select name="gal_sort_field">
						<option value="0" <?php if(!$galOptions['sort_field']) echo 'selected=selected'; ?>>When uploaded</option>
						
						<option value="1" <?php if($galOptions['sort_field'] == 1) echo 'selected=selected'; ?>>Manual sort</option>
						<?php /*
						<option value="2" <?php if($galOptions['sort_field'] == 2) echo 'selected=selected'; ?>>Custom field</option>
						*/
						?>
						<option value="3" <?php if($galOptions['sort_field'] == 3) echo 'selected=selected'; ?>>User ID</option>
						<option value="6" <?php if($galOptions['sort_field'] == 6) echo 'selected=selected'; ?>>User Name</option>
						<option value="7" <?php if($galOptions['sort_field'] == 7) echo 'selected=selected'; ?>>User Login</option>
						<option value="4" <?php if($galOptions['sort_field'] == 4) echo 'selected=selected'; ?>>Rating</option>
						<option value="5" <?php if($galOptions['sort_field'] == 5) echo 'selected=selected'; ?>>Favorited Count</option>
					</select>
					
					<input type="radio" name="gal_sort_order" value="0" <?php if(!$galOptions['sort_order']) echo 'checked'; ?>>Ascending &nbsp;
					
					<input type="radio" name="gal_sort_order" value="1" <?php if($galOptions['sort_order'] == 1) echo 'checked'; ?>>Descending
					
				</td>
			</tr>
	
			<tr>
				<th>Images per page:</th>
				<td>
					<input type='text' name="gal_img_perpage" value='<?php echo (int)$galOptions['img_perpage'];?>' style='width: 40px !important;'/>
					 <em>0 turns off paging and shows all images in gallery</em>
				</td>
			</tr>
			<tr>
				<th>Images per row (Standard Layout):</th>
				<td>
					<input type='text' name="gal_img_perrow" value='<?php echo (int)$galOptions['img_perrow'];?>' style='width: 40px !important;'/>
					 <em>0 places as many images per row as theme's width allows when you are using the Standard Layout</em>
				</td>
			</tr>
			
			
			<tr>
				<th>Image link (href) css class:</th>
				<td>
					<input type='text' name="gal_anchor_class" value='<?php echo $galOptions['anchor_class']; ?>'/> Set to 'thickbox' to use Thickbox to display images
				</td>
			</tr>
			
			<tr>
				<th>"Rel" parameter for image links:</th>
				<td>
					<input type='text' name="gal_img_rel" value='<?php echo $galOptions['img_rel'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Image css class:</th>
				<td>
					<input type='text' name="gal_img_class" value='<?php echo $galOptions['img_class']; ?>'/>
				</td>
			</tr>
			<tr>
				<th>Image caption style:</th>
				<td>
						<input type="radio" name="gal_show_imgcaption" value="0" <?php if($galOptions['show_imgcaption'] == 0) echo 'checked'; ?>>No caption<br/>
						<input type="radio" name="gal_show_imgcaption"  value="1" <?php if($galOptions['show_imgcaption'] == 1) echo 'checked'; ?>>Caption (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="7" <?php if($galOptions['show_imgcaption'] == 7) echo 'checked'; ?>>Caption (link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="2" <?php if($galOptions['show_imgcaption'] == 2) echo 'checked'; ?>>Contributor (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="3" <?php if($galOptions['show_imgcaption'] == 3) echo 'checked'; ?>>Contributor (link to website)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="10" <?php if($galOptions['show_imgcaption'] == 10) echo 'checked'; ?>>Contributor (link to WP author page)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="4" <?php if($galOptions['show_imgcaption'] == 4) echo 'checked'; ?>>Caption [by] Contributor (link to website)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="5" <?php if($galOptions['show_imgcaption'] == 5) echo 'checked'; ?>>Caption [by] Contributor (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="6" <?php if($galOptions['show_imgcaption'] == 6) echo 'checked'; ?>>Caption [by] Contributor (link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="11" <?php if($galOptions['show_imgcaption'] == 11) echo 'checked'; ?>>Caption [by] Contributor (link to WP author page)<br/>

						<hr/><span style='color: #888;'>Special: these also change thumbnail links (normal is link to image)</span><br/>
						<input type="radio" name="gal_show_imgcaption"  value="8" <?php if($galOptions['show_imgcaption'] == 8) echo 'checked'; ?>>No caption (thumbs link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="9" <?php if($galOptions['show_imgcaption'] == 9) echo 'checked'; ?>>Caption (thumbs & captions link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="12" <?php if($galOptions['show_imgcaption'] == 12) echo 'checked'; ?>>No caption (thumbs link to post)<br/>	
						<input type="radio" name="gal_show_imgcaption"  value="13" <?php if($galOptions['show_imgcaption'] == 13) echo 'checked'; ?>>Caption (thumbs & captions link to post)<br/>					
						<input type="radio" name="gal_show_imgcaption"  value="14" <?php if($galOptions['show_imgcaption'] == 14) echo 'checked'; ?>>No caption (thumbs link to WP Attachment Page)<br/>	
						<input type="radio" name="gal_show_imgcaption"  value="15" <?php if($galOptions['show_imgcaption'] == 15) echo 'checked'; ?>>Caption (thumbs & captions link to WP Attachment Page)<br/>	
						<br/>
						
						(Website links will be the website in the user's WordPress profile)<br/>
						(When 'user submitted url' is selected, but none exists, default is to user's WordPress profile)<br/>

						<input type="checkbox" name="gal_nofollow_caption" <?php if($galOptions['nofollow_caption'] == 1) echo 'checked'; ?> /> <a href='http://en.wikipedia.org/wiki/Nofollow'>NoFollow</a> on caption/contributor links
				</td>
			</tr>
			
			<tr>
				<th>Rating type:</th>
				<td>
					<select name="gal_poll_id">
						<option value="0" <?php if(!$galOptions['poll_id']) echo 'selected=selected'; ?>>None</option>
						<option value="-1" <?php if($galOptions['poll_id'] == -1) echo 'selected=selected'; ?>>Standard 5 Star</option>

						<option value="-2" <?php if($galOptions['poll_id'] == -2) echo 'selected=selected'; ?>>Standard Vote Up/Down</option>
						
						<option value="-3" <?php if($galOptions['poll_id'] == -3) echo 'selected=selected'; ?>>Standard Vote Up</option>

					</select>
				</td>
			</tr>
			
			<tr>
				<th>Rating position:</th>
				<td>
					<select name="gal_rating_position">
						<option value="0" <?php if(!$galOptions['rating_position']) echo 'selected=selected'; ?>>Overlay thumbnail</option>
						<option value="1" <?php if($galOptions['rating_position'] ==1) echo 'selected=selected'; ?>>Beneath caption</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Hide 'Toggle Ratings' link:</th>
				<td>
					<input type="checkbox" name="gal_hide_toggle_ratings" <?php if($galOptions['hide_toggle_ratings'] == 1) echo 'checked'; ?> /> hide the 'Toggle Ratings' link
				</td>
			</tr>
			
			
	</table>
</div>
<div id='bwbps_uploading'>
	<table class="form-table">
			<tr>
				<th>Max uploads per user:</th>
				<td>
					<input type='text' size="5" name="gal_max_user_uploads" value='<?php echo (int)$galOptions['max_user_uploads'];?>'/>
					 per time 
					<select name="gal_uploads_period">
						<option value="0" <?php if((int)$galOptions['uploads_period'] == 0) echo 'selected=selected'; ?>>Ever</option>
						<option value="1" <?php if((int)$galOptions['uploads_period'] == 1) echo 'selected=selected'; ?>>Hour</option>
						<option value="24" <?php if((int)$galOptions['uploads_period'] == 24) echo 'selected=selected'; ?>>24 hours</option>
						<option value="168" <?php if((int)$galOptions['uploads_period'] == 168) echo 'selected=selected'; ?>>Week</option>
					</select>
					<br/>Enter 0 for unlimited.
				</td>
			</tr>
			<tr>
				<th>Custom form name:</th>
				<td><?php echo $this->getCFDDL($galOptions['custom_formid']); ?> Only used when 'Use Custom Forms' is turned on in PhotoSmash Settings/Advanced</td>
			</tr>
				<tr>
				<th>Minimum role to upload photos:</th>
				<td>
					<select name="gal_contrib_role">
						<option value="-1" <?php if($psOptions['contrib_role'] == -1) echo 'selected=selected'; ?>>Anybody</option>
						<option value="0" <?php if($galOptions['contrib_role'] == 0) echo 'selected=selected'; ?>>Subscribers</option>
						<option value="1" <?php if($galOptions['contrib_role'] == 1) echo 'selected=selected'; ?>>Contributors/Authors</option>
						<option value="10" <?php if($galOptions['contrib_role'] == 10) echo 'selected=selected'; ?>>Admin</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Default moderation status:</th>
				<td>
					<select name="gal_img_status">
						<option value="0" <?php if(!$galOptions['img_status']) echo 'selected=selected'; ?>>Moderate</option>
						<option value="1" <?php if($galOptions['img_status'] == 1) echo 'selected=selected'; ?>>Active</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Text for Add Photo Link:</th>
				<td>
					<input type='text' name="gal_add_text" value='<?php echo $galOptions['add_text'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Upload form caption:</th>
				<td>
					<input type='text' name="gal_upload_form_caption" value='<?php echo $galOptions['upload_form_caption'];?>'/>
				</td>
			</tr>
			
			<tr>
				<th>Allow 'no image' uploads:</th>
				<td>
					<input type="checkbox" name="gal_allow_no_image" <?php if($galOptions['allow_no_image'] == 1) echo 'checked'; ?> /> 
					 Allows a record to be saved with no image file.
				</td>
			</tr>
			<tr>
				<th>Suppress 'no image' records:</th>
				<td>
					<input type="checkbox" name="gal_suppress_no_image" <?php if($galOptions['suppress_no_image'] == 1) echo 'checked'; ?> /> 
					 In normal galleries, will suppress records where no image exists.
				</td>
			</tr>
			<tr>
				<th>Default image on 'no image':</th>
				<td>
					<input type='text' name="gal_default_image" value='<?php esc_attr_e($galOptions['default_image']) ;?>'/>
					 Enter the file name of an image in the normal PhotoSmash image folders.  Will be used for 'no image' records if Suppress is off.
				</td>
			</tr>
	</table>
</div>
<div id='bwbps_thumbnails'>
	<table class="form-table">
			<tr>
			<th></th>
			<td><a style='color: #d54e21 !important; text-decoration: none !important;' href='<?php echo PHOTOSMASHWEBHOME; ?>tutorials/sizing-and-resizing-images/'  target='_blank' title='Video tutorial on sizing and resizing images.'>Get Help <img src='<?php echo BWBPSPLUGINURL;?>images/help.png' alt='Video - Sizing and Resizing images' /></a> - video tutorial on image sizes.</td>
			</tr>
			
			<tr>
				<th>Mini image size (px):</th>
				<td>
					<label>Width</label>
					<input type='text' class='small-text' name="gal_mini_width" value='<?php echo (int)$galOptions['mini_width'];?>'/>
					<label>Height</label>
					<input type='text' class='small-text' name="gal_mini_height" value='<?php echo (int)$galOptions['mini_height'];?>'/>
					
				</td>
			</tr>
			
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Mini cropping:</th>
				<td>
					<input type="checkbox" name="gal_mini_aspect" <?php if(!$galOptions['mini_aspect']) echo 'checked'; ?> /> Crop to exact dimensions
				</td>
			</tr>
	
						
			<tr>
				<th>Thumbnail size (px):</th>
				<td>
					<label>Width</label>
					<input type='text' class='small-text' name="gal_thumb_width" value='<?php echo (int)$galOptions['thumb_width'];?>'/>

					<label>Height</label>
					<input type='text' class='small-text' name="gal_thumb_height" value='<?php echo (int)$galOptions['thumb_height'];?>'/>
				</td>
			</tr>
			
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Thumbnail cropping:</th>
				<td>
					<input type="checkbox" name="gal_thumb_aspect" <?php if(!$galOptions['thumb_aspect']) echo 'checked'; ?> /> Crop to exact dimensions
					
				</td>
			</tr>

						
			
			
			<tr>
				<th>Medium size (px):</th>
				<td>
					<label>Width</label>
					<input type='text' class='small-text' name="gal_medium_width" value='<?php echo (int)$galOptions['medium_width'];?>'/>
					<label>Height</label>
					<input type='text' class='small-text' name="gal_medium_height" value='<?php echo (int)$galOptions['medium_height'];?>'/>
				</td>
			</tr>
			
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Medium cropping:</th>
				<td>
					<input type="checkbox" name="gal_medium_aspect" <?php if(!$galOptions['medium_aspect']) echo 'checked'; ?> /> Crop to exact dimensions
				</td>
			</tr>
			
			
			<tr>
				<th>Large size (px):</th>
				<td>
					<label>Width</label>
					<input type='text' class='small-text' name="gal_image_width" value='<?php echo (int)$galOptions['image_width'];?>'/> 
					<label>Height</label>
					<input type='text' class='small-text' name="gal_image_height" value='<?php echo (int)$galOptions['image_height'];?>'/>
					<br/>Enter 0 to set no maximum width/height
				</td>
			</tr>
			
			<tr>
				<th>Large cropping:</th>
				<td>
					<input type="checkbox" name="gal_image_aspect" <?php if(!$galOptions['image_aspect']) echo 'checked'; ?> /> Crop to exact dimensions
				</td>
			</tr>
	</table>
</div>



</div>
<p class="submit">
	<input type="submit" name="save_bwbPSGallery" class="button-primary" value="<?php _e('Save Gallery', 'bwbPS') ?>" />
</p>
</form>

<div>
		<a href="admin.php?page=bwb-photosmash.php" title="PhotoSmash General Settings">PhotoSmash General Settings</a> | 
		<a href="admin.php?page=managePhotoSmashImages&psget_gallery_id=<?php echo $galleryID;?>">Manage Images</a>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function(){
			jQuery('#bwbpsslider').tabs({ fxFade: true, fxSpeed: 'fast' });	
		});

</script>

<?php
	}
	
	
	//Disply the General Settings Page
	function printMassGalleryEdit(){
		global $wpdb;
		$psOptions = $this->psOptions;		
		
		if($this->gallery_id){
			$galleryID = (int)$this->gallery_id;
		} else { $galleryID = 0; }
		
		$galleryDDL = $this->getGalleryDDL($galleryID);
		if($galleryID){
			$galOptions = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.PSGALLERIESTABLE.' WHERE gallery_id = %d',$galleryID), ARRAY_A);
			
			$imageCount = $wpdb->get_var("SELECT COUNT(image_id) as imgcnt FROM ".PSIMAGESTABLE
				." WHERE gallery_id = ".(int)$galleryID);
			
		} else {
			$galOptions = $this->getGalleryDefaults();
		}
		
		$layoutsDDL = $this->getLayoutsDDL((int)$galOptions['layout_id'], false, 0 );
		
		?>
		<div class=wrap>
		
	<style type='text/css'>
		<!--
		/*	Admin */
		.bwbps-tabular td, .bwbps-tabular th{
			border-bottom: 1px solid #b4d2f5;
			background-color: #eef6fc;
		}
		-->
	</style>
		
		<h2>PhotoSmash Galleries</h2>
		
		<?php
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>
		

<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">	

<h3>Mass Edit Gallery Settings</h3>

<?php if($psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>

<table class="form-table">

	<tr>

		<th style='width: 92px; '>Select gallery:</th>
		<td><?php echo $galleryDDL;?>&nbsp;<input type="submit" name="show_bwbPSSettings" value="<?php _e('Single Edit', 'bwbPS') ?>" />
 <input type="submit" name="massGalleryEdit"  value="<?php _e('Mass Edit', 'photosmash') ?>" />
		</td>

	</tr>

</table>

</form>

<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<input type="hidden" id="bwbps_gallery_id" name="gal_gallery_id" value="<?php echo $galleryID;?>" />

<div id="multigallery-wrapper">

<p>
<a href='javascript: void(0);' onclick='bwbpsToggleDivHeight("multi-galleries", "100px"); return false;'>Toggle Full View</a> 

<span style='margin-left: 30px;'>

<button id='bwbpsDeleteSafety' onclick='$j(".bwbps-deletegroup").toggle(); return false;' class='bwbps-deletegroup'>Show Delete</button>

<button class='bwbps-deletegroup' onclick='$j(".bwbps-deletegroup").toggle(); return false;' style='display:none;'>Hide</button>

<input type='submit' name='deletePSGMultipleGalleries' onclick='return bwbpsConfirmDeleteMultipleGalleries();' class='bwbps-deletegroup' style='display: none; color: red;' value='Delete Selected' />

</span>

</p>

<!-- Galleries Box -->
<input type='checkbox' onclick="bwbpsToggleCheckboxes('bwbps_multigal', this.checked);"> toggle
<div id="multi-galleries" style="clear: both; height: 100px; overflow: auto; background-color: #fff; border: 2px solid #247aa3; padding: 10px;">
	<?php echo $this->getGalleriesCheckboxes(); ?>
</div>

<div id="slider" class="wrap" style='display:none;'>

<?php bwbps_nonce_field('update-galleries'); ?>
<?php bwbps_nonce_field('delete-gallery'); ?>
	<table class="form-table bwbps-tabular">
	<?php if($galleryID){
	?>
	<tr>
				<th>Basis gallery name:</th>
				<td>
					<input disabled type='text' name="gal_gallery_name" value='<?php echo $galOptions['gallery_name'];?>' style="width: 300px;"/>
				</td>
	</tr>
	<?php } ?>
	

	<tr>
				<th>Images per page:</th>
				<td>
					<input type='text' name="gal_img_perpage" value='<?php echo (int)$galOptions['img_perpage'];?>' style='width: 40px !important;'/>
					 <em>0 turns off paging</em>
				</td>
			</tr>
			<tr>
				<th>Images per row (Standard Layout):</th>
				<td>
					<input type='text' name="gal_img_perrow" value='<?php echo (int)$galOptions['img_perrow'];?>' style='width: 40px !important;'/>
					 <em>0 - as many images/row as theme's width allows (Standard Layout only)</em>
				</td>
			</tr>
			
			<tr>
				<th>Image link (href) css class:</th>
				<td>
					<input type='text' name="gal_anchor_class" value='<?php echo $galOptions['anchor_class']; ?>'/> Set to 'thickbox' to use Thickbox to display images
				</td>
			</tr>
			
			<tr>
				<th>"Rel" parameter for image links:</th>
				<td>
					<input type='text' name="gal_img_rel" value='<?php echo $galOptions['img_rel'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Default image css class:</th>
				<td>
					<input type='text' name="gal_img_class" value='<?php echo $galOptions['img_class']; ?>'/>
				</td>
			</tr>
			<tr>
				<th>Image caption style:</th>
				<td>
						<input type="radio" name="gal_show_imgcaption" value="0" <?php if($galOptions['show_imgcaption'] == 0) echo 'checked'; ?> />No caption<br/>
						<input type="radio" name="gal_show_imgcaption"  value="1" <?php if($galOptions['show_imgcaption'] == 1) echo 'checked'; ?> />Caption (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="7" <?php if($galOptions['show_imgcaption'] == 7) echo 'checked'; ?> />Caption (link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="2" <?php if($galOptions['show_imgcaption'] == 2) echo 'checked'; ?> />Contributor (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="3" <?php if($galOptions['show_imgcaption'] == 3) echo 'checked'; ?> />Contributor (link to website)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="4" <?php if($galOptions['show_imgcaption'] == 4) echo 'checked'; ?> />Caption [by] Contributor (link to website)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="5" <?php if($galOptions['show_imgcaption'] == 5) echo 'checked'; ?> />Caption [by] Contributor (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="6" <?php if($galOptions['show_imgcaption'] == 6) echo 'checked'; ?> />Caption [by] Contributor (link to user submitted url)
						<br/><hr/><span style='color: #888;'>Special: these also change thumbnail links (normal is link to image)</span><br/>
						<input type="radio" name="gal_show_imgcaption"  value="8" <?php if($galOptions['show_imgcaption'] == 8) echo 'checked'; ?> />No caption (thumbs link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="9" <?php if($galOptions['show_imgcaption'] == 9) echo 'checked'; ?> />Caption (thumbs & captions link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="12" <?php if($galOptions['show_imgcaption'] == 12) echo 'checked'; ?> />No caption (thumbs link to post)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="13" <?php if($galOptions['show_imgcaption'] == 13) echo 'checked'; ?>>Caption (thumbs & captions link to post)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="14" <?php if($galOptions['show_imgcaption'] == 14) echo 'checked'; ?> />No caption (thumbs link to WP Attachment Page)<br/>	
						<input type="radio" name="gal_show_imgcaption"  value="15" <?php if($galOptions['show_imgcaption'] == 15) echo 'checked'; ?> />Caption (thumbs & captions link to WP Attachment Page)<br/>									
						<br/>
						(Website links will be the website in the user's WordPress profile)<br/>
						(When 'user submitted url' is selected, but none exists, default is to user's WordPress profile)<br/>

						<input type="checkbox" name="gal_nofollow_caption" <?php if($galOptions['nofollow_caption'] == 1) echo 'checked'; ?> /> <a href='http://en.wikipedia.org/wiki/Nofollow'>NoFollow</a> on caption/contributor links
				</td>
			</tr>
		
			<tr>
				<th>Minimum role to upload photos:</th>
				<td>
					<select name="gal_contrib_role">
						<option value="-1" <?php if($psOptions['contrib_role'] == -1) echo 'selected=selected'; ?>>Anybody</option>
						<option value="0" <?php if($galOptions['contrib_role'] == 0) echo 'selected=selected'; ?>>Subscribers</option>
						<option value="1" <?php if($galOptions['contrib_role'] == 1) echo 'selected=selected'; ?>>Contributors/Authors</option>
						<option value="10" <?php if($galOptions['contrib_role'] == 10) echo 'selected=selected'; ?>>Admin</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Default moderation status:</th>
				<td>
					<select name="gal_img_status">
						<option value="0" <?php if(!$galOptions['img_status']) echo 'selected=selected'; ?>>Moderate</option>
						<option value="1" <?php if($galOptions['img_status'] == 1) echo 'selected=selected'; ?>>Active</option>
					</select>
				</td>
			</tr>
			<tr>
				<th>Upload form caption:</th>
				<td>
					<input type='text' name="gal_upload_form_caption" value='<?php echo $galOptions['upload_form_caption'];?>'/>
				</td>
			</tr>
			
			<tr>
				<th>Thumbnail width (px):</th>
				<td>
					<input type='text' name="gal_thumb_width" value='<?php echo (int)$galOptions['thumb_width'];?>'/>
				</td>
			</tr>
			
			<tr>
				<th>Thumbnail height (px):</th>
				<td>
					<input type='text' name="gal_thumb_height" value='<?php echo (int)$galOptions['thumb_height'];?>'/>
				</td>
			</tr>
			
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Thumbnail style:</th>
				<td>
					<input type="radio" name="gal_thumb_aspect" value="0" <?php if(!(int)$galOptions['thumb_aspect']) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="gal_thumb_aspect" value="1" <?php if((int)$galOptions['thumb_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain aspect ratio
				</td>
			</tr>
						
			<tr>
				<th>Medium width (px):</th>
				<td>
					<input type='text' name="gal_medium_width" value='<?php echo (int)$galOptions['medium_width'];?>'/>
				</td>
			</tr>
			
			<tr>
				<th>Medium height (px):</th>
				<td>
					<input type='text' name="gal_medium_height" value='<?php echo (int)$galOptions['medium_height'];?>'/>
				</td>
			</tr>
			
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Medium style:</th>
				<td>
					<input type="radio" name="gal_medium_aspect" value="0" <?php if(!(int)$galOptions['medium_aspect']) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="gal_medium_aspect" value="1" <?php if((int)$galOptions['medium_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain aspect ratio
				</td>
			</tr>
			
			<tr>
				<th>Max. image width (px):</th>
				<td>
					<input type='text' name="gal_image_width" value='<?php echo (int)$galOptions['image_width'];?>'/> 0 will maintain original width
				</td>
			</tr>
			<tr>
				<th>Max. image height (px):</th>
				<td>
					<input type='text' name="gal_image_height" value='<?php echo (int)$galOptions['image_height'];?>'/> 0 will maintain original height
				</td>
			</tr>
			
			<tr>
				<th>Image style:</th>
				<td>
					<input type="radio" name="gal_image_aspect" value="0" <?php if(!(int)$galOptions['image_aspect']) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="gal_image_aspect" value="1" <?php if((int)$galOptions['image_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain aspect ratio
				</td>
			</tr>
	</table>

</div><!-- end of #slider -->

</div> <!-- end of #multigallery-wrapper -->

</div>
<p class="submit">
	<input type="submit" name="save_bwbPSGallery" class="button-primary" value="<?php _e('Save Gallery', 'bwbPS') ?>" />
</p>
</form>


<div>
		<a href="admin.php?page=bwb-photosmash.php" title="PhotoSmash General Settings">PhotoSmash General Settings</a> | 
		<a href="admin.php?page=managePhotoSmashImages&psget_gallery_id=<?php echo $galleryID;?>">Manage Images</a>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function(){
			jQuery('#bwbpsslider').tabs({ fxFade: true, fxSpeed: 'fast' });	
		});

</script>

<?php
	}

	//Disply the General Settings Page
	function printGeneralSettings(){
		global $wpdb;
		
		$psOptions = $this->psOptions;
		
		?>
		<div class=wrap>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" id='bwbps_form_gensettings'>
		<input type="hidden" id='bwbps_gen_settingsform' name='bwbps_gen_settingsform' value='1' />
		<?php bwbps_nonce_field('update-gallery'); ?>
		
		
				
		<?php 
			$nonce = wp_create_nonce( 'bwbps_moderate_images' );
			echo '
		<input type="hidden" id="_moderate_nonce" name="_moderate_nonce" value="'.$nonce.'" />
		';
		
		?>
		
		<h2>PhotoSmash Galleries</h2>
		
		<?php 
		if(!get_option('photosmash_discontinued_msg')){ ?>
		<h3 style='color: red !important;'>ATTENTION! PhotoSmash is being discontinued...read about it <a href='http://smashly.net/blog/farewell-to-photosmash/'>here</a>. (<a href='admin.php?page=bwb-photosmash.php&ps-discon-msg=true'>Hide</a> this message.)</h3>
		<?php
		}
		
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>		
		<h3>PhotoSmash Default Settings</h3>
		
		<?php if($psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>
	
	<div id="slider" class="wrap">
	<span id="ps_savemsg" style="display: none; color: #fff; background-color: red; padding:3px; position: fixed; top: 0; right: 0;">saving...</span>
	<ul id="bwbpstabs">

		<li><a href="#bwbps_galleryoptions">Defaults</a></li>
		<li><a href="#bwbps_uploading">Uploading</a></li>
		<li><a href="#bwbps_moderation">Moderation</a></li>
		<li><a href="#bwbps_thumbnails">Images</a></li>
		<li><a href="#bwbps_advanced">Advanced</a></li>
		<li><a href="#bwbps_specgals">Spec. Galleries</a></li>
		<li><a href="#bwbps_maps">Maps</a></li>
		<li><a href="#bwbps_api">API</a></li>

	</ul>
	<div id='bwbps_galleryoptions'>
		<table class="form-table">
			<tr>
				<th>Need help?</th>
				<td style='font-size: 16px; color: #ff0000 !important;'><a href='http://smashly.net/photosmash-galleries/tutorials/'><span style='color: #ff0000 !important;'>Tutorials</span></a> <a href='<?php echo PHOTOSMASHWEBHOME; ?>tutorials/'  target='_blank' title='Video tutorials'><img src='<?php echo BWBPSPLUGINURL;?>images/help.png' alt='Video Tutorial' /></a> - <a href='http://smashly.net/community/'>Old Help Forum</a> - <a href='http://wordpress.org/tags/photosmash-galleries?forum_id=10'>Support on WordPress</a></td>
			<tr>
				<th>Gallery Viewer Page:</th>
				<td class='<?php if(!$psOptions['gallery_viewer']) echo 'message error'; ?>'>
					<?php 
	$args = array(
	    'name' => 'ps_gallery_viewer',
		'selected' => (int)$psOptions['gallery_viewer'],
		'show_option_none' => 'select page',
		'echo' => 0
		);
		$ddl_pages = wp_dropdown_pages( $args ); 
		if((int)$psOptions['gallery_viewer'] == -1){
			$galviewsel = " selected='selected' ";
		}
		
		$ddl_pages = str_replace('<option value="">select page</option>',
			'<option value="">select page</option>
			<option class="level-0" value="-1" '
			. $galviewsel . '>-- no gallery viewer --</option>', $ddl_pages);
			
		echo $ddl_pages;
		
		?>
		Set Page you want to have a gallery/image viewer on
				</td>
			</tr>
			
			<tr>
				<th>Auto-add gallery to posts:</th>
				<td>
					<em>NOTE: This feature is NOT recommended.  Use the shortcode [photosmash] instead.</em><br/>
					<select name="ps_auto_add">
						<option value="0" <?php if($psOptions['auto_add'] == 0) echo 'selected=selected'; ?>>No auto-add</option>
						<option value="1" <?php if($psOptions['auto_add'] == 1) echo 'selected=selected'; ?>>Add to top</option>
						<option value="2" <?php if($psOptions['auto_add'] == 2) echo 'selected=selected'; ?>>Add to bottom</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<th>Default Layout:</th>
				<td>
					<?php echo $this->getLayoutsDDL($psOptions['layout_id'], true, 0 );
					?> <a href='javascript: void(0);' class='psmass_update' id='save_ps_layout_id' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Default layout for displaying images
					
					<?php if($psOptions['use_advanced']){
						echo " - <a href='admin.php?page=editPSHTMLLayouts' title='Layout Editor'>Layout Editor</a>";
						}
					?>
				</td>
			</tr>
			
			<tr>
				<th>Sort Images by:</th>
				<td>
					<select name="ps_sort_field">
						<option value="0" <?php if(!$psOptions['sort_field']) echo 'selected=selected'; ?>>When uploaded</option>
						
						<option value="1" <?php if($psOptions['sort_field'] == 1) echo 'selected=selected'; ?>>Manual sort</option>
						<?php /*
						<option value="2" <?php if($psOptions['sort_field'] == 2) echo 'selected=selected'; ?>>Custom field</option>
						*/
						?>
						<option value="3" <?php if($psOptions['sort_field'] == 3) echo 'selected=selected'; ?>>User ID</option>
						<option value="6" <?php if($psOptions['sort_field'] == 6) echo 'selected=selected'; ?>>User Name</option>
						<option value="7" <?php if($psOptions['sort_field'] == 7) echo 'selected=selected'; ?>>User Login</option>
						<option value="4" <?php if($psOptions['sort_field'] == 4) echo 'selected=selected'; ?>>Rating</option>
						
						<option value="5" <?php if($psOptions['sort_field'] == 4) echo 'selected=selected'; ?>>Favorite Count (times favorited)</option>
						
					</select>
					 <a href='javascript: void(0);' class='psmass_update' id='save_ps_sort_field' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					
					<input type="radio" name="ps_sort_order" value="0" <?php if(!$psOptions['sort_order']) echo 'checked'; ?>>Ascending &nbsp;
					
					<input type="radio" name="ps_sort_order" value="1" <?php if($psOptions['sort_order'] == 1) echo 'checked'; ?>>Descending
					&nbsp;-&nbsp;<a href='javascript: void(0);' class='psmass_update' id='save_ps_sort_order' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					
				</td>
			</tr>
			
			<tr>
				<th>Default Images per page:</th>
				<td>
					<input type='text' id='ps_img_perpage' name="ps_img_perpage" value='<?php echo (int)$psOptions['img_perpage'];?>' style='width: 40px !important;'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_img_perpage' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					 <em>0 turns off paging and shows all images in galleries</em>
				</td>
			</tr>
			<tr>
				<th>Default Images per row (Standard Layout):</th>
				<td>
					<input type='text' name="ps_img_perrow" value='<?php echo (int)$psOptions['img_perrow'];?>' style='width: 40px !important;'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_img_perrow' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					 <em>0 places as many images per row as theme's width allows when using the Standard Layout</em>
				</td>
			</tr>
			
			<tr>
				<th>Image link (href) css class:</th>
				<td>
					<input type='text' name="ps_anchor_class" value='<?php echo $psOptions['anchor_class']; ?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_anchor_class' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Set to 'thickbox' to use Thickbox to display images
				</td>
			</tr>
			
			<tr>
				<th>"Rel" parameter for image links:</th>
				<td>
					<input type='text' name="ps_img_rel" value='<?php echo $psOptions['img_rel'];?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_img_rel' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			<tr>
				<th>Default image css class:</th>
				<td>
					<input type='text' name="ps_img_class" value='<?php echo $psOptions['img_class']; ?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_img_class' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			<tr>
				<th>Thumbnail & Caption link targets:</th>
				<td>
					<input type="checkbox" name="ps_img_targetnew" <?php if($psOptions['img_targetnew'] == 1) echo 'checked'; ?>> Thumbnail links open in new window<br/>
					<input type="checkbox" name="ps_caption_targetnew" <?php if($psOptions['caption_targetnew'] == 1) echo 'checked'; ?>> Caption links open in new window<br/>
				</td>
			</tr>
			<tr>
				<th>Default image caption style:</th>
				<td>
						<input type="radio" name="ps_show_imgcaption" value="0" <?php if($psOptions['show_imgcaption'] == 0) echo 'checked'; ?>>No caption<br/>
						<input type="radio" name="ps_show_imgcaption"  value="1" <?php if($psOptions['show_imgcaption'] == 1) echo 'checked'; ?>>Caption (link to image)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="7" <?php if($psOptions['show_imgcaption'] == 7) echo 'checked'; ?>>Caption (link to user submitted url)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="2" <?php if($psOptions['show_imgcaption'] == 2) echo 'checked'; ?>>Contributor (link to image)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="3" <?php if($psOptions['show_imgcaption'] == 3) echo 'checked'; ?>>Contributor (link to website)<br/>
						
						<input type="radio" name="ps_show_imgcaption"  value="10" <?php if($psOptions['show_imgcaption'] == 10) echo 'checked'; ?>>Contributor (link to WP author page)<br/>
						
						<input type="radio" name="ps_show_imgcaption"  value="4" <?php if($psOptions['show_imgcaption'] == 4) echo 'checked'; ?>>Caption [by] Contributor (link to website)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="5" <?php if($psOptions['show_imgcaption'] == 5) echo 'checked'; ?>>Caption [by] Contributor (link to image)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="6" <?php if($psOptions['show_imgcaption'] == 6) echo 'checked'; ?>>Caption [by] Contributor (link to user submitted url)<br/>
						
						<input type="radio" name="ps_show_imgcaption"  value="11" <?php if($psOptions['show_imgcaption'] == 11) echo 'checked'; ?>>Caption [by] Contributor (link to WP author page)<br/>
						
						<hr/><span style='color: #888;'>Special: these also change thumbnail links (normal is link to image)</span><br/>
						<input type="radio" name="ps_show_imgcaption"  value="8" <?php if($psOptions['show_imgcaption'] == 8) echo 'checked'; ?>>No caption (thumbs link to user submitted url)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="9" <?php if($psOptions['show_imgcaption'] == 9) echo 'checked'; ?>>Caption (thumbs & captions link to user submitted url)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="12" <?php if($psOptions['show_imgcaption'] == 12) echo 'checked'; ?>>No caption (thumbs link to post)<br/>	
						<input type="radio" name="ps_show_imgcaption"  value="13" <?php if($psOptions['show_imgcaption'] == 13) echo 'checked'; ?>>Caption (thumbs & captions link to post)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="14" <?php if($psOptions['show_imgcaption'] == 14) echo 'checked'; ?>>No caption (thumbs link to WP Attachment Page)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="15" <?php if($psOptions['show_imgcaption'] == 15) echo 'checked'; ?>>Caption (thumbs & captions link to WP Attachment Page)<br/>
						
						
						
						<a href='javascript: void(0);' class='psmass_update' id='save_ps_show_imgcaption' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Mass update galleries
						<br/>
						(Website links will be the website in the contributor's WordPress profile)<br/>
						(When 'user submitted url' is selected, but none exists, uses link in contributor's WordPress profile)<br/>
												
						<br/>
						
						<input type="checkbox" name="ps_nofollow_caption" <?php if($psOptions['nofollow_caption'] == 1) echo 'checked'; ?>> <a href='javascript: void(0);' class='psmass_update' id='save_ps_nofollow_caption' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> <a href='http://en.wikipedia.org/wiki/Nofollow'>NoFollow</a> on caption/contributor links
				</td>
			</tr>
			<tr>
				<th>Thumbs link to Post on Main Page</th>
				<td>
					<input type="checkbox" name="ps_imglinks_postpages_only" <?php if($psOptions['imglinks_postpages_only'] == 1) echo 'checked'; ?>> Select this to link thumbs to Posts on Main/Archive/Category pages.  Use linkages from above on Post pages.
				</td>
			
			</tr>
			
			<tr>
				<th>Favorites:</th>
				<td>
					<select name="ps_favorites">
						<option value="0" <?php if(!$psOptions['favorites']) echo 'selected=selected'; ?>>none</option>
						<option value="1" <?php if($psOptions['favorites'] ==1) echo 'selected=selected'; ?>>Top-left</option>
						<option value="2" <?php if($psOptions['favorites'] ==2) echo 'selected=selected'; ?>>Top-right</option>
						<option value="3" <?php if($psOptions['favorites'] ==3) echo 'selected=selected'; ?>>Bottom-left</option>
						<option value="4" <?php if($psOptions['favorites'] ==4) echo 'selected=selected'; ?>>Bottom-right</option>
						<option value="5" <?php if($psOptions['favorites'] ==5) echo 'selected=selected'; ?>>Left of Rating</option>
					</select> <a href='<?php echo PHOTOSMASHWEBHOME; ?>tutorials/favorites/'  target='_blank' title='Video tutorial for using Favorites.'><img src='<?php echo BWBPSPLUGINURL;?>images/help.png' alt='Video Tutorial' /></a> Allow users to favorite images
				</td>
			</tr>
			
			<tr>
				<th>Favorites Page:</th>
				<td>
				<?php $args = array(
    'selected'         => (int)$psOptions['favorites_page'],
    'echo'             => 1,
    'name'             => 'ps_favorites_page',
    'show_option_none' => 'none' ); 
    				wp_dropdown_pages( $args );
    			?>
				 Page to display logged in user's favorites. Use template tag photosmash_favlink({before}, {after}); to show link for logged in users.  Before and after are optional html.  Example: photosmash_favlink("&lt;li&gt;", "&lt;/li&gt;"); 
				</td>
			</tr>
			
			<tr>
				<th>Default rating type:</th>
				<td>
					<select name="ps_poll_id">
						<option value="0" <?php if(!$psOptions['poll_id']) echo 'selected=selected'; ?>>None</option>
						<option value="-1" <?php if($psOptions['poll_id'] == -1) echo 'selected=selected'; ?>>Standard 5 Star</option>

						<option value="-2" <?php if($psOptions['poll_id'] == -2) echo 'selected=selected'; ?>>Standard Vote Up/Down</option>
						
						<option value="-3" <?php if($psOptions['poll_id'] == -3) echo 'selected=selected'; ?>>Standard Vote Up</option>

					</select>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_poll_id' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> 
				</td>
			</tr>
			
			<tr>
				<th>Default rating position:</th>
				<td>
					<select name="ps_rating_position">
						<option value="0" <?php if(!$psOptions['rating_position']) echo 'selected=selected'; ?>>Overlay thumbnail</option>
						<option value="1" <?php if($psOptions['rating_position'] ==1) echo 'selected=selected'; ?>>Beneath caption</option>
					</select> <a href='javascript: void(0);' class='psmass_update' id='save_ps_rating_position' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			
			<tr>
				<th>Allow anonymous ratings:</th>
				<td>
					<input type="checkbox" name="ps_rating_allow_anon" <?php if( $psOptions['rating_allow_anon'] == 1) echo 'checked'; ?>/>  Ratings will be logged by user IP 
				</td>
			</tr>
			
			<tr>
				<th>Photo tags Page Title:</th>
				<td>
					<input type='text' name="ps_tag_label" value='<?php echo esc_attr($psOptions['tag_label']);?>'/>	will default to 'Photo tags'
				</td>
			</tr>
			
			<tr>
				<th>Photo tags URL Slug:</th>
				<td>
					<input type='text' name="ps_tag_slug" value='<?php echo esc_attr($psOptions['tag_slug']);?>'/>
					will default to 'photo-tag'
				</td>
			</tr>
			
			<tr>
				<th>Contributor taxonomy Page Title:</th>
				<td>
					<input type='text' name="ps_contributor_label" value='<?php echo esc_attr($psOptions['contributor_label']);?>'/>	will default to 'Contributors'.  This is similar to the Contributors page used by the Author's page feature except it uses a Custom Taxonomy for authors.
				</td>
			</tr>
			
			<tr>
				<th>Contributor URL Slug:</th>
				<td>
					<input type='text' name="ps_contributor_slug" value='<?php echo esc_attr($psOptions['contributor_slug']);?>'/>
					will default to 'contributor'<br/>
					<a href='javascript:void(0);' onclick="jQuery('#ps_update_contribs').val('true'); jQuery('#bwbps_form_gensettings').submit(); return false;" title='Update all images in the Contributor taxonomy'>Update All Images</a> - updates the contributor taxonomy for all images (could take a while depending on # of images in database)
					<input type='hidden' id='ps_update_contribs' name='ps_update_contribtags' value='' />
				</td>
			</tr>
			
			<tr>
				<th>Gallery Viewer Slug:</th>
				<td>
					<?php $psOptions['gallery_viewer_slug'] = $psOptions['gallery_viewer_slug'] ? $psOptions['gallery_viewer_slug'] : 'psmash-gallery'; ?>
					<input type='text' name="ps_gallery_viewer_slug" value='<?php echo esc_attr($psOptions['gallery_viewer_slug']);?>'/>
					will default to 'psmash-gallery'
				</td>
			</tr>
			<tr>
				<th>Users Can Delete Approved Images:</th>
				<td>
					<input type="checkbox" name="ps_can_delete_approved" <?php if( $psOptions['can_delete_approved'] == 1) echo 'checked'; ?>/> 
					Note: you still have to provide a [delete_button] in your Custom Layouts to give the user a button for deleting.  Leaving this blank only allows them to delete their 'unapproved' images...if you give them the button.
				</td>
			</tr>

		</table>
	</div>
	<div id='bwbps_uploading'>
		<table class="form-table">
				
			<tr>
				<th>Use WordPress Upload process:</th>
				<td>
					<input type="checkbox" name='ps_use_wp_upload_functions' <?php if($psOptions['use_wp_upload_functions'] == 1) echo 'checked'; ?>>
					Use built-in WordPress upload functions.
					<p>
						<input type="checkbox" name='ps_add_to_wp_media_library' <?php if($psOptions['add_to_wp_media_library'] == 1) echo 'checked'; ?>> Add images the WordPress Media Library
					</p><p><b>Don't use these if you're using custom upload code</b>...until you change your upload code to work with the new code in bwbps-wp-uploader.php</p>
					<p><b>Benefits?</b><ol><li>Fixes folder issues some people have had</li><li>Adds images to the WP Media Library...so you can edit them through WP functionality.</li></ol></p>
				</td>
			</tr>
			
			<tr>
				<th>Default Form for new Galleries:</th>
				<td>
					<?php 
						echo $this->getCFDDL($psOptions['custom_formid'], "ps_custom_formid");
					?> <a href='javascript: void(0);' class='psmass_update' id='save_ps_custom_formid' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Default upload form.  See custom form below
				</td>
			</tr>
		
		
			<tr>
				<th>Default Minimum role to upload photos:</th>
				<td>
					<select name="ps_contrib_role">
						<option value="-1" <?php if($psOptions['contrib_role'] == -1) echo 'selected=selected'; ?>>Anybody</option>
						<option value="0" <?php if($psOptions['contrib_role'] == 0) echo 'selected=selected'; ?>>Subscribers</option>
						<option value="1" <?php if($psOptions['contrib_role'] == 1) echo 'selected=selected'; ?>>Contributors/Authors</option>
						<option value="10" <?php if($psOptions['contrib_role'] == 10) echo 'selected=selected'; ?>>Admin</option>
					</select>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_contrib_role' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					<br/>Authors and Admins will not need moderation, even if selected below.
					<br/>Contributors, Subscribers, and "Anybody" will obey your moderation setting.
				</td>
			</tr>
			<tr>
				<th>Default moderation status:</th>
				<td>
					<select name="ps_img_status">
						<option value="0" <?php if($psOptions['img_status'] == 0) echo 'selected=selected'; ?>>Moderate</option>
						<option value="1" <?php if($psOptions['img_status'] == 1) echo 'selected=selected'; ?>>Approved</option>
					</select>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_img_status' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			<tr>
				<th>New Image email alert schedule:</th>
				<td>
					<input type="checkbox" name="ps_alert_all_uploads" <?php if( $psOptions['alert_all_uploads'] == 1) echo 'checked'; ?>/> Alert on all uploads (leave unchecked for moderations only)<br/>
					<select name="ps_image_alert_schedule">
						<option value="-1" <?php if($psOptions['img_alerts'] == -1) echo 'selected=selected'; ?>>alert immediately</option>
						<option value="0" <?php if($psOptions['img_alerts'] == 0) echo 'selected=selected'; ?>>no alert</option>
						<option value="600" <?php if($psOptions['img_alerts'] == 600) echo 'selected=selected'; ?>>every 10 min.</option>
						<option value="3600" <?php if($psOptions['img_alerts'] == 3600) echo 'selected=selected'; ?>>every 1 hr</option>
						<option value="21600" <?php if($psOptions['img_alerts'] == 21600) echo 'selected=selected'; ?>>every 6 hrs</option>
						<option value="86400" <?php if($psOptions['img_alerts'] == 86400) echo 'selected=selected'; ?>>every day</option>
					</select>
					<input type='hidden' name='ps_last_alert' value='<?php echo (int)$psOptions['last_alert'];?>'/> 
				</td>
			</tr>
			<tr>
				<th>Text for Add Photo link:</th>
				<td>
					<input type='text' name="ps_add_text" value='<?php echo $psOptions['add_text'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Upload form caption:</th>
				<td>
					<input type='text' name="ps_upload_form_caption" value='<?php echo $psOptions['upload_form_caption'];?>'/>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_upload_form_caption' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			<tr>
				<th>Include URL field for alternate Caption link:</th>
				<td>
					<input type="checkbox" name="ps_use_urlfield" <?php if( $psOptions['use_urlfield'] == 1) echo 'checked'; ?>/> Includes a field for user to supply an alternate URL for caption links.
				</td>
			</tr>
			
			<tr>
				<th>Include Attribution and License fields:</th>
				<td>
					<input type="checkbox" name="ps_use_attribution" <?php if( $psOptions['use_attribution'] == 1) echo 'checked'; ?>/> Includes a field for attribution and a dropdown of common image licenses.
				</td>
			</tr>
			<tr>
				<th>Use floating Upload Form:</th>
				<td>
					<input type="checkbox" id='bwbps_use_thickbox' name="ps_use_thickbox" onclick='bwbpsToggleFormAlwaysVisible();' <?php if($psOptions['use_thickbox'] == 1) echo 'checked'; ?>> Use Thickbox for floating upload form.
				</td>
			</tr>
			
			<tr>
				<th>Thickbox sizes:</th>
				<td>
					<input type="text" id='bwbps_tb_height' name="ps_tb_height" value='<?php echo $psOptions['tb_height'];?>'> height 
					<input type="text" id='bwbps_tb_width' name="ps_tb_width" value='<?php echo $psOptions['tb_width'];?>'> width 
				</td>
			</tr>
			
			
			<tr <?php if($psOptions['use_thickbox']){echo 'style="display:none;"';} ?> id='bwbps_formviz'>
				<th>Keep upload form visible:</th>
				<td>
					<input type="checkbox" id='bwbps_uploadform_visible' name="ps_uploadform_visible" onclick='bwbpsToggleFormAlwaysVisible();' <?php
					 if($psOptions['uploadform_visible'] == 1) echo 'checked'; 
			?>> Normally, do not use this setting.  Let PhotoSmash hide the form until ready for use.
				</td>
			</tr>
		</table>
	</div>
	<div id='bwbps_moderation'>
		<table class="form-table">
				
			<tr>
				<th>Email contributor on moderation:</th>
				<td>
					<input type="checkbox" name='ps_mod_send_msg' <?php if($psOptions['mod_send_msg'] == 1) echo 'checked'; ?>>
				</td>
			</tr>
			
			<tr>
				<th>Moderation Email Subject:</th>
				<td>
				
				<?php if( !$psOptions['mod_msg_subject'] ){
					$psOptions['mod_msg_subject'] = "Your Uploaded Image has been [status]";
				}
				?>
				
					<input type='text' size=80 maxlength="200" name="ps_mod_msg_subject" id="ps_mod_msg_subject" value="<?php esc_html_e($psOptions['mod_msg_subject']);?>" />
				</td>
			</tr>
			
			<tr>
				<th>Approve Message:</th>
				<td>
				
					<textarea name="ps_mod_approve_msg" cols="60" rows="5"><?php esc_html_e($psOptions['mod_approve_msg']);?></textarea>
					
				</td>
			</tr>
			
			<tr>
				<th>Reject Message:</th>
				<td>
					<textarea name="ps_mod_reject_msg" cols="60" rows="5"><?php esc_html_e($psOptions['mod_reject_msg']);?></textarea>
				</td>
			</tr>
		</table>
	</div>
	<div id="bwbps_thumbnails">
		<table class="form-table">
		
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Maximum image files size (bytes):</th>
				<td>
					<input type='text' name="ps_max_file_size" value='<?php echo (int)$psOptions['max_file_size'];?>'/> (ex. 600000 for 600kB) leave at 0 for unlimited<br/>Use this for Out of Memory problems during upload/resize
				</td>
			</tr>
			
			<tr>
			<th></th>
			<td><a style='color: #d54e21 !important; text-decoration: none !important;' href='<?php echo PHOTOSMASHWEBHOME; ?>tutorials/sizing-and-resizing-images/'  target='_blank' title='Video tutorial on sizing and resizing images.'>Get Help <img src='<?php echo BWBPSPLUGINURL;?>images/help.png' alt='Video - Sizing and Resizing images' /></a> - video tutorial on image sizes.</td>
			</tr>
			
			<tr>
				<th>Default Mini size (px):</th>
				<td>
					<label>Width</label>
					<input type='text' class='small-text' name="ps_mini_width" value='<?php echo (int)$psOptions['mini_width'];?>'/>   <a href='javascript: void(0);' class='psmass_update' id='save_ps_mini_width' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					<label>Height</label>
					<input type='text' class='small-text' name="ps_mini_height" value='<?php echo (int)$psOptions['mini_height'];?>'/>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_mini_height' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Mini cropping:</th>
				<td>
					<input type="checkbox" name="ps_mini_aspect" <?php if(!$psOptions['mini_aspect']) echo 'checked'; ?> />  <a href='javascript: void(0);' class='psmass_update' id='save_ps_mini_aspect' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Crop to exact dimensions
				</td>
			</tr>
	
						
			<tr>
				<th>Default Thumbnail size (px):</th>
				<td>
					<label>Width</label>
					<input type='text' class='small-text' name="ps_thumb_width" value='<?php echo (int)$psOptions['thumb_width'];?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_thumb_width' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>

					<label>Height</label>
					<input type='text' class='small-text' name="ps_thumb_height" value='<?php echo (int)$psOptions['thumb_height'];?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_thumb_height' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Thumbnail cropping:</th>
				<td>
					<input type="checkbox" name="ps_thumb_aspect" <?php if(!$psOptions['thumb_aspect']) echo 'checked'; ?> /> <a href='javascript: void(0);' class='psmass_update' id='save_ps_thumb_aspect' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Crop to exact dimensions
					
				</td>
			</tr>		
			
			<tr>
				<th>Default Medium size (px):</th>
				<td>
					<label>Width</label>
					<input type='text' class='small-text' name="ps_medium_width" value='<?php echo (int)$psOptions['medium_width'];?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_medium_width' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					<label>Height</label>
					<input type='text' class='small-text' name="ps_medium_height" value='<?php echo (int)$psOptions['medium_height'];?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_medium_height' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			
			<tr style='border-bottom: 1px solid #f0f0f0;'>
				<th>Medium cropping:</th>
				<td>
					<input type="checkbox" name="ps_medium_aspect" <?php if(!$psOptions['medium_aspect']) echo 'checked'; ?> /> <a href='javascript: void(0);' class='psmass_update' id='save_ps_medium_aspect' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Crop to exact dimensions
				</td>
			</tr>
			
			
			<tr>
				<th>Default Large size (px):</th>
				<td>
					<label>Width</label>
					<input type='text' class='small-text' name="ps_image_width" value='<?php echo (int)$psOptions['image_width'];?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_image_width' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					<label>Height</label>
					<input type='text' class='small-text' name="ps_image_height" value='<?php echo (int)$psOptions['image_height'];?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_image_height' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					<br/>Enter 0 to set no maximum width/height
				</td>
			</tr>
			
			<tr>
				<th>Large cropping:</th>
				<td>
					<input type="checkbox" name="ps_image_aspect" <?php if(!$psOptions['image_aspect']) echo 'checked'; ?> /> <a href='javascript: void(0);' class='psmass_update' id='save_ps_image_aspect' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Crop to exact dimensions
				</td>
			</tr>
		</table>
	</div>
	<div id="bwbps_advanced">
		<table class="form-table">
			<tr>
				<th>Show Advanced Menu Items:</th>
				<td>
					<input type="checkbox" name="ps_use_advanced" <?php if($psOptions['use_advanced'] == 1) echo 'checked'; ?>> Display advanced features menu items. See advanced features below
				</td>
			</tr>
			
			<tr>
				<th>Show Custom Fields in Default Form:</th>
				<td>
					<input type="checkbox" name="ps_use_customfields" <?php if($psOptions['use_customfields'] == 1) echo 'checked'; ?>> Use custom fields you define. See custom fields below
				</td>
			</tr>
			
			<tr>
				<th>Exclude PicLens javascript file:</th>
				<td>
					<input type="checkbox" name="ps_exclude_piclens_js" <?php if($psOptions['exclude_piclens_js'] == 1) echo 'checked'; ?>> Might need to exclude PicLens javascript file if another plugin is loading it.
				</td>
			</tr>
			
			<tr>
				<th>Customized Paging:</th>
				<td>
					Alternative paging parameter name (default is: bwbps_page_[gallery_id])<br/>
					<input type='text'  style='width: 300px;' name="ps_alt_paging" value='<?php echo $psOptions['alt_paging'];?>'/> 
					<br/>
					<input type="checkbox" name="ps_uni_paging" <?php if($psOptions['uni_paging'] == 1) echo 'checked'; ?>> Do not specify gallery ID in paging
				</td>
			</tr>
			
			<tr>
				<th>Alternate Ajax Upload Script:</th>
				<td>
					<input type="checkbox" name="ps_use_alt_ajaxscript" <?php if($psOptions['use_alt_ajaxscript'] == 1) echo 'checked'; ?>>
					<input type='text'  style='width: 300px;' name="ps_alt_ajaxscript" value='<?php echo $psOptions['alt_ajaxscript'];?>'/> <br/>Enter the file and it's path, relative to the 'wp-content/plugins/' folder (no leading '/'). Example:  myplugin/ajax_upload.php
					<br/><br/><b>WARNING: Please read description below before trying this.</b>
				</td>
			</tr>
			
			<tr>
				<th>Alternate Javascript Upload Function:</th>
				<td>
					<input type="text" style='width: 300px;' name="ps_alt_javascript" value="<?php echo $psOptions['alt_javascript']; ?>" /><br/>Enter the name of a javascript function that you must include into the page (probably through your own WP-Plugin) that will handle the returned results of an Ajax upload.  
					
					<p><b>IMPORTANT NOTE:</b><br/>Your function name should include any parameters that are to be passed.  Use parameter 'data' and 'statusText' for the JSON object and the upload status that are being returned by the server in the Ajax call.  These variables are passed into the function that will call your function. Your function can use them as follows...</p><p><b>Example:</b>  myFunction(data, statusText, form_pfx)</p><b>Leave Blank to not use this feature!</b>
					<br/><br/><b>WARNING: This too is a very advanced feature intended for developers.</b>
				</td>
			</tr>
			
			<tr>
				<th>Use Manual Form Placement:</th>
				<td>
					<input type="checkbox" name="ps_use_manualform" <?php if($psOptions['use_manualform'] == 1) echo 'checked'; ?>> This setting allows you to use a shortcode to place the upload form in posts/pages.  The Add Photos link will not display automatically...anywhere.  <br/><br/>Use this shortcode to place the form:  [photosmash form]<br/>If you want the gallery to display, you must use the normal gallery shortcode:  [photosmash] (for the default gallery for the post) or [photosmash id=?] (to specify a particular gallery by its ID #)
				</td>
			</tr>
			<tr>
				<th>Use link for 'Done':</th>
				<td>
					<input type="checkbox" name="ps_use_donelink" <?php if($psOptions['use_donelink'] == 1) echo 'checked'; ?>> Use a link for the 'Done' button on Upload Forms instead of a button. This is for compatibility with themes and plugins that break Button behavior.
				</td>
			</tr>
			<tr>
				<th>Exclude default CSS file:</th>
				<td>
					<input type="checkbox" name="ps_exclude_default_css" <?php if($psOptions['exclude_default_css'] == 1) echo 'checked'; ?>> Excludes the default CSS file (bwbps.css) from being loaded.  You will need to apply your own CSS styling to make things pretty.
				</td>
			</tr>
			
			<tr>
				<th>Custom CSS file to include:</th>
				<td>
					<input type='text' style='width: 300px;' name="ps_css_file" value='<?php echo trim($psOptions['css_file']);?>'/> Enter the folder/filename of a CSS file to include.  Note that this file must be in the themes directory (wp-content/themes/).  Example:  my_photosmash_theme/my_theme.css 
				</td>
			</tr>
			<tr>
				<th>Default Date Format:</th>
				<td>
					<input type='text' style='width: 300px;' name="ps_date_format" value='<?php echo trim($psOptions['date_format']);?>'/> Enter the PHP style date format.  Example: m/d/Y .  See the <a target='_blank' href='http://www.php.net/manual/en/function.date.php' title='PHP date formats'>PHP Manual</a> for a detailed description of date formatting string options.  
				</td>
			</tr>
			
			<tr>
				<th>Msg - No Authorization for Uploading:</th>
				<td>
					<input type='text' style='width: 300px;' name="ps_upload_authmessage" value='<?php echo trim($psOptions['upload_authmessage']);?>'/> Message to display when user does not have enough Authorization to upload images to a gallery.  Use normal HTML for this message.<br/>To display a link the login page, use:  [login]<br/>Leave blank to not display any message. </br>
					Note, you must have a Form Name in your shortcode for the message to be displayed.  If you're using the standard upload form, you can simply add 'form=std' to your shortcode, e.g. [photosmash form=std]<br/>
					If you want to NOT show the upload message on a certain gallery, you can add this to your shortcode:  'no_signin_msg=true', e.g. [photosmash form=std no_signin_msg=true]
				</td>
			</tr>
			
		</table>
		
		<?php if($psOptions['use_advanced']){
			$alayouts = "<a href='admin.php?page=editPSHTMLLayouts'>HTML Layouts</a>";
			$acf = "<a href='admin.php?page=editPSFields'>Custom Fields</a>";
			$acform = "<a href='admin.php?page=editPSForm'>Custom Form</a>";
		} else {
			$alayouts = "HTML Layouts";
			$acf = "Custom Fields";
			$acform = "Custom Form";
		}
		?>
		<h3>Description of Advanced Features</h3>
		<ol>
		<li><b><?php echo $alayouts;?></b> - you can create highly customized gallery formats using the HTML Layouts feature.  This allows you to enter an HTML template for images that can include custom fields as well as standard fields.</li>
		
		<li><b><?php echo $acform;?></b> -  allows you to create a custom layout for the image upload form. Set the Custom Form option to 'yes' and go to <?php echo $alayouts;?> to build your layout.</li>
		
		<li><b><?php echo $acf;?></b> - you can create custom fields for the upload form.  These fields can be displayed with images in completely customizable layouts by using the <?php echo $alayouts;?> feature. You do <b>not</b> have to use the custom form to use custom fields, but you will need to use Layouts to display their values in your galleries.</li>
		
		<li><b>Alternate Ajax Upload Scripts</b> - this feature allows you to plug in completely different behavior on Uploading of an image by utilizing your own server-side script. It is intended for developers wishing to take PhotoSmash way beyond its core uses. If you need to alter the saving behavior on upload and you are not a developer, there are plenty of WordPress developers who can give you a hand for a reasonable fee or potentially gratis, depending on the time involved. (Note: 'Use Advanced Features' does NOT need to be set.) <b>Use carefully, and at your own risk.</b></li>
		
		
		<li><b>Alternate Javascript Function</b> - this feature allows you to plug in completely different behavior in the browser after an image is uploaded.  If you need to alter the display behavior upon upload and you are not a developer, there are plenty of WordPress/Javascript developers who can give you a hand for a reasonable fee or potentially gratis, depending on the time involved. (Note: 'Use Advanced Features' does NOT need to be set.) <b>Use carefully, and at your own risk.</b></li>
		</ol>
	</div>
	
	<div id="bwbps_specgals">
		<table class="form-table">
			<tr>
				<th>Show Contributor Galleries:</th>
				<td>
					<input type="checkbox" name="ps_contrib_gal_on" <?php if($psOptions['contrib_gal_on'] == 1) echo 'checked'; ?>> Displays a gallery of all images by a contributor in the WordPress Author page. You don't have to tweak the file...this inserts the gallery as a new post at the top.
				</td>
			</tr>
			
			<tr>
				<th>Suppress Contributor posts:<br/><span style='font-size:10px; color: #999;' >Suppresses on author page only.</span></th>
				<td>
					<input type="checkbox" name="ps_suppress_contrib_posts" <?php if($psOptions['suppress_contrib_posts'] == 1) echo 'checked'; ?>> Will suppress all other posts on the authors page except for the Contributor Gallery.
				</td>
			</tr>
			
		</table>
	</div>
	
	<div id="bwbps_maps">
		<table class="form-table">
			<tr>
				<th>Auto-map to Widget:</th>
				<td>
					<input type="checkbox" name="ps_auto_maptowidget" <?php if($psOptions['auto_maptowidget'] == 1) echo 'checked'; ?>> will display all images to the Widget map with map ID: 'gmap_widget'<br/>Note: you have to add a PhotoSmash Map Widget in your Appearance / Widgets settings.  The map must have the ID 'gmap_widget'
				</td>
			</tr>
			
			<tr>
				<th>Map ID for Tag Galleries:</th>
				<td>
					<input type='text' name="ps_tags_mapid" value='<?php echo $psOptions['tags_mapid'];?>'/> if you want to map Tag Galleries, supply a Map ID.  Use 'true' or 'post' to automatically add a DIV with a proper ID.  Use 'gmap_widget' if you have a Widget with that map ID. If you use something else, you'll need to manually place a DIV with the ID where you need it.
				</td>
			</tr>
			
			<tr>
				<th>Google Maps Size:</th>
				<td>
					<?php 
						if(!(int)$psOptions['gmap_width']){ $psOptions['gmap_width'] = 400; }
						if(!(int)$psOptions['gmap_height']){ $psOptions['gmap_height'] = 300; }
					?>
					<label>Width</label>
					<input type='text' class='small-text' name="ps_gmap_width" value='<?php echo (int)$psOptions['gmap_width'];?>'/>
					<label>Height</label>
					<input type='text' class='small-text' name="ps_gmap_height" value='<?php echo (int)$psOptions['gmap_height'];?>'/>
				</td>
			</tr>
			
			<tr>
				<th>Layout for map popups:</th>
				<td>
					Enter HTML code like you would in Edit Layouts<br/>
					<textarea id="ps_gmap_layout" name="ps_gmap_layout" cols="60" rows="4"><?php esc_html_e($psOptions['gmap_layout']);?></textarea>
				</td>
			</tr>
			
			<tr>
				<th>Geocode label:</th>
				<td>
					<input type="text" id='ps_geocode' name="ps_geocode_label" value='<?php echo esc_attr($psOptions['geocode_label']);?>'> label for Geocode block in upload form (default: Geocode)
					<br/>
					To add Lat/Lng boxes + a geocoder to your upload form, include 'geocode=true' in your shortcode.
					<br/>
					To add the Lat/Lng boxes + a geocoder that uses the following custom fields for the address that gets geocoded:<br/>
					address, locality, region, postal_code, country
				</td>
			</tr>
			
			<tr>
				<th>Geocode description:</th>
				<td>
					<input type="text" id='ps_geocode_description' name="ps_geocode_description" value='<?php echo esc_attr($psOptions['geocode_description']);?>'> tells the user what's going on (has a default)
				</td>
			</tr>
			
			<tr>
				<th>Latitude label:</th>
				<td>
					<input type="text" id='ps_latitude_label' name="ps_latitude_label" value='<?php echo esc_attr($psOptions['latitude_label']);?>'> how you want to say 'Latitude' (default: Latitude)
				</td>
			</tr>
			
			<tr>
				<th>Longitude label:</th>
				<td>
					<input type="text" id='ps_longitude_label' name="ps_longitude_label" value='<?php echo esc_attr($psOptions['longitude_label']);?>'> how you want to say 'Longitude' (default: Longitude)
				</td>
			</tr>
			
			<tr>
				<th>Google Maps Javascript to load:</th>
				<td>
					You probably DON'T need this.<br/>Leave this blank to use Google Maps JavaScript API V3.<br/>
					Enter 'none' to skip loading the Google Maps Javascript (only needed if you have another plugin already loading it)<br/>
					PhotoSmash will only load the GMaps Javascript if needed<br/>
					<textarea id="ps_gmap_js" name="ps_gmap_js" cols="60" rows="4"><?php esc_html_e($psOptions['gmap_js']);?></textarea>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="bwbps_api">
		<h2>Mobile API Settings</h2>
		<h3>iPhone App is Available Now!</h3>
		The PhotoSmash iPhone App is now available on the iTunes App Store!  Visit <a href='http://smashly.net/photosmash-galleries/iphone/'>Smashly.net</a> for more info, or get your copy on the <a href='http://www.itunes.com/apps/photosmash/'>App Store</a> now!
		
		<h3 style='color:red;'>Special Invitation to PS Extend Users</h3>
		<b>PhotoSmash Extend users</b> may apply to have their sites included in the PhotoSmash Mobile Listing.  Visit the <a href='http://smashly.net/photosmash-galleries/extend/'>PhotoSmash Extend</a> homepage today!  We want family-friendly sites to be included in the downloadable list of PhotoSmash Sites!
		
		<p><a style='color: #d54e21 !important; text-decoration: none !important;' href='<?php echo PHOTOSMASHWEBHOME; ?>iphone/getting-started/'  target='_blank' title='Video tutorial on configuring the Mobile API.'>Get Help <img src='<?php echo BWBPSPLUGINURL;?>images/help.png' alt='Video - Configuring API for Mobile' /></a> - video tutorial on configuring the Mobile API.</p>
		<table class="form-table">
			
			<tr>
				<th>Enable Mobile API:</th>
				<td>
					<input type="checkbox" name="ps_api_enabled" <?php if($psOptions['api_enabled']) echo 'checked'; ?> /> turns on ability for mobile Apps to interact with PhotoSmash
				</td>
			</tr>
			
			<tr>
				<th>Disable Uploading via API:</th>
				<td>
					<input type="checkbox" name="ps_api_disable_uploads" <?php if($psOptions['api_disable_uploads']) echo 'checked'; ?> /> turns off ability for mobile Apps to Upload Images
				</td>
			</tr>
		
			<tr>
				<th>API URL:</th>
				<td>
					<?php
					if( !$psOptions['api_url'] ){
						$psOptions['api_url'] = admin_url('admin-ajax.php');
					}
					?>
				
					<input type="text" id='ps_api_url' name="ps_api_url" value='<?php echo esc_attr($psOptions['api_url']);?>' style='width:300px;' /> Should point to your WP-Admin.  Only change this if you're using MOD rewrite in your .htaccess.  It's not really necessary to change this, so feel free to leave it as is.
				</td>
			</tr>
			
			<tr>
				<th>Maximum Image Width:</th>
				<td><em>Max pixels in longest side:</em><br/>
					<input type="text" id='ps_api_tags' name="ps_api_max_width" value='<?php 
						echo ((int)$psOptions['api_max_width'] ? (int)$psOptions['api_max_width'] : 1024);
					?>' /> <br/>(the phone will resize the image using this as maximum pixels in longest side - so if you enter 800, then in landscape, the width will be a maximum of 800 pixels...in portrait, the height will be max of 800)
				</td>
			</tr>
			
			<tr>
				<th>Use for Large Image Size:</th>
				<td><em>Size of image to send for view large images Mobile Devices:</em><br/>
					
					<select name="ps_api_big_url">
						<option value="0" <?php if((int)$psOptions['api_big_url'] == 0) echo 'selected=selected'; ?>>Medium image</option>
						<option value="1" <?php if($psOptions['api_big_url'] == 1) echo 'selected=selected'; ?>>Large/Full image</option>
						<option value="2" <?php if($psOptions['api_big_url'] == 2) echo 'selected=selected'; ?>>Mini image</option>
						<option value="3" <?php if($psOptions['api_big_url'] == 3) echo 'selected=selected'; ?>>Thumb image</option>
					</select>
				<br/>This is the image size that will be used when the user taps an image in the Photo Viewer to view it's full size.  You want to aim for something 800x600 or less since sending big images takes a while to download on mobile devices.  These are the sizes that you set in Gallery Settings.
				</td>
			</tr>
			
			<tr>
				<th>Default Upload Gallery:</th>
				<td>
					<?php 
						$api_upgal = (int)$psOptions['api_upload_gallery'];
						echo $this->getGalleryDDL($api_upgal, "Select", "", "ps_api_upload_gallery", 40, true, true);
						
					?>
					
				</td>
			</tr>
			
			<tr>
				<th>Upload Gallery List:</th>
				<td>
					<em>List of Galleries that user can select from to upload to (<b>Gallery Names</b>, comma separated):</em><br/>
					<input style='width: 300px;' type="text" id='ps_api_galleries' name="ps_api_galleries" value='<?php echo esc_attr($psOptions['api_galleries']);?>'> Names
				</td>
			</tr>
			
			<tr>
				<th>Viewable Gallery List:</th>
				<td>
					<em>List of Gallery IDs for images that will be viewable in the Mobile app (<b>Gallery IDs</b>, comma separated):</em><br/>
					<input type="text" id='ps_api_view_galleries' name="ps_api_view_galleries" value='<?php echo esc_attr($psOptions['api_view_galleries']);?>' /> Numeric IDs<br/>
					<em>You can find Gallery IDs in the Drop Down (it's the number right after 'ID:').</em>
				</td>
			</tr>
			
			<tr>
				<th>Prefer Link to Attachment Pages:</th>
				<td>
					<em>Link to Image Attachment Pages if they Exist (otherwise, links to Posts first):</em><br/>
					<input type="checkbox" name="ps_api_link_toattachments" <?php if($psOptions['api_link_toattachments']) echo 'checked'; ?> />
				</td>
			</tr>
			
			<tr>
				<th>Tag List:</th>
				<td><em>List of Tags that user can select from (comma separated):</em><br/>
					<input style='width: 400px'  type="text" id='ps_api_tags' name="ps_api_tags" value='<?php echo esc_attr($psOptions['api_tags']);?>'>
				</td>
			</tr>
			
			<tr>
				<th>Category List:</th>
				<td>
					<em>(PhotoSmash Extend - for New Posts on Upload)</em>
					<div style='height:100px; overflow: auto;'>
						<?php
							echo $this->getAPICategoryList($psOptions['api_categories']);
						?>
					</div>
				</td>
			</tr>
			
			<?php
			
			$custom_fields_cbx = $this->getCustomFieldsCheckBoxes($psOptions['api_custom_fields']);
			
			if($custom_fields_cbx){
			
				?>
			<tr>
				<th>Custom Fields:</th>
				<td>
					<em>Select custom fields for uploads:</em><br/>
					<?php 
					echo $custom_fields_cbx;
					?>
				</td>
			</tr>	
				<?php
			
			}
			?>
			
			<tr>
				<th>Layout for New Posts:</th>
				<td>
					<?php 
						echo $this->getLayoutsDDL($psOptions['api_post_layout'], false, false, 'ps_api_post_layout', true);
					?> (Requires PhotoSmash Extend) For Extend users using the New Post feature, all of your New Posts via Mobile will use this layout unless you use the 'postcat_XX' layout naming schema to work with Categories.  <br/><br/>Leave BLANK to NOT create a new post. Post on Upload must be enabled in PSmashExtend settings for posts to be created.
				</td>
			</tr>
			
			<tr>
				<th>Enable Mobile Logging:</th>
				<td>
					<input type="checkbox" name="ps_api_logging" <?php if($psOptions['api_logging']) echo 'checked'; ?> /> - <b>logging does NOT record phone number, user name, or device ID.</b>  Purpose: aids in detecting use and abuse patterns. How are unique users identified?  The user's phone records the time they first started using PhotoSmash iPhone app.  This time is passed as an identifier - as such, this timestamp doesn't tell you very much, but does allow you to distinguish between different timestamps.  Logging does not help you determine WHO is using or abusing your system, merely whether it is a mobile user or not.
				</td>
			</tr>
			<tr>
				<th><p class="submit">
					<input type="submit" name="update_bwbPSDefaults" class="button-primary" value="<?php _e('Update Defaults', 'bwbPS') ?>" />
					</p>
				</th>
				<td>
					Days to view: <input type="text" name="bwbps_logged_days" value='<?php
		echo ((int)$_REQUEST['bwbps_logged_days'] ? (int)$_REQUEST['bwbps_logged_days'] : 1);
		?>' size='5'/> <input type="submit" name="view_log" class="button-primary" value="<?php _e('View Log', 'bwbPS'); ?>" />  (log will appear beneath the form)
				</td>
			</tr>
			
		</table>
	</div>
	
	</div>
	<p class="submit">
		<input type="submit" name="update_bwbPSDefaults" class="button-primary" value="<?php _e('Update Defaults', 'bwbPS') ?>" /> &nbsp; &nbsp; <input type="submit" name="reset_bwbPSDefaults" onclick="return bwbpsConfirmResetDefaults();" class="button-primary" value="<?php _e('Reset Defaults', 'bwbPS') ?>" /> &nbsp; &nbsp; <a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a>
	</p>
</form>


<script type="text/javascript">
	jQuery(document).ready(function(){
			jQuery('#slider').tabs({ fxFade: true, fxSpeed: 'fast' });	
		});

</script>

<?php
	if(isset($_REQUEST['view_log'])){
		$daysback = (int)$_REQUEST['bwbps_logged_days'] ? (int)$_REQUEST['bwbps_logged_days'] : 1;
		echo  $this->getParamTable($daysback);
	}
?>
</div>
<?php 
	
	}
	
	function getParamTable($timeframe){
		global $wpdb;
		
		$sql = "SELECT * FROM " . PSPARAMSTABLE 
			. " WHERE param_group LIKE 'mob-%' AND updated_date + interval " 
			. (int)$timeframe . " DAY > now() ORDER BY param_group, updated_date DESC";
			
		$res = $wpdb->get_results($sql, ARRAY_A);
		
		if(is_array($res)){
			$i = 1;
			foreach($res as $row){
				
				$retrow = "<td>" . $i++ . "</td>";
				
				foreach($row as $r){
					$retrow .= "<td>" . $r . "</td>";
				}
	
				$ret .= "<tr>" . $retrow . "</tr>";
				$retrow = '';
			
			}
		}
		
		if($ret){
		
			$ret = "<thead><tr><th>#</th><th>ID</th><th>Action</th><th><a href='javascript:void(0)' onclick='alert(\"The iPhone App creates a timestamp that when the App is first run.  This lets you discern unique users, but not their identity.\");'>Unique ID</a></th><th>Time of Action</th><th>[blank val]</th><th>[blank text]</th><th>IP Address</th></tr></thead>" . $ret;
		
			return "<table class='widefat'> " . $ret . "</table>";
		}
	
	}
	
	// Get Form for Update Lat and Lng by clicking Google Map
	function getMapForm(){
		
		?>
		<script type="text/javascript">
		//<![CDATA[
		
			jQuery(document).ready(function() {
				bwbmap_post_map = bwb_gmap.showMap( "bwbps_post_map", 38, -60, 2);
				
				bwbmap_post_map.setZoom(2);
				
				google.maps.event.addListener(bwbmap_post_map, 'click', function(event) {
					if(typeof(bwb_marker) == "object"){ bwb_marker.setMap(null); }
				    bwb_marker = bwb_gmap.addMarker(bwbmap_post_map, event.latLng);
					bwbmap_post_map.setCenter(event.latLng);
				    photosmash.setLatLngEdit(event.latLng);
				});
				
			});
			
		
		//]]>
		</script>
		

		<h3>Edit Image Locations</h3>
		
		<div style='margin-top: 20px; border: 3px #a0a0a0 solid; background-color: #f0f0f0; padding: 5px; height: 115px; width: 720px;' id='image-loc-editing'>
			<div style='float:left;'>
				<div id='bwbmap_image' style='float: left;'></div>
				<div style='float:left; padding: 0 10px; position: relative;'>
					<a href='javascript: void(0);' onclick='photosmash.mapEditDone(); return false;'>Back to Images</a>
					<span class="ps_savemsg" style="display: none; color: #fff; background-color: red; padding:3px; position: fixed; top: 0; right: 0;">saving...</span>
					<br/>
					To set latitude / longitude:
					<br/><b><span style='color: #cc0000;'>Click Map</span> or use "Mark Address"</b>
				</div>
				<div style='clear: both; padding-top: 8px;'>
					<input id="bwbmap_address" type="textbox" value="St.Louis, MO" size="40">
					<input type="button" value="Mark Address" onclick="bwb_gmap.codeAddress(bwbmap_post_map, 'bwbmap_address'); return false;">
				</div>
			</div>			
			<div style='float: right; border-left: 1px #999 solid; padding: 0 10px 0 10px; height: 110px;'>
				<b>Lat/Lng for image: <span id='bwbmap_image_id_disp'></span></b>
				<p>Lat: <input type='text' class='small-text' name="ps_lat" id='bwbmap_lat' value='0'/>
				<input class='button-primary' type='button' id='bwbmap_save_btn' value='Save' onclick='bwb_gmap.saveLatLng(jQuery("#bwbmap_image_id").val(), jQuery("#bwbmap_lat").val(), jQuery("#bwbmap_lng").val(), jQuery("#_moderate_nonce").val()  ); return false;' /></p>
				<p>Lng: <input type='text' class='small-text' name="ps_lng" id='bwbmap_lng' value='0'/>
				<input type='hidden' class='small-text' name="ps_imgid" id='bwbmap_image_id' value='0'/>
				<input class='button' type='button' id='bwbmap_set_btn' onclick='bwb_gmap.clearMarker(bwb_marker); bwb_marker = bwb_gmap.simpleMarker(bwbmap_post_map, jQuery("#bwbmap_lat").val(), jQuery("#bwbmap_lng").val()); return false;' value='Set' /></p>
				
			</div>
		</div>	
		<div id='bwbps_post_map' class='bwbps_gmap bwbps_gmap_ ' style='width: 80%; height: 370px;'></div>
		
		<?php
		
		return;	
	
	}
	
	/**
	 * printManageImages()
	 * 
	 * @access public 
	 * @prints the manage images page
	 */
	function printManageImages()
	{
	
		global $wpdb;
		global $bwbPS;
		$psOptions = $this->psOptions;
		
		
		if(isset($_POST['showModerationImages'])){
			//Getting images needing moderation
			$galleryID ='moderation';
			$ddlID = 0;
			$caption = " > Images for Moderation";
			$imgcount = $this->getGalleryImageCount('mod');
		} else {
			if(isset($_POST['showAllImages'])){
				//Getting all images
				$galleryID ='all';
				$ddlID = 0;
				$caption = " > All Images";
				$imgcount = $this->getGalleryImageCount('all');
			} else {
				//We're getting a specific Gallery	
				if($this->gallery_id){
					$galleryID = $this->gallery_id;
				} else { 
					if(isset($_GET['psget_gallery_id'])){
						$galleryID = (int)$_GET['psget_gallery_id'];
					}else{
						$galleryID = 0; 
					}
				}
				$ddlID = $galleryID;
				
				$imgcount = $this->getGalleryImageCount($galleryID);
				$bwbPS->img_funcs->updateGalleryImageCount((int)$galleryID,0, $imgcount);
			}
		}
		
		if(!$imgcount){ $imgmsg = 'no images'; } else { $imgmsg = $imgcount . " images";}
		
		if(!$galleryID){
			$galleryID ='moderation';
		}
		
		$start = 0;
		$limit = 0;
		if(isset($_POST['bwbpsStartImg'] )){$start = (int)$_POST['bwbpsStartImg'];}
		if(isset($_POST['bwbpsLimitImg'] )){$limit = (int)$_POST['bwbpsLimitImg'];}
		
		if($start > 0){ $start--; }
		if($limit < 1){ $limit = 50; }
		
		$nonce = wp_create_nonce( 'bwbps_moderate_images' );
		
		$result = $this->getGalleryImages($galleryID, true, $limit, $start, $nonce);
		$galleryDDL = $this->getGalleryDDL($ddlID, "Select", "", "gal_gallery_id", 30, true, true);
		
		$start++; //set it up for the form below
		
		if($ddlID){
			$galOptions = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.PSGALLERIESTABLE.' WHERE gallery_id = %d',$ddlID), ARRAY_A);
			$caption = " > ".$galOptions['gallery_name'];
		}

		?>
		
		<div class=wrap>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<?php bwbps_nonce_field('update-gallery'); ?>
		<h2>PhotoSmash Galleries</h2>
		
		<?php
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>		
		<h3>Photo Manager<?php echo $caption;?> [<?php echo $imgmsg; ?>]</h3>
		<?php if($psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>
		<br/>
		<?php 
			echo $galleryDDL;
		?>&nbsp;<input type="submit" name="show_bwbPSSettings" value="<?php _e('Edit', 'bwbPS') ?>" />
			&nbsp;<input type="submit" name="showModerationImages" value="<?php _e('Moderation/New', 'bwbPS') ?>" />
			&nbsp;<input type="submit" name="showAllImages" value="<?php _e('All Images', 'bwbPS') 
				?>" />	
		
		
			Show: <input type='text' name='bwbpsLimitImg' size=4 value='<?php echo $limit;
				?>' /> |  Start: <input type='text' name='bwbpsStartImg' size=4 value='<?php  echo $start;
				?>' />
		
		<div style='margin: 5px 0; padding: 3px 0; background-color: #fff; border-bottom: 2px solid #c0c0c0;'>
		<?php 
		
		if((int)$galleryID){
		?>		
		<a title='Upload Images' href='media-upload.php?type=image&bwbps_galid=<?php 
			echo $galleryID . "&bwbps_galname=" . urlencode($galOptions['gallery_name']);
		?>&TB_iframe=true&width=640&height=310' class='thickbox' onclick='return false;'><img src='<?php echo BWBPSPLUGINURL; ?>images/add.png' alt='[add icon]' />Add Images</a> | 
		<?php
		}
		
		?>		
			<a href='javascript: void(0);' onclick='bwbpsToggleFileURL(); return false;'>Video/File URL</a>
			| <a  href='javascript: void(0);' onclick='jQuery("#moderationmessages").toggle("slow"); return false;'>Moderation Msgs</a> | <a  href='javascript: void(0);' onclick='bwbpsPrepareImageSelection("copymoveimages"); jQuery("#copymoveimages").toggle("slow"); return false;'>Copy/Move Images</a> | <a  href='javascript: void(0);' onclick=' bwbpsPrepareImageSelection("resizeimages"); jQuery("#resizeimages").toggle("slow"); return false;'>Resize Images</a> <a href='<?php echo PHOTOSMASHWEBHOME; ?>tutorials/sizing-and-resizing-images/'  target='_blank' title='Video tutorial on sizing and resizing images.'><img src='<?php echo BWBPSPLUGINURL;?>images/help.png' alt='Video - Sizing and Resizing images' /></a>
		</div>
		</form>	
		
		<div id='moderationmessages' style='position: relative; display: none; padding: 10px; border: 1px solid #999; background-color: #fff; margin-top: 10px;'>
		<span style='position: absolute; top: 2px; right: 20px;font-size: 10px;'><a  href='javascript: void(0);' onclick='jQuery("#moderationmessages").toggle("slow"); return false;'>hide</a></span>
		<b>Send message?</b> <input id="ps_mod_send_msg" type="checkbox" name='ps_mod_send_msg' <?php if($psOptions['mod_send_msg'] == 1) echo 'checked'; ?>>
		<br/>
		<b>Message subject:</b><br/>
		<?php 
		if(!$psOptions['mod_msg_subject']){ $psOptions['mod_msg_subject'] = "Your Uploaded Image has been [status]";}
		?>
		
		<input type='text' size=80 maxlength="200" name="ps_mod_msg_subject" id="ps_mod_msg_subject" value="<?php 
			echo esc_attr($psOptions['mod_msg_subject']); 
			?>" />
		<br/>
		<b>Approve message:</b><br/>
		<textarea id="ps_mod_approve_msg" name="ps_mod_approve_msg" cols="60" rows="4"><?php esc_html_e($psOptions['mod_approve_msg']);?></textarea>
		<br/>
		<b>Reject message:</b><br/>
		<textarea  id="ps_mod_reject_msg" name="ps_mod_reject_msg" cols="60" rows="4"><?php esc_html_e($psOptions['mod_reject_msg']);?></textarea>
		</div>
		
		<div id='copymoveimages' style='position: relative; display: none; padding: 10px; border: 1px solid #999; background-color: #fff; margin-top: 10px;'>
		<span style='position: absolute; top: 2px; right: 20px;font-size: 10px;'><a  href='javascript: void(0);' onclick=' bwbpsPrepareImageSelection("copymoveimages"); jQuery("#copymoveimages").toggle("slow"); return false;'>hide</a></span>
		<span style='font-size: 14px; font-weight: bold; margin:0; padding: 0 0 5px;'>Copy/Move Images to new Gallery:</span> &nbsp; <span>Click images to select.</span>
		<p style="margin: 2px 0;">
			<span><a href='javascript: void(0);' onclick='bwbpsCopyMoveSelect(true); return false;'>Select All</a> </span> | 
			<span><a href='javascript: void(0);' onclick='bwbpsCopyMoveSelect(false); return false;'>Deselect All</a> </span> | 
			<span><a class='ps-modbutton' href='javascript: void(0);' onclick='bwbpsCopyToGallery(true); return false;'>&nbsp;Copy&nbsp;</a> </span> | 
			<span><a class='ps-modbutton' href='javascript: void(0);' onclick='bwbpsCopyToGallery(false); return false;'>&nbsp;Move&nbsp;</a> </span> | 
			<span>Copy/move to: 
			<?php
			
				echo $this->getGalleryDDL( 0, "skipnew", "copygal", "copygal_gallery_id", 30, true, true);
			
			?>
			</span>
		</p>
		</div>
		
		<div id='resizeimages' style='position: relative; height: 100px; display: none; padding: 5px; border: 1px solid #999; background-color: #fff; margin-top: 10px;'>
		<div style='float: right; background-color: #f0f0f0; padding: 8px; width: 300px; height: 85px;'>
		<span style='position: absolute; top: 2px; right: 20px;font-size: 10px;'>
		<a  href='javascript: void(0);' onclick=' bwbpsPrepareImageSelection("resizeimages"); jQuery("#resizeimages").toggle("slow"); return false;'>hide</a></span>
		<span id='resizestatusmsg'>Resizing status...</span>
		<span><a class='ps-modbutton' style='display: none;' href='javascript: void(0);' onclick='bwbpsStopResizing = true; jQuery("#bwbpsStopResizing").hide(); return false;' id='bwbpsStopResizing'>&nbsp;Stop&nbsp;</a></span><br/><textarea style='font-size: 11px;' rows="3" cols="36" id='resizeresultmsg'></textarea></div>
		<span style='font-size: 14px; font-weight: bold; margin:0; padding: 0 0 5px;'>Resize Selected Images:</span> &nbsp; <span>Click images to select.</span>
		<p style="margin: 2px 0;">
			<span><a href='javascript: void(0);' onclick='bwbpsCopyMoveSelect(true); return false;'>Select All</a> </span> | 
			<span><a href='javascript: void(0);' onclick='bwbpsCopyMoveSelect(false); return false;'>Deselect All</a> </span> | 
			<span><a class='ps-modbutton' href='javascript: void(0);' onclick='bwbpsResizeSelectedImages(); return false;'>&nbsp;Resize&nbsp;</a> 
		</p>
		
		</div>
		
		<div id='bwbps_uploaded_images' style='display: none; border: 2px solid #cc0000;padding: 5px;'>
		<h4>Newly Uploaded Images (<a href='admin.php?page=managePhotoSmashImages&psget_gallery_id=<?php
			echo $galleryID;
		?>'>refresh</a> page to edit) (Previews don't show for Flash Uploader)</h4>
		
		</div>
		
		<?php
			if($result){
				
				echo '
				<input type="hidden" id="_moderate_nonce" name="_moderate_nonce" value="'.$nonce.'" />
				';
			}
			echo $result;
			
			$this->getMapForm();	
		?>

 	</div>
	
	
 		
<?php
	}
	
	function getGalleryImageCount($gid){
		global $wpdb;
		
		switch ($gid) {
			case 'mod' :
				$where = ' WHERE status < 0 OR alerted < 0 ';
				break;
			
			case 'all' :
				$where = '';
				break;
				
			default :
				$where = " WHERE gallery_id = " 
				. (int)$gid;
		
		}
	
			
		// Get total images in gallery
		$sql = "SELECT COUNT(image_id) FROM " . PSIMAGESTABLE
			. $where;
			
		$ret = $wpdb->get_var($sql);
		
		return $ret;
	
	}
	
	/**
	 * getGalleryImages()
	 * 
	 * @access public 
	 * @param integer $gallery_id
	 * @return a table of the images
	 */
	function getGalleryImages($gallery_id, $sort_desc=false, $limit=0, $start=0, $nonce=false)
	{
		global $wpdb;
		global $bwbPS;
		
		if(!isset($this->psForm)){
			require_once( WP_PLUGIN_DIR .'/photosmash-galleries/bwbps-uploadform.php');
			
			if(!$bwbPS->stdFieldList || !$bwbPS->cfList){
				$bwbPS->loadCustomFormOptions();	
			}
			
			$this->psForm = new BWBPS_UploadForm($bwbPS->psOptions, $bwbPS->cfList);
		}
		
		
		$images = $this->getImagesQuery($gallery_id, $sort_desc, $limit, $start);
		$admin = current_user_can('level_10');
		
		if(!$admin){
			$admin = current_user_can('photosmash_photo_manager');
		}
		
		
		$uploads = wp_upload_dir();
		
		$imgcnt =0;
		if($images){
		//Get image count
		if(is_array($images)){
			$imgcnt=count($images);
		} 
		
		if(is_array($this->psForm->cfList)){
		foreach( $this->psForm->cfList as $f ){
			$cfNamesArrayForJS[] .= '"' . $f->field_name . '"';
			$cfArrayForJS[] .= ' "' . $f->field_name . '" : "" ';
		
		}
		}
		
		if(!(int)get_option('bwbps_show_fileurl')){
			$showfileurl = "display: none;";
		}
			
		if(!(int)get_option('bwbps_show_customdata')){
			$showcustomdata = "display: none;";
		}
		
		if(!(int)get_option('bwbps_show_fields')){
			$showfields = "display: none;";
		}
			
			if(is_array($cfArrayForJS)){
				$jsfieldarray = implode(',', $cfArrayForJS);
			}
			
			if(is_array($cfNamesArrayForJS)){
				$jsfieldnamearray = implode(',', $cfNamesArrayForJS);
			}
					
			$psTableWrap .= "
			<script type='text/javascript'>var bwbpsCustomFields = {" . $jsfieldarray . "};
			var bwbpsCustomFieldNames = [" . $jsfieldnamearray . "];
			</script>
			<div id='bwbpsGetMediaGalleryBox' style='display: none;'>
			Get the Video links!
			</div>
			
			<table class='widefat fixed' cellspacing='0'>
			<thead><tr>
				<th class='' scope='col' style='width: 380px; color: #cc0000 !important; font-size: 12px;'>Toggle <a href='javascript: void(0);' onclick='bwbpsTogglePhotoMgrFields(); return false;'>Fields <img src='" . BWBPSPLUGINURL . "images/down.png' alt='expand' style='margin: 5px 0 0 !important; padding: 0 !important;' /></a><span id='bwbps-fieldtoglinks' style='$showfields'>: &nbsp; 
			<a href='javascript: void(0);' onclick='jQuery(\".bwbps-stdfields\").toggle(); return false;'>Standard</a>,
			<a href='javascript: void(0);' onclick='bwbpsToggleCustomData(); return false;'>Custom</a>,
			<a href='javascript: void(0);' onclick='jQuery(\".bwbps-metafields\").toggle(); return false;'>Meta</a></span> </th>
				<th class='' scope='col' style='width: 380px;'>Images</th>
				</tr>
			</thead>
			<tfoot><tr>
				<th class='' scope='col'>Images</th>
				<th class='' scope='col'>Images</th>
				</tr>
			</tfoot>
			<tbody id='psimage-tbody'>
			";
		
		
		
		$i=-1;
		$ialt = 1;
		foreach($images as $image){
			
			if($i==-1){
				$psTable .= "<tr id='bwbps-img-" . $image->image_id . "' class='ps-image-row iedit $rowstyle' valign='top'>";
				$i = 0;
			}
		
			if($i == 2){
				if($ialt == 1){
					$rowstyle = "alternate";
				} else {
					$rowstyle = "";
					$ialt =0;
				}
				$ialt++;
				
				$i = 1;
				$psTable .= "</tr><tr id='bwbps-img-" 
					. $image->image_id . "' class='iedit $rowstyle' valign='top'>";
				
			} else {
				$i++; 
				$rowstyle="";
			}
			
			$mod = $this->getGIModStatus($g, $image, $admin);
			
			if ( !$image->thumb_url ){
				
				$image->thumb_url = PSTHUMBSURL.$image->file_name;
				$image->image_url = PSIMAGESURL.$image->file_name;
						
			} else {
			
				// Add the Uploads base URL to the image urls.
				// This way if the user ever moves the blog, everything might still work ;-) 
				// set $uploads at top of function...only do it once
				if(!$image->mini_url){ $image->mini_url = $image->thumb_url; }
				$image->mini_url = $uploads['baseurl'] . '/' . $image->mini_url;
				$image->thumb_url = $uploads['baseurl'] . '/' . $image->thumb_url;
				$image->medium_url = $uploads['baseurl'] . '/' . $image->medium_url;
				$image->image_url = $uploads['baseurl'] . '/' . $image->image_url;
			
			}
			
			
			
			$galDDL = $this->getGalleryDDL($image->gallery_id, "skipnew"
				, "g".$image->image_id, "bwbps_set_imggal", 15, false, true);
			
			$galDDL .= "<a href='javascript: void(0);' onclick='bwbpsSetNewGallery(".$image->image_id."); return false;' id='save_ps_show_imgcaption' title='Save to new gallery.'><img src='" . BWBPSPLUGINURL. "images/disk.png' alt='Set gallery' /></a>";
			
			
			$galupdate = "<input type='text' id='image_post_id_" 
						. $image->image_id."' name='image_post_id"
						. $image->image_id."' value='" . $image->post_id . "' size='4' style='width: 45px !important;' />"; 
			
			if((int)$image->post_id){
				
				$post_link = "<a href='"
				. get_permalink( $image->post_id )
				. "' title='View related post.' class='' target='_blank'>post</a> | ";
				
				$galupdate .= "
				<a href='post.php?action=edit&post="
				. $image->post_id 
				. "' title='Edit related post.' class='ps-modbutton'>edit</a> &nbsp; 
				<a href='"
				. get_permalink( $image->post_id )
				. "' title='View related post.' class='ps-modbutton' target='_blank'>view</a>";
				
				if( $image->post_status == 'publish' ){
					$galupdate .= " <span style='color: green; font-size: 9px;'>published</span>";
				} else {
				
					$galupdate .= " <span id='psimg_pubpost" . $image->image_id . "'><a href='javascript: void(0);' onclick='bwbpsModerateImage(\"publishpost\", ".$image->image_id.", ". $image->post_id . ");' class='ps-modbutton'>publish</a> </span>";
					
				}
			}
			
			$mod['menu'] = "
				<span class='ps-modmenu' id='psmod_".$image->image_id."'>"
				.$mod['menu']."</span><span class='ps-modmenu' id='psmodmsg_".$image->image_id."'></span><br/>
				
				ID: " . $image->image_id . " | By: <em>" . $image->user_login . "</em> | $post_link Seq: <input type='text' id='imgseq_" 
						. (int)$image->image_id."' name='imgseq"
						. (int)$image->image_id."' value='"
						. (int)$image->seq . "' style='width: 45px !important;' /><div class='row-actions'>
				<a href='javascript: void(0);' onclick='bwbpsModerateImage(\"bury\", "
				.$image->image_id.");' >delete</a> | <a href='javascript: void(0);' onclick='bwbpsModerateImage(\"remove\", "
				.$image->image_id.");' >remove</a> | <a href='javascript: void(0);' onclick=\"bwbpsResizeImage('"
				.$image->image_id."',true); return false;\" >resize</a> | 
				<a href='javascript: void(0);' onclick='bwbpsSaveCustFldsAdmin(".$image->image_id.", true);' >save</a> | 
				<a href='javascript: void(0);' title='Click Map to set Coordinates' onclick='photosmash.showMapEdit(" 
					. $image->image_id . ", jQuery(this).offset().top ); return false;'><img src='" 
					. BWBPSPLUGINURL . "images/world_edit.png' alt='Find' /></a>
				</div>
				";
				
				
			//Image HTML
			$psTable .= "
				<td class='ps_copy psgal_".$image->gallery_id."' id='psimg_"
				. $image->image_id."' style='padding-top: 6px;'>
				<span class='ps_clickmsg' style='display:none;'>Click to select</span>
				<a class='thickbox' rel='bwbps-mgr' href='"
				. $image->image_url."' rel='"
				. $g['img_rel']."' title='".str_replace("'","",$image->image_caption)
				. "'>
				<span id='psimage_".$image->image_id."' class='"
				. $mod['class'] . "' style='float:left; margin-right: 10px;'>
					<img src='"
				. $image->thumb_url ."' style='width: 70px; height: 70px;"
				. $modStyle . "' />
				</span>
				</a>" 
				. $mod['menu'] . "
				";
			
			
			// IMAGE DETAILS
			
			$psCaption = esc_attr($image->image_caption);
			
			if($i==0){$border = " style='border-right: 1px solid #999;'";} else {$border = '';}
			
			$argsarray = array('name');
			
			$terms = wp_get_object_terms( $image->image_id, 'photosmash', $argsarray);
			$termlist = "";
			$termlist = get_the_term_list($image->image_id, 'photosmash', '', ', ');
			
			if(isset($_terms)){ unset($_terms); }
					
			if( is_array($terms) && count($terms) ){
				
				foreach ( $terms as $term ) {
					$_terms[] = esc_attr($term->name);
				}
				
				unset($terms);
				$terms = implode(", ", $_terms);
			
			} else { $terms = ''; }
			
			$contribtermslist = "";
			$contribtermslist = get_the_term_list($image->image_id, 'photosmash_contributors', '', ', ');
			
			$psTable .= "
			<div class='bwbps-fields-container' style='$showfields'>								
			<table class='widefat fixed bwbps_admintable' cellspacing=0>
				<thead><tr>
					<th class='manage-column' style='width: 30%;'><a href='javascript: void(0);' onclick='bwbpsSaveCustFldsAdmin(".$image->image_id.", true);' ><img src='" . BWBPSPLUGINURL. "images/disk.png' alt='Save fields' class='bwbps_save_flds_".$image->image_id."' /></a></th>
					<th class='manage-column' style='width: 70%;' ><div class='bwbps_toggle_box'>
						<a onclick='jQuery(\".bwbps-stdfields-" .
						(int)$image->image_id . "\").toggle(); return false;' href='javascript: void(0);' title='toggle'>
							<img src='" . BWBPSPLUGINURL . "images/down.png' alt='expand' /></a></div>Standard Fields </th>
				</tr></thead>
				<tbody class='bwbps-stdfields bwbps-stdfields-" .(int)$image->image_id . "'>
				<tr valign='top'>
					<td>
						<span>Gallery:</span>
					</td>
					<td>" .$galDDL. "</td>
				</tr>
				
				<tr valign='top'>
					<td>
						<span>Post ID:</span>
					</td>
					<td>" .$galupdate. "</td>
				</tr>
				<tr valign='top'>
					<td>
						<span>Caption:</span>
					</td>
					<td><input type='text' id='imgcaption_" 
						. $image->image_id."' name='imgcaption"
						. $image->image_id."' value='$psCaption' style='' />
					</td>
				</tr>
				<tr>
					<td>URL:</td>
					<td><input type='text' id='imgurl_" 
						. $image->image_id."' name='imgurl"
						. $image->image_id."' value='"
						. $image->url. "' style='' />
					</td>
				</tr>
				
				<tr>
					<td>Tags:</td>
					<td><input type='text' id='imgtags_" 
						. $image->image_id."' name='imgtags"
						. $image->image_id."' value='"
						. $terms . "' style='' /><br/>
						". $termlist ."
					</td>
				</tr>
				<tr>
					<td>Latitude:</td>
					<td><input type='text' id='geolat_" 
						. $image->image_id."' name='geolat"
						. $image->image_id."' value='"
						. floatval($image->geolat) . "' style='' />
						
						<a href='javascript: void(0);' title='Click Map to set Coordinates' onclick='photosmash.showMapEdit(" . $image->image_id . ", jQuery(this).offset().top - 220 ); return false;'>
							<img src='" 
						. BWBPSPLUGINURL . "images/world_edit.png' alt='Find' />
						</a>
					</td>
				</tr>
				<tr>
					<td>Longitude:</td>
					<td><input type='text' id='geolong_" 
						. $image->image_id."' name='geolong"
						. $image->image_id."' value='"
						. floatval($image->geolong). "' style='' />
					</td>
				</tr>
				
				<tr>
					<td>Meta Data (exif):</td>
					<td><input disabled='true' type='text' id='imgmeta_" 
						. $image->image_id."' name='imgmeta"
						. $image->image_id."' value='"
						. $image->meta_data . "' style='' />
						<a href='javascript: void(0);' title='Fetch and Save image meta from WP Media Library Attachment record' onclick='bwbpsFetchMeta(" . $image->image_id . ", " 
							. $image->wp_attach_id . "); return false;'>
							<img id='bwbps_fetch_img_"
						. $image->image_id."' src='" 
						. BWBPSPLUGINURL . "images/camera_add.png' alt='Fetch meta' />
						</a>
					</td>
				</tr>
				<tr class='ps-fileurl' style='$showfileurl'>
					<td style='padding: 3px;'>Video/File URL:</td>
					<td style='padding: 3px 2px 3px 7px;'>
						<input type='text' id='fileurl_" 
						. $image->image_id."' name='fileurl"
						. $image->image_id."' value='"
						. $image->file_url . "' style='' />
						<a href='" 
						. BWBPSPLUGINURL 
						. "ajax_medialoader.php?image_id=" . $image->image_id
						. "&width=700&height=430' class='thickbox' title='Select Media' onclick='return false;'>
							<img src='" 
						. BWBPSPLUGINURL . "images/exp.png' alt='Find' />
						</a>
						
					</td>
				</tr>
				</tbody>
			</table>
			";
			$cfTable = "";
			
			foreach( $this->psForm->cfList as $f ){
				$gtemp['pfx'] = "img". $image->image_id . "_"; 
				
				$cfTable .= "<tr>
					<td>" . $f->field_name . "
					</td><td>"
					.	$this->psForm->getField($gtemp, $f, false, $image->{$f->field_name}, true )
					. "</td></tr>"
					;
			}
			
			//Custom Fields
			$psTable .= "
			<table class='widefat fixed ps-customflds bwbps_admintable' cellspacing=0 id='ps-customflds-" . $image->image_id . "' >
				<thead><tr>
					<th class='manage-column' style='width: 30%;'><a href='javascript: void(0);' onclick='bwbpsSaveCustFldsAdmin(".$image->image_id.", true);' ><img src='" . BWBPSPLUGINURL. "images/disk.png' alt='Save fields' class='bwbps_save_flds_".$image->image_id."' /></a></th>
					<th class='manage-column' style='width: 70%;' ><div class='bwbps_toggle_box'><a onclick='jQuery(\".bwbps-custfields-" .
						(int)$image->image_id . "\").toggle(); return false;' href='javascript: void(0);' title='toggle'>
							<img src='" . BWBPSPLUGINURL . "images/down.png' alt='expand' /></a></div>Custom Fields</th>
				</tr></thead>
				<tbody class='bwbps-custfields bwbps-custfields-" .(int)$image->image_id . "' style='$showcustomdata'>
				"
				. $cfTable 
				. "					
				</tbody>
				</table>";
			
			if($image->file_url){
				$fileURLData = "<br/>File data: " . $image->file_url;
			} else { $fileURLData = ""; }
			
			
			//Image Meta Data
			$psTable .= "
				<table class='widefat fixed bwbps_admintable' cellspacing=0 id='ps-customflds-" . $image->image_id . "'>
				<thead><tr>
					<th class='manage-column' style='width: 30%;'>Label</th>
					<th class='manage-column' style='width: 70%;' ><div class='bwbps_toggle_box'>
						<a onclick='jQuery(\".bwbps-metafields-" .
						(int)$image->image_id . "\").toggle(); return false;' href='javascript: void(0);' title='toggle'>
							<img src='" . BWBPSPLUGINURL . "images/down.png' alt='expand' /></a></div>Meta Fields</th>
				</tr></thead>
				<tbody class='bwbps-metafields bwbps-metafields-" .(int)$image->image_id . "' style='display:none;'>
				<tr><td>Image id:</td><td>"
				. $image->image_id."</td></tr>
				<tr><td>WP media id: </td>
					<td><a href='" . get_attachment_link( $image->wp_attach_id ) . "' title='view attachment'>" . $image->wp_attach_id . "</a></td></tr>
				<tr><td>Gallery: </td>
					<td><a href='admin.php?"
				. "page=managePhotoSmashImages&amp;psget_gallery_id="
				. $image->gallery_id."'>id(".$image->gallery_id.") "
				. $image->gallery_name."</a></td></tr>
				<tr><td>Image name: </td>
					<td>"
				. $image->image_name . "
				</td></tr>
				<tr><td>Image url: </td>
					<td>"
				. $image->image_url . "
				</td></tr>
				<tr><td>Thumb url: </td>
					<td><a href='$image->mini_url' class='thickbox'>"
				. $image->thumb_url . "</a>
				</td></tr>
				<tr><td>Uploaded by: </td>
					<td>"
				. $this->calcUserName($image->user_login, $image->user_nicename
				, $image->display_name)." - " . $contribtermslist ."
				</td></tr>
				<tr><td>Date: </td>
					<td>".$image->created_date
				. $fileURLData . "</td></tr>
				</tbody></table>
				</div> <!-- closes the div that holds the tables -->
				</td>
				";
			
		}
		
		if(strpos($psTable,"bwbps_admintable")){
			$psTable .= "</tr>";
		}
		
		$data['alerted'] = -1;
		$where['alerted'] = 0;
		if( (int)$gallery_id ){
			$where['gallery_id'] = (int)$gallery_id;
		}
		$wpdb->update(PSIMAGESTABLE, $data, $where);
		
		return '<div>&nbsp;<span id="ps_savemsg" style="display: none; color: #fff; background-color: red; padding:3px;">saving...</span>'.$psTableWrap.$psTable.'</tbody></table></div>';
	} else {
		return "<h3>No images in gallery yet...go to post page to load images.</h3>";
	}
	
	}
	
	/*
	 *	Get Image Moderation Status
	 *  Determine the moderation status of an Imageand supply a link for moderating
	 *  Returns array:  $mod['class'] and $mod['menu']
	*/
	function getGIModStatus($g, $image, $admin){
	
		switch ($image->status) {
				case -1 :
					$mod['class'] = "ps-moderate";
					
					if($admin){
						$mod['menu'] = "<a href='javascript: void(0);' onclick='bwbpsModerateImage(\"approve\", ".$image->image_id.");' class='ps-modbutton'>approve</a>";
					}
					break;
				case -2 :
					break;
				default :
					if( $image->alerted == -1 || $image->alerted == 0 ){
						$mod['class']= 'ps-newimage';
						if($admin){
							$mod['menu'] = "<a href='javascript: void(0);' onclick='bwbpsModerateImage(\"review\", ".$image->image_id.");' class='ps-modbutton'>mark reviewed</a>";
						}
					break;
					} else {
						$mod['class'] = '';
					}
					break;
			}
		
		return $mod;
	
	}
	
	function getGalleryImagesThumb($g, $image){
		
		
	
	}
	
	function getGalleryImagesStdFields($g, $image){
		
		
	
	}
	
	function getGalleryImagesCustFields($g, $image){
		
		
	
	}
	
	
	function getGalleryImagesMeta($g, $image){
	
		
	
	}
	
	
	//Get the Gallery Images
	function getImagesQuery($gallery_id, $sort_desc=false, $limit=0, $start=0){
		global $wpdb;
		global $user_ID;
		
		if($sort_desc){ $desc = ' DESC'; }
		
		if($limit || $start){
			$start = (int)$start;
			if((int)$limit == 0){$limit = 50;}
			
			$limitsql = ' LIMIT ' . $start . ', ' . $limit;
		}
		
		foreach ($this->psForm->cfList as $f){
		
			switch ($f->field_name){
				case 'id' :
					break;
				case 'image_id' :
					break;
				case 'updated_date' :
					break;
				case 'bwbps_status' :
					break;
				default :
					$cflds[] = PSCUSTOMDATATABLE . "." . $f->field_name;
					break;
			}
		}
		
		if(is_array($cflds)){ $cfsql = ", " . implode(", ", $cflds); }
		
		if(current_user_can('level_10')){
			switch ($gallery_id){
				case "all" :
					$sql = $wpdb->prepare('SELECT '.PSIMAGESTABLE.'.*, '
						.$wpdb->users.'.user_nicename,'
						. $wpdb->users.'.display_name, '.$wpdb->users
						. '.user_login, '.PSGALLERIESTABLE.'.gallery_name, '
						. $wpdb->posts . '.post_status '
						. $cfsql . " "
						. ' FROM ' . PSIMAGESTABLE 
						. ' LEFT OUTER JOIN '.PSCUSTOMDATATABLE.' ON '.PSCUSTOMDATATABLE
						. '.image_id = '. PSIMAGESTABLE. '.image_id '
						. ' LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
						. '.ID = '. PSIMAGESTABLE. '.user_id '
						. ' LEFT OUTER JOIN '.PSGALLERIESTABLE 
						. ' ON '.PSGALLERIESTABLE.'.gallery_id = '
						. PSIMAGESTABLE.'.gallery_id '
						. ' LEFT OUTER JOIN ' . $wpdb->posts . ' ON '
						. $wpdb->posts . '.ID = ' . PSIMAGESTABLE . '.post_id '
						. ' ORDER BY '
						. PSIMAGESTABLE. '.image_id' . $desc . $limitsql);
					break;
					
				case "moderation" :
					$sql = $wpdb->prepare('SELECT '.PSIMAGESTABLE
					. '.*, '.$wpdb->users.'.user_nicename,'
					. $wpdb->users.'.display_name, '.$wpdb->users
					. '.user_login, '.PSGALLERIESTABLE.'.gallery_name, '
					. $wpdb->posts . '.post_status '
					. $cfsql . " "
					. ' FROM ' . PSIMAGESTABLE 
					. ' LEFT OUTER JOIN '.PSCUSTOMDATATABLE.' ON '.PSCUSTOMDATATABLE
					. '.image_id = '. PSIMAGESTABLE. '.image_id '
					. ' LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					. '.ID = '. PSIMAGESTABLE. '.user_id '
					. ' LEFT OUTER JOIN '.PSGALLERIESTABLE 
					. ' ON '.PSGALLERIESTABLE.'.gallery_id = '
					. PSIMAGESTABLE.'.gallery_id '
					. ' LEFT OUTER JOIN ' . $wpdb->posts . ' ON '
					. $wpdb->posts . '.ID = ' . PSIMAGESTABLE . '.post_id '
					. ' WHERE '. PSIMAGESTABLE
					. '.status = -1 OR '. PSIMAGESTABLE
					. '.alerted IN (-1, 0) ORDER BY '
					. PSIMAGESTABLE. '.image_id' . $desc . $limitsql);
					break;
					
				default:
					$gallery_id = (int)$gallery_id;
					$sql = $wpdb->prepare('SELECT '.PSIMAGESTABLE.'.*, '
					. $wpdb->users.'.user_nicename,'
					. $wpdb->users.'.display_name, '.$wpdb->users
					. '.user_login, '.PSGALLERIESTABLE.'.gallery_name, '
					. $wpdb->posts . '.post_status '
					. $cfsql . " "
					. ' FROM '.PSIMAGESTABLE 
					. ' LEFT OUTER JOIN '.PSCUSTOMDATATABLE.' ON '.PSCUSTOMDATATABLE
					. '.image_id = '. PSIMAGESTABLE. '.image_id '
					. ' LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					. '.ID = '. PSIMAGESTABLE. '.user_id '
					. ' LEFT OUTER JOIN '.PSGALLERIESTABLE 
					. ' ON '.PSGALLERIESTABLE.'.gallery_id = '
					. PSIMAGESTABLE.'.gallery_id '
					. ' LEFT OUTER JOIN ' . $wpdb->posts . ' ON '
					. $wpdb->posts . '.ID = ' . PSIMAGESTABLE . '.post_id '
					. ' WHERE '. PSIMAGESTABLE
					. '.gallery_id = %d ORDER BY '
					. PSIMAGESTABLE. '.image_id ' . $desc . $limitsql, $gallery_id);			
			}
			
			$images = $wpdb->get_results($sql);
						
		} else {
				$uid = $user_ID ? $user_ID : -1;
				$images = $wpdb->get_results($wpdb->prepare('SELECT '.PSIMAGESTABLE.'.*, '
					. $wpdb->users.'.user_nicename,'
					. $wpdb->users.'.display_name, '.$wpdb->users.'.user_login FROM '.PSIMAGESTABLE 
					. ' LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					. '.ID = '. PSIMAGESTABLE. '.user_id WHERE '. PSIMAGESTABLE
					. '.gallery_id = %d AND ('. PSIMAGESTABLE. '.status > 0 OR '
					. PSIMAGESTABLE. '.user_id = '.$uid.')ORDER BY '. PSIMAGESTABLE
					. '.seq ' . $desc . ', '. PSIMAGESTABLE. '.image_id ' . $desc . $limitsql, $gallery_id));
		}
		return $images;
	}
	
	
	function getGalleriesQuery($exclude = false){
		
		global $wpdb;
		
		if($exclude){
			$excludesql = " WHERE ".PSGALLERIESTABLE.".gallery_type < 10 OR ".PSGALLERIESTABLE.".gallery_type IS NULL ";
		}
		
		
		$sql = "SELECT ".PSGALLERIESTABLE.".gallery_id, ".PSGALLERIESTABLE.".gallery_name, "
			.$wpdb->prefix."posts.post_title, COUNT("
			.PSIMAGESTABLE.".image_id) as img_cnt, ".PSGALLERIESTABLE.".status FROM "
			.PSGALLERIESTABLE." LEFT OUTER JOIN "
			.PSIMAGESTABLE." ON ".PSIMAGESTABLE.".gallery_id = "
			.PSGALLERIESTABLE.".gallery_id LEFT OUTER JOIN "
			.$wpdb->prefix."posts ON ".PSGALLERIESTABLE.".post_id = "
			.$wpdb->prefix."posts.ID $excludesql GROUP BY "
			.PSGALLERIESTABLE.".gallery_id, ".PSGALLERIESTABLE.".gallery_name, "
			.$wpdb->prefix."posts.post_title, ".PSIMAGESTABLE.".gallery_id,"
			.PSGALLERIESTABLE.".status, "
			.$wpdb->prefix."posts.ID, ".PSGALLERIESTABLE.".post_id";
			
		
		$exclude = $exclude ? 1 : 0;
		
		if(!$this->galleryQuery[$exclude]){
		
			$query = $wpdb->get_results($sql);
			$this->galleryQuery[$exclude] = $query;
		
		} else {
		
			$query = $this->galleryQuery[$exclude];
			
		}
	
		return $query;
	}
	
	function getGalleriesCheckboxes($selected = false, $idPfx = "", $cbxName = "gal_gallery_ids"){
	
		$query = $this->getGalleriesQuery();
		
		if(!is_array($selected)){
			$selected = array($selected);
		}
		
		foreach($query as $row){
			if(in_array($row->gallery_id, $selected)){
				$checked = "checked"; 
			} else { $checked = "";}
			
			if(trim($row->gallery_name) <> ""){$title = $row->gallery_name;} else {
				$title = $row->post_title;
			}
			
			$title = "Gal: $row->gallery_id - " . $title .  " (".$row->img_cnt." imgs)";
			
			$ret .= '<input type="checkbox" class="bwbps_multigal" name="' . $cbxName . '[]" '. $checked .' /value="'.$row->gallery_id.'"> '.$title.' <br/>
			';
			
		}
		
		return $ret;
	}
	
	//Returns markup for a DropDown List of existing Galleries
	function getGalleryDDL($selectedGallery = 0, $newtag = "New", $idPfx = "", $ddlName= "gal_gallery_id", $length = 0, $showImgCount = true, $exclude_virtual = false)
 	{
 		global $wpdb;
 		 
 		if($newtag <> 'skipnew' ){
			$ret = "<option value='0'>&lt;$newtag&gt;</option>";
		}
		
		$query = $this->getGalleriesQuery($exclude_virtual);
				
		if(is_array($query)){
		foreach($query as $row){
			if($selectedGallery == $row->gallery_id){$sel = "selected='selected'";}else{$sel = "";}
			
			if(trim($row->gallery_name) <> ""){$title = $row->gallery_name;} else {
				$title = $row->post_title;
			}
			
			if($length){
				$title = substr($title,0,$length). "&#8230;";
			}
			
			if($showImgCount){
				$title .=  " (".$row->img_cnt." imgs)";
			}
			
			if( !$row->status ){
				$title .= " - inactive";
			}
			
			$ret .= "<option value='".$row->gallery_id."' ".$sel.">ID: ".$row->gallery_id."-".$title."</option>";
		}
		}
		$ret ="<select id='" . $idPfx . "bwbpsGalleryDDL' name='$ddlName'>".$ret."</select>";		
		
		return $ret;
	}
	
	//Get Layouts DDL
	function getLayoutsDDL($selected_layout,$psDefault, $type=false, $ele_name=false, $use_name_for_value = false){
		
 		global $wpdb;
 		
 		
 		if( !$use_name_for_value ){
	 		if($psDefault && !$selected_layout){ $selected_layout = -1; }
 		
 			if($selected_layout == -1){$sel = "selected='selected'";}else{$sel = "";}
				$ret .= "<option value='-1' ".$sel.">Standard display</option>";

		
			if(!$psDefault){
				if(!$selected_layout){$sel = "selected='selected'";}else{$sel = "";}
				$ret .= "<option value='0' ".$sel.">&lt;Default layout&gt;</option>";
			}
			
		}
		
		// Type specifies if we're limiting to a specific Layout Type
		if($type !== false){
			$where = " WHERE layout_type=" . (int) $type. " ";
		}
				
		$query = $wpdb->get_results("SELECT layout_id, layout_name FROM "
			.PSLAYOUTSTABLE. $where ." ORDER BY layout_name;");
		
		if($query){
			foreach($query as $row){
				if($use_name_for_value){
					
					if($selected_layout == $row->layout_id){
						$sel = "selected='selected'";
					}else{$sel = "";}
					
					$ret .= "<option value='".$row->layout_name."' "
						.$sel.">".$row->layout_name."</option>";
					
				} else {
					if($selected_layout == $row->layout_id){
						$sel = "selected='selected'";}else{$sel = "";}

					$ret .= "<option value='".$row->layout_id."' "
						.$sel.">".$row->layout_name."</option>";
				}
		
			}
		}
		if(!$ele_name){
			if(!$psDefault){
				$ret ="<select name='gal_layout_id'>".$ret."</select>";
			} else {
				$ret ="<select name='ps_layout_id'>".$ret."</select>";
			}
		} else {
			$ret ="<select name='$ele_name'>".$ret."</select>";
		}
		return $ret;
	}
	
	//Get DDL of Custom Forms
		
	function getCFDDL($selected_id, $ele_name='gal_custom_formid' ){		
		
		$ret = "<option value='default'>&lt;default&gt;</option>";
		
		$cfList = $this->getCustomFormsList();
		
		if(is_array($cfList)){
			foreach($cfList as $row){
				if($selected_id == $row['form_id']){
					$sel = "selected='selected'";
					if($selectedCF){
						$bNoInput = true;
					}	
				}else{$sel = "";}
			
				$ret .= "<option value='".$row['form_id']."' ".$sel.">".$row['form_name']."</option>";
			}
		}
		
		$ret ="<select id='bwbpsCFDDL' name='$ele_name' >".$ret."</select>";
		
		return $ret;

	
	}
	
	
	function getCustomFormsList(){
		global $wpdb;
		
		$query = $wpdb->get_results("SELECT form_id, form_name FROM " . PSFORMSTABLE, ARRAY_A);
		return $query;
	}
	
	function calcUserName($loginname, $nicename = false, $displayname = false){
		if($displayname) return $displayname;
		if($nicename) return $nicename;
		return $loginname;
	}
	
	function getAPICategoryList($selected=""){
		$sel_raw = explode(",", $selected);
		
		$sel = array_map("trim",$sel_raw);
		
		$cats = get_categories(array('hide_empty' => 0));
		
		foreach( $cats as $cat ){
			
			if(in_array($cat->name, $sel)){
				$checked = ' checked="checked"';
			} else { $checked = ''; }
			
			$c .= '<input type="checkbox" name="ps_api_categories[]" value="' 
				. esc_attr($cat->name) . '" ' .$checked.' /> ' . $cat->name . '<br />';
			
		}
		
		return $c;
		
	}
	
	function getCustomFieldsCheckBoxes($selected=""){
		global $bwbPS;
		
		if(!$bwbPS->stdFieldList || !$bwbPS->cfList){
			$bwbPS->loadCustomFormOptions();	
		}
		
		$selected = str_replace(" ","", $selected);
		
		$sel = explode(",", $selected);
		
		if(is_array($bwbPS->cfList)){
			foreach( $bwbPS->cfList as $f ){
				if(is_array($sel)){
					$checked = in_array($f->field_id, $sel) ? " checked='checked' " : "";
				} else {
					$checked = "";
				}
				$cfs .= '<input type="checkbox" name="ps_api_custom_fields[]" value="' . esc_attr($f->field_id) . '" ' 
					.$checked.'/> ' . $f->label . '<br />';
			}
		}
		
		return $cfs;
	}
	
	

	
}  //closes out the class

if ( !function_exists('wp_nonce_field') ) {
        function bwbps_nonce_field($action = -1) { return; }
        $bwbps_plugin_nonce = -1;
} else {
        function bwbps_nonce_field($action = -1) { return wp_nonce_field($action); }
}


?>