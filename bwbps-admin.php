<?php
//Admin Pages for BWB-PhotoSmash plugin


class BWBPS_Admin{
	
	var $psOptions;
	var $message = false;
	var $msgclass = "updated fade";
	
	//Constructor
	function BWBPS_Admin(){
		//Get PS Defaults
		$this->psOptions = $this->getPSOptions();
		
		//Save PS General Settings
		if(isset($_POST['update_bwbPSDefaults'])){
			check_admin_referer( 'update-gallery');
			$this->saveGeneralSettings($this->psOptions);
			//Refresh options
			$this->psOptions = $this->getPSOptions();
		}
		
		//Save Gallery Settings
		if(isset($_POST['save_bwbPSGallery'])){
			check_admin_referer( 'update-gallery');
			$this->saveGallerySettings($this->psOptions);
		}
		
		//Delete Gallery
		if(isset($_POST['deletePhotoSmashGallery'])){
			check_admin_referer( 'update-gallery');
			$this->deleteGallery($this->options);
		}
	
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
		return $psAdminOptions;
	}
	
	
	function getPSOptionsDefaults()
	{
		//get some defaults if nothing is in the database
		return array(
				'auto_add' => 0,
				'thumb_width' => 110,
				'thumb_height' => 110,
				'img_rel' => 'lightbox',
				'add_text' => 'Add Photo',
				'gallery_caption' => 'PhotoSmash Gallery',
				'upload_form_caption' => 'Select an image to upload:',
				'img_class' => 'ps_images',
				'show_caption' => 1,
				'img_alerts' => 3600,
				'show_imgcaption' => 1,
				'contrib_role' => 10,
				'img_status' => 0,
				'last_alert' => 0
		);
	}
	
	function deleteGallery($options)
	{
		global $wpdb;
		
		//This section deletes a Gallery
		$gallery_id = (int)$_POST['gal_gallery_id'];
		
		if($gallery_id){
			$ret = $wpdb->query("DELETE FROM ".PSGALLERIESTABLE." WHERE gallery_id="
				.$gallery_id." LIMIT 1" );
		}
		if($ret){$this->message = "Gallery deleted...";}
		
		return;
	}

	
	//Checks to see if we're saving options
	function saveGeneralSettings($ps){
		global $wpdb;
		
		//This section Saves the overall PhotoSmash defaults
			if(isset($_POST['ps_auto_add'])){
				$ps['auto_add'] = (int)$_POST['ps_auto_add'];
			}
			if(isset($_POST['ps_thumb_width'])){
				$ps['thumb_width'] = (int)$_POST['ps_thumb_width'];
			}
			if(isset($_POST['ps_thumb_height'])){
				$ps['thumb_height'] = (int)$_POST['ps_thumb_height'];
			}
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
			//Update the PS Defaults
			update_option('BWBPhotosmashAdminOptions', $ps);
			
			$this->message = "PhotoSmash defaults updated...";
			return true;
	}
	
	function saveGallerySettings()
	{
		global $wpdb;
		//This section saves Gallery specific settings
			$gallery_id = (int)$_POST['gal_gallery_id'];
			$d['gallery_name'] = $_POST['gal_gallery_name'];
			$d['thumb_width'] = (int)$_POST['gal_thumb_width'];
			$d['thumb_height'] = (int)$_POST['gal_thumb_height'];
			$d['img_rel'] = $_POST['gal_img_rel'];
			$d['upload_form_caption'] = $_POST['gal_upload_form_caption'];
			$d['img_class'] = $_POST['gal_img_class'];
			$d['show_imgcaption'] = (int)$_POST['gal_show_imgcaption'];
			$d['img_status'] = (int)$_POST['gal_img_status'];
			$d['contrib_role'] = (int)$_POST['gal_contrib_role'];
			
			
			if($d['thumb_width']==0) $d['thumb_width'] = $psOptions['thumb_width'];
			if($d['thumb_height']==0) $d['thumb_height'] = $psOptions['thumb_height'];
			
			
			$tablename = $wpdb->prefix.'bwbps_galleries';
			
			if($gallery_id == 0){
				//Create new Gallery Record
				$d['created_date'] = date('Y-m-d H:i:s');
				$d['status'] = 1;
				$wpdb->insert($tablename,$d);
				$this->message = "New Gallery Created: ".$d['gallery_name'];
			}else{
				$where['gallery_id'] = $gallery_id;
				$wpdb->update($tablename, $d, $where);
				$this->message .= "Gallery Updated: ".$d['gallery_name'];
			}		
	}
	
	
	//Disply the General Settings Page
	function printGallerySettings(){
		global $wpdb;
		$psOptions = $this->psOptions;
		
		if(isset($_POST['gal_gallery_id'])){
			$galleryID = (int)$_POST['gal_gallery_id'];
		} else { $galleryID = 0; }
		
		$galleryDDL = $this->getGalleryDDL($galleryID);
		if($galleryID){
			$galOptions = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'bwbps_galleries WHERE gallery_id = %d',$galleryID), ARRAY_A);
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
		<h3>Gallery Settings</h3>
<table class="form-table"><tr>
<th><input type="submit" name="save_bwbPSGallery" class="button-primary" value="<?php _e('Save Gallery', 'bwbPS') ?>" /></th><td><a href='admin.php?page=bwb-photosmash.php'>PhotoSmash General Settings</a>
<?php if($galleryID){
	echo "&nbsp;|&nbsp;<a href='admin.php?page=managePhotoSmashImages&psget_gallery_id=".(int)$galleryID."'>Manage photos</a>";
}
?>
</td>
</tr>
<tr>
<th>Select Gallery to edit:</th><td><?php echo $galleryDDL;?>&nbsp;<input type="submit" name="show_bwbPSSettings" value="<?php _e('Edit', 'bwbPS') ?>" />
<input type="submit" name="deletePhotoSmashGallery" onclick='return bwbpsConfirmDeleteGallery();' value="<?php _e('Delete', 'suppleLang') ?>" />
</td></tr>

<?php if($galleryID){
?>
<tr><th><b>Display code:</b></th><td><span style="color: red; font-weight: bold;">[photosmash=<?php echo $galleryID;?>]</span>
<br/>Copy/paste this code into Post or Page content <br/>where you want gallery to display...(include the []'s)</td></tr>
<?php }?>
	<tr>
				<th>Gallery name:</th>
				<td>
					<input type='text' name="gal_gallery_name" value='<?php echo $galOptions['gallery_name'];?>'/>
				</td>
	</tr>

	<tr>
				<th>Thumbnail width (px):</th>
				<td>
					<input type='text' name="gal_thumb_width" value='<?php echo $galOptions['thumb_width'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Thumbnail height (px):</th>
				<td>
					<input type='text' name="gal_thumb_height" value='<?php echo $galOptions['thumb_height'];?>'/>
				</td>
			</tr>
			<tr>
				<th>"Rel" parameter for image links:</th>
				<td>
					<input type='text' name="gal_img_rel" value='<?php echo $galOptions['img_rel'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Upload form caption:</th>
				<td>
					<input type='text' name="gal_upload_form_caption" value='<?php echo $galOptions['upload_form_caption'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Default image css class:</th>
				<td>
					<input type='text' name="gal_img_class" value='<?php echo $galOptions['img_class']; ?>'/>
				</td>
			</tr>
			<tr>
				<th>Show image caption:</th>
				<td>
					<select name="gal_show_imgcaption">
						<option value="0" <?php if($galOptions['show_imgcaption'] == 0) echo 'selected=selected'; ?>>No</option>
						<option value="1" <?php if($galOptions['show_imgcaption'] == 1) echo 'selected=selected'; ?>>Yes</option>
					</select>
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
				<th>Default image status:</th>
				<td>
					<select name="gal_img_status">
						<option value="0" <?php if($galOptions['img_status'] == 0) echo 'selected=selected'; ?>>Moderate</option>
						<option value="1" <?php if($galOptions['img_status'] == 1) echo 'selected=selected'; ?>>Active</option>
					</select>
				</td>
			</tr>

</table>
<p class="submit">
	<input type="submit" name="save_bwbPSGallery" class="button-primary" value="<?php _e('Save Gallery', 'bwbPS') ?>" />
</p>
</form>
<?php
	}
	

	//Disply the General Settings Page
	function printGeneralSettings(){
		global $wpdb;
		
		$psOptions = $this->psOptions;
		
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
		<h3>PhotoSmash Default Settings</h3>
		<table class="form-table">
			<tr><th><input type="submit" name="update_bwbPSDefaults" class="button-primary" value="<?php _e('Update Defaults', 'bwbPS') ?>" /></th><td><a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a></td></tr>
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
				<th>Default thumb width (px):</th>
				<td>
					<input type='text' name="ps_thumb_width" value='<?php echo $psOptions['thumb_width'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Default thumb height (px):</th>
				<td>
					<input type='text' name="ps_thumb_height" value='<?php echo $psOptions['thumb_height'];?>'/>
				</td>
			</tr>
			<tr>
				<th>"Rel" parameter for image links:</th>
				<td>
					<input type='text' name="ps_img_rel" value='<?php echo $psOptions['img_rel'];?>'/>
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
					<input type='text' name="ps_upload_form_caption" value='<?php echo $psOptions['upload_form_caption'];?>'/>
				</td>
			</tr>
			<tr>
				<th>Default image css class:</th>
				<td>
					<input type='text' name="ps_img_class" value='<?php echo $psOptions['img_class']; ?>'/>
				</td>
			</tr>
			<tr>
				<th>Show image caption:</th>
				<td>
					<select name="ps_show_imgcaption">
						<option value="0" <?php if($psOptions['show_imgcaption'] == 0) echo 'selected=selected'; ?>>No</option>
						<option value="1" <?php if($psOptions['show_imgcaption'] == 1) echo 'selected=selected'; ?>>Yes</option>
					</select>
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
					</select>
					<br/>Authors/Contributors and Admins will not need moderation, even if selected below.
				</td>
			</tr>
			<tr>
				<th>Default moderation status:</th>
				<td>
					<select name="ps_img_status">
						<option value="0" <?php if($psOptions['img_status'] == 0) echo 'selected=selected'; ?>>Moderate</option>
						<option value="1" <?php if($psOptions['img_status'] == 1) echo 'selected=selected'; ?>>Active</option>
					</select>
				</td>
			</tr>
		</table>
	<p class="submit">
		<input type="submit" name="update_bwbPSDefaults" class="button-primary" value="<?php _e('Update Defaults', 'bwbPS') ?>" />
	</p>
</form>

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
				if(isset($_POST['gal_gallery_id'])){
					$galleryID = (int)$_POST['gal_gallery_id'];
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
		$images = $this->getGalleryQuery($gallery_id);
		$admin = current_user_can('level_10');
		if($images){
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
			
			$modMenu = "<br/><span class='ps-modmenu' id='psmod_".$image->image_id."'>".$modMenu."</span> | <a href='javascript: void(0);' onclick='bwbpsModerateImage(\"savecaption\", ".$image->image_id.");'>save</a> | <a href='javascript: void(0);' onclick='bwbpsModerateImage(\"bury\", ".$image->image_id.");' class='ps-modbutton'>delete</a>";
			
			$psTable .= "<td class='psgal_".$image->gallery_id." $modClass' id='psimg_".$image->image_id."'><a target='_blank' href='".PSIMAGESURL.$image->file_name."' rel='"
				.$g['img_rel']."' title='".str_replace("'","",$image->image_caption)
				."'><span id='psimage_".$image->image_id."'><img src='".PSTHUMBSURL
				.$image->image_name."' ".$modClass." /></span></a></td>";
				
			$psCaption = htmlentities($image->image_caption, ENT_QUOTES);
			if($i==0){$border = " style='border-right: 1px solid #999;'";} else {$border = '';}
			$psTable .= "<td $border><span><input type='text' id='imgcaption_" . $image->image_id."' name='imgcaption"
					. $image->image_id."' value='$psCaption' style='width: 165px !important;' /></span>";

			$psTable .= $modMenu;
			
			$psTable .= "<br/><b>Details:</b><br/>Uploaded by: ".$image->user_nicename."<br/>Date: ".$image->created_date."</td>";
			if($i == 1){
				$psTable .= "</tr><tr>";
				$i = 0;
			} else {$i++;}
		}
		
		return '<div>&nbsp;<span id="ps_savemsg" style="display: none; color: #fff; background-color: red; padding:3px;">saving...</span><table class="widefat fixed" cellspacing="0">'.$psTable.'</table></div>';
	} else {
		return "<h3>No images in gallery yet...go to post page to load images.</h3>";
	}
	
	}
	
	
	//Get the Gallery Images
	function getGalleryQuery($gallery_id){
		global $wpdb;
		global $user_ID;
		if(current_user_can('level_10')){
			switch ($gallery_id){
				case "all" :
					$sql = $wpdb->prepare('SELECT *, '.$wpdb->users.'.user_nicename FROM '. $wpdb->prefix 
					. 'bwbps_images LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					.'.ID = '. $wpdb->prefix. 'bwbps_images.user_id ORDER BY file_name');
					break;
				case "moderation" :
					$sql = $wpdb->prepare('SELECT *, '.$wpdb->users.'.user_nicename FROM '. $wpdb->prefix 
					. 'bwbps_images LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					.'.ID = '. $wpdb->prefix. 'bwbps_images.user_id WHERE status = -1 ORDER BY seq, file_name');
					break;
				default:
					$gallery_id = (int)$gallery_id;
					$sql = $wpdb->prepare('SELECT *, '.$wpdb->users.'.user_nicename FROM '. $wpdb->prefix 
					. 'bwbps_images LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					.'.ID = '. $wpdb->prefix. 'bwbps_images.user_id WHERE gallery_id = %d ORDER BY seq, file_name', $gallery_id);			
			}
			
			$images = $wpdb->get_results($sql);
		} else {
				$uid = $user_ID ? $user_ID : -1;
				$images = $wpdb->get_results($wpdb->prepare('SELECT *, '.$wpdb->users.'.user_nicename FROM '. $wpdb->prefix 
					. 'bwbps_images LEFT OUTER JOIN '.$wpdb->users.' ON '.$wpdb->users
					.'.ID = '. $wpdb->prefix. 'bwbps_images.user_id WHERE gallery_id = %d AND (status > 0 OR user_id = '.$uid.')ORDER BY seq, file_name', $gallery_id));
		}
		return $images;
	}
	
	//Returns markup for a DropDown List of existing Galleries
	function getGalleryDDL($selectedGallery = 0, $newtag= "New")
 	{
 		global $wpdb;
 		 
		$ret = "<option value='0'>&lt;$newtag&gt;</value>";
		
		$query = $wpdb->get_results("SELECT ".PSGALLERIESTABLE.".gallery_id, ".PSGALLERIESTABLE.".gallery_name, ".$wpdb->prefix."posts.post_title FROM ".PSGALLERIESTABLE." LEFT OUTER JOIN ".$wpdb->prefix."posts ON ".PSGALLERIESTABLE.".post_id = ".$wpdb->prefix."posts.ID WHERE ".PSGALLERIESTABLE.".status = 1 ORDER BY ".PSGALLERIESTABLE.".gallery_id");
		if(is_array($query)){
		foreach($query as $row){
			if($selectedGallery == $row->gallery_id){$sel = "selected='selected'";}else{$sel = "";}
			
			if(trim($row->gallery_name) <> ""){$title = $row->gallery_name;} else {
				$title = $row->post_title;
			}
			
			$ret .= "<option value='".$row->gallery_id."' ".$sel.">ID: ".$row->gallery_id."-".$title."</option>";
		}
		}
		$ret ="<select id='bwbpsGalleryDDL' name='gal_gallery_id'>".$ret."</select>";
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