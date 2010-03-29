<?php

class BWBPS_ImageFunc{
	
	

	//Constructor
	function BWBPS_ImageFunc(){
	
	
	}
	
	
	function getImage($image_id){
	
		global $wpdb;
		
		$sql = "SELECT * FROM " . PSIMAGESTABLE . " WHERE image_id = " . (int)$image_id;
		
		$result = $wpdb->get_row($sql, ARRAY_A);
		return $result;
		
	}
	
	function getGallery($gallery_id){
	
		global $wpdb;
		
		$sql = "SELECT * FROM " . PSGALLERIESTABLE . " WHERE gallery_id = " . (int)$gallery_id;
		
		$result = $wpdb->get_row($sql, ARRAY_A);
		return $result;
	
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
				
		$upd['updated'] = $wpdb->update(PSIMAGESTABLE, $data, $where);
		
		if((int)$upd['updated'] > 0){
			$upd['message'] = "Image $image_id resized...";
		} else {
			$upd['message'] = "Resize failed...image $image_id";
		}
		
		$upd['status'] = 1;
		
		
		return $upd;
	
	}
	
	function createResized( $g, $size, $file, $relpath ){
	

		$resized = image_make_intermediate_size($file, $g[$size.'_width'], $g[$size.'_height'], !$g[$size.'_aspect'] );
			
		if( $resized ){
		
			return $relpath . $resized['file'];
		
		} 
		
		return false;
			
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
	
}	//Closes class

?>