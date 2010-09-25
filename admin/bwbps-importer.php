<?php
//Importer Pages for BWB-PhotoSmash plugin

class BWBPS_Importer{
	
	var $psOptions;
	var $message = false;
	var $msgclass = "updated fade";
	
	var $gallery_id;
	var $galleryQuery;
	
	//Constructor
	function BWBPS_Importer($psOptions){
		//Get PS Defaults
		$this->psOptions = $psOptions;
		
		$this->gallery_id = (int)$_POST['gal_gallery_id'];				
				
		//Save Gallery Settings
		if(isset($_POST['save_bwbPSImages'])){
			check_admin_referer( 'bwbps-import-images');
			$this->addImagesToGallery($this->psOptions);
		}
	}
	
	
	// Add the selected images to the selected gallery
	function addImagesToGallery($psOptions){
		global $wpdb;
		
		if(!$this->gallery_id){
		
			$this->message = "No gallery selected to import the images to.";
			$this->msgclass = "error";
			return;
			
		}
		
		if(!current_user_can('level_10')){
			$this->message = "Insufficient rights to add images.";
			$this->msgclass = "error";
			return;
		}
		
		$cnt = 0;
		
		if(!isset($_POST['bwbps_selectedimg'])){
			$this->message = "No images selected.";
			$this->msgclass = "error";
			return;
		}
		
		$g = $this->getGallery($this->gallery_id);
		
		$uploads = wp_upload_dir();
		
		foreach ($_POST['bwbps_selectedimg'] as $sel_img){
			if(!$sel_img){ continue; }
			$attach_id = str_replace('bwbimg_', '', $sel_img);
			
			if((int)$attach_id){
				
				// get the WP attachment info
				$img = get_post_meta($attach_id, '_wp_attachment_metadata');
				
				$post = get_post($attach_id);
				
				$imgdata['meta_data'] = serialize($img[0]['image_meta']);
				$imgdata['image_caption'] = $post->post_title;
				$imgdata['post_id'] = $post->post_parent;
											
				// make the file name
				
				$file = $uploads['basedir'] . "/" . $img[0]['file'];
				
				if(!is_file($file)) {
					$failed[] = "No image: " . $img[0]['file'];
					continue;
				}
				
				$relpath = $this->get_relative_path( $img[0]['file'] );
				
				$imgdata['image_url'] =  $img[0]['file'];
				
				$imgdata['wp_attach_id'] = $attach_id;	
				
				
				//Resize and Get Image URL
				if( $g['image_width'] || $g['image_height'] ){
					$this->createResized($g, 'image', $file, $uploads, $relpath, $imgdata, $img );
				}
				if(!$imgdata['image_url']){
					$imgdata['image_url'] =  $img[0]['file'];
				}
								
				
				//Resize and Get Medium URL
				if( $g['medium_width'] || $g['medium_height'] ){
					$this->createResized($g, 'medium', $file, $uploads, $relpath, $imgdata, $img );
				}
				if(!$imgdata['medium_url']){
					$imgdata['medium_url'] =  $imgdata['image_url'];
				}
				
				//Resize and Get Thumb URL
				if( $g['thumb_width'] || $g['thumb_height'] ){
					$this->createResized($g, 'thumb', $file, $uploads, $relpath, $imgdata, $img );
				}
				if(!$imgdata['thumb_url']){
					$imgdata['thumb_url'] =  $imgdata['medium_url'];
				}
				
				//Create Mini Size
				if( $g['mini_width'] || $g['mini_height'] ){
					$this->createResized($g, 'mini', $file, $uploads, $relpath, $imgdata, $img );
				}
				if(!$imgdata['mini_url']){
					$imgdata['mini_url'] =  $imgdata['thumb_url'];
				}
				
				$this->saveImageToDB($g, $imgdata);
				
				$cnt++;
				
				unset($imgdata);
				
			} else {
				$failed[] = "Attach id (no metadata): " . $sel_img;
			}
					
		}
		
		if($cnt){
			$this->message = "Images saved: " . $cnt;
		}
		if(is_array($failed)){
		
			$this->message .= "<p>". implode(", " , $failed);
			$this->msgclass = 'error';
		}
		
		return;
	}
	
	function createResized( $g, $size, $file, $uploads, $relpath, &$imgdata, $attach ){
	
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
	 *	STEP 5:   Save Image to the Database
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
		
		// Add the 3 image URLs
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
	
		return $ret;
	}
			
	
	/**
	 * printImageImporter()
	 * 
	 * @access public 
	 * @prints the manage images page
	 */
	function printImageImporter()
	{
		global $wpdb;
		
		if(!current_user_can('level_10')){
			echo "<h3>Insufficient rights!</h3>";
			return;
		}

		$psOptions = $this->psOptions;
		$uploads = wp_upload_dir();
		
		$gal_id = (int)$this->gallery_id;

		$galleryDDL = $this->getGalleryDDL($gal_id, "Select destination Gallery", "", "gal_gallery_id", 15);
		
		if($gal_id){
			$galOptions = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.PSGALLERIESTABLE.' WHERE gallery_id = %d',$gal_id), ARRAY_A);
			$caption = " > ".$galOptions['gallery_name'];
		}
		
		$post_id = (int)$_POST['bwbps_post_id'];
		
		$start = 0;
		$limit = 0;
		if(isset($_POST['bwbpsStartImg'] )){$start = (int)$_POST['bwbpsStartImg'];}
		if(isset($_POST['bwbpsLimitImg'] )){$limit = (int)$_POST['bwbpsLimitImg'];}
		
		if($start > 0){ $start--; }
		if($limit < 1){ $limit = 50; }
		
		if(isset($_POST['showModerationImages']) || isset($_POST['save_bwbPSImages']))
		{
			$images = $this->getImages($uploads, $gal_id, $post_id, $start, $limit);
		} else {
			$post_id = -1;
		}
		
		$postDDL = $this->getPostsDDL($post_id, 'bwbps_post_id'); 
		$start++; //set it up for the form below
		?>
		
	<div class=wrap>
		
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<?php bwbps_nonce_field('bwbps-import-images'); ?>
		<h2>PhotoSmash Galleries</h2>
		
		<?php
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>		
		<h3>Import Photos from WordPress Media Library</h3>
		<?php if($this->psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>
		<hr/>
		
		<div class='tablenav'>
		Fetch: <?php echo $postDDL; ?> or Search post: <input type='text' name='postname_search' id='postname_search' value='<?php if( isset($_POST['postname_search']) && $_POST['postname_search']) echo wp_kses(esc_attr($_POST['postname_search']), array() ); ?>'/> <input type="submit" name="showModerationImages" class="button-primary" value="<?php _e('Fetch Images', 'bwbPS') ?>" />
		Show: <input type='text' name='bwbpsLimitImg' size=4 value='<?php echo $limit;
				?>' /> | Start:
				<input type='text' name='bwbpsStartImg' size=4 value='<?php  echo $start;
				?>' />
		</div>
		<div style='width: 98%;'>
		<table class='widefat fixed'>
		<thead><tr><th>
		<span style='float: left;'>
			<a href='javascript: void(0);' onclick='bwbpsToggleAllImportImages(true);'>Select all</a> | <a href='javascript: void(0);' onclick='bwbpsToggleAllImportImages(false);'>Deselect</a> &nbsp; &nbsp;<span class='ps-hint'>(Click to select)</span>
		</span>
		</th>
		<th>
			<?php echo $galleryDDL; ?>
			 <input type="submit" name="save_bwbPSImages" class="button-primary" value="<?php _e('Add Images', 'bwbPS') ?>" /> 

		</th>
		</tr></thead>
		<tbody><tr><td colspan="2">
		<?php
			
			if($images){
				$nonce = wp_create_nonce( 'bwbps_moderate_images' );
				echo '
				<input type="hidden" id="_moderate_nonce" name="_moderate_nonce" value="'.$nonce.'" />
				';
			}
			echo $images;
	?>
		</td></tr></tbody>
		</table>
		</div>
	</form>

 	</div>
 	
 	<script type="text/javascript">
 		jQuery(document).ready(function() { 
 			bwbpsActivateImageImports();
 		});
 	</script>
 	
<?php
	}
	
	function getImages($uploads, $gal_id, $post_id, $start,$limit){
		
		$images = $this->getMediaLibImages($gal_id, $post_id, '',$start,$limit);
		
		foreach($images as $img){
			$i = $this->getImage($img,$uploads);
			
			if($i){
			
				$ret .= $i;
			
			}
		}
		
		return $ret;
	
	}
	
	function getGallery($gal_id){
	
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE 
			. " WHERE gallery_id = %d", (int)$gal_id), ARRAY_A);
	
	}

	function getGalleriesQuery(){
		
		global $wpdb;
		
		$sql = "SELECT ".PSGALLERIESTABLE.".gallery_id, ".PSGALLERIESTABLE.".gallery_name, "
			.$wpdb->prefix."posts.post_title, COUNT("
			.PSIMAGESTABLE.".image_id) as img_cnt, ".PSGALLERIESTABLE.".status FROM "
			.PSGALLERIESTABLE." LEFT OUTER JOIN "
			.PSIMAGESTABLE." ON ".PSIMAGESTABLE.".gallery_id = "
			.PSGALLERIESTABLE.".gallery_id LEFT OUTER JOIN "
			.$wpdb->prefix."posts ON ".PSGALLERIESTABLE.".post_id = "
			.$wpdb->prefix."posts.ID WHERE ".PSGALLERIESTABLE
			.".gallery_type < 10  GROUP BY "
			.PSGALLERIESTABLE.".gallery_id, ".PSGALLERIESTABLE.".gallery_name, "
			.$wpdb->prefix."posts.post_title, ".PSIMAGESTABLE.".gallery_id,"
			.PSGALLERIESTABLE.".status, "
			.$wpdb->prefix."posts.ID, ".PSGALLERIESTABLE.".post_id";
		
		
		if(!$this->galleryQuery){
		
			$query = $wpdb->get_results($sql);
		
			$this->galleryQuery = $query;
		
		} else {
		
			$query = $this->galleryQuery;
			
		}
	
		return $query;
	}
	
	//Returns markup for a DropDown List of existing Galleries
	function getGalleryDDL($selectedGallery = 0, $newtag = "New", $idPfx = "", $ddlName= "gal_gallery_id", $length = 0, $showImgCount = true)
 	{
 		global $wpdb;
 		 
 		if($newtag <> 'skipnew' ){
			$ret = "<option value='0'>&lt;$newtag&gt;</option>";
		}
		
		$query = $this->getGalleriesQuery();
				
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
	
		//Get DDL of Posts 
		
	function getPostsDDL($selected_id, $ele_name='bwbps_post_id', $category_filter=false ){		
		
		$ret = "<option value='-1'>&lt;All posts&gt;</option>";
		
		if($selected_id === 0){
			$sel = "selected='selected'";
		}
		$ret .= "<option value='0' $sel>&lt;Unattached Images (no post)&gt;</option>";
		
		$posts = $this->getPostsList();
		
		if(is_array($posts)){
			foreach($posts as $row){
				if($selected_id == $row['ID']){
					
					$sel = "selected='selected'";
						
				}else{$sel = "";}
				
				if(strlen($row['post_title']) > 30){
					$title = substr($row['post_title'],0,30). "&#8230;";
				} else {
					$title = $row['post_title'];
				}
				$ret .= "<option value='".$row['ID']."' ".$sel.">".esc_attr($title)." (" . $row['cnt'] . ")</option>";
			}
		}
		
		$ret ="<select id='bwbpsCFDDL' name='$ele_name' >".$ret."</select>";
		
		return $ret;

	
	}
	
	
	function getPostsList(){
		global $wpdb;
		
		$query = $wpdb->get_results("SELECT a.ID, a.post_title, (SELECT COUNT(c.ID) FROM "
			. $wpdb->posts . " c WHERE c.post_parent = a.ID AND c.post_type = "
			. "'attachment') as cnt FROM " . $wpdb->posts
			. " a WHERE a.ID IN (SELECT b.post_parent FROM " . $wpdb->posts 
			. " b WHERE b.post_type = 'attachment')", ARRAY_A);
			
		return $query;
	}
		
	function getImage($img, $uploads, $size='thumbnail'){
		
		if( isset($img) ){
			$m = unserialize($img->meta_value);
			
			if( is_array($m) ){
					
							
				$filepath = $m['file'];
																
				$relpath = $this->get_relative_path( $filepath );
				
				if($m['sizes'][$size]['file']){
					
					$sizeurl = $m['sizes'][$size]['file'];
				} else {
					$sizeurl = $filepath;
					$relpath  = "";
				
				}
				
				$imgurl = $uploads['baseurl'] . "/" . $relpath . $sizeurl;
				
				$imgfile = $uploads['basedir'] . "/" . $relpath . $sizeurl;
									
				if($img->post_title){
					$c = esc_attr($img->post_title);
				}
				
				$info = "<div>Post id: ". $img->post_parent . " &nbsp; Gal id: " . $img->gallery_id 
				. "<br/>Attach id: ". $img->post_id
				."</div>";
				
				if( $sizeurl ){
				
					if($img->gallery_id){
						$imgborder = "#cc0000";
					} else {
						$imgborder = "#a0a0a0";
					}
				
					$f = "<div class='bwbps_theimage'><a id='bwbimg_" . $img->post_id. "' class='bwbps-notsel' href='javascript: void(0);'><img style='border: 2px solid $imgborder;' src='" . $imgurl . "' title='$c' width='95px' height='95px'/></a>
					<input class='bwbps-imagesforsel' type='hidden' name='bwbps_selectedimg[]' id='bwbimg_" 
						. $img->post_id. "sel' value='' /> 
					</div>";
					
					if (!is_file($imgfile) ){
					
						$f = "Invalid file<br/><br/>". $filepath . "<br/>";
					
					}
				
					$ret = "<div class='bwbps_imgbox'>". $f. $info."</div>";
				
				} else {
					
					$ret = "<div class='bwbps_imgbox'>Bad Thumbnail<br/><br/>". $info."</div>";
				
				}
				
			}
			
		}
		
		return $ret;
	
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
	
	/**
	 *	Query for getting the Images from the WP Image Library
	 *
	 *	@param $post_id: the post ID to limit the image results to
	 *	@param $gallery_id: the gallery ID to filter out existing images on
	*/
	function getMediaLibImages( $gallery_id=false, $post_id = false, $sql_attach_ids = "", $start=0, $limit=50 ){
		
		global $wpdb;
		
		
		if((int)$post_id> -1){
		
			$sql_post = " AND b.post_parent = " . (int)$post_id;	
			
		}
		
		if( isset($_POST['postname_search']) ){
		
			$postsearch = trim(wp_kses( esc_sql($_POST['postname_search']), array() ));
			
			if( $postsearch ){
				$sql_post = " AND d.post_title LIKE '%" . $postsearch . "%' ";
				
				$sql_post_join = " LEFT OUTER JOIN " . $wpdb->posts . " d ON b.post_parent = d.ID ";	
			}
			
		}
		
		if((int)$gallery_id ){
		
			$med_ids = $this->getMediaLibIdsForGallery($gallery_id);
			if($med_ids){
				$sql_med_ids = implode(", ", $med_ids);
				$sql_med_ids = " AND NOT a.post_id IN (" . $sql_med_ids . ")";
			}
		
		}
		
		if($limit || $start){
			$start = (int)$start;
			if((int)$limit == 0){$limit = 50;}
			
			$limitsql = ' LIMIT ' . $start . ', ' . $limit;
		}
		
		$sql = "SELECT a.*, b.post_parent, b.post_title, c.gallery_id FROM " .$wpdb->postmeta . " a LEFT OUTER JOIN " 
			. $wpdb->posts . " b ON a.post_id = b.ID LEFT OUTER JOIN " . PSIMAGESTABLE 
			. " c ON c.wp_attach_id = a.post_id " . $sql_post_join . " WHERE a.meta_key = '_wp_attachment_metadata'"
			. $sql_post . $sql_med_ids . $sql_attach_ids . $limitsql;
			
			
		$ret = $wpdb->get_results($sql);
		
		return $ret;			
		
	
	}
	
	/**
	 *	Query for getting the Images from the WP Image Library
	 *
	 *	@param $post_id: the post ID to limit the image results to
	 *	@param $gallery_id: the gallery ID to filter out existing images on
	*/
	function getMediaLibIdsForGallery($gallery_id){
	
		global $wpdb;
		
		$sql = "SELECT DISTINCT wp_attach_id FROM " . PSIMAGESTABLE . " WHERE gallery_id = " 
			. (int)$gallery_id . " AND wp_attach_id > 0 AND wp_attach_id IS NOT NULL";
		
		$ret = $wpdb->get_col($sql);
		
		return $ret;
	
	}

	
}  //closes out the class

if ( !function_exists('wp_nonce_field') ) {
        function bwbps_nonce_field($action = -1) { return; }
        $bwbps_plugin_nonce = -1;
} else {
        function bwbps_nonce_field($action = -1) { return wp_nonce_field($action); }
}


?>