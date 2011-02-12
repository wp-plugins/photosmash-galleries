<?php

class BWBPS_GalleryFunc{
	
	
	var $g;	//Gallery
	var $uploads; //The wp_upload_dir array
	var $options; 
	var $added_images;
	
	var $c;
	
	var $galleryQuery;	// caching gallery queries

	//Constructor
	function BWBPS_GalleryFunc(&$psOptions){
		$this->options = $psOptions;		
	}
	
	function selectGallery($gallery_id){
		global $wpdb;
		$gquery = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
				." WHERE gallery_id = %d",$gallery_id),ARRAY_A);
		return $gquery;
	}
	
	
	// Retrieve the Gallery....Creates new Gallery record linked to Post if gallery ID is false
	function getGallery($g, $no_create = false){
		global $wpdb;
		global $post;
		global $bwbPS;
		
		if(!is_array($g) && (int)$g){
			return $this->selectGallery($g);
		}
			
		//See if Gallery is Cached
		if($g['gallery_id'] && is_array($bwbPS->galleries) && array_key_exists($g['gallery_id'], $bwbPS->galleries) && $g['gallery_type'] <> 10 && $g['gallery_type'] <> 20 && $g['gallery_type'] <> 30)
		{
			// Set $g = to the cached gallery, but keep any values that $g already has
			foreach ( $bwbPS->galleries[$g['gallery_id']] as $key => $option ){
				if(!$g[$key]){
					$g[$key] = $option;
				}
			}
			
			$galleryfound = true;
		
		} else {
		
			//Gallery was not cached......Get from either Gallery ID or Post ID
			
			$gquery = false;
			
			switch ((int)$g['gallery_type']) {
				
				case 10 :	// Contributor Gallery
					$g['gallery_id'] = (int)$this->options['contributor_gallery'];
					
					$gquery = $this->getGalleryByType($g, 'Contributor Gallery');
					
					break;
				
				case 20 : // Random images
		
					$gquery = $this->getGalleryByType($g, "Random Images");
					
					break;
				
				case 30 : // Recent images
					$d['sort_order'] = 1;
					$gquery = $this->getGalleryByType($g, "Recent Images", $d);
					break;
				
				case 40 : // Tag Gallery
					
					$d['show_imgcaption'] = 12;
					$gquery = $this->getGalleryByType($g, "Tag Gallery", $d);
					
					break;
					
				case 70 : // User Favorites
					
					$gquery = $this->getGalleryByType($g, "User Favorites");
					break;
					
				case 71 : // Most Favorited
					
					$d['sort_field'] = 5;
					$gquery = $this->getGalleryByType($g, "Most Favorited", $d);
					break;
					
				case 99 : // Highest Ranked
					
					$d['gallery_name'] = 'Highest Ranked';
					$d['sort_field'] = 4;
					$d['sort_order'] = 1;
					$d['rating_position'] = 0;
					$d['poll_id'] = -1;
					
					$gquery = $this->getGalleryByType($g, "Highest Ranked", $d);
										
					break;

				case 100 : // Gallery Viewer
					
					$gquery = $this->getGalleryByType($g, "PhotoSmash Gallery Viewer");
					break;
					
				default :
		
					//Get the specified gallery params if valid gallery_id
					if($g['gallery_id']){
						//Get gallery params based on Gallery_ID
						$gquery = $wpdb->get_row(
							$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
								." WHERE gallery_id = %d",$g['gallery_id']),ARRAY_A);
							
						//If query is false, then Bad Gallery ID provided...alert user
						if(!$gquery){$g['gallery_id'] = false; return $g;}
				
					} else {
										
						if($g['gallery_name']){
							//Get gallery params based on Gallery Name
							$gquery = $wpdb->get_row(
							$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
								." WHERE gallery_name = %s",$g['gallery_name']),ARRAY_A);
							
							//If query is false, then Gallery Name doesn't exist...create it
							if(!$gquery && !$no_create){
								$gquery = $this->createGallery($g);
							}
							
						} else {
							
							//Get gallery params based on Post_ID
							
							if( is_array($bwbPS->postGalleries) && 
								!empty($bwbPS->postGalleries[(int)$post->ID]) &&
								(int)$bwbPS->postGalleries[(int)$post->ID]
								){
							
								$gid = (int)$bwbPS->postGalleries[(int)$post->ID];
								if(!empty($bwbPS->galleries) && is_array($bwbPS->galleries) 
									&& !empty($bwbPS->galleries[$gid])){
									$gquery = $bwbPS->galleries[$gid];
								}
							
							}
							
							if( empty($gquery) ){
								$gquery = $wpdb->get_row(
									$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
										." WHERE post_id = %d",$post->ID),ARRAY_A);
							}
						}
							
					}
			
			}
			
			if($gquery){
				
				/* Keep the parameters passed in From the [photosmash] tag in the Content
				   ...fill in the holes from the Gallery's default settings
				   
				   CANNOT USE array_merge() -- 0 values mess you up
				*/
				
				$g['gallery_id'] = $gquery['gallery_id'];
				
				$g = $this->mergeArrays($g, $gquery);
								
				//Cache the new gallery
				$bwbPS->galleries[$gquery['gallery_id']] = $gquery;
				
				$galleryfound = true;
			} 
			
		}
		
		if(isset($g['contrib_role'])){
			switch ($g['contrib_role']) {
				case -1 :
					break;
				case 0 :
					break;
				case 10 :
					break;
				case 1 :
					break;
				default: 
					// Use PhotoSmash defaults and place into appropriate fields if missing.
					$g['contrib_role'] = (int)$this->options['contrib_role'];
					break;
			}
		}
			
		if( !$galleryfound && $post->ID && !$no_create){
			$g = $this->createGallery($g);
		}
		
		
		$g['add_text'] = $g['add_text'] ? $g['add_text'] : 
					( $this->options['add_text'] ? $this->options['add_text'] : "Add Photos" );
				
		return $g;
		
	}
	
	/*	Get Gallery by Gallery Type
	 *
	 *	Will create a new gallery if no gallery found
	*/
	function getGalleryByType($g, $altname="", $create_options = false){
		global $wpdb;
		
		if($g['gallery_id']){
			// Try to get the Gallery by ID
			$sql = $wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
					." WHERE gallery_id = %d AND gallery_type = %d",$g['gallery_id'], $g['gallery_type']);
		} else {
			// Try to get the Gallery by Name
			if($g['gallery_name']){
				$sql = $wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
					." WHERE gallery_name = %s AND gallery_type = %d", $g['gallery_name'], $g['gallery_type']);
			} 
		}
		
		// Retrieve from DB
		if($sql){
			$gquery = $wpdb->get_row( $sql,ARRAY_A);
		}
		
		// Did we get it?
		if( !$gquery ){
			// Try to get the gallery based on the Type alone
			$sql = $wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
					." WHERE gallery_type = %d  AND status = 1",$g['gallery_type']);
					
			$gquery = $wpdb->get_row( $sql,ARRAY_A);
		}
				
		if( !$gquery && !$no_create ){

			$g['gallery_name'] = $altname;
			
			if((int)$show_imgcaption){ 
				$g['show_imgcaption'] = (int)$show_imgcaption; 
			}
			
			$gquery = $this->createGallery($g, $create_options);
		
		}
		
		return $gquery;
	}
	
	
	
	/*	
	 *	Create Gallery
	*/
	function createGallery($g, $create_options=false){
		global $wpdb;
		global $post;
				
		//No Gallery found...Need to create a Record for this Gallery
		if((int)$g['gallery_type'] < 10 ){
			$data['post_id'] = $post->ID;
		}
		$data['gallery_name'] = $g['gallery_name'] ? $g['gallery_name']  : $post->post_title;
		$data['gallery_type'] = isset($g['gallery_type']) ? (int)$g['gallery_type'] : 0;
		$data['caption'] =  $g['caption'] ? $g['caption'] : $this->options['gallery_caption'];
		
		$data['rating_position'] =  isset($g['rating_position']) ? (int)$g['rating_position'] : (int)$this->options['rating_position'];
		$data['poll_id'] =  isset($g['poll_id']) ? (int)$g['poll_id'] : (int)$this->options['poll_id'];
					
		$data['add_text'] = $g['add_text'] ? $g['add_text'] : 
			( $this->options['add_text'] ? $this->options['add_text'] : "Add Photos" );
		
		$data['upload_form_caption'] =  $g['upload_form_caption'] ? $g['upload_form_caption'] : $this->options['upload_form_caption'];
		$data['contrib_role'] =  isset($g['contrib_role']) ? (int)$g['contrib_role'] : $this->options['contrib_role'];
		$data['img_rel'] =  $g['img_rel'] ? $g['img_rel'] : $this->options['img_rel'];
		$data['img_class'] =  $g['img_class'] ? $g['img_class'] : $this->options['img_class'];
		
		$data['anchor_class'] =  $g['anchor_class'] ? $g['anchor_class'] : $this->options['anchor_class'];
		
		if($g['img_status'] === 0 || $g['img_status'] == 1){
			$data['img_status'] = $g['img_status'];
		} else {
			$data['img_status'] = (int)$this->options['img_status'];
		}
		$data['img_perrow'] = isset($g['img_perrow']) ? (int)$g['img_perrow'] : (int)$this->options['img_perrow'];
		$data['img_perpage'] = isset($g['img_perpage']) ? (int)$g['img_perpage'] : (int)$this->options['img_perpage'];
		
		$data['mini_aspect'] = isset($g['mini_aspect']) ? (int)$g['mini_aspect'] : (int)$this->options['mini_aspect'];
		$data['mini_width'] = isset($g['mini_width']) ? (int)$g['mini_width'] : (int)$this->options['mini_width'];
		$data['mini_height'] =  isset($g['mini_height']) ? (int)$g['mini_height'] : (int)$this->options['mini_height'];
		
		$data['thumb_aspect'] = isset($g['thumb_aspect']) ? (int)$g['thumb_aspect'] : (int)$this->options['thumb_aspect'];
		$data['thumb_width'] = isset($g['thumb_width']) ? (int)$g['thumb_width'] : (int)$this->options['thumb_width'];
		$data['thumb_height'] =  isset($g['thumb_height']) ? (int)$g['thumb_height'] : (int)$this->options['thumb_height'];
		
		$data['medium_aspect'] = isset($g['medium_aspect']) ? (int)$g['medium_aspect'] : (int)$this->options['medium_aspect'];
		$data['medium_width'] = isset($g['medium_width']) ? (int)$g['medium_width'] : (int)$this->options['medium_width'];
		$data['medium_height'] =  isset($g['medium_height']) ? (int)$g['medium_height'] : (int)$this->options['medium_height'];
		
		$data['image_aspect'] = isset($g['image_aspect']) ? (int)$g['image_aspect'] : (int)$this->options['image_aspect'];
					
		$data['image_width'] = isset($g['image_width']) ? (int)$g['image_width'] : (int)$this->options['image_width'];
		
		$data['image_height'] = isset($g['image_height']) ? (int)$g['image_height'] : (int)$this->options['image_height'];
		
		$data['show_caption'] =  isset($g['show_caption']) ? (int)$g['show_caption'] : (int)$this->options['show_caption'];
		$data['nofollow_caption'] =  isset($g['nofollow_caption']) ? (int)$g['nofollow_caption'] : (int)$this->options['nofollow_caption'];
		$data['show_imgcaption'] =  isset($g['show_imgcaption']) ? (int)$g['show_imgcaption'] : (int)$this->options['show_imgcaption'];
		
		$data['use_customform'] = isset($data['use_customform']) ? (int)$data['use_customform'] : (isset($this->options['use_customform']) ? 1 : 0);
		
		$data['use_customfields'] = isset($data['use_customfields']) ? $data['use_customfields'] : (isset($this->options['use_customfields']) ? 1 : 0);
		
		$data['custom_formid'] = isset($data['custom_formid']) ? (int)$data['custom_formid'] : (int)$this->options['custom_formid'];
		
		$data['layout_id'] = isset($data['layout_id']) ? (int)$data['layout_id'] : (int)$this->options['layout_id'];
		
		$data['sort_field'] = isset($data['sort_field']) ? (int)$data['sort_field'] : (int)$this->options['sort_field'];
		
		$data['sort_order'] = isset($data['sort_order']) ? (int)$data['sort_order'] : (int)$this->options['sort_order'];
		
		$data['created_date'] = date( 'Y-m-d H:i:s');
		$data['status'] = 1;
		
		if($create_options && is_array($create_options)){
			$data = $this->mergeArrays($create_options, $data);
		}
		
		$wpdb->insert(PSGALLERIESTABLE, $data); //Insert into Galleries Table
		
		if(is_array($g) && is_array($data)){
			$g = $this->mergeArrays($g, $data);
			$g['gallery_id'] = $wpdb->insert_id;
		} else {
			unset($g);
			$g = false;
		}	
		return $g;
	}
	
	function mergeArrays($base, $addon){
		if(is_array($base) && is_array($addon)){
			foreach ( $addon as $key => $option ){
				if(!$base[$key]){
					$base[$key] = $option;
				}
			}
		}
		return $base;
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
	
}	//Closes class

?>