<?php
/*
Plugin Name: PhotoSmash
Plugin URI: http://smashly.net/photosmash-galleries/
Description: PhotoSmash - user contributable photo galleries for WordPress pages and posts.  Focuses on ease of use, flexibility, and moxie. Deep functionality for developers. PhotoSmash is licensed under the GPL.
Version: 0.3.03
Author: Byron Bennett
Author URI: http://www.whypad.com/
*/
 
/** 
 * Copyright 2009  Byron W Bennett (email: bwbnet@gmail.com)
 *
 * LICENSE: GPL
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

//VERSION - Update PhotoSmash Extend!!!
define('PHOTOSMASHVERSION', '0.3.03');


//Database Verifications
define('PHOTOSMASHVERIFYTABLE', $wpdb->prefix.'bwbps_galleries');
define('PHOTOSMASHVERIFYFIELD', 'rating_position');

//Set Database Table Constants
define("PSGALLERIESTABLE", $wpdb->prefix."bwbps_galleries");
define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
define("PSLAYOUTSTABLE", $wpdb->prefix."bwbps_layouts");
define("PSFORMSTABLE", $wpdb->prefix."bwbps_forms");
define("PSFIELDSTABLE", $wpdb->prefix."bwbps_fields");
define("PSLOOKUPTABLE", $wpdb->prefix."bwbps_lookup");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");

//Set the Upload Path
define('PSBLOGURL', get_bloginfo('wpurl')."/");
define('PSUPLOADPATH', WP_CONTENT_DIR .'/uploads');

define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSIMAGESPATH2',PSUPLOADPATH."/bwbps");
define('PSIMAGESURL',WP_CONTENT_URL."/uploads/bwbps/");

define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define('PSTHUMBSPATH2',PSUPLOADPATH."/bwbps/thumbs");
define('PSTHUMBSURL',PSIMAGESURL."thumbs/");

define('PSDOCSPATH',PSUPLOADPATH."/bwbps/docs/");
define('PSDOCSPATH2',PSUPLOADPATH."/bwbps/docs");
define('PSDOCSURL',PSIMAGESURL."docs/");

define('PSTABLEPREFIX', $wpdb->prefix."bwbps_");
define('PSTEMPLATESURL',WP_CONTENT_URL."/themes/");

define('BWBPSPLUGINURL',WP_PLUGIN_URL."/photosmash-galleries/");

define('PSADVANCEDMENU', "<a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a> | <a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a> | <a href='admin.php?page=managePhotoSmashImages'>Photo Manager</a> | <a href='admin.php?page=editPSForm'>Custom Forms</a> | <a href='admin.php?page=editPSFields'>Custom Fields</a> | <a href='admin.php?page=editPSHTMLLayouts'>Layouts Editor</a> 
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


//Move up to WP 2.8+
//Using new url sanitizing function
if( ! function_exists('esc_url_raw') ){

	function esc_url_raw($url){
		
		if( function_exists('sanitize_url') ){
			return sanitize_url($url);
		} else {
			return false;
		}
	
	}
}

if( ! function_exists('esc_url') ){

	function esc_url($url){
		
		if( function_exists('clean_url') ){
			return clean_url($url);
		} else {
			return false;
		}
	
	}
}

if( ! function_exists('esc_attr__') ){

	function esc_attr__($string){
		
		if( function_exists('attribute_escape') ){
			return attribute_escape($string);
		} else {
			return false;
		}
	
	}
	
	function esc_attr($string){
		
		if( function_exists('attribute_escape') ){
			return attribute_escape($string);
		} else {
			return false;
		}
	
	}
	
	function esc_attr_e($string){
		
		if( function_exists('attribute_escape') ){
			echo ($string);
		} 
	
	}
}

//include ('ajax_upload.php');
class BWB_PhotoSmash{

	var $customFormVersion = 14;  //Increment this to force PS to update the Custom Fields Option
	var $adminOptionsName = "BWBPhotosmashAdminOptions";
	
	var $uploadFormCount;
	var $manualFormCount = 0;
	var $loadedGalleries;
	var $moderateNonceCount = 0;
	var $psAdmin;
		
	var $psOptions;
	var $psLayout;
	
	var $psForm;
	
	var $shortCoded;
	var $stdFieldList;
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
		
		
		add_action( 'bwbps_contributor_gallery', array(&$this,'displayContributorGallery') );
		*/
		
		if( $this->psOptions['contrib_gal_on'] ){
			add_filter('the_posts',  array(&$this,'displayContributorGallery') );
		
			add_filter('the_excerpt',array(&$this,'fixExcerptGallery') ); 
		}

	}
	
	function loadCustomFormOptions(){
		$this->stdFieldList = $this->getstdFieldList();
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
				'alert_all_uploads' => 0,
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
				'custom_formid' => 0,
				'use_donelink' => 0,
				'css_file' => '',
				'exclude_default_css' => 0,
				'date_format' => 'm/d/Y',
				'upload_authmessage' => '',
				'imglinks_postpages_only' => 0,
				'sort_field' => 0,
				'sort_order' => 0,
				'contrib_gal_on' => 0,
				'suppress_contrib_posts' => 0,
				'poll_id' => 0,
				'rating_position' => 0,
				'rating_allow_anon' => 0,
				'version' => PHOTOSMASHVERSION
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
	
	function getstdFieldList(){
		
		$cfVer = get_option('bwbps_custfield_ver');
		
		$cfOpts = get_option('bwbps_cf_stdfields');
		
		if(!$cfVer || $cfVer < $this->customFormVersion || !$cfOpts || empty($cfOpts)){
			$cfOpts = $this->getFormsStandardFields();
			if($cfOpts && !empty($cfOpts)){
				update_option('bwbps_cf_stdfields',$cfOpts);				
			} else {
				add_option('bwbps_cf_stdfields',$cfOpts);				
			}
		}
		
		if(!$cfVer || $cfVer < $this->customFormVersion){
			delete_option('bwbps_custfield_ver');
			add_option('bwbps_custfield_ver', $this->customFormVersion);
		}
		
		return $cfOpts;
	}
	
	//Custom Forms De
	function getFormsStandardFields(){

		$ret = array(
			'image_select',
			'image_select_2',
			'video_select',
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
			'post_id',
			'allow_no_image'
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
					, __('Custom Forms'), 9, 'editPSForm'
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
		
		if(!get_option('BWBPhotosmashNeedAlert') == 1){ return; }
		
		if( !$this->psOptions['alert_all_uploads'] ){
			
			$sqlStatus = " AND status = -1 " ;
			$msgStatus = " awaiting moderation.";
		
		}
		
		$sql = "SELECT * FROM ".PSIMAGESTABLE." WHERE alerted = 0 $sqlStatus ;";
		$results = $wpdb->get_results($sql);
		if(!$results) return;
		
		$ret = get_bloginfo('name')." has ". $results->num_rows. " new photos". $msgStatus. ".  Select the appropriate gallery or click image below.<p><a href='".get_bloginfo('url')
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
		update_option('BWBPhotosmashNeedAlert',0);
		
		$data['alerted'] = -1;
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
	
	//This does the alert if it is set to alert immediately
	if( $this->psOptions['img_alerts'] == -1 ){
		$this->sendNewImageAlerts();
		return;
	}
	
	//This is the timer for sending Alerts 
		if( $this->psOptions['img_alerts'] > 0 ){
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
		
		if(!is_array($atts)){
			$atts = array();
		}
		
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
			'gallery_type' => false,
			'no_form' => false,
			'gallery' => false,
			'gal_id' => false,
			'image_id' => false,
			'field' => false,
			'layout' => false,
			'thickbox' => false,
			'form_visible' => false,
			'single_layout' => false,
			'author' => false
		),$atts));
		
		
		//A beautiful little shortcode that lets you set a different layout for single Post and Page pages than the one on Main Page, Categories, and Archives
		if($single_layout){
			if(is_page() || is_single()){
				$layout = $single_layout;
			}
		}
		
		if(!$id && ($gallery || $gal_id)){
			$id=$gallery;
			if(!$id){
				$id=$gal_id;
			}
		}
				
		
		$galparms = $atts;
		
		// Set up for contributor gallery
		if( $gallery_type == 'contributor' ){
			$id = 0;
			$galparms['gallery_type'] = 10;
		}
		
		if( $galparms['gallery_type'] == 10 ){
			
			$galparms['gallery_name'] = 'Contributor Gallery';
			
			if($author){
			
				$galparms['author'] == $author;
				$galparms['smart_where'] = array ( "user_id" => $author );
			
			}
			
			$galparms['smart_gallery'] = true;
			
			
		}
		
		$galparms['gallery_id'] = (int)$id;
		$galparms['photosmash'] = $galparms['gallery_id'];
	
		//Get Gallery	
		$g = $this->getGallery($galparms);	//Get the Gallery params
				
		$g['use_thickbox'] = $thickbox;
		$g['form_visible'] = $form_visible;
		
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
										
					$ret = $this->getAddPhotosLink($g, $blogname, $formName);
					$ret .= $this->getPhotoForm($g,$formName);
					$this->manualFormCount++;
					$skipForm = true;
					
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
			$ret .= $this->buildGallery($g, $skipForm, $layoutName, $formName );
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

	//See if Gallery is Cached
	if($g['gallery_id'] && is_array($this->galleries) && array_key_exists($g['gallery_id'], $this->galleries) && $g['gallery_type'] <> 10)
	{
		// Set $g = to the cached gallery, but keep any values that $g already has
		foreach ( $this->galleries[$g['gallery_id']] as $key => $option ){
			if(!$g[$key]){
				$g[$key] = $option;
			}
		}
		
		$galleryfound = true;
	
	} else {
		//Gallery was not cached......Get from either Gallery ID or Post ID
		
		
		//Is it a Contributor Gallery???
		if( $g['gallery_type'] == 10 ){
			
			$gquery = false;
			if($g['gallery_id']){
			
				$g['gallery_id'] = (int)$this->psOptions['contributor_gallery'];
				
				$gquery = $wpdb->get_row(
					$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
						." WHERE gallery_id = %d AND gallery_type = 10",$g['gallery_id']),ARRAY_A);
			
			}
			
			if( !$gquery ){
			
				$gquery = $wpdb->get_row(
					$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
						." WHERE gallery_type = 10 AND status = 1 "),ARRAY_A);
			
			}
			
			if( !$gquery ){
	
				$g['gallery_name'] = 'Contributor Gallery';
			
			}
			
				
		} else {
	
			//Get the specified gallery params if valid gallery_id
			if($g['gallery_id']){
				//Get gallery params based on Gallery_ID
				$gquery = $wpdb->get_row(
					$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
						." WHERE gallery_id = %d",$g['gallery_id']),ARRAY_A);
					
				//If query is false, then Bad Gallery ID provided...alert user
				if(!$gquery){$g['gallery_id'] = false; return $g;}
		
			} else {
			
				//Get gallery params based on Post_ID
				$gquery = $wpdb->get_row(
					$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
						." WHERE post_id = %d",$post->ID),ARRAY_A);
					
			}
		
		}
		
		if($gquery){
			
			/* Keep the parameters passed in From the [photosmash] tag in the Content
			   ...fill in the holes from the Gallery's default settings
		
				Can't do array_merge
			*/
			
			$g['gallery_id'] = $gquery['gallery_id'];
			
			foreach ( $gquery as $key => $option ){
				if(!$g[$key]){
					$g[$key] = $option;
				}
			}
			
			//Cache the new gallery
			$this->galleries[$gquery['gallery_id']] = $gquery;
			
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
				$g['contrib_role'] = (int)$psoptions['contrib_role'];
				break;
		}
	}
		
	
	if( !$galleryfound ){
	
		//No Gallery found...Need to create a Record for this Gallery
			$data['post_id'] = $post->ID;
			$data['gallery_name'] = $g['gallery_name'] ? $g['gallery_name']  : $post->post_title;
			$data['gallery_type'] = isset($g['gallery_type']) ? (int)$g['gallery_type'] : 0;
			$data['caption'] =  $g['caption'] ? $g['caption'] : $psoptions['gallery_caption'];
			
			$data['add_text'] = $g['add_text'] ? $g['add_text'] : 
				( $psoptions['add_text'] ? $psoptions['add_text'] : "Add Photos" );
			
			$data['upload_form_caption'] =  $g['upload_form_caption'] ? $g['upload_form_caption'] : $psoptions['upload_form_caption'];
			$data['contrib_role'] =  isset($g['contrib_role']) ? (int)$g['contrib_role'] : $psoptions['contrib_role'];
			$data['img_rel'] =  $g['img_rel'] ? $g['img_rel'] : $psoptions['img_rel'];
			$data['img_class'] =  $g['img_class'] ? $g['img_class'] : $psoptions['img_class'];
			if($g['img_status'] === 0 || $g['img_status'] == 1){
				$data['img_status'] = $g['img_status'];
			} else {
				$data['img_status'] = (int)$psoptions['img_status'];
			}
			$data['img_perrow'] = isset($g['img_perrow']) ? (int)$g['img_perrow'] : (int)$psoptions['img_perrow'];
			$data['img_perpage'] = isset($g['img_perpage']) ? (int)$g['img_perpage'] : (int)$psoptions['img_perpage'];
			$data['thumb_aspect'] = isset($g['thumb_aspect']) ? (int)$g['thumb_aspect'] : (int)$psoptions['thumb_aspect'];
			$data['thumb_width'] = isset($g['thumb_width']) ? (int)$g['thumb_width'] : (int)$psoptions['thumb_width'];
			$data['thumb_height'] =  isset($g['thumb_height']) ? (int)$g['thumb_height'] : (int)$psoptions['thumb_height'];
			
			$data['image_aspect'] = isset($g['image_aspect']) ? (int)$g['image_aspect'] : (int)$psoptions['image_aspect'];
						
			$data['image_width'] = isset($g['image_width']) ? (int)$g['image_width'] : (int)$psoptions['image_width'];
			
			$data['image_height'] = isset($g['image_height']) ? (int)$g['image_height'] : (int)$psoptions['image_height'];
			
			$data['show_caption'] =  isset($g['show_caption']) ? (int)$g['show_caption'] : (int)$psoptions['show_caption'];
			$data['nofollow_caption'] =  isset($g['nofollow_caption']) ? (int)$g['nofollow_caption'] : (int)$psoptions['nofollow_caption'];
			$data['show_imgcaption'] =  isset($g['show_imgcaption']) ? (int)$g['show_imgcaption'] : (int)$psoptions['show_imgcaption'];
			
			$data['use_customform'] = isset($data['use_customform']) ? (int)$data['use_customform'] : (isset($this->psOptions['use_customform']) ? 1 : 0);
			
			$data['use_customfields'] = isset($data['use_customfields']) ? $data['use_customfields'] : (isset($this->psOptions['use_customfields']) ? 1 : 0);
			
			$data['custom_formid'] = isset($data['custom_formid']) ? (int)$data['custom_formid'] : (int)$this->psOptions['custom_formid'];
			
			$data['layout_id'] = isset($data['layout_id']) ? (int)$data['layout_id'] : (int)$this->psOptions['layout_id'];
			
			$data['sort_field'] = isset($data['sort_field']) ? (int)$data['sort_field'] : (int)$this->psOptions['sort_field'];
			
			$data['sort_order'] = isset($data['sort_order']) ? (int)$data['sort_order'] : (int)$this->psOptions['sort_order'];
			
			$data['created_date'] = date( 'Y-m-d H:i:s');
			$data['status'] = 1;
			
			
			$wpdb->insert(PSGALLERIESTABLE, $data); //Insert into Galleries Table
			$g = $data;
			$g['gallery_id'] = $wpdb->insert_id;
			
			//Cache the new gallery
			$this->galleries[$g['gallery_id']] = $g;
			
	}
	
	$g['add_text'] = $g['add_text'] ? $g['add_text'] : 
				( $psoptions['add_text'] ? $psoptions['add_text'] : "Add Photos" );
	
	return $g;
	
}


function getAddPhotosLink(&$g, $blogname, &$formname){
	
	global $post;
	
	$use_tb = (int)$this->psOptions['use_thickbox'];
	$use_tb = $g['use_thickbox'] == 'false' ? false : $use_tb;
	$use_tb = $g['use_thickbox'] == 'true' ? true : $use_tb;
	$use_tb = $g['form_visible'] == 'true' ? false : $use_tb;
	
	$g['using_thickbox'] = $use_tb;
	
	if( $formname || (int)$g['custom_formid'] ){
		
		if($formname){
	
			$g['cf'] = $this->getCustomFormDef($formname);
		
		} else {
			
			$g['cf'] = $this->getCustomFormDef( "",(int)$g['custom_formid'] );
			
		}
				
		$cf = (int)$g['cf']['form_id'];		
		
		//If the custom form is not defined, return the standard form
		if(!$cf){
			
			$formname = "";
			unset($g['cf']);
			
		} else {
			
			//Set up Form ID Prefix for Custom Forms...will use this in form element IDs
			$g['pfx'] = "c" . $g['cf']['form_id'];
			$formname = $g['cf']['form_name'];
			
		}
	}
		
	if( $use_tb	)
	{
	
		$ret = '<span class="bwbps_addphoto_link"><a href="TB_inline?height=390&amp;width=545&amp;inlineId='.$g["pfx"].'bwbps-formcont" onclick="bwbpsShowPhotoUpload('.(int)$g["gallery_id"].', '.(int)$post->ID.', \''.$g["pfx"].'\');" title="'.$blogname.' - Gallery Upload" class="thickbox">'.$g['add_text'].'</a></span>';
	
	} else {

		$form_vis = (int)$this->psOptions['uploadform_visible'];
		$form_vis = $g['form_visible'] == 'true' ? true : $form_vis;
		$form_vis = $g['form_visible'] == 'false' ? false : $form_vis;
		
		$g['form_isvisible'] = $form_vis;
			
		if( !$form_vis )
		{
			$ret = '<span class="bwbps_addphoto_link"><a href="javascript: void(0);" onclick="bwbpsShowPhotoUploadNoThickbox('.(int)$g["gallery_id"].', '.(int)$post->ID.', \''.$g["pfx"].'\');" title="'.$blogname.' - Gallery Upload">'.$g['add_text'].'</a></span><div id="bwbpsFormSpace_'.$g['gallery_id'].'" style="display:none;"></div>';
		}
	}
	return $ret;
}


	function getPhotoForm($g, $formName=false){	
	
		$frm = $formName ? $formName : 'std';
		
		if($this->uploadFormCount[$frm]){ return;}
		$this->uploadFormCount[$frm]++;
		
	
		if(!isset($this->psForm)){
			require_once('bwbps-uploadform.php');
			
			if(!$this->stdFieldList || !$this->cfList){
				$this->loadCustomFormOptions();	
			}
			
			$this->psForm = new BWBPS_UploadForm($this->psOptions, $this->cfList);
		}
				
		return $this->psForm->getUploadForm($g, $formName);
	}
	
	/*
	 *	Get Custom Form Definition - from database
	 *	@param $formname - retrieves by name
	 *
	 */
	function getCustomFormDef($formname = "", $formid = false){
		
		global $wpdb;
		
		if($formname){
			$sql = $wpdb->prepare("SELECT * FROM " . PSFORMSTABLE . " WHERE form_name = %s", $formname);		
		} else {
		
			$sql = $wpdb->prepare("SELECT * FROM " . PSFORMSTABLE . " WHERE form_id = %d", $formid);		
		
		}
		
		$query = $wpdb->get_row($sql, ARRAY_A);
		return $query;
	}

function buildGallery($g, $skipForm=false, $layoutName=false, $formName=false)
{
	$blogname = str_replace('"',"",get_bloginfo("blogname"));
	$admin = current_user_can('level_10');
		
	$ret = '<div class="photosmash_gallery">';
			
	if($this->moderateNonceCount < 1 && $admin)
	{
		$nonce = wp_create_nonce( 'bwbps_moderate_images' );
				
		$ret .= 
			'<form><input type="hidden" id="_moderate_nonce" name="_moderate_nonce" value="'
			.$nonce.'" /></form>';
				
		$this->moderateNonceCount++;
	}
		
	//Get UPLOAD FORM if 'use_manualform' is NOT set
	if( ( $formName || !$this->psOptions['use_manualform'] ) && !$skipForm ){
		if( $g['contrib_role'] == -1 || current_user_can('level_'.$g['contrib_role']) ||
			current_user_can('upload_to_photosmash') || current_user_can('photosmash_'
			.$g["gallery_id"])){		
	
			//Takes into account whether we're using Thickbox or not
			$ret .= $this->getAddPhotosLink($g, $blogname, $formName);
									
			//Get the Upload Form
			if($this->psOptions['uploadform_visible'] || $g['form_visible'] ){
				if(is_page() || is_single()){
					$ret .= $this->getPhotoForm($g,$formName);
				}
			}else{
				$ret .= $this->getPhotoForm($g, $formName);
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
	
	
	//Add JS libraris
	function enqueueBWBPS(){		
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-form');
		wp_enqueue_script('thickbox');
				
		wp_register_script('bwbps_js', WP_PLUGIN_URL . '/photosmash-galleries/js/bwbps.js', array('jquery'), '1.0');
		wp_enqueue_script('bwbps_js');
		/*
		//enqueue jQuery Star Rating Plugin
		wp_register_script('jquery_metadata'
			, WP_PLUGIN_URL . '/photosmash-galleries/js/jquery.MetaData.js'
			, array('jquery'), '1.0');
		wp_enqueue_script('jquery_metadata');
		*/
		
		//enqueue jQuery Star Rating Plugin
		wp_register_script('jquery_starrating'
			, WP_PLUGIN_URL . '/photosmash-galleries/js/star.rating.js'
			, array('jquery'), '1.0');
		wp_enqueue_script('jquery_starrating');
		
		if($this->psOptions['use_customfields']){
			//enqueue jQuery DatePicker
			wp_register_script('jquery_datepicker'
				, WP_PLUGIN_URL . '/photosmash-galleries/js/ui.datepicker.js'
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
	<link rel="stylesheet" href="<?php echo WP_PLUGIN_URL;?>/photosmash-galleries/css/rating.css" type="text/css" media="screen" />
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
	var bwbpsAjaxURL = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax.php";
	var bwbpsAjaxUserURL = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax_useractions.php";
	var bwbpsAjaxRateImage = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax_rateimage.php";
	var bwbpsAjaxUpload = "<?php 
		echo WP_PLUGIN_URL."/photosmash-galleries/ajax_upload.php";
		?>";
	var bwbpsImagesURL = "<?php echo PSIMAGESURL; ?>";
	var bwbpsThumbsURL = "<?php echo PSTHUMBSURL; ?>";
	var bwbpsPhotoSmashURL = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/";
	var bwbpsBlogURL = "<?php echo PSBLOGURL; ?>";
	
	function bwbpsAlternateUploadFunction(data, statusText, form_pfx){
		
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
		wp_enqueue_script( 'jquery-ui-tabs' );
		
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

		$gallery_id = $wpdb->get_var($wpdb->prepare("SELECT gallery_id FROM ". PSGALLERIESTABLE ." WHERE gallery_handle = %s", 'post-'.$post_id));
		
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
		if(!is_array($atts)){
			$atts = array();
		}
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
			'alt' => false,
			'author' => false
		),$atts));
		
		
		
		if($img_key || $id || $img_id || $image_id){
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
					$g = array('gallery_id' => $this->images[$img_key]['gallery_id']);
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
					.PSGALLERIESTABLE.".img_rel, "
					.$wpdb->users.".user_nicename,"
					.$wpdb->users.".display_name,"
					.$wpdb->users.".user_login,"
					.$wpdb->users.".user_url". $custdata 
					." FROM ".PSIMAGESTABLE
					." LEFT OUTER JOIN ".PSGALLERIESTABLE." ON "
					.  PSGALLERIESTABLE.".gallery_id = ".PSIMAGESTABLE
					.".gallery_id ".$custDataJoin. " LEFT OUTER JOIN ".$wpdb->users." ON "
				.$wpdb->users.".ID = ". PSIMAGESTABLE.".user_id WHERE ".PSIMAGESTABLE
					.".image_id = %d", $image_id);
			
		} else {
			//Non-Admins can see their own images and Approved images
			$uid = $user_ID ? $user_ID : -1;
			
			$sql = $wpdb->prepare("SELECT ".PSIMAGESTABLE.".*, ".PSGALLERIESTABLE.".img_class,"
					.PSGALLERIESTABLE.".img_rel, "
					.$wpdb->users.".user_nicename,"
					.$wpdb->users.".display_name,"
					.$wpdb->users.".user_login,"
					.$wpdb->users.".user_url". $custdata 
					." FROM ".PSIMAGESTABLE
					." LEFT OUTER JOIN ".PSGALLERIESTABLE." ON "
					.PSGALLERIESTABLE.".gallery_id = ".PSIMAGESTABLE
					.".gallery_id ". $custDataJoin ." LEFT OUTER JOIN ".$wpdb->users." ON "
				.$wpdb->users.".ID = ". PSIMAGESTABLE.".user_id WHERE ".PSIMAGESTABLE
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
	
	function verifyDatabase(){
		global $wpdb;
		
		if(isset($_REQUEST['bwbpsRunDBUpdate'])){ return; }
			
		$sql = "SELECT * FROM ".PHOTOSMASHVERIFYTABLE." LIMIT 1";
	
		$ret = $wpdb->get_row($sql);
		
		if(! $ret ){
				echo "<div class='message error'><h2>PhotoSmash Database - Needs to be Updated</h2><p>Your PhotoSmash database is missing field(s) due to an update of the Plugin. This will prevent it from operating properly.  Click <a href='admin.php?page=psInfo&amp;bwbpsRunDBUpdate=1'>here</a> to Update the DB and view Plugin Info.</p></div>";
			return;
		}
	
		//Field to be checked against database
		$col = PHOTOSMASHVERIFYFIELD;
	
		foreach($wpdb->get_col_info('name') as $name){
			$colname[] = $name;
		}
		
		if(! in_array($col, $colname) ){
				echo "<div class='message error'><h2>PhotoSmash Database needs to be Updated</h2><p>Your PhotoSmash database is missing field(s) due to an update of the Plugin. This will prevent it from operating properly.  Click <a href='admin.php?page=psInfo&amp;bwbpsRunDBUpdate=1'>here</a> to Update the DB and view Plugin Info.</p></div>";
		}
						
		
		if($msg){$this->message = $msg. $this->message; $this->msgclass = 'error';}
		
		return;
	}
	
	function displayContributorGallery($theposts){
			
		if(is_author()){
						
			global $wp_rewrite;
			
			$author = (int) get_query_var( 'author' );
			
			$author_name = get_the_author_meta(  'user_nicename', $author );
			
			$authorpg = $wp_rewrite->author_base ."/" . $author_name;
			
			
			$d = date( 'Y-m-d H:i:s' );
			
			//Create an objec for a new post to un_shift onto the posts array
				$newpost->ID = -1;
				$newpost->post_author = $author;
				$newpost->post_date = $d;
				$newpost->post_date_gmt = $d;
				$newpost->post_content = "[photosmash gallery_type=contributor author=".$author
					." no_form=true]";
				$newpost->post_title = 'Images by ' . $author_name; 
				$newpost->post_category = 0;
				$newpost->post_excerpt = '';
				$newpost->post_status = 'publish';
				$newpost->comment_status = 'closed';
				$newpost->ping_status = 'closed';
				$newpost->post_password = '';
				$newpost->post_name = $authorpg;
				$newpost->to_ping = '';
				$newpost->pinged = '';
				$newpost->post_modified = $d;
				$newpost->post_modified_gmt = $d;
				$newpost->post_content_filtered = '';
				$newpost->post_parent = 0;
				$newpost->guid = '';
				$newpost->menu_order = 0;
				$newpost->post_type = 'post';
				$newpost->post_mime_type = '';
				$newpost->comment_count = 0;
				$newpost->photosmash = 'author';
				
			if( $this->psOptions['suppress_contrib_posts'] ){
				
				unset($theposts);
				$theposts = array($newpost);
				
			} else {
				
				array_unshift( $theposts, $newpost );
				
			}
		}	
		
		return $theposts;
	
	}
	
	function getContributorPost($author, $author_name){
		global $wpdb;
			
		
				
		$post_name = sanitize_title("Images by $author_name psmash");
		
		$data = array(
			"author" => $author,
			"name"	=>	$post_name
		);
		
		$thepost = $wpdb->get_row($wpdb->prepare("SELECT * FROM " 
			.$wpdb->posts . " WHERE post_author = %d AND post_name = %s "
			, $author, $post_name));
			
			
			
		if( !$thepost ){

			$post_content = "[photosmash gallery_type=contributor author=".$author
					." no_form=true]";
					
			$post = array (
				"post_author"	=> $author,
				"post_type" => 'page',
				"post_title"     => "Images by $author_name",
				"comment_status" => "open",
				"post_name"      => $post_name,
				"post_status"    => 'publish',
				"post_content" => $post_content,
				"post_category"  => array(0)
	          );
	          
	          $post_id = wp_insert_post($post);
	          
	          if($post_id){
	
		          $thepost = $wpdb->get_row($wpdb->prepare("SELECT * FROM " 
					.$wpdb->posts . " WHERE ID = %d "
					, $post_id));
		          
		      }
		}
		
		if( $thepost ){
			
			$thepost->status = 'publish';
		
		}
		
		return $thepost;
	}
	
	
	function fixExcerptGallery($excerpt){
		global $post;
		
		if($post->photosmash == 'author') {		
			the_content();
			return "";
		} else {
			return $excerpt;
		}	
	}
	
	
	
	
} //End of BWB_PhotoSmash Class


/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */

$bwbPS = new BWB_PhotoSmash();

//
function bwbps_contributor_gallery(){
	//do_action('bwbps_contributor_gallery');
}


add_action('admin_notices', array(&$bwbPS, 'verifyDatabase'));

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