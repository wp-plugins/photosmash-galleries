<?php
//Admin Pages for BWB-PhotoSmash plugin

class BWBPS_Admin{
	
	var $psOptions;
	var $message = false;
	var $msgclass = "updated fade";
	
	var $gallery_id;
	var $galleryQuery;
	
	//Constructor
	function BWBPS_Admin(){
		//Get PS Defaults
		$this->psOptions = $this->getPSOptions();
		
		$this->gallery_id = (int)$_POST['gal_gallery_id'];
				
		//Save PS General Settings
		if(isset($_POST['update_bwbPSDefaults'])){
			check_admin_referer( 'update-gallery');
			$this->saveGeneralSettings($this->psOptions);
			//Refresh options
			$this->psOptions = $this->getPSOptions();
		}
		
		//Reset PS General Settings to Default
		if(isset($_POST['reset_bwbPSDefaults'])){
			check_admin_referer( 'update-gallery');
			$this->psOptions = $this->getPSOptionsDefaults();
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
	
	
	function getPSOptionsDefaults()
	{
		//get some defaults if nothing is in the database
		return array(
				
				'auto_add' => 0,
				'img_perpage' => 0,
				'img_perrow' => 0,
				'thumb_aspect' => 0,
				'thumb_width' => 125,
				'thumb_height' => 125,
				'image_aspect' => 0,
				'image_width' => 0,
				'image_height' => 0,
				'img_rel' => 'lightbox[album]',
				'add_text' => 'Add Photo',
				'gallery_caption' => 'PhotoSmash Gallery',
				'upload_form_caption' => 'Select an image to upload:',
				'img_class' => 'ps_images',
				'show_caption' => 1,
				'nofollow_caption' => 1,
				'img_alerts' => 3600,
				'show_imgcaption' => 1,
				'contrib_role' => 10,
				'img_status' => 0,
				'last_alert' => 0,
				'use_advanced' => 0,
				'use_urlfield' => 0,
				'use_customform' => 0,
				'use_customfields' => 0,
				'use_thickbox' => 1,
				'use_alt_ajaxscript' => 0,
				'alt_ajaxscript' => '',
				'alt_javascript' => '',
				'uploadform_visible' => 0,
				'use_manualform' => 0,
				'layout_id' => -1,
				'caption_targetnew' => 0,
				'img_targetnew' => 0,
				'custom_formid' => 0,
				'use_donelink' => 0,
				'css_file' => '',
				'exclude_default_css' => 0,
				'date_format' => 'm/d/Y',
				'upload_authmessage' => '',
				'imglinks_postpages_only' => 0,
				'sort_field' => 0,
				'sort_order' => 0
		);
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
				$filename = $wpdb->get_var($wpdb->prepare("SELECT file_name FROM "
					.PSIMAGESTABLE. " WHERE image_id = %d", $imgid));
				if($filename){
					unlink(PSIMAGESPATH.$filename);
					unlink(PSTHUMBSPATH.$filename);
				}
			
				$ret = $wpdb->query($wpdb->prepare('DELETE FROM '.
					PSIMAGESTABLE.' WHERE image_id = %d', $imgid ));
				
				$wpdb->query($wpdb->prepare('DELETE FROM '. PSCUSTOMDATATABLE
					.' WHERE image_id = %d', $imgid));
					
				
			} else {$ret = 0;}
		} else {
			$ret = 0;
		}
		
		return $ret;
	}

	
	//Checks to see if we're saving options
	function saveGeneralSettings($ps){
		global $wpdb;
		
		//This section Saves the overall PhotoSmash defaults
			if(isset($_POST['ps_auto_add'])){
				$ps['auto_add'] = (int)$_POST['ps_auto_add'];
			}


			$ps['thumb_aspect'] = (int)$_POST['ps_thumb_aspect'];
			$ps['thumb_width'] = (int)$_POST['ps_thumb_width'];
			$ps['thumb_height'] = (int)$_POST['ps_thumb_height'];
			
			$ps['image_aspect'] = (int)$_POST['ps_image_aspect'];
			$ps['image_width'] = (int)$_POST['ps_image_width'];
			$ps['image_height'] = (int)$_POST['ps_image_height'];

			
			$ps['img_perpage'] = (int)$_POST['ps_img_perpage'];
			$ps['img_perrow'] = (int)$_POST['ps_img_perrow'];
			
			if(isset($_POST['ps_img_rel'])){
				$ps['img_rel'] = attribute_escape($_POST['ps_img_rel']);
			}
			if(isset($_POST['ps_add_text'])){
				$ps['add_text'] = attribute_escape($_POST['ps_add_text']);
			}
			if(isset($_POST['ps_upload_form_caption'])){
				$ps['upload_form_caption'] = attribute_escape($_POST['ps_upload_form_caption']);
			}
			if(isset($_POST['ps_img_class'])){
				$ps['img_class'] = attribute_escape($_POST['ps_img_class']);
			}
			if(isset($_POST['ps_show_imgcaption'])){
				$ps['show_imgcaption'] = (int)$_POST['ps_show_imgcaption'];
			} else {
				$ps['show_imgcaption'] = 0;
			}
			
			$ps['nofollow_caption'] = isset($_POST['ps_nofollow_caption']) ? 1 : 0;
			
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
			$ps['custom_formid'] = (int)$_POST['ps_custom_formid'];
			$ps['use_customfields'] = isset($_POST['ps_use_customfields']) ? 1 : 0;
			$ps['use_thickbox'] = isset($_POST['ps_use_thickbox']) ? 1 : 0;
			$ps['caption_targetnew'] = isset($_POST['ps_caption_targetnew']) ? 1 : 0;
			$ps['img_targetnew'] = isset($_POST['ps_img_targetnew']) ? 1 : 0;
			
			$ps['imglinks_postpages_only'] = isset($_POST['ps_imglinks_postpages_only']) ? 1 : 0;
						
			if(isset($_POST['ps_use_alt_ajaxscript']) ){
				if(!file_exists(WP_PLUGIN_DIR.'/'.attribute_escape($_POST['ps_alt_ajaxscript']))){
					if($this->message){
						$this->message .= "<br/>";
					}
					$this->message .= "<span style='color:red'>WARNING - Alternate Ajax Upload File does not exist:<br/>".WP_PLUGIN_DIR.'/'.attribute_escape($_POST['ps_alt_ajaxscript'])."
					</span>";
				}
			}
			$ps['use_alt_ajaxscript'] = 
				isset($_POST['ps_use_alt_ajaxscript']) ? 1 : 0;	
						
			$ps['alt_ajaxscript'] = 
				attribute_escape($_POST['ps_alt_ajaxscript']);
				
			$ps['alt_javascript'] = 
				$this->cleanSlashes($_POST['ps_alt_javascript']);
			
			
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
			$ps['upload_authmessage'] = attribute_escape(stripslashes(trim($_POST['ps_upload_authmessage'])));
			
			$ps['sort_field'] = (int)$_POST['ps_sort_field'];
			
			$ps['sort_order'] = (int)$_POST['ps_sort_order'];
			

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
	
	function saveGallerySettings()
	{
		global $wpdb;
		//This section saves Gallery specific settings
			$gallery_id = $this->gallery_id;
			$d['gallery_name'] = $_POST['gal_gallery_name'];
			$d['gallery_type'] = (int)$_POST['gal_gallery_type'];
			$d['img_perpage'] = (int)$_POST['gal_img_perpage'];
			$d['img_perrow'] = (int)$_POST['gal_img_perrow'];
			$d['thumb_aspect'] = (int)$_POST['gal_thumb_aspect'];
			$d['thumb_width'] = (int)$_POST['gal_thumb_width'];
			$d['thumb_height'] = (int)$_POST['gal_thumb_height'];
			
			$d['image_aspect'] = (int)$_POST['gal_image_aspect'];
			$d['image_width'] = (int)$_POST['gal_image_width'];
			$d['image_height'] = (int)$_POST['gal_image_height'];
			
			$d['img_rel'] = $_POST['gal_img_rel'];
			$d['add_text'] = attribute_escape($_POST['gal_add_text']);
			$d['upload_form_caption'] = $_POST['gal_upload_form_caption'];
			$d['img_class'] = $_POST['gal_img_class'];
			$d['show_imgcaption'] = (int)$_POST['gal_show_imgcaption'];
			$d['nofollow_caption'] = isset($_POST['gal_nofollow_caption']) ? 1 : 0;
			$d['img_status'] = (int)$_POST['gal_img_status'];
			$d['contrib_role'] = (int)$_POST['gal_contrib_role'];
			
			$d['use_customform'] = isset($_POST['gal_use_customform']) ? 1 : 0;
			
			$d['custom_formid'] = (int)$_POST['gal_custom_formid'];
			$d['use_customfields'] = isset($_POST['gal_use_customfields']) ? 1 : 0;
			$d['layout_id'] = (int)$_POST['gal_layout_id'];
			
			$d['sort_field'] = (int)$_POST['gal_sort_field'];
			$d['sort_order'] = (int)$_POST['gal_sort_order'];
			
			
			if($d['thumb_width']==0) $d['thumb_width'] = $psOptions['thumb_width'];
			if($d['thumb_height']==0) $d['thumb_height'] = $psOptions['thumb_height'];
			
			
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
				$wpdb->update($tablename, $d, $where);
				$this->message .= "Gallery Updated: ".$d['gallery_name'];
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
		
		$layoutsDDL = $this->getLayoutsDDL((int)$galOptions['layout_id'], false);
		
		?>
		<div class=wrap>
		
		<h2>PhotoSmash Galleries</h2>
		
		<?php
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">	
		<?php bwbps_nonce_field('delete-gallery'); ?>
<h3>Gallery Settings</h3>
<?php if($psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>
<table class="form-table">
<tr>
<th style='width: 92px; '>Select gallery:</th><td><?php echo $galleryDDL;?>&nbsp;<input type="submit" name="show_bwbPSSettings" value="<?php _e('Edit', 'bwbPS') ?>" />
<input type="submit" name="deletePhotoSmashGallery" onclick='return bwbpsConfirmDeleteGallery();' value="<?php _e('Delete', 'photosmash') ?>" /> 

<input type="submit" name="massGalleryEdit"  value="<?php _e('Mass Edit', 'photosmash') ?>" />

</td></tr>
</table>
</form>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<input type="hidden" id="bwbps_gallery_id" name="gal_gallery_id" value="<?php echo $galleryID;?>" />

<div id="slider" class="wrap">
<ul id="tabs">

			<li><a href="#bwbps_galleryoptions">Gallery Options</a></li>
			<li><a href="#bwbps_uploading">Uploading</a></li>
			<li><a href="#bwbps_thumbnails">Thumbs/Images</a></li>
			
			<?php
if($psOptions['use_advanced'] ==1){
?>
			<li><a href="#bwbps_advanced">Advanced</a></li>
<?php } ?>

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
		<br/>Copy/paste this code into Post or Page content <br/>where you want gallery to display...(include the []'s)<?php if($galOptions['post_id']){ echo "<br/>Assiciated with post: ".$galOptions['post_id'];} ?>
		</td>
	</tr>
	
	<?php }?>
	
	<tr>
				<th>Gallery name:</th>
				<td>
					<input type='text' name="gal_gallery_name" value='<?php echo $galOptions['gallery_name'];?>' style="width: 300px;"/>
				</td>
	</tr>
	
	<tr>
				<th>Gallery type:</th>
				<td>
					<select name="gal_gallery_type">
						<option value="0" <?php if($psOptions['gallery_type'] == 0) echo 'selected=selected'; ?>>Photo gallery</option>
						<option value="3" <?php if($galOptions['gallery_type'] == 3) echo 'selected=selected'; ?>>YouTube gallery</option>
						<option value="4" <?php if($galOptions['gallery_type'] == 4) echo 'selected=selected'; ?>>Video - YouTube + Upload</option>
						<option value="5" <?php if($galOptions['gallery_type'] == 5) echo 'selected=selected'; ?>>Video - Uploads only</option>
						<option value="6" <?php if($galOptions['gallery_type'] == 6) echo 'selected=selected'; ?>>Mixed - Images + YouTube</option>
						
					</select>
				</td>
	</tr>
	
	<tr>
				<th>Sort Images by:</th>
				<td>
					<select name="gal_sort_field">
						<option value="0" <?php if(!$galOptions['sort_field']) echo 'selected=selected'; ?>>When uploaded</option>
						<?php /*
						<option value="1" <?php if($galOptions['sort_field'] == 1) echo 'selected=selected'; ?>>Manual sort</option>
						<option value="2" <?php if($galOptions['sort_field'] == 2) echo 'selected=selected'; ?>>Custom field</option>
						*/
						?>
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
				<th>Images per row in gallery:</th>
				<td>
					<input type='text' name="gal_img_perrow" value='<?php echo (int)$galOptions['img_perrow'];?>' style='width: 40px !important;'/>
					 <em>0 places as many images per row as theme's width allows</em>
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
						<input type="radio" name="gal_show_imgcaption" value="0" <?php if($galOptions['show_imgcaption'] == 0) echo 'checked'; ?>>No caption<br/>
						<input type="radio" name="gal_show_imgcaption"  value="1" <?php if($galOptions['show_imgcaption'] == 1) echo 'checked'; ?>>Caption (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="7" <?php if($galOptions['show_imgcaption'] == 7) echo 'checked'; ?>>Caption (link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="2" <?php if($galOptions['show_imgcaption'] == 2) echo 'checked'; ?>>Contributor (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="3" <?php if($galOptions['show_imgcaption'] == 3) echo 'checked'; ?>>Contributor (link to website)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="4" <?php if($galOptions['show_imgcaption'] == 4) echo 'checked'; ?>>Caption [by] Contributor (link to website)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="5" <?php if($galOptions['show_imgcaption'] == 5) echo 'checked'; ?>>Caption [by] Contributor (link to image)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="6" <?php if($galOptions['show_imgcaption'] == 6) echo 'checked'; ?>>Caption [by] Contributor (link to user submitted url)
						<br/><hr/><span style='color: #888;'>Special: these also change thumbnail links (normal is link to image)</span><br/>
						<input type="radio" name="gal_show_imgcaption"  value="8" <?php if($galOptions['show_imgcaption'] == 8) echo 'checked'; ?>>No caption (thumbs link to user submitted url)<br/>
						<input type="radio" name="gal_show_imgcaption"  value="9" <?php if($galOptions['show_imgcaption'] == 9) echo 'checked'; ?>>Caption (thumbs & captions link to user submitted url)<br/>					
						<br/>
						(Website links will be the website in the user's WordPress profile)<br/>
						(When 'user submitted url' is selected, but none exists, default is to user's WordPress profile)<br/>

						<input type="checkbox" name="gal_nofollow_caption" <?php if($galOptions['nofollow_caption'] == 1) echo 'checked'; ?> /> <a href='http://en.wikipedia.org/wiki/Nofollow'>NoFollow</a> on caption/contributor links
				</td>
			</tr>
		
	</table>
</div>
<div id='bwbps_uploading'>
	<table class="form-table">
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
	</table>
</div>
<div id='bwbps_thumbnails'>
	<table class="form-table">
			<tr>
				<th>Thumbnail style:</th>
				<td>
					<input type="radio" name="gal_thumb_aspect" value="0" <?php if(!(int)$galOptions['thumb_aspect']) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="gal_thumb_aspect" value="1" <?php if((int)$galOptions['thumb_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain aspect ratio
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
			
			<tr>
				<th>Image style:</th>
				<td>
					<input type="radio" name="gal_image_aspect" value="0" <?php if(!(int)$galOptions['image_aspect']) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="gal_image_aspect" value="1" <?php if((int)$galOptions['image_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain aspect ratio
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
	</table>
</div>

<?php
if($psOptions['use_advanced'] ==1){
?>
<div id="bwbps_advanced">
		<table class="form-table">
			<tr>
				<th>Display using Layout:</th>
				<td>
					<?php echo $layoutsDDL;?>
				</td>
			</tr>
			<tr>
				<th>Custom form name:</th>
				<td><?php echo $this->getCFDDL($galOptions['custom_formid']); ?> Only used when 'Use Custom Forms' is turned on in PhotoSmash Settings/Advanced</td>
			</tr>

<?php 
/*  Not implemented at gallery level

<?php
if($psOptions['use_customform']){ ?>
			<tr>
				<th>Use Custom Form:</th>
				<td>
					<input type="checkbox" name="gal_use_customform" <?php if($galOptions['use_customform'] == 1) echo 'checked'; ?>> Enable use of Custom Form in this gallery.
				</td>
			</tr>
<?php } ?>
			<tr>
				<th>Use Custom Fields:</th>
				<td>
					<input type="checkbox" name="gal_use_customfields" <?php if($galOptions['use_customfields'] == 1) echo 'checked'; ?>> Enables custom fields in the standard form for this gallery.
				</td>
			</tr>
*/
?>
		</table>
</div>
<?php } ?>

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
			jQuery('#slider').tabs({ fxFade: true, fxSpeed: 'fast' });	
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
		
		$layoutsDDL = $this->getLayoutsDDL((int)$galOptions['layout_id'], false);
		
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
				<th>Images per row in gallery:</th>
				<td>
					<input type='text' name="gal_img_perrow" value='<?php echo (int)$galOptions['img_perrow'];?>' style='width: 40px !important;'/>
					 <em>0 - as many images/row as theme's width allows</em>
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
				<th>Thumbnail style:</th>
				<td>
					<input type="radio" name="gal_thumb_aspect" value="0" <?php if(!(int)$galOptions['thumb_aspect']) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="gal_thumb_aspect" value="1" <?php if((int)$galOptions['thumb_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain aspect ratio
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
			
			<tr>
				<th>Image style:</th>
				<td>
					<input type="radio" name="gal_image_aspect" value="0" <?php if(!(int)$galOptions['image_aspect']) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="gal_image_aspect" value="1" <?php if((int)$galOptions['image_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain aspect ratio
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
			jQuery('#slider').tabs({ fxFade: true, fxSpeed: 'fast' });	
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
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
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
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>		
		<h3>PhotoSmash Default Settings</h3>
		
		<?php if($psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>
	
	<div id="slider" class="wrap">
	<span id="ps_savemsg" style="display: none; color: #fff; background-color: red; padding:3px; position: fixed; top: 0; right: 0;">saving...</span>
	<ul id="tabs">

		<li><a href="#bwbps_galleryoptions">Gallery Defaults</a></li>
		<li><a href="#bwbps_uploading">Uploading</a></li>
		<li><a href="#bwbps_thumbnails">Thumbs/Images</a></li>
		<li><a href="#bwbps_advanced">Advanced</a></li>

	</ul>
	<div id='bwbps_galleryoptions'>
		<table class="form-table">
			<tr>
				<th>Auto-add gallery to posts:</th>
				<td>
					<select name="ps_auto_add">
						<option value="0" <?php if($psOptions['auto_add'] == 0) echo 'selected=selected'; ?>>No auto-add</option>
						<option value="1" <?php if($psOptions['auto_add'] == 1) echo 'selected=selected'; ?>>Add to top</option>
						<option value="2" <?php if($psOptions['auto_add'] == 2) echo 'selected=selected'; ?>>Add to bottom</option>
					</select>
				</td>
			</tr>
			
			<tr>
				<th>Sort Images by:</th>
				<td>
					<select name="ps_sort_field">
						<option value="0" <?php if(!$psOptions['sort_field']) echo 'selected=selected'; ?>>When uploaded</option>
						<?php /*
						<option value="1" <?php if($psOptions['sort_field'] == 1) echo 'selected=selected'; ?>>Manual sort</option>
						<option value="2" <?php if($psOptions['sort_field'] == 2) echo 'selected=selected'; ?>>Custom field</option>
						*/
						?>
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
				<th>Default Images per row in galleries:</th>
				<td>
					<input type='text' name="ps_img_perrow" value='<?php echo (int)$psOptions['img_perrow'];?>' style='width: 40px !important;'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_img_perrow' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					 <em>0 places as many images per row as theme's width allows</em>
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
						<input type="radio" name="ps_show_imgcaption"  value="4" <?php if($psOptions['show_imgcaption'] == 4) echo 'checked'; ?>>Caption [by] Contributor (link to website)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="5" <?php if($psOptions['show_imgcaption'] == 5) echo 'checked'; ?>>Caption [by] Contributor (link to image)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="6" <?php if($psOptions['show_imgcaption'] == 6) echo 'checked'; ?>>Caption [by] Contributor (link to user submitted url)<br/>
						<hr/><span style='color: #888;'>Special: these also change thumbnail links (normal is link to image)</span><br/>
						<input type="radio" name="ps_show_imgcaption"  value="8" <?php if($psOptions['show_imgcaption'] == 8) echo 'checked'; ?>>No caption (thumbs link to user submitted url)<br/>
						<input type="radio" name="ps_show_imgcaption"  value="9" <?php if($psOptions['show_imgcaption'] == 9) echo 'checked'; ?>>Caption (thumbs & captions link to user submitted url)<br/>					
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
		</table>
	</div>
	<div id='bwbps_uploading'>
		<table class="form-table">
			<tr>
				<th>Default Minimum role to upload photos:</th>
				<td>
					<select name="ps_contrib_role">
						<option value="-1" <?php if($psOptions['contrib_role'] == -1) echo 'selected=selected'; ?>>Anybody</option>
						<option value="0" <?php if($psOptions['contrib_role'] == 0) echo 'selected=selected'; ?>>Subscribers</option>
						<option value="1" <?php if($psOptions['contrib_role'] == 1) echo 'selected=selected'; ?>>Contributors/Authors</option>
						<option value="10" <?php if($psOptions['contrib_role'] == 10) echo 'selected=selected'; ?>>Admin</option>
					</select>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_contrib_role' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
					<br/>Authors/Contributors and Admins will not need moderation, even if selected below.
				</td>
			</tr>
			<tr>
				<th>Default moderation status:</th>
				<td>
					<select name="ps_img_status">
						<option value="0" <?php if($psOptions['img_status'] == 0) echo 'selected=selected'; ?>>Moderate</option>
						<option value="1" <?php if($psOptions['img_status'] == 1) echo 'selected=selected'; ?>>Active</option>
					</select>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_img_status' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			<tr>
				<th>New Image email alert schedule:</th>
				<td>
					<select name="ps_image_alert_schedule">
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
				<th>Use floating Upload Form:</th>
				<td>
					<input type="checkbox" id='bwbps_use_thickbox' name="ps_use_thickbox" onclick='bwbpsToggleFormAlwaysVisible();' <?php if($psOptions['use_thickbox'] == 1) echo 'checked'; ?>> Use Thickbox for floating upload form.
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
	<div id="bwbps_thumbnails">
		<table class="form-table">
			<tr>
				<th>Default thumb style:</th>
				<td>
					<input type="radio" name="ps_thumb_aspect" value="0" <?php if((int)$psOptions['thumb_aspect'] == 0) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="ps_thumb_aspect" value="1" <?php if((int)$psOptions['thumb_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain Aspect<br/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_thumb_aspect' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Mass update galleries
				</td>
			</tr>
			<tr>
				<th>Default thumb width (px):</th>
				<td>
					<input type='text' name="ps_thumb_width" value='<?php echo (int)$psOptions['thumb_width'];?>'/>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_thumb_width' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			<tr>
				<th>Default thumb height (px):</th>
				<td>
					<input type='text' name="ps_thumb_height" value='<?php echo (int)$psOptions['thumb_height'];?>'/>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_thumb_height' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a>
				</td>
			</tr>
			
			<tr>
				<th>Default image style:</th>
				<td>
					<input type="radio" name="ps_image_aspect" value="0" <?php if((int)$psOptions['image_aspect'] == 0) echo 'checked'; ?>> Resize &amp; Crop<br/>
					<input type="radio" name="ps_image_aspect" value="1" <?php if((int)$psOptions['image_aspect'] == 1) echo 'checked'; ?>> Resize &amp; Maintain Aspect
					<br/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_image_aspect' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Mass update galleries
				</td>
			</tr>
			<tr>
				<th>Default max image width (px):</th>
				<td>
					<input type='text' name="ps_image_width" value='<?php echo (int)$psOptions['image_width'];?>'/>  <a href='javascript: void(0);' class='psmass_update' id='save_ps_image_width' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> 0 will maintain original width
				</td>
			</tr>
			<tr>
				<th>Default max image height (px):</th>
				<td>
					<input type='text' name="ps_image_height" value='<?php echo (int)$psOptions['image_height'];?>'/> <a href='javascript: void(0);' class='psmass_update' id='save_ps_image_height' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> 0 will maintain original height
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
				<th>Default Form for new Galleries:</th>
				<td>
					<?php 
						echo $this->getCFDDL($psOptions['custom_formid'], "ps_custom_formid");
					?> <a href='javascript: void(0);' class='psmass_update' id='save_ps_custom_formid' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Default upload form.  See custom form below
				</td>
			</tr>
			<tr>
				<th>Show Custom Fields in Default Form:</th>
				<td>
					<input type="checkbox" name="ps_use_customfields" <?php if($psOptions['use_customfields'] == 1) echo 'checked'; ?>> Use custom fields you define. See custom fields below
				</td>
			</tr>
			<tr>
				<th>Default Layout:</th>
				<td>
					<?php echo $this->getLayoutsDDL($psOptions['layout_id'], true);
					?> <a href='javascript: void(0);' class='psmass_update' id='save_ps_layout_id' title='Update ALL GALLERIES with this value.'><img src='<?php echo BWBPSPLUGINURL;?>images/disk_multiple.png' alt='Mass update' /></a> Default layout for displaying images
					
					<?php if($psOptions['use_advanced']){
						echo " - <a href='admin.php?page=editPSHTMLLayouts' title='Layout Editor'>Layout Editor</a>";
						}
					?>
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
					
					<p><b>IMPORTANT NOTE:</b><br/>Your function name should include any parameters that are to be passed.  Use parameter 'data' and 'statusText' for the JSON object and the upload status that are being returned by the server in the Ajax call.  These variables are passed into the function that will call your function. Your function can use them as follows...</p><p><b>Example:</b>  myFunction(data, statusText)</p><b>Leave Blank to not use this feature!</b>
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
					<input type='text' style='width: 300px;' name="ps_upload_authmessage" value='<?php echo trim($psOptions['upload_authmessage']);?>'/> Message to display when user does not have enough Authorization to upload images to a gallery.  Use normal HTML for this message.<br/>To display a link the login page, use:  [login]<br/>Leave blank to not display any message. 
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
</div>
<?php 
	
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
		$psOptions = $this->psOptions;
		
		if(isset($_POST['showModerationImages'])){
			//Getting images needing moderation
			$galleryID ='moderation';
			$ddlID = 0;
			$caption = " > Images for Moderation";
		} else {
			if(isset($_POST['showAllImages'])){
				//Getting all images
				$galleryID ='all';
				$ddlID = 0;
				$caption = " > All Images";
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
			}
		}
		
		if(!$galleryID){
			$galleryID ='moderation';
		}
		
		$result = $this->getGalleryImages($galleryID);
		$galleryDDL = $this->getGalleryDDL($ddlID, "Select");
		
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
		<h3>Photo Manager<?php echo $caption;?></h3>
		<?php if($psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>
		<br/>
		<?php 
			echo $galleryDDL;
		?>&nbsp;<input type="submit" name="show_bwbPSSettings" value="<?php _e('Edit', 'bwbPS') ?>" />
			&nbsp;<input type="submit" name="showModerationImages" value="<?php _e('In Moderation', 'bwbPS') ?>" />
			&nbsp;<input type="submit" name="showAllImages" value="<?php _e('All Images', 'bwbPS') ?>" />		
		<?php
			
			if($result){
				$nonce = wp_create_nonce( 'bwbps_moderate_images' );
				echo '
				<input type="hidden" id="_moderate_nonce" name="_moderate_nonce" value="'.$nonce.'" />
				';
			}
			echo $result;
	?>
	</form>

 	</div>
<?php
	}

	
	/**
	 * getGalleryImages()
	 * 
	 * @access public 
	 * @param integer $gallery_id
	 * @return a table of the images
	 */
	function getGalleryImages($gallery_id)
	{
		$images = $this->getImagesQuery($gallery_id);
		$admin = current_user_can('level_10');
		
		$imgcnt =0;
		if($images){
		//Get image count
		if(is_array($images)){
			$imgcnt=count($images);
		} 
		
		foreach($images as $image){
			$modMenu = "";
			switch ($image->status) {
				case -1 :
					$modClass = "style='border: 2px red solid;'";
					if($admin){
						$modMenu = "<a href='javascript: void(0);' onclick='bwbpsModerateImage(\"approve\", ".$image->image_id.");' class='ps-modbutton'>approve</a>";
					}
					break;
				case -2 :
					break;
				default :
					$modClass = '';
					break;
			}
			
			$galupdate = $this->getGalleryDDL($image->gallery_id, "skipnew"
				, "g".$image->image_id, "bwbps_set_imggal", 15, false);
			
			$galupdate .= "<a href='javascript: void(0);' onclick='bwbpsSetNewGallery(".$image->image_id."); return false;' id='save_ps_show_imgcaption' title='Set new gallery.'><img src='" . BWBPSPLUGINURL. "images/disk_multiple.png' alt='Set gallery' /></a>";
			
			if($image->post_id){
				$galupdate .= "<br/>
				<span>Edit related post: <a href='post.php?action=edit&post="
				.$image->post_id."' title='Edit related post.'>". $image->post_id . "</a>";
			}
			
			$modMenu = "<br/><span class='ps-modmenu' id='psmod_".$image->image_id."'>".$modMenu."</span> | <a href='javascript: void(0);' onclick='bwbpsModerateImage(\"savecaption\", ".$image->image_id.");'>save</a> | <a href='javascript: void(0);' onclick='bwbpsModerateImage(\"bury\", ".$image->image_id.");' class='ps-modbutton'>delete</a>";
			
			//Image HTML
			
			$psTable .= "<td class='psgal_".$image->gallery_id." $modClass' id='psimg_".$image->image_id."'><a target='_blank' href='".PSIMAGESURL.$image->file_name."' rel='"
				.$g['img_rel']."' title='".str_replace("'","",$image->image_caption)
				."'><span id='psimage_".$image->image_id."'><img src='".PSTHUMBSURL
				.$image->image_name."' ".$modClass." /></span></a></td>";
			
			
			// IMAGE DETAILS
			
			$psCaption = htmlentities($image->image_caption, ENT_QUOTES);
			
			if($i==0){$border = " style='border-right: 1px solid #999;'";} else {$border = '';}
			
			$psTable .= "<td $border><span>Select Gallery:</span><br/>" .$galupdate. "<br/>				
				<span>Caption:<br/><input type='text' id='imgcaption_" . $image->image_id."' name='imgcaption"
					. $image->image_id."' value='$psCaption' style='width: 165px !important;' /><br/>URL:<br/><input type='text' id='imgurl_" . $image->image_id."' name='imgurl"
					. $image->image_id."' value='".$image->url."' style='width: 165px !important;' /></span>";

			$psTable .= $modMenu;
			
			
			
			if($image->file_url){
				$fileURLData = "<br/>File data: " . $image->file_url;
			} else { $fileURLData = ""; }
			
			$psTable .= "<br/><b>Details: </b>(image id: ".$image->image_id.")<br/>Gallery: <a href='admin.php?page=managePhotoSmashImages&amp;psget_gallery_id="
			.$image->gallery_id."'>id(".$image->gallery_id.") ".$image->gallery_name."</a><br/>Uploaded by: ".$this->calcUserName($image->user_login, $image->user_nicename, $image->display_name)."<br/>Date: ".$image->created_date. $fileURLData . "</td>";
			if($i == 1){
				$psTable .= "</tr><tr>";
				$i = 0;
			} else {$i++;}
		}
		
		return '<div>&nbsp;<span id="ps_savemsg" style="display: none; color: #fff; background-color: red; padding:3px;">saving...</span> <span>('.$imgcnt.') Images</span><br/><table class="widefat fixed" cellspacing="0">'.$psTable.'</table></div>';
	} else {
		return "<h3>No images in gallery yet...go to post page to load images.</h3>";
	}
	
	}
	
	
	//Get the Gallery Images
	function getImagesQuery($gallery_id){
		global $wpdb;
		global $user_ID;
		if(current_user_can('level_10')){
			switch ($gallery_id){
				case "all" :
					$sql = $wpdb->prepare('SELECT '.PSIMAGESTABLE.'.*, '
						.$wpdb->users.'.user_nicename,'
						. $wpdb->users.'.display_name, '.$wpdb->users
						. '.user_login, '.PSGALLERIESTABLE.'.gallery_name '
						. 'FROM '.PSIMAGESTABLE 
						. ' LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
						. '.ID = '. PSIMAGESTABLE. '.user_id '
						. ' LEFT OUTER JOIN '.PSGALLERIESTABLE 
						. ' ON '.PSGALLERIESTABLE.'.gallery_id = '
						. PSIMAGESTABLE.'.gallery_id ORDER BY '
						. PSIMAGESTABLE. '.file_name');
					break;
					
				case "moderation" :
					$sql = $wpdb->prepare('SELECT '.PSIMAGESTABLE
					. '.*, '.$wpdb->users.'.user_nicename,'
					. $wpdb->users.'.display_name, '.$wpdb->users
					. '.user_login, '.PSGALLERIESTABLE.'.gallery_name '
					. ' FROM '.PSIMAGESTABLE 
					. ' LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					. '.ID = '. PSIMAGESTABLE. '.user_id '
					. ' LEFT OUTER JOIN '.PSGALLERIESTABLE 
					. ' ON '.PSGALLERIESTABLE.'.gallery_id = '
					. PSIMAGESTABLE.'.gallery_id WHERE '. PSIMAGESTABLE
					. '.status = -1 ORDER BY '. PSIMAGESTABLE. '.seq, '
					. PSIMAGESTABLE. '.file_name');
					break;
					
				default:
					$gallery_id = (int)$gallery_id;
					$sql = $wpdb->prepare('SELECT '.PSIMAGESTABLE.'.*, '
					. $wpdb->users.'.user_nicename,'
					. $wpdb->users.'.display_name, '.$wpdb->users
					. '.user_login, '.PSGALLERIESTABLE.'.gallery_name '
					. ' FROM '.PSIMAGESTABLE 
					. ' LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					. '.ID = '. PSIMAGESTABLE. '.user_id '
					. ' LEFT OUTER JOIN '.PSGALLERIESTABLE 
					. ' ON '.PSGALLERIESTABLE.'.gallery_id = '
					. PSIMAGESTABLE.'.gallery_id WHERE '. PSIMAGESTABLE
					. '.gallery_id = %d ORDER BY '. PSIMAGESTABLE. '.seq, '
					. PSIMAGESTABLE. '.file_name', $gallery_id);			
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
					. '.seq, '. PSIMAGESTABLE. '.file_name', $gallery_id));
		}
		return $images;
	}
	
	
	function getGalleriesQuery(){
		
		global $wpdb;
		
		$sql = "SELECT ".PSGALLERIESTABLE.".gallery_id, ".PSGALLERIESTABLE.".gallery_name, "
			.$wpdb->prefix."posts.post_title, COUNT("
			.PSIMAGESTABLE.".image_id) as img_cnt FROM "
			.PSGALLERIESTABLE." LEFT OUTER JOIN "
			.PSIMAGESTABLE." ON ".PSIMAGESTABLE.".gallery_id = "
			.PSGALLERIESTABLE.".gallery_id LEFT OUTER JOIN "
			.$wpdb->prefix."posts ON ".PSGALLERIESTABLE.".post_id = "
			.$wpdb->prefix."posts.ID WHERE ".PSGALLERIESTABLE.".status = 1 GROUP BY "
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
			
			$ret .= "<option value='".$row->gallery_id."' ".$sel.">ID: ".$row->gallery_id."-".$title."</option>";
		}
		}
		$ret ="<select id='" . $idPfx . "bwbpsGalleryDDL' name='$ddlName'>".$ret."</select>";		
		
		return $ret;
	}
	
	
	//Get Layouts DDL
	function getLayoutsDDL($selected_layout,$psDefault){
		
 		global $wpdb;
 		
 		if($psDefault && !$selected_layout){ $selected_layout = -1; }
 		
 		if($selected_layout == -1){$sel = "selected='selected'";}else{$sel = "";}
		$ret .= "<option value='-1' ".$sel.">Standard display</option>";
		
		if(!$psDefault){
			if($selected_layout == 0){$sel = "selected='selected'";}else{$sel = "";}
			$ret .= "<option value='0' ".$sel.">&lt;Default layout&gt;</option>";
		}
		
		$query = $wpdb->get_results("SELECT layout_id, layout_name FROM "
			.PSLAYOUTSTABLE." ORDER BY layout_name;");
		
		if($query){
			foreach($query as $row){
		
				if($selected_layout == $row->layout_id){$sel = "selected='selected'";}else{$sel = "";}
				$ret .= "<option value='".$row->layout_id."' ".$sel.">".$row->layout_name."</option>";
		
			}
		}
		if(!$psDefault){
			$ret ="<select name='gal_layout_id'>".$ret."</select>";
		} else {
			$ret ="<select name='ps_layout_id'>".$ret."</select>";
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

	
}  //closes out the class

if ( !function_exists('wp_nonce_field') ) {
        function bwbps_nonce_field($action = -1) { return; }
        $bwbps_plugin_nonce = -1;
} else {
        function bwbps_nonce_field($action = -1) { return wp_nonce_field($action); }
}


?>