<?php
/*
Plugin Name: PhotoSmash
Plugin URI: http://www.whypad.com/posts/photosmash-galleries-wordpress-plugin-released/507/
Description: PhotoSmash - user contributable photo galleries for WordPress pages and posts.  Auto-add galleries to posts or specify with simple tags.  Utilizes class.upload.php by Colin Verot at http://www.verot.net/php_class_upload.htm, licensed GPL.  PhotoSmash is licensed under the GPL.
Version: 0.2.99
Author: Byron Bennett
Author URI: http://www.whypad.com/
*/
 
/** LICENSE: GPL
 *
 * This work is free software; you can redistribute it and/or 
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 
 * 2 of the License, or any later version.
 *
 * This work is distributed in the hope that it will be useful, 
 * but without any warranty; without even the implied warranty 
 * of merchantability or fitness for a particular purpose. See 
 * Version 2 and version 3 of the GNU General Public License for
 * more details. You should have received a copy of the GNU General 
 * Public License along with this program; if not, write to the 
 * Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, 
 * Boston, MA 02110-1301 USA
*/


//Set Database Table Constants
define("PSGALLERIESTABLE", $wpdb->prefix."bwbps_galleries");
define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSLAYOUTSTABLE", $wpdb->prefix."bwbps_layouts");
define("PSCUSTOMFORMSTABLE", $wpdb->prefix."bwbps_customforms");
define("PSFIELDSTABLE", $wpdb->prefix."bwbps_fields");
define("PSLOOKUPTABLE", $wpdb->prefix."bwbps_lookup");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");

//Set the Upload Path
define('PSBLOGURL', get_bloginfo('wpurl')."/");
define('PSUPLOADPATH', WP_CONTENT_DIR .'/uploads');
define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSIMAGESPATH2',PSUPLOADPATH."/bwbps");
define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define('PSTHUMBSPATH2',PSUPLOADPATH."/bwbps/thumbs");
define('PSIMAGESURL',WP_CONTENT_URL."/uploads/bwbps/");
define('PSTHUMBSURL',PSIMAGESURL."thumbs/");
define("PSTABLEPREFIX", $wpdb->prefix."bwbps_");
define('PSTEMPLATESURL',WP_CONTENT_URL."/themes/");

define('PSADVANCEDMENU', "<a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a> | <a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a> | <a href='admin.php?page=managePhotoSmashImages'>Photo Manager</a> | <a href='admin.php?page=editPSForm'>Custom Form</a> | <a href='admin.php?page=editPSFields'>Custom Fields</a> | <a href='admin.php?page=editPSHTMLLayouts'>Layouts Editor</a> 
		<br/>");

define('PSSTANDARDDMENU', "<a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a> | <a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a> | <a href='admin.php?page=managePhotoSmashImages'>Photo Manager</a> 
		<br/>");

if ( (gettype( ini_get('safe_mode') ) == 'string') ) {
	// if sever did in in a other way
	if ( ini_get('safe_mode') == 'off' ) define('SAFE_MODE', FALSE);
	else define( 'SAFE_MODE', ini_get('safe_mode') );
} else {
	define( 'SAFE_MODE', ini_get('safe_mode') );
}

//include ('ajax_upload.php');
class BWB_PhotoSmash{

	var $customFieldVersion = 10;  //Increment this to force PS to update the Custom Fields Option
	var $adminOptionsName = "BWBPhotosmashAdminOptions";
	
	var $uploadFormCount = 0;
	var $manualFormCount = 0;
	var $loadedGalleries;
	var $moderateNonceCount = 0;
	var $psAdmin;
		
	var $psOptions;
	var $psLayout;
	
	var $psForm;
	
	var $shortCoded;
	var $cfStdFields;
	var $cfList;
	
	var $galleries;
	
	var $images;
	
	//Constructor
	function BWB_PhotoSmash(){
		$this->psOptions = $this->getPSDefaultOptions();
		
		if($this->psOptions['use_customfields']){
			$this->loadCustomFormOptions();
		}
		
		/*	Code for uploading without AJAX...doesn't work
		*
		if(isset($_POST['bwbps_submitBtn'])){
			include_once("ajax_upload.php");
		}
		*
		*/

	}
	
	function loadCustomFormOptions(){
		$this->cfStdFields = $this->getcfStdFields();
		$this->cfList = $this->getCustomFields();
	}
	
	//Called when plugin is activated
	function init(){
		require_once('admin/bwbps-init.php');
		$bwbpsinit = new BWBPS_Init();					
	}
	
	//Returns an array of default options
	function getPSDefaultOptions()
	{
		$psOptions = get_option($this->adminOptionsName);
		if($psOptions && !empty($psOptions))
		{
			//Options were found..add them to our return variable array
			foreach ( $psOptions as $key => $option ){
				$psAdminOptions[$key] = $option;
			}
		}else{
			$psAdminOptions = array(
				'auto_add' => 0,
				'img_perrow' => 0,
				'img_perpage' => 0,
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
				'img_alerts' => 3600,
				'show_imgcaption' => 1,
				'nofollow_caption' => 1,
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
				'custom_formname' => 'default',
				'use_donelink' => 0,
				'css_file' => '',
				'exclude_default_css' => 0,
				'date_format' => 'm/d/Y',
				'upload_authmessage' => '',
				'imglinks_postpages_only' => 0
			);
			if(!$psOptions){
				add_option($this->adminOptionsName, $psAdminOptions);
			} else {
				update_option($this->adminOptionsName, $psAdminOptions);
			}
		}
		if (!array_key_exists('use_thickbox', $psAdminOptions)) {
				$psAdminOptions['use_thickbox']=1;
				$runUpdate = true;
		}
		
		if (!array_key_exists('date_format', $psAdminOptions)) {
				$psAdminOptions['date_format']='m/d/Y';
				$runUpdate = true;
		}
		
		if($runUpdate){
				update_option($this->adminOptionsName, $psAdminOptions);
		}
		
		return $psAdminOptions;
	}
	
	function getcfStdFields(){
		
		$cfVer = get_option('bwbps_custfield_ver');
		
		$cfOpts = get_option('bwbps_cf_stdfields');
		
		if(!$cfVer || $cfVer < $this->customFieldVersion || !$cfOpts || empty($cfOpts)){
			$cfOpts = $this->getCFDefaultOptions();
			if($cfOpts && !empty($cfOpts)){
				update_option('bwbps_cf_stdfields',$cfOpts);				
			} else {
				add_option('bwbps_cf_stdfields',$cfOpts);				
			}
		}
		
		if(!$cfVer || $cfVer < $this->customFieldVersion){
			delete_option('bwbps_custfield_ver');
			add_option('bwbps_custfield_ver', $this->customFieldVersion);
		}
		
		return $cfOpts;
	}
	
	function getCFDefaultOptions(){

		$ret = array(
			'image_select',
			'image_select_2',
			'submit',
			'caption',
			'caption2',
			'user_name',
			'user_url',
			'url',
			'thumbnail',
			'thumbnail_2',
			'user_submitted_url',
			'done',
			'loading',
			'message',
			'category_name',
			'category_link',
			'category_id',
			'post_id'
		);
		return $ret;
	}
	
	//Get the Custom Fields Query Results
	function getCustomFields(){
		global $wpdb;
		$sql = "SELECT * FROM ".PSFIELDSTABLE." WHERE status = 1 ORDER BY seq";
		
		$query = $wpdb->get_results($sql);
		return $query;
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
			
			//Advanced Features (Layouts and Custom Fields
			if($this->psOptions['use_advanced'] == 1){
				$bshowadv = true;
			}
			if(isset($_POST['update_bwbPSDefaults'])){
				if(isset($_POST['ps_use_advanced'])){
					$bshowadv = true;
				}else{
					$bshowadv = false;
				}
			}
			if($bshowadv){
				add_submenu_page(basename(__FILE__), __('PS Form Editor')
					, __('Custom Form'), 9, 'editPSForm'
					, array(&$bwbPS, 'loadFormEditor'));
					
				add_submenu_page(basename(__FILE__), __('PS Field Editor')
					, __('Custom Fields'), 9, 'editPSFields'
					, array(&$bwbPS, 'loadFieldEditor'));
				
				add_submenu_page(basename(__FILE__), __('PS Layouts Editor')
					, __('Layouts Editor'), 9, 'editPSHTMLLayouts'
					, array(&$bwbPS, 'loadLayoutsEditor'));
					
			}
			add_submenu_page(basename(__FILE__), __('Plugin Info'), __('Plugin Info'), 9,  
			'psInfo', array(&$bwbPS, 'loadPsInfo'));
				
		}
		
	}
	
	//Prints out the Admin Options Page
	function loadAdminPage(){
		if(!$this->psAdmin){
			require_once("admin/bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printGeneralSettings();
	}
	
	function loadGallerySettings(){
		if(!$this->psAdmin){
			require_once("admin/bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printGallerySettings();
		return true;
	}
	
	function loadPhotoManager(){
		if(!$this->psAdmin){
			require_once("admin/bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printManageImages();
		
		return true;
	}
	
	function loadLayoutsEditor(){
		require_once("admin/bwbps-layouts.php");
		$layouts = new BWBPS_LayoutsEditor();		
		return true;
	}
	
	function loadFieldEditor(){
		require_once("admin/bwbps-fieldeditor.php");
		$fieldEditor = new BWBPS_FieldEditor($this->psOptions);		
		return true;
	}
	
	function loadFormEditor(){
		require_once("admin/bwbps-formeditor.php");
		$psform = new BWBPS_FormEditor($this->psOptions);		
		return true;
	}
	
	function loadPSInfo(){
		require_once("admin/bwbps-info.php");
		$ts = new BWBPS_Info();			
		return true;
	}
	
	//Send email alerts for new images
	function sendNewImageAlerts()
	{
		global $wpdb;
		
		$sql = "SELECT * FROM ".PSIMAGESTABLE." WHERE alerted = 0 AND status = -1;";
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
		$wpdb->update(PSIMAGESTABLE, $data, $where);
		
	}
		
		
/* ******************   End of Admin Section ******************************** */
	
/*	****************************************  Gallery Code  *************************************** */
//	AutoAdd Galleries
function autoAddGallery($content='')
{
	global $post;
	
	if(is_array($this->shortCoded) && in_array($post->ID, $this->shortCoded)){
		return $content;
	}
		
	//Determine if Auto-add is set up...add it to top or bottom if so
	$psoptions = $this->psOptions;// Get PhotoSmash defaults
	if($psoptions['auto_add']){
		//Auto-add is set..but first, see if there is a skip tag:  [ps-skip]
		if(strpos($content, "[ps-skip]") === false){}else{return str_replace("[ps-skip]","",$content);}
		$galparms = array("gallery_id" => false);
		$g = $this->getGallery($galparms);	//Get the Gallery params
		$loadedGalleries[] = $g['gallery_id'];
		$gallery = $this->buildGallery($g);
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
	return $content;	
}


function checkEmailAlerts(){
	// Get the Class level psOptions variable
	// contains Options defaults and the Alert message psuedo-cron	
	
	//This is the timer for sending Alerts 
		if($this->psOptions['img_alerts'] >0 ){
			$time = time();
			if($time - $this->psOptions['last_alert'] > 
				$this->psOptions['img_alerts'])
			{
				$this->sendNewImageAlerts();
			}
		}

}


//  Loop through Content and inject Gallery where [photosmash id=###] is found
//  Called by add_shortcode filter
function shortCodeGallery($atts, $content=null){
		global $post;
		
		$this->checkEmailAlerts();
		
		if(!$atts['id'] && $atts[0])
		{
			$maybeid = str_replace("=", "", trim($atts[0]));
			//Backwards compatibility with old shortcodes like: [photosmash=9]
			if(is_numeric($maybeid)){$atts['id'] = (int)$maybeid;}
		}
		
		extract(shortcode_atts(array(
			'id' => false,
			'form' => false,
			'form_alone' => false,
			'no_gallery' => false,
			'no_form' => false,
			'gallery' => false,
			'gal_id' => false,
			'field' => false,
			'layout' => false,
			'thickbox' => false
		),$atts));
				
		if(!$id && ($gallery || $gal_id)){
			$id=$gallery;
			if(!$id){
				$id=$gal_id;
			}
		}
				
		//Get Gallery
		$galparms = $atts;
		
		$galparms['gallery_id'] = (int)$id;
		$galparms['photosmash'] = $galparms['gallery_id'];
	
			
		$g = $this->getGallery($galparms);	//Get the Gallery params
				
		$g['use_thickbox'] = $thickbox;
		
		/* *********************************** */
		// Shortcode for MANUAL FORM placement //
		$formName = false;
		
		if($form == "none" || $form == "false" || $no_form){
			$skipForm = true;
		} else {
			$formName = trim($form);
		}
		
		if(in_array("form",$atts) || in_array("form_alone", $atts)){
			$form_alone = true;
		}
		
		if($form_alone){
			if($form_alone <> 'true'){
				$formName = trim($form_alone);
			}
			$no_gallery = true;
			$manualForm = true;
		}
		
		if($this->psOptions['use_manualform'] 
			&& !$formName && !$form_alone
		){
			$skipForm = true;
		}
		
		
		if(($formName || $manualForm) && !$skipForm){
					
			//See if Manual Form Placement is on, or if a Custom Form was given
			if(($this->psOptions['use_manualform'] 
				|| $formName) && $this->manualFormCount < 1 ){
				
				//See if user has rights to Upload
				if( $g['contrib_role'] == -1 
					|| current_user_can('level_'.$g['contrib_role']) 
					|| current_user_can('upload_to_photosmash') 
					|| current_user_can('photosmash_'.$g["gallery_id"]))
				{
					$blogname = str_replace('"',"",get_bloginfo("blogname"));
					$ret = $this->getAddPhotosLink($g, $blogname);
					$ret .= $this->getPhotoForm($g,$formName);
					$this->manualFormCount++;
					
				} else {
				
					if(trim($this->psOptions['upload_authmessage'])){
						
						$this->psOptions['upload_authmessage'] = str_replace("&#039;","'",$this->psOptions['upload_authmessage']);
						$this->psOptions['upload_authmessage'] = str_replace("&quot;",'"',$this->psOptions['upload_authmessage']);
						$this->psOptions['upload_authmessage'] = str_replace("&lt;",'<',$this->psOptions['upload_authmessage']);
						$this->psOptions['upload_authmessage'] = str_replace("&gt;",'>',$this->psOptions['upload_authmessage']);
						
						
						$loginatts = $this->getFieldsWithAtts($this->psOptions['upload_authmessage'], 'login');
												
						if($loginatts['name']){
							$logvalue = trim($loginatts['name']);
							$logreplace = $loginatts['bwbps_match'];
						} else { $logvalue = "Login"; $logreplace = '[login]'; }
						
						$loginurl = wp_login_url( get_permalink() );
						
						$loginurl = "<a href='".$loginurl."' title='Login'>".$logvalue."</a>";
						
						$ret .= str_ireplace($logreplace,$loginurl
							,$this->psOptions['upload_authmessage']);
					}
				}
			}
			if($form_alone){
				return $ret;
			}
		}  //Closing out Manual Form Placement coe
		
		$this->shortCoded[] = $post->ID;
		
		if($no_gallery){
			return $ret;
		}

		if(!$g['gallery_id']){
			//Bad Gallery ID was provided.
			$ret = "Missing PhotoSmash gallery: ".$g['photosmash']; 
			return $ret;
		}
				
		//Check duplicate gallery on page...only allow once
		if(is_array($this->loadedGalleries) 
			&& in_array($post->ID."-".$g['gallery_id'] 
			, $this->loadedGalleries)){

			//Bad Gallery ID was provided.	
			$ret = "Duplicate gallery: " . $g['photosmash']; 
				
		}else{
								
			if($layout){
				$layoutName = trim($layout);
			} else { $layoutName = false; }
				
			
			$this->loadedGalleries[] = $post->ID."-".$g['gallery_id'];
			$ret = $this->buildGallery($g, $skipForm, $layoutName, $formName );
			$ret .= "
				<script type='text/javascript'>
					displayedGalleries += '|".$g['gallery_id']."';
				</script>
			";
		}
		
		unset($galparms);

	return $ret;
}

// Retrieve the Gallery....Creates new Gallery record linked to Post if gallery ID is false
function getGallery($g){
	global $post;
	global $wpdb;
	$psoptions = $this->psOptions;
	//Define Galleries table name for use in queries
	$table_name = $wpdb->prefix . "bwbps_galleries";
	
	//See if Gallery is Cached
	if($g['gallery_id'] && is_array($this->galleries) && array_key_exists($g['gallery_id'], $this->galleries))
	{
		$g = $this->galleries[$g['gallery_id']];
	} else {
	
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
			$data['img_perrow'] = (int)$g['img_perrow'] ? (int)$g['img_perrow'] : (int)$psoptions['img_perrow'];
			$data['img_perpage'] = (int)$g['img_perpage'] ? (int)$g['img_perpage'] : (int)$psoptions['img_perpage'];
			$data['thumb_aspect'] = (int)$g['thumb_aspect'] ? (int)$g['thumb_aspect'] : (int)$psoptions['thumb_aspect'];
			$data['thumb_width'] = (int)$g['thumb_width'] ? (int)$g['thumb_width'] : (int)$psoptions['thumb_width'];
			$data['thumb_height'] =  $g['thumb_height'] ? (int)$g['thumb_height'] : (int)$psoptions['thumb_height'];
			$data['show_caption'] =  $g['show_caption'] ? (int)$g['show_caption'] : (int)$psoptions['show_caption'];
			$data['nofollow_caption'] =  $g['nofollow_caption'] ? (int)$g['nofollow_caption'] : (int)$psoptions['nofollow_caption'];
			$data['show_imgcaption'] =  $g['show_imgcaption'] ? (int)$g['show_imgcaption'] : (int)$psoptions['show_imgcaption'];
			$data['created_date'] = date( 'Y-m-d H:i:s');
			$data['status'] = 1;
			
			//$wpdb->insert($table_name, $data); //Insert into Galleries Table
			$g = $data;
			$g['gallery_id'] = $wpdb->insert_id;
	}
	$g['add_text'] = $psoptions['add_text'] ? $psoptions['add_text'] : "Add Photos";
	
	$this->galleries[$g['gallery_id']] = $g;
	
	return $g;
	
}


function getAddPhotosLink($g, $blogname){
	global $post;
	if($this->psOptions['use_thickbox'] || $g['use_thickbox']){
		$ret = '<span style="margin-left: 10px;"><a href="TB_inline?height=390&amp;width=545&amp;inlineId=bwbps-formcont" onclick="bwbpsShowPhotoUpload('.$g["gallery_id"].', '.(int)$post->ID.');" title="'.$blogname.' - Gallery Upload" class="thickbox">'.$g['add_text'].'</a></span>';
	} else {
		if( !$this->psOptions['uploadform_visible']){
			$ret = '<span style="margin-left: 10px;"><a href="javascript: void(0);" onclick="bwbpsShowPhotoUploadNoThickbox('.$g["gallery_id"].', '.(int)$post->ID.');" title="'.$blogname.' - Gallery Upload" class="thickbox">'.$g['add_text'].'</a></span><div id="bwbpsFormSpace_'.$g['gallery_id'].'" style="display:none;"></div>';
		}
	}
	return $ret;
}


function buildGallery($g, $skipForm=false, $layoutName=false, $formName=false)
{
	$blogname = str_replace('"',"",get_bloginfo("blogname"));
	$admin = current_user_can('level_10');
	
	$ret = '<div class="photosmash_gallery">';
		
	//Get UPLOAD FORM if 'use_manualform' is NOT set
	if($formName || (!$this->psOptions['use_manualform'] && !$skipForm)){
		if( $g['contrib_role'] == -1 || current_user_can('level_'.$g['contrib_role']) ||
			current_user_can('upload_to_photosmash') || current_user_can('photosmash_'
			.$g["gallery_id"])){		
	
			//Takes into account whether we're using Thickbox or not
			$ret .= $this->getAddPhotosLink($g, $blogname);
		
			if($this->moderateNonceCount < 1)
			{
				$nonce = wp_create_nonce( 'bwbps_moderate_images' );
				
				$ret .= 
					'<input type="hidden" id="_moderate_nonce" name="_moderate_nonce" value="'
					.$nonce.'" />';
					
				$this->moderateNonceCount++;
			}
			
			if($this->uploadFormCount < 1){
				if($this->psOptions['uploadform_visible']){
					if(is_page() || is_single()){
						$ret .= $this->getPhotoForm($g,$formName);
					}
				}else{
					$ret .= $this->getPhotoForm($g, $formName);
				}
				$this->uploadFormCount++;
			}			
		}
	} //closes out the use_manualform condition
	
	if(!isset($this->psLayout)){
		require_once('bwbps-layout.php');
		$this->psLayout = new BWBPS_Layout($this->psOptions, $this->cfList);
	}

	$ret .=	$this->psLayout->getGallery($g, $layoutName);
	
	$ret .= "</div>
		<div class='bwbps_clear'></div>";
	
	$this->galleries[$g['gallery_id']] = $g;
	return $ret;

}

function validURL($str)
{
	return ( ! preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $str)) ? FALSE : TRUE;
}

	function validateURL($url){
		if (preg_match("/^(http(s?):\\/\\/{1})((\w+\.)+)\w{2,}(\/?)$/i", $url)) {
			return true; 
		} else { 
			return false;
		} 
	}
	
function getPhotoForm($g, $formName=false){	
	if(!isset($this->psForm)){
		require_once('bwbps-uploadform.php');
		
		if(!$this->cfStdFields || !$this->cfList){
			$this->loadCustomFormOptions();	
		}
		
		$this->psForm = new BWBPS_UploadForm($g, $this->psOptions, $this->cfList);
	}
	
	return $this->psForm->getUploadForm($formName);
}

	
	
	//Add JS libraris
	function enqueueBWBPS(){		
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-form');
		wp_enqueue_script('thickbox');
//		if($this->psOptions['use_thickbox']){
			
//		}
		wp_enqueue_script('jquery-ui-tabs');
				
		/*
		//enqueue jQuery Forms
		wp_register_script('jquery_forms', WP_PLUGIN_URL . '/photosmash-galleries/js/jquery.form.js', array('jquery'), '2.17');
		wp_enqueue_script('jquery_forms');
		*/
		//enqueue BWB-PS Javascript
		wp_register_script('bwbps_js', WP_PLUGIN_URL . '/photosmash-galleries/js/bwbps.js', array('jquery'), '1.0');
		wp_enqueue_script('bwbps_js');
		
		if($this->psOptions['use_customfields']){
			//enqueue jQuery DatePicker
			wp_register_script('jquery_datepicker' 
				, get_bloginfo('wpurl') 
				. '/wp-content/plugins/photosmash-galleries/js/ui.datepicker.js'
				, array('jquery'), '1.0');
			wp_enqueue_script('jquery_datepicker');
		
		}
	}
	
	//Add CSS
	function injectBWBPS_CSS(){
	?>
	<link rel="stylesheet" href="<?php bloginfo('wpurl'); ?>/<?php echo WPINC; ?>/js/thickbox/thickbox.css" type="text/css" media="screen" />
	
	<?php
	if(!$this->psOptions['exclude_default_css']){  ?>
	<link rel="stylesheet" href="<?php echo WP_PLUGIN_URL;?>/photosmash-galleries/css/bwbps.css" type="text/css" media="screen" />
	<?php 
	}
	
	if(trim($this->psOptions['css_file'])){  
	?>
	<link rel="stylesheet" href="<?php echo PSTEMPLATESURL.$this->psOptions['css_file'];?>" type="text/css" media="screen" />
	<?php } ?>
	
	<link rel="stylesheet" type="text/css" href="<?php echo WP_PLUGIN_URL;?>/photosmash-galleries/css/ui.core.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo WP_PLUGIN_URL;?>/photosmash-galleries/css/ui.datepicker.css" />
	
    <script type="text/javascript">
    var tb_pathToImage = "<?php bloginfo('wpurl'); ?>/<?php echo WPINC; ?>/js/thickbox/loadingAnimation.gif";
    var tb_closeImage = "<?php bloginfo('wpurl'); ?>/<?php echo WPINC; ?>/js/thickbox/tb-close.png";
	var displayedGalleries = "";
	var bwbpsCustomLayout = false;
	var bwbpsAjaxURL = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax.php";
	var bwbpsAjaxUpload = "<?php 
		echo WP_PLUGIN_URL."/photosmash-galleries/ajax_upload.php";
		?>";
	var bwbpsImagesURL = "<?php echo PSIMAGESURL; ?>";
	var bwbpsThumbsURL = "<?php echo PSTHUMBSURL; ?>";
	var bwbpsBlogURL = "<?php echo PSBLOGURL; ?>";
	
	function bwbpsAlternateUploadFunction(data, statusText){
		
		var ret = false;
		
		<?php if(trim($this->psOptions['alt_javascript'])){
			
			echo "try{ 
				return " . trim($this->psOptions['alt_javascript']) . ";
			}
			 catch(err)
			{ 
				alert(err);
			 }";
		}
		?>
		// Returning true will cause the normal Ajax Upload Success callback to abort...false continues 
		return false;
	}
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
	
	function injectAdminStyles()
	{
		wp_enqueue_style( 'bwbpstabs', WP_PLUGIN_URL.'/photosmash-galleries/css/bwbps.css', false, '1.0', 'screen' );		
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
	
	function shortCodes($atts, $content=null){
		
		extract(shortcode_atts(array(
			'id' => false,
			'img_id' => false,
			'img_key' => false,
			'image_id' => false,
			'image' => false,
			'thumbnail' => false,
			'form' => false,
			'gallery' => false,
			'gal_id' => false,
			'field' => false,
			'layout' => false,
			'alt' => false
		),$atts));
		
		
		
		if($img_key || $id || $img_id){
			if($id){
				$img_key = $id;
			} else {
				if($img_id){
					$img_key = $img_id;
				} else {
					if($image_id){
						$img_key = $image_id;
					}
				}
			}
			if(is_array($this->images)){
				if(!array_key_exists($img_key, $this->images)){
					$this->images[$img_key] = $this->getImage($img_key);
				}
			}else{
				$this->images[$img_key] = $this->getImage($img_key);
			}
			
			
			//Fields and Layouts
			if($this->images[$img_key]){
				if($field ){
					$ret = $this->images[$img_key][$field];
				}
				//Layout
				if($layout){
					
					if(!isset($this->psLayout)){
						require_once('bwbps-layout.php');
						$this->psLayout = new BWBPS_Layout($this->psOptions, $this->cfList);
					}
					$g = array('gallery_id' => $images['gallery_id']);
					$g = $this->getGallery($g);
					$ret .= $this->psLayout->getPartialLayout($g, $this->images[$img_key], $layout, $alt);
				}
			}
		}

		//Image
		if($image){
			if(is_array($this->images)){
				if(!array_key_exists($image, $this->images)){
					$this->images[$image] = $this->getImage($image);
				}
			}else{
				$this->images[$image] = $this->getImage($image);
			}
			
			$img = $this->images[$image];
			if($img){
				$imgtitle = str_replace("'","",$img['image_caption']);
				$ret = "<img src='".PSIMAGESURL.$img['file_name']."'".$img['imgclass']
					." alt='".$imgtitle."' />";
			}
		}
		
		//Thumbnail
		if($thumbnail){
			if(is_array($this->images)){
				if(!array_key_exists($thumbnail, $this->images)){
					$this->images[$thumbnail] = $this->getImage($thumbnail);
				}
			} else{
				$this->images[$thumbnail] = $this->getImage($thumbnail);
			}
			
			$img = $this->images[$thumbnail];
			
			if($img){
				if($this->psOptions['img_targetnew']){
					$imagetargblank = " target='_blank' ";
				}
				$imgtitle = str_replace("'","",$img['image_caption']);
				
				if($img['img_rel']){$imgrel = " rel='".$img['img_rel']."'";} else {$imgrel="";}
				
				$imgurl = "<a href='".PSIMAGESURL.$img['file_name']."'"
						.$imgrel." title='".$imgtitle."' ".$imagetargblank.">";
				
				$ret = $imgurl."
					<img src='".PSTHUMBSURL.$img['file_name']."'".$img['imgclass']
					." alt='".$imgtitle."' /></a>";
					
			}
		}
		return $ret;
	}
	
	function getImage($image_id){
		global $wpdb;
		global $user_ID;
		
		//Set up SQL for Custom Data if in Use
		$custDataJoin = " LEFT OUTER JOIN ".PSCUSTOMDATATABLE
			." ON ".PSIMAGESTABLE.".image_id = "
			.PSCUSTOMDATATABLE.".image_id ";
		
		$custdata = ", ".PSCUSTOMDATATABLE.".* ";
		
		
		//Admins can see all images
		if(current_user_can('level_10')){
			$sql = $wpdb->prepare("SELECT ".PSIMAGESTABLE.".*, ".PSGALLERIESTABLE.".img_class,"
					.PSGALLERIESTABLE.".img_rel". $custdata 
					." FROM ".PSIMAGESTABLE
					." LEFT OUTER JOIN ".PSGALLERIESTABLE." ON "
					.  PSGALLERIESTABLE.".gallery_id = ".PSIMAGESTABLE
					.".gallery_id ".$custDataJoin. " WHERE ".PSIMAGESTABLE
					.".image_id = %d", $image_id);
			
		} else {
			//Non-Admins can see their own images and Approved images
			$uid = $user_ID ? $user_ID : -1;
			
			$sql = $wpdb->prepare("SELECT ".PSIMAGESTABLE.".*, ".PSGALLERIESTABLE.".img_class,"
					.PSGALLERIESTABLE.".img_rel". $custdata 
					." FROM ".PSIMAGESTABLE
					." LEFT OUTER JOIN ".PSGALLERIESTABLE." ON "
					.PSGALLERIESTABLE.".gallery_id = ".PSIMAGESTABLE
					.".gallery_id ". $custDataJoin ."WHERE ".PSIMAGESTABLE
					.".image_id = %d AND (".PSIMAGESTABLE
					.".status > 0 OR ".PSIMAGESTABLE
					.".user_id = '"
					.$uid."')", $image_id);
			
		}
				
		$image = $wpdb->get_row($sql, ARRAY_A);
		
		return $image;
	}
	
	function getFieldsWithAtts($content, $fieldname){
				
		$pattern = '\[('.$fieldname.')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\1\])?';
		
		preg_match_all('/'.$pattern.'/s', $content,  $matches );
		
		$attr = $this->field_parse_atts($matches[2][0]);

		$attr['bwbps_match'] = $matches[0][0];
		return $attr;
				
	}
		
	function field_parse_atts($text) {
		$atts = array();
		$pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
		$text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
		if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
			foreach ($match as $m) {
				if (!empty($m[1]))
					$atts[strtolower($m[1])] = stripcslashes($m[2]);
				elseif (!empty($m[3]))
					$atts[strtolower($m[3])] = stripcslashes($m[4]);
				elseif (!empty($m[5]))
					$atts[strtolower($m[5])] = stripcslashes($m[6]);
				elseif (isset($m[7]) and strlen($m[7]))
					$atts[] = stripcslashes($m[7]);
				elseif (isset($m[8]))
					$atts[] = stripcslashes($m[8]);
			}
		} else {
			$atts = ltrim($text);
		}	
		return $atts;
	}
	
	
} //End of BWB_PhotoSmash Class


/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */

$bwbPS = new BWB_PhotoSmash();


//Call the Function that will Add the Options Page
add_action('admin_menu', array(&$bwbPS, 'photoSmashOptionsPage'));

//Inject Admin Javascript & Styles
add_action('admin_print_scripts', array(&$bwbPS, 'injectAdminJS') );
add_action('admin_print_styles', array(&$bwbPS, 'injectAdminStyles') );

//Call the INIT function whenever the Plugin is activated
add_action('activate_photosmash-galleries/bwb-photosmash.php',
array(&$bwbPS, 'init'));


add_action('init', array(&$bwbPS, 'enqueueBWBPS'), 1);

add_action('wp_head', array(&$bwbPS, 'injectBWBPS_CSS'), 10);

add_filter('the_content',array(&$bwbPS, 'autoAddGallery'), 100);

add_shortcode('photosmash', array(&$bwbPS, 'shortCodeGallery'));

add_shortcode('psmash', array(&$bwbPS, 'shortCodes'));
?>