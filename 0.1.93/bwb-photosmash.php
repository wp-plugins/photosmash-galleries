<?php
/*
Plugin Name: PhotoSmash
Plugin URI: http://www.whypad.com/posts/photosmash-galleries-wordpress-plugin-released/507/
Description: PhotoSmash - user contributable photo galleries for WordPress pages and posts.  Auto-add galleries to posts or specify with simple tags.  Utilizes class.upload.php by Colin Verot at http://www.verot.net/php_class_upload.htm, licensed GPL.  PhotoSmash is licensed under the GPL.
Version: 0.1.93
Author: Byron Bennett
Author URI: http://www.whypad.com/
*/

// SafeMode code Borrow from NextGen gallery
// get value for safe mode
if ( (gettype( ini_get('safe_mode') ) == 'string') ) {
	// if sever did in in a other way
	if ( ini_get('safe_mode') == 'off' ) define('SAFEMODE', FALSE);
	else define( 'SAFEMODE', ini_get('safe_mode') );
} else{
define( 'SAFEMODE', ini_get('safe_mode') );
}


// required for Windows & XAMPP
define('WINABSPATH', str_replace("\\", "/", ABSPATH) );

define("PSGALLERIESTABLE", $wpdb->prefix."bwbps_galleries");

$bwbpsuploaddir = wp_upload_dir();
define('PSUPLOADPATH', $bwbpsuploaddir['basedir']);
define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define('PSIMAGESURL',$bwbpsuploaddir['baseurl']."/bwbps/");
define('PSTHUMBSURL',PSIMAGESURL."thumbs/");


class BWB_PhotoSmash{
	var $adminOptionsName = "BWBPhotosmashAdminOptions";
	
	var $uploadFormCount = 0;
	var $moderateNonceCount = 0;
	var $loadedGalleries;
	
	var $psAdmin;
	
	var $psOptions;
	
	//Constructor
	function BWB_PhotoSmash(){
	
	}
	
	//Called when plugin is activated
	function init(){
		require_once('bwbps-init.php');
		$bwbpsinit = new BWBPS_Init();					
	}
	
	//Returns an array of default options
	function getPSDefaultOptions()
	{
		$psOptions = get_option($this->adminOptionsName);
		if(!empty($psOptions))
		{
			//Options were found..add them to our return variable array
			foreach ( $psOptions as $key => $option ){
				$psAdminOptions[$key] = $option;
			}
		}else{
			$psAdminOptions = array(
				'auto_add' => 0,
				'thumb_width' => 125,
				'thumb_height' => 125,
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
			update_option($this->adminOptionsName, $psAdminOptions);
		}
		
		return $psAdminOptions;
	}
	
	
		
	/**
	 * Adds the PhotoSmash menu items	to Admin
	 * 
	 */
	function photoSmashOptionsPage()
	{
		global $bwbPS;
		if (!isset($bwbPS)) {
			return;
		}
		if (function_exists('add_menu_page')) {
			
			add_menu_page('PhotoSmash', 'PhotoSmash', 9, basename(__FILE__), array(&$bwbPS, 'loadAdminPage'));
			
			add_submenu_page(basename(__FILE__), __('PhotoSmash Settings'), __('PhotoSmash Settings'), 9,  basename(__FILE__), array(&$bwbPS, 'loadAdminPage'));
			
			add_submenu_page(basename(__FILE__), __('Gallery Settings'), __('Gallery Settings'), 9,  
			'editPSGallerySettings', array(&$bwbPS, 'loadGallerySettings'));
			
			add_submenu_page(basename(__FILE__), __('Database Viewer'), __('Photo Manager'), 9,  
			'managePhotoSmashImages', array(&$bwbPS, 'loadPhotoManager'));
		}
		
	}
	
	//Prints out the Admin Options Page
	function loadAdminPage(){
		if(!$this->psAdmin){
			require_once("bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printGeneralSettings();
	}
	
	function loadGallerySettings(){
		if(!$this->psAdmin){
			require_once("bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printGallerySettings();
		return true;
	}
	
	function loadPhotoManager(){
		if(!$this->psAdmin){
			require_once("bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printManageImages();
		
		return true;
	}
	
	
	//Send email alerts for new images
	function sendNewImageAlerts()
	{
		global $wpdb;
		
		$sql = "SELECT * FROM ".$wpdb->prefix."bwbps_images WHERE alerted = 0 AND status = -1;";
		$results = $wpdb->get_results($sql);
		if(!$results) return;
		
		$ret = get_bloginfo('name')." has ". $results->num_rows. " new photos awaiting moderation.  Select the appropriate gallery or click image below.<p><a href='".get_bloginfo('url')
		."/wp-admin/admin.php?page=managePhotoSmashImages'>".get_bloginfo('name')." - PhotoSmash Photo Manager</a></p>";
		
		
		$ret .= "<table><tr>";
		$i = 0;
		foreach($results as $row)
		{
			$ret .= "<td><a href='".get_bloginfo('url')
		."/wp-admin/admin.php?page=managePhotoSmashImages&psget_gallery_id=".$row->gallery_id."'><img src='".PSTHUMBSURL.$row->file_name."' /><br/>gallery id: ".$row->gallery_id."</a></td>";
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
		$this->psOptions['last_alert'] = time();
		
		update_option($this->adminOptionsName, $this->psOptions);
		
		$data['alerted'] = 1;
		$where['alerted'] = 0;
		$wpdb->update($wpdb->prefix."bwbps_images", $data, $where);
		
	}
		
		
/* ******************   End of Admin Section ******************************** */
	
/*	****************************************  Gallery Code  *************************************** */


//  Loop through Content and inject Gallery where [photosmash=###] is found
//  Called by add_action filter
function injectPhotoSmash($content=''){
		//Get the Class level psOptions variable..contains Options defaults and the Alert message psuedo-cron
		$this->psOptions = $this->getPSDefaultOptions();
		
		if($this->psOptions['img_alerts'] >0 ){
			$time = time();
			if($time - $this->psOptions['last_alert'] > $this->psOptions['img_alerts'])
			{
				$this->sendNewImageAlerts();
			}
		}
		
		//Regex to see if [photosmash=...] tag is in Content
		if(preg_match_all('/\[photosmash[0-9a-zA-Z=_ \-&amp;]+\]/',$content, $matches, PREG_SET_ORDER))
		{						
			foreach($matches as $matcharr){
				$match = $matcharr[0];
				$ret = str_replace("&amp;","&",$match);
				$ret = str_replace(array("[","]"),array("",""),$ret);
				//If so, get the GalleryID
				$tag = explode('&',$ret);
			
				foreach ($tag as $t){
					$parm = explode('=',$t);
					if(count($parm) > 1){
						$galparms[trim($parm[0])] = trim($parm[1]);
					} else {
						$galparms[trim($parm[0])] = false;
					}
				}
				
				$galparms['gallery_id'] = (int)$galparms['photosmash'];
				
				$g = $this->getGallery($galparms);	//Get the Gallery params
				
				if(!$g['gallery_id']){
					$content = str_replace($match, "Missing PhotoSmash gallery: ".
						$g['photosmash'], $content); //Bad Gallery ID was provided.
				}else{		
					//Check duplicate gallery on page...only allow once
					if(is_array($loadedGalleries) && in_array($g['gallery_id'], $loadedGalleries)){
						$content = str_replace($match, "Duplicate gallery: ".
						$g['photosmash'], $content); //Bad Gallery ID was provided.	
					}else{
						$loadedGalleries[] = $g['gallery_id'];
						$gallery = $this->build_PhotoSmash($g);
						$gallery .= "
							<script type='text/javascript'>
								displayedGalleries += '|".$g['gallery_id']."';
							</script>
						";
						$content = str_replace($match, $gallery, $content);
					}
				}
				unset($galparms);
			
			}
		} else {
			//Determine if Auto-add is set up...add it to top or bottom if so
			$psoptions = $this->psOptions;// Get PhotoSmash defaults
			if($psoptions['auto_add']){
				//Auto-add is set..but first, see if there is a skip tag:  [ps-skip]
				if(strpos($content, "[ps-skip]") === false){}else{return str_replace("[ps-skip]","",$content);}
				$galparms = array("gallery_id" => false);
				$g = $this->getGallery($galparms);	//Get the Gallery params
				$loadedGalleries[] = $g['gallery_id'];
				$gallery = $this->build_PhotoSmash($g);
				$gallery .= "
					<script type='text/javascript'>
						displayedGalleries += '|".$g['gallery_id']."';
					</script>
				";
				if($psoptions['auto_add'] == 1){
					$content = $gallery . $content;
				} else  {
					$content = $content.$gallery;
				}
			}
		}
	
	return $content;
}

// Retrieve the Gallery....Creates new Gallery record linked to Post if gallery ID is false
function getGallery($g){
	global $post;
	global $wpdb;
	$psoptions = $this->psOptions;
	//Define Galleries table name for use in queries
	$table_name = $wpdb->prefix . "bwbps_galleries";
	
	//Get the specified gallery params if valid gallery_id
	if($g['gallery_id']){
		//Get gallery params based on Gallery_ID
		$gquery = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM ".$table_name
				." WHERE gallery_id = %d",$g['gallery_id']),ARRAY_A);

		//If query is false, then Bad Gallery ID provided...alert user
		if(!$gquery){$g['gallery_id'] = false; return $g;}
		
	} else {

		//Get gallery params based on Post_ID
		$gquery = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM ".$table_name
				." WHERE post_id = %d",$post->ID),ARRAY_A);
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
				$g['contrib_role'] = $psoptions['contrib_role'];
				break;
		}
	}
	
	if($gquery){
		//Keep the parameters passed in From the [photosmash] tag in the Content...fill in the holes from the Gallery's default settings
		foreach ( $gquery as $key => $option ){
			if(!$g[$key]){
				$g[$key] = $option;
			}
		}
		
	} else {
	
		//No Gallery found...Need to create a Record for this Gallery
			$data['post_id'] = $post->ID;
			$data['gallery_name'] = $g['gallery_name'] ? $g['gallery_name']  : $post->post_title;
			$data['caption'] =  $g['caption'] ? $g['caption'] : $psoptions['gallery_caption'];
			$data['upload_form_caption'] =  $g['upload_form_caption'] ? $g['upload_form_caption'] : $psoptions['upload_form_caption'];
			$data['contrib_role'] =  $g['contrib_role'] ? $g['contrib_role'] : $psoptions['contrib_role'];
			$data['img_rel'] =  $g['img_rel'] ? $g['img_rel'] : $psoptions['img_rel'];
			$data['img_class'] =  $g['img_class'] ? $g['img_class'] : $psoptions['img_class'];
			if($g['img_status'] === 0 || $g['img_status'] == 1){
				$data['img_status'] = $g['img_status'];
			} else {
				$data['img_status'] = (int)$psoptions['img_status'];
			}
				
			$data['thumb_width'] = (int)$g['thumb_width'] ? (int)$g['thumb_width'] : (int)$psoptions['thumb_width'];
			$data['thumb_height'] =  $g['thumb_height'] ? (int)$g['thumb_height'] : (int)$psoptions['thumb_height'];
			$data['show_caption'] =  $g['show_caption'] ? (int)$g['show_caption'] : (int)$psoptions['show_caption'];
			$data['show_imgcaption'] =  $g['show_imgcaption'] ? (int)$g['show_imgcaption'] : (int)$psoptions['show_imgcaption'];
			$data['created_date'] = date( 'Y-m-d H:i:s');
			$data['status'] = 1;
			
			$wpdb->insert($table_name, $data); //Insert into Galleries Table
			$g = $data;
			$g['gallery_id'] = $wpdb->insert_id;
	}
	
	return $g;
	
}


//  Build the Markup that gets inserted into the Content...$g == the gallery data
function build_PhotoSmash($g)
{
	$blogname = str_replace('"',"",get_bloginfo("blogname"));
	
	$ret = '<div class="photosmash_gallery">';
	$admin = current_user_can('level_10');
	
	if( $g['contrib_role'] == -1 || current_user_can('level_'.$g['contrib_role'])){		
		$ret .= '<span style="margin-left: 10px;"><a href="#TB_inline?height=375&width=530&inlineId=bwbps-formcont" onclick="bwbpsShowPhotoUpload('.$g["gallery_id"].');" title="'.$blogname.' - Gallery Upload" class="thickbox">Add Photos</a></span>';
		
		if($this->moderateNonceCount < 1)
		{
			$nonce = wp_create_nonce( 'bwbps_moderate_images' );
			$ret .= '<input type="hidden" id="_moderate_nonce" name="_moderate_nonce" value="'.$nonce.'" />';
		}


		
		if($this->uploadFormCount < 1){
			$ret .= $this->getPhotoForm($g);
			$this->uploadFormCount++;
		}
	}
	$ret .= "<div class='bwbps_gallery_div'>";
	
	$ret .= "
		<table><tr><td>";
	
	$images = $this->getGalleryImages($g['gallery_id']);
	if($images){
		foreach($images as $image){
			$modMenu = "";
			switch ($image->status) {
				case -1 :
					$modClass = 'ps-moderate';
					if($admin){
						$modMenu = "<br/><span class='ps-modmenu' id='psmod_".$image->image_id."'><input type='button' onclick='bwbpsModerateImage(\"approve\", ".$image->image_id.");' value='approve' class='ps-modbutton'/><input type='button' onclick='bwbpsModerateImage(\"bury\", ".$image->image_id.");' value='bury' class='ps-modbutton'/></span>";
					}
					break;
				case -2 :
					$modClass = 'ps-buried';
					break;
				default :
					$modClass = '';
					break;
			}
			
			$psTable .= "<li class='psgal_".$g['gallery_id']." $modClass' id='psimg_".$image->image_id."'><a href='".PSIMAGESURL.$image->file_name."' rel='"
				.$g['img_rel']."' title='".str_replace("'","",$image->image_caption)
				."'><span id='psimage_".$image->image_id."'><img src='".PSTHUMBSURL
				.$image->image_name."' />";
				
			if($g['show_imgcaption'] && $image->image_caption){
				$scaption = strlen($image->image_caption) > 16 ? substr($image->image_caption,0,15).'&hellip;' : $image->image_caption;
				$psTable .= "<br/><span>".$scaption."</span>";
			}
			$psTable .= "</a></span>$modMenu</li>";
		}
	} else {
		$psTable .= "<li class='psgal_".$g['gallery_id']
			."' style='height: ".($g['thumb_height'] + 15)."px; margin: 15px 0;'><img src='".WP_PLUGIN_URL."/photosmash-galleries/images/"
			."ps_blank.gif' width='1' height='".$g['thumb_height']."' /></li>";
	}
	$ret .= "<ul id='bwbps_gal_".$g['gallery_id']."' class='bwbps_gallery'>".$psTable;
	
	
	$ret .= "</ul>
		</td></tr></table>
	</div></div>\n<div class='bwbps_clear'></div>
	";
	
	return $ret;
}


function getPhotoForm($g){
	$nonce = wp_create_nonce( 'bwb_upload_photos' );
	
	$retForm = '
      <div id="bwbps-formcont" class="thickbox" style="display:none;">
        	'.$g["upload_form_caption"].'
        <form id="bwbps_uploadform" name="bwbps_uploadform" method="post" action="" style="margin:0px;">
        	<input type="hidden" id="_ajax_nonce" name="_ajax_nonce" value="'.$nonce.'" />
        	<input type="hidden" name="MAX_FILE_SIZE" value="'.$g["max_file_size"].'" />
        	<input type="hidden" name="bwbps_imgcaption" id="bwbps_imgcaption" />
        	<input type="hidden" name="gallery_id" id="bwbps_galleryid" value="'.$g["gallery_id"].'" />
        	<table class="ps-form-table">
			<tr><th>Select image to upload:<br/>(Max. allowed size: 400k)';
	
	if(SAFEMODE) {$retForm .= '<br/>Safe Mode is <b>ON</b>.';} 
	$retForm .= '
			</th>
				<td>
					<input type="file" name="bwbps_uploadfile" id="bwbps_uploadfile" />
				</td>
			</tr>
			<tr><th>Caption:</th>
				<td>
					<input type="text" name="bwbps_imgcaptionInput" id="bwbps_imgcaptionInput" />
					<input type="Submit" class="ps-submit" value="Submit" id="bwbps_submitBtn" />
				</td>
			</tr>
	        <tr><th>
	        		<input type="button" class="ps-submit" value="Done" onclick="tb_remove();return false;" />
	        	</th>
	        	<td>
	        		<img id="bwbps_loading" src="'.WP_PLUGIN_URL.'/photosmash-galleries/images/loading.gif" style="display:none;" />	
	        	</td>
	        </tr>
	        <tr><th><span id="bwbps_message"></span></th>
	        <td><span id="bwbps_result"></span></td>
	        </tr>
	        </table>
        </form>
      </div>
      
';
      
	return $retForm;
	}


	
	//Get the Gallery Images
	function getGalleryImages($gallery_id){
		global $wpdb;
		global $user_ID;

		if(current_user_can('level_10')){
			$images = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix 
				. 'bwbps_images WHERE gallery_id = %d ORDER BY file_name', $gallery_id));
		} else {
				$uid = $user_ID ? $user_ID : -1;
				$images = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . $wpdb->prefix 
				. 'bwbps_images WHERE gallery_id = %d AND (status > 0 OR user_id = '.$uid.')ORDER BY file_name', $gallery_id));
		}
		return $images;
	}
	
	
	//Add JS libraris
	function enqueueBWBPS(){		
		wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox');
		
		//enqueue jQuery Forms
		wp_register_script('jquery_forms', WP_PLUGIN_URL . '/photosmash-galleries/js/jquery.form.js', array('jquery'), '2.17');
		wp_enqueue_script('jquery_forms');
		
		//enqueue BWB-PS Javascript
		wp_register_script('bwbps_js', WP_PLUGIN_URL . '/photosmash-galleries/js/bwbps.js', array('jquery'), '1.0');
		wp_enqueue_script('bwbps_js');
	}
	
	//Add CSS
	function injectBWBPS_CSS(){
	?>
	<link rel="stylesheet" href="<?php bloginfo('wpurl'); ?>/<?= WPINC; ?>/js/thickbox/thickbox.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?php echo WP_PLUGIN_URL;?>/photosmash-galleries/bwbps.css" type="text/css" media="screen" />
    <script type="text/javascript">
    var tb_pathToImage = "<?php bloginfo('wpurl'); ?>/<?= WPINC; ?>/js/thickbox/loadingAnimation.gif";
    var tb_closeImage = "<?php bloginfo('wpurl'); ?>/<?= WPINC; ?>/js/thickbox/tb-close.png";
	var displayedGalleries = "";
	var bwbpsAjaxURL = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax.php";
	var bwbpsAjaxUpload = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax_upload.php";
	var bwbpsImagesURL = "<?php echo PSIMAGESURL; ?>";
	var bwbpsThumbsURL = "<?php echo PSTHUMBSURL; ?>";
	</script>
	<?php
	}
	
	//Add Javascript variables to Admin header
	function injectAdminJS()
	{
		?>
		<script type="text/javascript">
		//<![CDATA[
			var bwbpsAjaxURL = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax.php";
			var bwbpsAjaxUpload = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax_upload.php";
			var bwbpsImagesURL = "<?php echo PSIMAGESURL; ?>";
			var bwbpsThumbsURL = "<?php echo PSTHUMBSURL; ?>";
		//]]>
		</script>
		<?php	
	}
	
	
	//PhotoSmash Database Interactions
	function getPostGallery($post_id, $gallery_string)
	{
		//Check if default gallery already exists for post and return HTML for gallery
		//If not exists, create gallery record and return HTML
		$table_name = $wpdb->prefix . "bwbps_galleries";
		$gallery_id = $wpdb->get_var($wpdb->prepare("SELECT gallery_id FROM ".$table_name." WHERE gallery_handle = %s", 'post-'.$post_id));
		
		if(!$gallery_id){
			$data = $this->getGalleryDefaults();
			$galparms = explode("&", $gallery_string);
			foreach($galparms as $parm){
				$parmval = explode("=",$parm);
				$data[$parmval[0]] = $parmval[1];
			}
		}
	}
	
	

} //End of BWB_PhotoSmash Class
/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */

$bwbPS = new BWB_PhotoSmash();


//Call the Function that will Add the Options Page
add_action('admin_menu', array(&$bwbPS, 'photoSmashOptionsPage'));

//Inject Admin Javascript
add_action('admin_print_scripts', array(&$bwbPS, 'injectAdminJS') );


//Call the INIT function whenever the Plugin is activated
add_action('activate_photosmash-galleries/bwb-photosmash.php',
array(&$bwbPS, 'init'));


add_action('init', array(&$bwbPS, 'enqueueBWBPS'), 1);

add_action('wp_head', array(&$bwbPS, 'injectBWBPS_CSS'), 10);

add_filter('the_content',array(&$bwbPS, 'injectPhotoSmash'), 1);
?>