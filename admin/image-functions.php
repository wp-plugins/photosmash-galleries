<?php

class BWBPS_ImageFunc{
	
	
	var $g;	//Gallery
	var $uploads; //The wp_upload_dir array

	var $added_images;
	var $options;	// PS Options

	//Constructor
	function BWBPS_ImageFunc(&$o){
		$this->options = $o;
	}
	
	
	function getImage($image_id){
	
		global $wpdb;
		
		$sql = "SELECT * FROM " . PSIMAGESTABLE . " WHERE image_id = " . (int)$image_id;
		
		$result = $wpdb->get_row($sql, ARRAY_A);
		return $result;
		
	}
	
	function getImagesForMobile($cnt, $gallery_ids, $page=1){
	
		//global $wpdb;
		global $bwbPS;
		
		if(is_array($gallery_ids)){
			foreach($gallery_ids as $id){
				if((int)$id){
					$ids[] = (int)$id;
				}
			}
			/*
			if(is_array($ids)){
				$img_ids = " AND " . PSIMAGESTABLE . ".gallery_id IN (" . implode(",", $ids) .") ";
			}
			*/
		}
		
		$cnt = (int)$cnt ? (int)$cnt : 60;
		
		$g['sort_order'] = 1;
		$g['gallery_type'] = 30;
		$g['limit_page'] = (int)$page;
		$g['limit_images'] = $cnt;
		$g['smart_gallery'] = true;
		
		if(is_array($ids)){
			$g['smart_where'] = array( PSIMAGESTABLE . '.gallery_id' => $ids );
		}
		
		if(!isset($this->psLayout)){
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/bwbps-layout.php");
			$psLayout = new BWBPS_Layout($bwbPS->psOptions, $bwbPS->cfList);
		}
		
		$results = $psLayout->getGalleryImages($g);		
		
		foreach($results as $img){
			unset($image);
			$image['caption'] = $img['image_caption'];
			$urls = $this->getImageLocArray($img, 'url');
			$image['thumb_url'] = $urls['mobile_url'] ? $urls['mobile_url'] : $urls['thumb_url'];
		
		
			switch ((int)$this->options['api_big_url']){
				case 1 :
					$image['big_url'] = $urls['image_url'];
					break;
					
				case 2 :
					$image['big_url'] = $urls['mini_url'];
					break;
				
				case 3 :
					$image['big_url'] = $urls['thumb_url'];
					break;
					
				default :
					$image['big_url'] = $urls['medium_url'];
					break;

			}
			
			$image['geolat'] = $img['geolat'];
			$image['geolong'] = $img['geolat'];
			
			
			//Check to see if user prefers Attachment links
			$post_perma = "";
			if((int)$bwbPS->psOptions['api_link_toattachments'] && (int)$img['wp_attach_id']){
			
				$post_perma = get_permalink((int)$img['wp_attach_id']);
				
			}
			
			if(!$post_perma){
				if((int)$img['post_id']){
						$post_perma = get_permalink((int)$img['post_id']);
				} else {
					if((int)$image['gal_post_id']){
						$post_perma = get_permalink((int)$img['gal_post_id']);
					} else {
						$post_perma = get_permalink((int)$img['wp_attach_id']);
					}
				}
			}
			
			if($post_perma){
				$image['web_url'] = $post_perma;
			}
			
			$images[] = $image;
		
		}
		
		return $images;
	
	}
	
	function getGallery($gallery_id){
	
		global $wpdb;
		
		if($g['gallery_id'] === $gallery_id){
			return $g;
		}
		
		$sql = "SELECT * FROM " . PSGALLERIESTABLE . " WHERE gallery_id = " . (int)$gallery_id;
		
		$result = $wpdb->get_row($sql, ARRAY_A);
		return $result;
	
	}
	
	function getImageURLs($image){
		return $this->getImageLocArray($image, 'url');
	}
	
	function getImageDirs($image){
		return $this->getImageLocArray($image, 'dir');
	}
	
	// Returns an array of all the available Image Size URLs
	function getImageLocArray($image, $loc_type='url'){
	
		$uploads = wp_upload_dir();
		
		if($loc_type == 'dir'){
			$thumbloc = PSTHUMBSPATH;
			$imageloc = PSIMAGESPATH;
			$baseloc = 'basedir';
		} else {
			$thumbloc = PSTHUMBSURL;
			$imageloc = PSIMAGESURL;
			$baseloc = 'baseurl';
		}
	
		if( !$image['thumb_url'] ){
				
			if( $image['file_name'] ){
				$urls['mini_url'] = $thumbloc.$image['file_name'];
				$urls['thumb_url'] = $thumbloc.$image['file_name'];
				$urls['medium_url'] = $thumbloc.$image['file_name'];
				$urls['image_url'] = $imageloc.$image['file_name'];
			}
		
		} else {
		
			// Add the Uploads base URL to the image urls.
			// This way if the user ever moves the blog, everything might still work ;-) 
			// set $uploads at top of function...only do it once
			if(!$image['mini_url']){ $urls['mini_url'] = $image['thumb_url']; }
			$urls['mini_url'] = $uploads[$baseloc] . '/' . $image['mini_url'];
			$urls['thumb_url'] = $uploads[$baseloc] . '/' . $image['thumb_url'];
			$urls['medium_url'] = $uploads[$baseloc] . '/' . $image['medium_url'];
			$urls['image_url'] = $uploads[$baseloc] . '/' . $image['image_url'];
		
		}
		
		if( $image['file_url'] && $this->psValidateURL($image['file_url']) ){
			$urls['image_url'] = $image['file_url'];
		}
		
		if(!$urls['mini_url']){ $urls['mini_url'] = $urls['thumb_url']; }
		
		if(!$urls['medium_url']){ $urls['medium_url'] = $urls['thumb_url']; }
		
		return $urls;
	
	}
	
	
	function resizeImage($image_id){
		
		global $wpdb;
	
		$image = $this->getImage($image_id);
		
		if(!$image){
			return array("msg" => "Invalid image record.", "status" => 0);
		}
		
		$g = $this->getGallery((int)$image['gallery_id']);
		
		$uploads = wp_upload_dir();
		
		$file = $uploads['basedir'] . "/" . $image['image_url'];
		
		if(!is_file($file)) {
			return array("msg" => "Image file is missing: " . $file, "status" => 0);
		}
		
		$relpath = $this->get_relative_path( $image['image_url'] );
		
		
		
		//Resize and Get Image URL
		if( $g['image_width'] || $g['image_height'] ){
			$newpath =	$this->createResized($g, 'image', $file, $relpath);
			if($newpath){
				$data['image_url'] = $newpath;
			}
		}
												
		//Resize and Get Medium URL
		if( $g['medium_width'] || $g['medium_height'] ){
			$newpath =	$this->createResized($g, 'medium',  $file, $relpath);
			if($newpath){
				$data['medium_url'] = $newpath;
			}
		} else {
			$data['medium_url'] =  $imgdata['image_url'];
		}
		
		//Resize and Get Thumb URL
		if( $g['thumb_width'] || $g['thumb_height'] ){
			$newpath =	$this->createResized($g, 'thumb', $file, $relpath);
			if($newpath){
				$data['thumb_url'] = $newpath;
			}
		} else {
			$data['thumb_url'] =  $imgdata['image_url'];
		}
		
		//Create Mini Size
		if( $g['mini_width'] || $g['mini_height'] ){
			$newpath =	$this->createResized($g, 'mini',  $file, $relpath);
			if($newpath){
				$data['mini_url'] = $newpath;
			}
		} else {
			$data['mini_url'] =  $imgdata['image_url'];
		}
		
		$where['image_id'] = (int)$image_id;
		if(is_array($data) ){
		$upd['updated'] = $wpdb->update(PSIMAGESTABLE, $data, $where);
		}
		if((int)$upd['updated'] > 0){
			$upd['message'] = "Image $image_id resized...";
			$upd['status'] = 1;
		} else {
			$upd['message'] = "Could not resize (probably not needed)...image $image_id";
			$upd['status'] = 0;
		}
		
		
		
		
		return $upd;
	
	}
	
	function createResized( $g, $size, $file, $relpath ){
	

		$resized = image_make_intermediate_size($file, $g[$size.'_width'], $g[$size.'_height'], !$g[$size.'_aspect'] );
			
		if( $resized ){
		
			return $relpath . $resized['file'];
		
		} 
		
		return false;
			
	}
	
	
	//
	//	Functions for Importing Images from Media Gallery
	//



	
	/*	
	 *   Add the selected images to the selected gallery
	 *
	*/
	function addAttachmentToGallery($gallery_id, $attach_id, $image_only=true){
		global $wpdb;
		
		$json['gallery_id'] = (int)$gallery_id;
		
		if(!$json['gallery_id']){
		
			$json['message'] = "No gallery selected to import the images to.";
			$json['msgclass'] = "error";
			return $json;
			
		}
		
		if(!(int)$attach_id){
		
			$json['message'] = "No attachment (image) selected to import.";
			$json['msgclass'] = "error";
			return $json;
			
		}
		
		if(!current_user_can('level_10')){
			$json['message'] = "Insufficient rights to add images.";
			$json['msgclass'] = "error";
			return $json;
		}
		
		$cnt = 0;
				
		$g = $this->getGallery($json['gallery_id']);
		
		if(!isset($this->uploads) || !is_array($this->uploads)){
			$this->uploads = wp_upload_dir();
		}
		
		$uploads = $this->uploads;
			
		// get the WP attachment info
		$attach_file = get_post_meta($attach_id, '_wp_attached_file');

		$img[0]['file'] = $attach_file[0];
		$imgdata['meta_data'] = serialize($attach_file[0]['image_meta']);
		
		
		$post = get_post($attach_id);
		
		
		$imgdata['image_caption'] = $post->post_title;
		$imgdata['post_id'] = $post->post_parent;
									
		// make the file name
		
		$file = $uploads['basedir'] . "/" . $img[0]['file'];
		
		//Make sure file exists
		if(!is_file($file)) {
			$json['message'] = "No image: " . $img[0]['file'];
			$json['msgclass'] = 'error';

			return $json;
		}
		
		//Make sure it's an image
		if($image_only && !file_is_valid_image($file)) {
			$json['message'] = "File is not an image: " . $img[0]['file'];
			$this->added_images[0]['notimage'] = true;
			$this->added_images[0]['file_name'] = $img[0]['file'];
			$json['msgclass'] = 'error';

			return $json;
		}
		
		$relpath = $this->get_relative_path( $img[0]['file'] );
		
		$imgdata['image_url'] =  $img[0]['file'];
		
		$imgdata['wp_attach_id'] = $attach_id;	
		
		
		//Resize and Get Image URL
		if( $g['image_width'] || $g['image_height'] ){
			$this->createResizedFromAttachment($g, 'image', $file, $uploads, $relpath, $imgdata, $img );
		}
		if(!$imgdata['image_url']){
			$imgdata['image_url'] =  $img[0]['file'];
		}
						
			
			//Resize and Get Medium URL
			if( $g['medium_width'] || $g['medium_height'] ){
				$this->createResizedFromAttachment($g, 'medium', $file, $uploads, $relpath, $imgdata, $img );
			}
			if(!$imgdata['medium_url']){
				$imgdata['medium_url'] =  $imgdata['image_url'];
			}
			
			//Resize and Get Thumb URL
			if( $g['thumb_width'] || $g['thumb_height'] ){
				$this->createResizedFromAttachment($g, 'thumb', $file, $uploads, $relpath, $imgdata, $img );
			}
			if(!$imgdata['thumb_url']){
				$imgdata['thumb_url'] =  $imgdata['medium_url'];
			}
			
			//Create Mini Size
			if( $g['mini_width'] || $g['mini_height'] ){
				$this->createResizedFromAttachment($g, 'mini', $file, $uploads, $relpath, $imgdata, $img );
			}
			if(!$imgdata['mini_url']){
				$imgdata['mini_url'] =  $imgdata['thumb_url'];
			}
			
			$json = $imgdata;
			
			$json['image_id'] = $this->saveImageToDB($g, $imgdata);			
		
		
		if($json['image_id']){
			$json['thumb_full_url'] = $uploads['baseurl'] . '/' . $json['thumb_url'];
			$json['message'] = "Images saved: " . $cnt;
			
			$this->added_images[] = $json;
		
		}
						
		return $json;
	}
	
	
	
	/*	
	 *   Create Resized Images
	 *
	*/
	function createResizedFromAttachment( $g, $size, $file, $uploads, $relpath, &$imgdata, $attach ){
	
		$resized = image_make_intermediate_size( $file,
			$g[$size.'_width'], $g[$size.'_height'], !$g[$size.'_aspect']  );
			
		if( $resized ){
		
			$imgdata[$size.'_url'] = $relpath . $resized['file'];
		
		} else {
			
			//We didn't need to resize it, so just use the same image
			if(isset($attach[0]["sizes"]) && is_array($attach[0]["sizes"])){
				
				$sizeattach = $size == 'thumb' ? 'thumbnail' : 'medium';
				
				
				if( $size == 'image' ){
					$imgdata['image_url'] =  $attach[0]['file'];
				} else {
					$imgdata[$size.'_url'] = $attach[0]['sizes'][$sizeattach]['file'];	
				}
				
			
			}
		}
			
	}
	
	
	/*	
	 *   Save Image to the Database
	 *
	*/
	function saveImageToDB($g, $imgdata){
		global $current_user;
		global $wpdb;
		
			
		$data['user_id'] = (int)$current_user->ID;
		$data['gallery_id'] = (int)$g['gallery_id'];
		$data['comment_id'] = -1;
		$data['post_id'] = (int)$imgdata['post_id'];
		
		$data['image_name'] = basename($imgdata['image_url']);
		$data['image_caption'] = $imgdata['image_caption'];
		$data['url'] = "";
		$data['file_name'] = $imgdata['file_name'];
		
		$data['file_type'] = 0;
		
		$data['file_url'] = $imgdata['file_url'];
		
		$data['meta_data'] = $imgdata['meta_data'];
		
		// Add the 4 image URLs
		$data['thumb_url'] = $imgdata['thumb_url'];
		$data['medium_url'] = $imgdata['medium_url'];
		$data['image_url'] = $imgdata['image_url'];
		$data['mini_url'] = $imgdata['mini_url'];
		
		$data['wp_attach_id'] = $imgdata['wp_attach_id'];
		
		
		$data['status'] = 1;
		
		
		$data['alerted'] = 1;
		
		$data['updated_by'] = $current_user->ID;
		$data['created_date'] = date( 'Y-m-d H:i:s');
		$data['seq'] = -1;
		$data['avg_rating'] = 0;
		$data['rating_cnt'] = 0;
			
		$ret = (int)$wpdb->insert(PSIMAGESTABLE, $data);
				
		$image_id = $wpdb->insert_id;
		
		$this->updateGalleryImageCount((int)$g['gallery_id']);
	
		return $image_id;
	}
	
	function updateGalleryImageCount($gallery_id=false, $image_id=false, $image_count=false){
	
		global $wpdb;
		
		if(!$gallery_id){
			$ret = $this->getImage($image_id);
			if($ret){
				$gallery_id = $ret['gallery_id'];
			}
		}
		
		if(!(int)$gallery_id){ return; }
		
		if(!(int)$image_count){
			$sql = "SELECT COUNT(image_id) FROM " . PSIMAGESTABLE
				. " WHERE gallery_id = " . (int)$gallery_id 
				. " AND status = 1 ";
				
			$cnt = $wpdb->get_var($sql);
		} else {
			$cnt = $image_count;
		}
		
		$sql = "UPDATE " . PSGALLERIESTABLE . " SET img_count = " . $cnt
			. " WHERE gallery_id = " . (int)$gallery_id;
		$wpdb->query($sql);
	
	}
	
	
	/**
	 * Adapted from wp-includes/post.php
	 * 
	 * Used to update the file path of the attachment, which uses post meta name
	 * '_wp_attached_file' to store the path of the attachment.
	 *
	 * @since 2.1.0
	 * @uses apply_filters() Calls 'update_attached_file' on file path and attachment ID.
	 *
	 * @param int $attachment_id Attachment ID
	 * @param string $file File path for the attachment
	 * @return bool False on failure, true on success.
	 */
	function get_relative_path( $filepath ) {
		
		$ret = str_replace(basename($filepath), "", $filepath);
		return $ret;
	
	}
	
	/* 
	 *	Send New Image Alerts
	 *
	*/
	//Send email alerts for new images
	function sendNewImageAlerts($alert_now = false)
	{
		global $wpdb;
		
		if(!$alert_now && get_option('BWBPhotosmashNeedAlert') != 1){ return; }
		
		if( !$this->options['alert_all_uploads'] ){
			
			$sqlStatus = " AND status = -1 " ;
			$msgStatus = " awaiting moderation.";
		
		}
		
		$last_alert = time();
		
		update_option('BWBPhotosmashLastAlert', $last_alert);
		update_option('BWBPhotosmashNeedAlert',0);
		
		$sql = "SELECT * FROM ".PSIMAGESTABLE." WHERE alerted = 0 $sqlStatus ;";
		$results = $wpdb->get_results($sql);
		if(!$results) return;
		
		$ret = get_bloginfo('name')." has ". $results->num_rows. " new photos". $msgStatus. ".  Select the appropriate gallery or click image below.<p><a href='".get_bloginfo('url')
		."/wp-admin/admin.php?page=managePhotoSmashImages'>".get_bloginfo('name')." - PhotoSmash Photo Manager</a></p>";
		
		
		$ret .= "<table><tr>";
		$i = 0;
		
		$uploads = wp_upload_dir();
		
		foreach($results as $row)
		{
		
			if( !$row->thumb_url ){
				
					$row->thumb_url = PSTHUMBSURL.$row->file_name;
			
			} else {
				$row->thumb_url = $uploads['baseurl'] . '/' . $row->thumb_url;
			
			}
		
			$ret .= "<td><a href='".get_bloginfo('url')
		."/wp-admin/admin.php?page=managePhotoSmashImages&psget_gallery_id=".$row->gallery_id."'><img src='".$row->thumb_url."' /><br/>gallery id: ".$row->gallery_id."</a></td>";
			$i++;
			if($i==4){
				$ret .="</tr><tr>";
				$i=0;
			}
		}
		$ret .="</tr></table>";
		$admin_email = get_bloginfo( "admin_email" );
		
 		$headers = "MIME-Version: 1.0\n" . "From: " . get_bloginfo("site_name" ) ." <{$admin_email}>\n" . "Content-Type: text/html; charset=\"" . get_bloginfo('charset') . "\"\n";
 		
 		wp_mail($admin_email, "New images for moderation", $ret, $headers );
		
		$data['alerted'] = -1;
		$where['alerted'] = 0;
		$wpdb->update(PSIMAGESTABLE, $data, $where);
		
	}
	
	/* 
	 *	Update the Tag Counts for images...useful when deleting images
	 *
	*/
	function updateTagCounts(){
	
		global $wpdb;
		
		$sql = "SELECT " . $wpdb->term_relationships . ".object_id FROM " 
			. $wpdb->term_relationships . "  JOIN " . $wpdb->term_taxonomy 
			. " ON " . $wpdb->term_relationships . ".term_taxonomy_id = " 
			. $wpdb->term_taxonomy . ".term_taxonomy_id AND " 
			. $wpdb->term_taxonomy . ".taxonomy = 'photosmash' LEFT OUTER JOIN " 
			. $wpdb->prefix."bwbps_images ON " 
			. $wpdb->prefix."bwbps_images.image_id = " 
			. $wpdb->term_relationships . ".object_id WHERE " 
			. $wpdb->prefix."bwbps_images.image_id IS NULL";
			
		$res = $wpdb->get_col($sql);
				
		if($res && is_array($res)){
		
			$sql = implode(", ", $res);
			
			$sql = "DELETE FROM " . $wpdb->term_relationships . " WHERE "
				. $wpdb->term_relationships . ".object_id IN ( " . $sql . " )";
			
			$wpdb->query($sql);
		
		}
		
		$terms = get_terms("photosmash");
		
		foreach($terms as $term){
		
			$t[] = $term->term_taxonomy_id;
		
		}
		
		wp_update_term_count_now($t, 'photosmash');
		
	}
	
	
	// Used to determine number images uploade by user by time by gallery
	function getUserImageCountByGallery($user_id, $gallery_id = 0, $hours = 0){
		global $wpdb;
		
		if( !(int)$user_id ){ return 0; }
		
		if( (int)$user_id ){ $w[] = 'user_id = ' . (int)$user_id; }
		if( (int)$gallery_id ){ $w[] = 'gallery_id = ' . (int)$gallery_id; }
		
		if( (int)$hours ){ 
			$gmtoffset = get_option( 'gmt_offset' ) * 3600 ;
			$t = ((int)$hours * 3600) - (int)$gmtoffset;	// get seconds to subtract
			$t = time() - $t;	// get current time minus all those hours/secs
			$t = date('Y-m-d H:i:s', $t);	// format the time
			$w[] = "created_date > '$t'";
		}
		
		$where = implode(" AND ", $w);
		
		$sql = "SELECT COUNT(image_id) as cnt FROM " . PSIMAGESTABLE . " WHERE " . $where;
		
		$result = $wpdb->get_var($sql);
		return $result;
	
	}
	
}	//Closes class

?>