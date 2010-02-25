<?php
/*
Plugin Name: PhotoSmash
Plugin URI: http://smashly.net/photosmash-galleries/
Description: PhotoSmash - user contributable photo galleries for WordPress pages and posts.  Focuses on ease of use, flexibility, and moxie. Deep functionality for developers. PhotoSmash is licensed under the GPL.
Version: 0.5.06
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
 *
 * Additional terms as provided by the GPL: if you use this
 * code, in part or in whole, you must attribute the work to the
 * copyright holder.
 * 
*/

//VERSION - Update PhotoSmash Extend!!!
define('PHOTOSMASHVERSION', '0.5.05');
define('PHOTOSMASHEXTVERSION', '0.2.00');


//Database Verifications
define('PHOTOSMASHVERIFYTABLE', $wpdb->prefix.'bwbps_categories');
define('PHOTOSMASHVERIFYFIELD', 'tag_name');

//Set Database Table Constants
define("PSGALLERIESTABLE", $wpdb->prefix."bwbps_galleries");

define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");
define("PSCATEGORIESTABLE", $wpdb->prefix."bwbps_categories");

define("PSLAYOUTSTABLE", $wpdb->prefix."bwbps_layouts");
define("PSFORMSTABLE", $wpdb->prefix."bwbps_forms");
define("PSFIELDSTABLE", $wpdb->prefix."bwbps_fields");
define("PSLOOKUPTABLE", $wpdb->prefix."bwbps_lookup");


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
		
$bwbps_special_msg = "";
$bwbps_preview_id = 0;

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
}
if( ! function_exists('esc_attr') ){
	function esc_attr($string){
		
		if( function_exists('attribute_escape') ){
			return attribute_escape($string);
		} else {
			return false;
		}
	
	}
}

if( ! function_exists('esc_attr_e') ){
	function esc_attr_e($string){
		
		if( function_exists('attribute_escape') ){
			echo attribute_escape($string);
		} 
	
	}
}

if( ! function_exists('esc_html__') ){

	function esc_html__($string){
		
		if( function_exists('wp_specialchars') ){
			return wp_specialchars($string);
		} else {
			return esc_attr__($string);;
		}
	
	}
}

if( ! function_exists('esc_html') ){

	function esc_html($string){
		
		if( function_exists('wp_specialchars') ){
			return wp_specialchars($string);
		} else {
			return esc_attr($string);;
		}
	
	}
}

if( ! function_exists('esc_html_e') ){
	function esc_html_e($string){
		
		if( function_exists('wp_specialchars') ){
			echo wp_specialchars($string);
		} else {
			esc_attr_e($string);
		}
	
	}
}


class BWB_PhotoSmash{

	var $customFormVersion = 20;  //Increment this to force PS to update the Custom Fields Option
	var $adminOptionsName = "BWBPhotosmashAdminOptions";
	
	var $uploadFormCount;
	var $manualFormCount = 0;
	var $loadedGalleries;
	var $moderateNonceCount = 0;
	
	var $uploads; 	//WP uploads folder array info on uploads folder
	
	var $psAdmin;  //Admin object
	var $psImporter;	//Importer object
	
		
	var $psOptions;
	var $psLayout;
	
	var $psForm;
	
	var $shortCoded;
	var $stdFieldList;
	var $cfList;
	
	var $galleries;
	
	var $images;
	
	var $footerJS = ""; // Load this up with Javascript...PS uses wp_footer hook to put Javascript in footer
	var $footerReady = "";
	
	//Constructor
	function BWB_PhotoSmash(){
		
		$this->uploads = wp_upload_dir();
				
		$this->psOptions = $this->getPSOptions();
		
		
		
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
		
		//Add actions for Contributor Gallery
		if( $this->psOptions['contrib_gal_on'] ){
			add_filter('the_posts',  array(&$this,'displayContributorGallery') ); 
		}
		
		add_filter('the_permalink',array(&$this,'fixSpecialGalleryLinks') );
		
		//Add action for Tags Gallery
		add_filter('the_posts', array(&$this, 'displayTagGallery') );

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
	function getPSOptions()
	{
		$runUpdate = false;
		$psOptions = get_option($this->adminOptionsName);
		if($psOptions && !empty($psOptions))
		{
			//Options were found..add them to our return variable array
			foreach ( $psOptions as $key => $option ){
				$psAdminOptions[$key] = $option;
			}
		}else{
		
			$psAdminOptions = $this->getPSDefaultOptions();
			
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
	
	
	function getPSDefaultOptions(){
		
		//get some defaults if nothing is in the database
		return array(
				'auto_add' => 0,
				'img_perpage' => 0,
				'img_perrow' => 0,
				'use_wp_upload_functions' => 1,
				'add_to_wp_media_library' => 1,
				'thumb_aspect' => 0,
				'thumb_width' => 125,
				'thumb_height' => 125,
				'medium_aspect' => 0,
				'medium_width' => 300,
				'medium_height' => 300,
				'image_aspect' => 0,
				'image_width' => 0,
				'image_height' => 0,
				'anchor_class' => '',
				'img_rel' => 'lightbox[album]',
				'add_text' => 'Add Photo',
				'gallery_caption' => 'PhotoSmash Gallery',
				'upload_form_caption' => 'Select an image to upload:',
				'img_class' => 'ps_images',
				'show_caption' => 1,
				'nofollow_caption' => 1,
				'alert_all_uploads' => 0,
				'img_alerts' => 3600,
				'show_imgcaption' => 1,
				'contrib_role' => 10,
				'img_status' => 0,
				'last_alert' => 0,
				'use_advanced' => 0,
				'use_urlfield' => 0,
				'use_attribution' => 0,
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
				'mod_send_msg' => 0,
				'mod_approve_msg' => "Thanks for submitting your image to [blogname]! It has been accepted and is now visible in the appropriate galleries.",
				'mod_reject_message' => "Sorry, the image you submitted to [blogname] has been reviewed, but did not meet our submission guidelines.  Please review our guidelines to see what types of images we accept.  We look forward to your future submissions.",
				'version' => PHOTOSMASHVERSION,
				'tb_height' => 390,
				'tb_width' => 545
		);
	
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
			'img_attribution',
			'img_license',
			'category_name',
			'category_link',
			'category_id',
			'post_id',
			'allow_no_image',
			'post_cat',
			'post_cat1',
			'post_cat2',
			'post_cat3',
			'post_tags',
			'bloginfo',
			'plugin_url'
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
			
			add_submenu_page(basename(__FILE__), __('Image Importer'), __('Import Photos'), 9,  
			'importPSImages', array(&$bwbPS, 'loadImageImporter'));
			
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
	
	function loadImageImporter(){
	
		if(!$this->psImporter){
			require_once("admin/bwbps-importer.php");
			$this->psImporter = new BWBPS_Importer($this->psOptions);
		}
		$this->psImporter->printImageImporter();
		
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


/*
 *	ShortCode Handler for Galleries
 *	
 *	param:	$atts - the array of attributes
 *	param:	$content - for dealing with enclosing shortcodes 
 *		like: [photosmash]contents go here[/photosmash]
 *	Note: can be used to return a completed gallery...just supply the proper $atts array
*/
function shortCodeGallery($atts, $content=null){
		global $post;
		
		$this->checkEmailAlerts();
		
		if(!is_array($atts)){
			$atts = array();
		}
		
		if(!isset($atts['id']) && isset($atts[0]) && $atts[0] )
		{
			$maybeid = str_replace("=", "", trim($atts[0]));
			//Backwards compatibility with old shortcodes like: [photosmash=9]
			if(is_numeric($maybeid)){$atts['id'] = (int)$maybeid;}
		}
				
		extract(shortcode_atts(array(
			'id' => false,
			'form' => false,
			'no_gallery_header' => false,
			'form_alone' => false,
			'no_gallery' => false,
			'gallery_type' => false,
			'no_form' => false,
			'gallery' => false,
			'gal_id' => false,
			'image_id' => false,			// NOTE: when referencing image_id from the DB results, use: psimageID   ...image_id is aliased!
			'field' => false,
			'if_before' => false,
			'if_after' => false,
			'layout' => false,
			'thickbox' => false,
			'form_visible' => false,
			'single_layout' => false,
			'author' => false,
			'tags' => false,
			'tags_for_uploads' => false,
			'images' => 0,
			'thumb_height' => 0,
			'thumb_width' => 0,
			'no_signin_msg' => false,
			'where_gallery' => false, // This is used with Random/Recent Galleries to limit selection to a single gallery
			'create_post' => false,	// Give a Custom Layout name to turn on creating new posts with PExt
			'preview_post' => false,
			'cat_layout' => false,	// Give a prefix to be used with the first Category ID to determine what layout should be used...it will default back to the layout specified in create_post if Cat Layout is not found...e.g.  cat_layout='postcat' ...it will look for a custom layout called postcat_##  where ## is the id of the first category in the upload
			'post_cat_child_of' => false,
			'post_cat_exclude' => false,
			'post_cat_show' => false,	// Supply this with a value that evaluates to true (e.g. something other than 0 or false or '') and it turns on the post categories selection box and is used as the LABEL for the field
			'post_cat_depth' => 0,
			'post_cat_selected' => false,
			'post_cat_single_select' => false,	// make this 0 to turn off multi-select
			'post_thumbnail_meta' => false,	// use this as the name of the post meta (custom field) for post thumbnail
			'post_tags' => false,	// whether or not to show an input box for tags (comma separated)
			'post_tags_label' => false,
			'post_excerpt_field' => false,	// For use with PExtend - will use this field to create an excerpt when creating new post
			'tags_has_all' => false,	// Enter true to display only images have all tags
			'piclens' => false, 	// Enter true to include a link to a piclens slideshow
			'piclens_link' => '',	// Defaults to "Start Slideshow " with a little icon
			'piclens_class' => ''	// Defaults to "alignright"
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
		$galparms['gallery_id'] = (int)$id;
		$galparms['photosmash'] = $galparms['gallery_id'];
		
		//Figure out Tags
		$tags = html_entity_decode($tags, ENT_QUOTES);
		

		//Was an Extended Nav form submitted (PhotoSmash Extend)
		if( isset($_POST['bwbps_photo_tag']) && !get_query_var( 'bwbps_wp_tag' )){
			if(!isset($_POST['bwbps_extnav_gal']) || 
				((int)$_POST['bwbps_extnav_gal'] == $galparms['gallery_id']))
			{
				$tags = $this->getRequestedTags($tags);	
			}	
		}

		
		if($tags){$gallery_type = 'tags';}
		
		switch ( $gallery_type ){
			
			case 'contributor' :
				
				$id = 0;
				$galparms['gallery_type'] = 10;	
				
				$galparms['gallery_name'] = 'Contributor Gallery';
			
				if($author){
				
					$galparms['author'] == $author;
					$galparms['smart_where'] = array ( PSIMAGESTABLE . ".user_id" => array($author) );
				
				}
				
				$galparms['smart_gallery'] = true;
						
				break;
				
			case 'random' :
				$galparms['gallery_type'] = 20;	

				break;
				
			case 'recent' :
				$galparms['gallery_type'] = 30;	
				
				break;
				
			case 'ranked' :
				$galparms['gallery_type'] = 99;	
				$galparms['sort_field'] = 4;
				$galparms['sort_order'] = 1;
													
				break;
				
			case 'tags' :
				$galparms['gallery_type'] = 40;	
				
				break;
				
			default :
			
				break;	
		
		}	
		
		
		$galparms['no_signin_msg'] = $no_signin_msg;	//used with $psOptions['upload_authmessage'] to not show signin message if this is true in shortcode
	
		//Get Gallery	
		$g = $this->getGallery($galparms);	//Get the Gallery params
		
		//Set up for a Tag driven gallery
			$g['tags'] = $tags ? $tags : false;
		
		//Set
		if($tags_for_uploads){ $g['tags_for_uploads'] = $tags_for_uploads; }
		
		//PhotoSmash Extend Variables used in Post on Upload (creating new Posts on Uploads)
		$g['create_post'] = $create_post;
		$g['preview_post'] = $preview_post;
		$g['cat_layout'] = $cat_layout;
		$g['post_cat_child_of'] = $post_cat_child_of;
		$g['post_cat_exclude'] = $post_cat_exclude;
		$g['post_cat_show'] = $post_cat_show;
		$g['post_cat_depth'] = $post_cat_depth;
		$g['post_cat_selected'] = $post_cat_selected;
		$g['post_thumbnail_meta'] = $post_thumbnail_meta;
		$g['post_tags'] = $post_tags;
		$g['tags_has_all'] = $tags_has_all;
		$g['post_tags_label'] = $post_tags_label;
		$g['post_excerpt_field'] = $post_excerpt_field;
		$g['no_gallery_header'] = $no_gallery_header;
		$g['piclens'] = $piclens;
		$g['piclens_link'] = $piclens_link;
		$g['piclens_class'] = $piclens_class;
		
		if( isset($_POST['bwbps_tags_has_all'] )){ $g['tags_has_all'] = true; }
		
		/*
		 *	Random/Recent/Highest Ranked Gallery settings
		*/
		if($g['gallery_type'] == 20 || $g['gallery_type'] == 30 || $g['gallery_type'] == 99){
			
			$g['smart_gallery'] = true;
									
			if(!$images){
				$images = 8;
			}
					
			//removed:  && !$g['gallery_type'] == 99
			if((int)$where_gallery ){
				$g['smart_where'] = array ( PSIMAGESTABLE.".gallery_id" => (int)$where_gallery );
			}
			
			$no_form = true;
			
		}
		
		$g['limit_images'] = (int)$images;
		
		if($thumb_height){
			$g['thumb_height'] = (int)$thumb_height;
		}
		
		if($thumb_width){
			$g['thumb_width'] = (int)$thumb_width;
		}
				
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
				
					if(trim($this->psOptions['upload_authmessage'] && !$g['no_signin_msg'])){
						
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
			, $this->loadedGalleries) && !$g['gallery_type'] == 20){

			//Bad Gallery ID was provided.	
			$ret = "Duplicate gallery: " . $g['photosmash']; 
				
		}else{
								
			if($layout){
				$layoutName = strtolower(trim($layout));
				
				if($layoutName == "std" || $layoutName == "standard"){
					$layoutName = false;
					$g['layout_id'] = 0;
				}
				
			} else { $layoutName = false; }
				
			
			$this->loadedGalleries[] = $post->ID."-".$g['gallery_id'];
			$ret .= $this->buildGallery($g, $skipForm, $layoutName, $formName );
			
			if(!$g['no_gallery_header']){			
				$ret .= "
					<script type='text/javascript'>
						displayedGalleries += '|".$g['gallery_id']."';
					</script>
				";
			}
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
	if($g['gallery_id'] && is_array($this->galleries) && array_key_exists($g['gallery_id'], $this->galleries) && $g['gallery_type'] <> 10 && $g['gallery_type'] <> 20 && $g['gallery_type'] <> 30)
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
		switch ((int)$g['gallery_type']) {
			
			case 10 :
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
				
				break;
			
			case 20 : // Random images
				$gquery = false;
				if($g['gallery_id']){
									
					$gquery = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
							." WHERE gallery_id = %d AND gallery_type = 20",$g['gallery_id']),ARRAY_A);
				
				}
				
				if( !$gquery ){
				
					$gquery = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
							." WHERE gallery_type = 20 AND status = 1 "),ARRAY_A);
				
				}
				
				if( !$gquery ){
		
					$g['gallery_name'] = 'Random Images';
				
				}
				
				break;
			
			case 30 : // Recent images
				$gquery = false;
				if($g['gallery_id']){
									
					$gquery = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
							." WHERE gallery_id = %d AND gallery_type = 30",$g['gallery_id']),ARRAY_A);
				
				}
				
				if( !$gquery ){
				
					$gquery = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
							." WHERE gallery_type = 30 AND status = 1 "),ARRAY_A);
				
				}
				
				if( !$gquery ){
		
					$g['gallery_name'] = 'Recent Images';
				
				}
				
				break;
			
			case 40 :
				$gquery = false;
				if($g['gallery_id']){
									
					$gquery = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
							." WHERE gallery_id = %d ",$g['gallery_id']),ARRAY_A);
				
				}
				
				if( !$gquery ){
				
					$gquery = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
							." WHERE gallery_type = 40 AND status = 1 "),ARRAY_A);
				
				}
				
				if( !$gquery ){
		
					$g['gallery_name'] = 'Tag Gallery';
					$g['show_imgcaption'] = 12;
					$g['gallery_type'] = 40;
				
				} else {
					$gquery['gallery_type'] = 40;
				}
				
				break;
				
			case 99 : // Recent images
				$gquery = false;
				if($g['gallery_id']){
									
					$gquery = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
							." WHERE gallery_id = %d AND gallery_type = 99",$g['gallery_id']),ARRAY_A);
				
				}
				
				if( !$gquery ){
				
					$gquery = $wpdb->get_row(
						$wpdb->prepare("SELECT * FROM ". PSGALLERIESTABLE
							." WHERE gallery_type = 99 AND status = 1 "),ARRAY_A);
				
				}
				
				if( !$gquery ){
		
					$g['gallery_name'] = 'Highest Ranked';
					$g['sort_field'] = 4;
					$g['sort_order'] = 1;
					$g['rating_position'] = 0;
					$g['poll_id'] = -1;
				
				}
				
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
			if((int)$g['gallery_type'] < 10 ){
				$data['post_id'] = $post->ID;
			}
			$data['gallery_name'] = $g['gallery_name'] ? $g['gallery_name']  : $post->post_title;
			$data['gallery_type'] = isset($g['gallery_type']) ? (int)$g['gallery_type'] : 0;
			$data['caption'] =  $g['caption'] ? $g['caption'] : $psoptions['gallery_caption'];
			
			$data['rating_position'] =  isset($g['rating_position']) ? (int)$g['rating_position'] : (int)$psoptions['rating_position'];
			$data['poll_id'] =  isset($g['poll_id']) ? (int)$g['poll_id'] : (int)$psoptions['poll_id'];
						
			$data['add_text'] = $g['add_text'] ? $g['add_text'] : 
				( $psoptions['add_text'] ? $psoptions['add_text'] : "Add Photos" );
			
			$data['upload_form_caption'] =  $g['upload_form_caption'] ? $g['upload_form_caption'] : $psoptions['upload_form_caption'];
			$data['contrib_role'] =  isset($g['contrib_role']) ? (int)$g['contrib_role'] : $psoptions['contrib_role'];
			$data['img_rel'] =  $g['img_rel'] ? $g['img_rel'] : $psoptions['img_rel'];
			$data['img_class'] =  $g['img_class'] ? $g['img_class'] : $psoptions['img_class'];
			
			$data['anchor_class'] =  $g['anchor_class'] ? $g['anchor_class'] : $psoptions['anchor_class'];
			
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
			
			$data['medium_aspect'] = isset($g['medium_aspect']) ? (int)$g['medium_aspect'] : (int)$psoptions['medium_aspect'];
			$data['medium_width'] = isset($g['medium_width']) ? (int)$g['medium_width'] : (int)$psoptions['medium_width'];
			$data['medium_height'] =  isset($g['medium_height']) ? (int)$g['medium_height'] : (int)$psoptions['medium_height'];
			
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
		$this->psOptions['tb_height'] = (int)$this->psOptions['tb_height'] ? (int)$this->psOptions['tb_height'] : 390;
		$this->psOptions['tb_width'] = (int)$this->psOptions['tb_width'] ? (int)$this->psOptions['tb_width'] : 545;
	
		$ret = '<span class="bwbps_addphoto_link"><a href="TB_inline?height='
			. $this->psOptions['tb_height'] .'&amp;width=' 
			. $this->psOptions['tb_width']. '&amp;inlineId='.$g["pfx"].'bwbps-formcont" onclick="bwbpsShowPhotoUpload('.(int)$g["gallery_id"].', '.(int)$post->ID.', \''.$g["pfx"].'\');" title="'.$blogname.' - Gallery Upload" class="thickbox">'.$g['add_text'].'</a></span>';
	
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
	
	if(!$g['no_gallery_header']){
		$ret = '<div class="photosmash_gallery">';
	}
			
	if($this->moderateNonceCount < 1 && $admin && !$g['no_gallery_header'])
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
	
	// Add PicLens link if needed
	if( $g['piclens'] ){
		$atts['link_text'] = $g['piclens_link'];
		$class = $g['piclens_class'] ? $g['piclens_class'] : 'alignright';
		
		$ret = "<div class='bwbps-piclens-link $class'>" . $this->getPicLensLink($g, $atts) . "</div>" . $ret . "<div style='clear: both;'></div>";
	}
	
	if(!isset($this->psLayout)){
		require_once('bwbps-layout.php');
		$this->psLayout = new BWBPS_Layout($this->psOptions, $this->cfList);
	}

	$ret .=	$this->psLayout->getGallery($g, $layoutName);
	if(!$g['no_gallery_header']){
		$ret .= "</div>
			<div class='bwbps_clear'></div>";
	}
	
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
	
		$this->addFooterJS('
			var tb_pathToImage = "'. get_bloginfo('wpurl') . '/' . WPINC .'/js/thickbox/loadingAnimation.gif";
			var tb_closeImage = "'. get_bloginfo('wpurl') . '/' . WPINC .'/js/thickbox/tb-close.png";
		');
	
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
	
	<link rel="alternate" href="<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/bwbps-media-rss.php" type="application/rss+xml" title="" id="gallery" />

	<?php if( !$this->psOptions['exclude_piclens_js'] ) { ?>
      <script type="text/javascript" 
    src="http://lite.piclens.com/current/piclens_optimized.js"></script>
	<?php } ?>
	
    <script type="text/javascript">
	var displayedGalleries = "";
	var bwbpsAjaxURL = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax.php";
	var bwbpsAjaxUserURL = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax_useractions.php";
	var bwbpsAjaxRateImage = "<?php echo WP_PLUGIN_URL; ?>/photosmash-galleries/ajax_rateimage.php";
	var bwbpsAjaxUpload = "<?php 
		if( $this->psOptions['use_wp_upload_functions'] ){
			echo WP_PLUGIN_URL."/photosmash-galleries/ajax-wp-upload.php";
		} else {
			echo WP_PLUGIN_URL."/photosmash-galleries/ajax_upload.php";
		}
		?>";
	var bwbpsImagesURL = "<?php echo PSIMAGESURL; ?>";
	var bwbpsThumbsURL = "<?php echo PSTHUMBSURL; ?>";
	var bwbpsUploadsURL = "<?php echo $this->uploads['baseurl'] . "/"; ?>";
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
		wp_enqueue_script( 'thickbox' );
		
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
		wp_enqueue_style( 'bwbpsuicore', WP_PLUGIN_URL.'/photosmash-galleries/css/ui.core.css', false, '1.0', 'screen' );		
		wp_enqueue_style( 'bwbpsdatepicker', WP_PLUGIN_URL.'/photosmash-galleries/css/ui.datepicker.css', false, '1.0', 'screen' );
		wp_enqueue_style('thickbox');
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
			'if_before' => false,
			'if_after' => false,
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
		
		if( $if_before && $ret ){ $ret = $if_before . $ret; }
		if( $if_after && $ret ){ $ret = $if_after . $ret; }
		
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
					
		$sql = "SHOW COLUMNS FROM ".PHOTOSMASHVERIFYTABLE . " LIKE '". PHOTOSMASHVERIFYFIELD ."'";
	
		$ret = $wpdb->get_results($sql);
		
		if(! $ret ){
				echo "<div class='message error'><h2>PhotoSmash Database - Needs to be Updated</h2>"
				. "<p>The PhotoSmash database is <b>missing field: "
				. PHOTOSMASHVERIFYFIELD. "</b> in <b>table: " . PHOTOSMASHVERIFYTABLE 
				. "</b>.</p><p> Update required. Click <a href='admin.php?page=psInfo&amp;bwbpsRunDBUpdate=1'>here</a> to Update the DB and view Plugin Info.</p></div>";
			return;
		}
	
		//Field to be checked against database
		$col = PHOTOSMASHVERIFYFIELD;
	
		foreach($ret as $fld){

			if( PHOTOSMASHVERIFYFIELD == $fld->Field ){
				$field_found = true;	
			}
			
		}
		
		if(! $field_found ){
				echo "<div class='message error'><h2>PhotoSmash Database needs to be Updated</h2><p>Your PhotoSmash database is missing field(s) due to an update of the Plugin. This will prevent it from operating properly.  Click <a href='admin.php?page=psInfo&amp;bwbpsRunDBUpdate=1'>here</a> to Update the DB and view Plugin Info.</p></div>";
		}
						
		
		if(isset($msg) && $msg){$this->message = $msg. $this->message; $this->msgclass = 'error';}
		
		return;
	}
	
	function displayTagGallery($theposts){
			
		
		if(!get_query_var( 'bwbps_wp_tag' )){ return $theposts; } //leave if this isn't the tag page
		
		$tag = $this->getRequestedTags();
		
		add_filter('the_excerpt',array(&$this,'fixExcerptGallery') );
		
		$d = date( 'Y-m-d H:i:s' );
		
		//Create an object for a new post to un_shift onto the posts array
			$newpost->ID = -1;
			$newpost->post_author = $author;
			$newpost->post_date = $d;
			$newpost->post_date_gmt = $d;
			$newpost->post_content = "[photosmash gallery_type=tags tags='". esc_attr($tag)
				."' no_form=true]";
			$newpost->post_title = 'Images tagged ' . $tag; 
			$newpost->post_category = 0;
			$newpost->post_excerpt = '';
			$newpost->post_status = 'publish';
			$newpost->comment_status = 'closed';
			$newpost->ping_status = 'closed';
			$newpost->post_password = '';
			$newpost->post_name = 'photo-tag/' . $tag;
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
			$newpost->photosmash = 'tag';
			$newpost->photosmash_link = $this->getTagContextUrl();			
			
		unset($theposts);
		$theposts = array($newpost);
				
		return $theposts;
	}
	
	function getFormSubmittedTags(){
	
		if(isset($_POST['bwbps_photo_tag'])){
	
			if(is_array($_POST['bwbps_photo_tag'])){
				foreach($_POST['bwbps_photo_tag'] as $posttag){
					if($posttag){
						$posttags[] = $posttag;
					}
				}
			} else {
				$posttags = explode(',', $_POST['bwbps_photo_tag']);
			}
		
		}
		
		return $posttags;
	
	}
	
	function getRequestedTags($tags=""){
	
		$qtags = get_query_var( 'bwbps_wp_tag' );
	
		$qtags = str_replace("'","",$qtags);
		
		$qtags = explode(",", $qtags);
		
		// Get tags submitted by Form...and merge with $qtags array
		$formtags = $this->getFormSubmittedTags();
		if(!empty($formtags) && is_array($formtags)){
			$qtags = array_merge($qtags, $formtags);
		}
		
		// Get tags passed in and merge with $qtags
		if($tags){
			$tags = str_replace("'","",$tags);
			$tags = explode(",", $tags);
			$qtags = array_merge($qtags, $tags);
		}
		
		$qtags = array_map("esc_sql", $qtags);
		
		$qtags = implode("','", $qtags);
		
		global $wpdb;
		unset($tags);
		$tags = $wpdb->get_col($wpdb->prepare("SELECT name FROM " . $wpdb->terms . " WHERE slug IN ('" . $qtags . "')"));
		
		$tags = implode(",", $tags);
		
		return $tags;
	
	}

	function getTagContextUrl(){
		global $wp_query;
		global $wpdb;
		
		
		$url = '';
		
		$tag_name = $wp_query->query_vars['bwbps_wp_tag'];
				
		$url = get_term_link($tag_name, 'photosmash');
				
		if( !$url ){
			$url = get_bloginfo('url');
		}

		return $url;
	}
	
	function displayContributorGallery($theposts){
			
		if(is_author()){
			add_filter('the_excerpt',array(&$this,'fixExcerptGallery') );												
			$author = (int) get_query_var( 'author' );
			
			$author_name = get_the_author_meta(  'user_nicename', $author );
			
			$authorpg = get_author_posts_url($author);				
			
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
				$newpost->post_name = $author;
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
				$newpost->photosmash_link = $auhtorpg;
				
				
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
		
		if($post->photosmash == 'author' || $post->photosmash == 'tag' ) {		
			the_content();
			return "";
		} else {
			return $excerpt;
		}	
	}
	
	function fixSpecialGalleryLinks($perma){
		global $post;
		
		if(!isset($post->photosmash)){ return $perma; }
				
		switch ($post->photosmash) {
		
			case "author" :
				
				return get_author_posts_url($post->post_name);
				break;
			
			case "tag" :
				return $post->photosmash_link;
				break;
			
			default :
				return $perma;
		}

	}
	
	
	/**
	 * Injects JavaScript into the Footer - called by PhotoSmash through wp_footer hook
	 * To use, just...global the $bwbPS object and add to either $bwbPS->addFooterJS($js) 
	 * or $bwbPS->addFooterReady($js);
	 */
	function injectFooterJavaScript(){
	
		if(!$this->footerJS && !$this->footerReady){ return; }
	
		$ret = "
		<!-- PhotoSmash JavaScript  -->
		<script type='text/javascript'>
		". $this->footerJS."
		
		";
		
		if($this->footerReady){
		
			$ret .= "
			// On Document Ready
			jQuery(document).ready(function() {
			". $this->footerReady . "
			
			});
			";
		
		}
			
		$ret .="
		</script>
		";
		
		echo $ret;
	
	}
	
	/**
	 * Adds JavaScript that will be inserted into the Footer wrapped in Script tags
	 * 
	 */
	function addFooterJS($js){
		
		if($js){
		
			$this->footerJS .= "
			
			".$js;
		
		}
	}

	/**
	 * Adds JavaScript that will be inserted into the Footer wrapped 
	 * in Script tags and jQuery(document).ready() function
	 * 
	 */
	function addFooterReady($js){
		if($js){
		
			$this->footerReady .= "
			
			".$js;
		
		}
	}
	
	/**
	 * Load the PhotoSmash WIDGET
	 *
	 */
	function loadPSWidgets(){
		require_once('widgets/bwbps-widget.php');
		register_widget( 'PhotoSmash_Widget' );
		
		require_once('widgets/bwbps-tagcloud.php');
		register_widget( 'PhotoSmash_TagCloud' );
	
	}
	
	/**
	 *	Create the Photo Tags taxonomy
	 *
	 */
	 function createTaxonomy(){
	 	register_taxonomy( 'photosmash', 'post', array( 'hierarchical' => false, 'label' => __('Photo tags', 'series'), 'query_var' => 'bwbps_wp_tag', 'rewrite' => array( 'slug' => 'photo-tag' ) ) );	
	 }
	 
	 /**
	  *	Get Random Tag with Count >= x
	  *
	  */
	 function getRandomTag($min_count=0){
	 	
	 	global $wpdb;
		$min_count = (int)$min_count - 1;
	 	$sql = "SELECT name FROM $wpdb->terms JOIN " 
	 		. $wpdb->term_taxonomy . " ON " . $wpdb->terms . ".term_id = "
	 		. $wpdb->term_taxonomy . ".term_id "
	 		. "WHERE " . $wpdb->term_taxonomy . ".taxonomy = 'photosmash' AND "
	 		. $wpdb->term_taxonomy . ".count > " . $min_count . " ORDER BY RAND(); ";
	 	
	 	return $wpdb->get_var($sql);
	 	
	 }
	 
	 /*
	  * Get 
	  */
	 
	 function filterMRSSAttsFromArray($atts, $apostrophe=""){
	 	
	 	if(!is_array($atts)){ return array(); }
	 
		//Layout
		if( isset($atts['layout']) ){
			$sc_atts[] = "layout=$apostrophe" . $atts['layout'] . "$apostrophe";
		} else {
			$sc_atts[] = "layout=" . $apostrophe . "media_rss" .$apostrophe;
		}
		
		//ID
		if( isset($atts['id']) && (int)$atts['id'] ){
			$sc_atts[] = "id=" . (int)$atts['id'];
		}
		
		//Gallery Type
		if( isset($atts['gallery_type']) ){
			$sc_atts[] = "gallery_type=$apostrophe" . $atts['gallery_type'] . "$apostrophe";
		}
		
		//Tags
		if( isset($atts['tags']) ){
			$sc_atts[] = "tags=$apostrophe" . $atts['tags'] . "$apostrophe";
		}
		
		//Where Gallery = ID - this is for special galleries like highest rated or random...it limits the images ot this gallery, while using the settings of the gallery with id = id (above)
		if( isset($atts['where_gallery']) ){
			$sc_atts[] = "where_gallery=$apostrophe" . (int)$atts['where_gallery'] 
				. "$apostrophe";
		}
		
		//thumb_height
		if( isset($atts['thumb_height']) ){
			$sc_atts[] = "thumb_height=$apostrophe" . $atts['thumb_height'] . "$apostrophe";
		}
		
		//thumb_width
		if( isset($atts['thumb_width']) ){
			$sc_atts[] = "thumb_width=$apostrophe" . $atts['thumb_width'] . "$apostrophe";
		}
		
		//images
		if( isset($atts['images']) ){
			$sc_atts[] = "images=$apostrophe" . $atts['images'] . "$apostrophe";
		}
		
		return $sc_atts;
	}
	
	//Get a link for the Start Slideshow for PicLens
	function getPicLensLink($g, $atts){
		if($atts['link_text']){
			$link_text = $atts['link_text'];
		} else {
			$link_text = 'Start Slideshow 
  <img src="http://lite.piclens.com/images/PicLensButton.png"
  alt="PicLens" width="16" height="12" border="0" align="absmiddle">';
		}
		
		$picatts['id'] = $g['gallery_id'];
		$picatts['thumb_width'] = $g['thumb_width'];
		$picatts['thumb_height'] = $g['thumb_height'];
		$picatts['gallery_type'] = $g['gallery_type'];
		$picatts['images'] = $g['images'];
		
		
		if($g['tags'] == 'post_tags'){
			$picatts['tags'] = $this->getPostTags(0);
		} else {
			$picatts['tags'] = $g['tags'];
		}
		
		$param_array = $this->filterMRSSAttsFromArray($picatts, "");
		
		if( is_array($param_array)){
			$params = implode("&", $param_array);
			$params = urlencode($params);
		}
				
		$ret = '<a class="piclenselink" href="javascript:PicLensLite.start({feedUrl:\'' 
			.  WP_PLUGIN_URL . '/photosmash-galleries/bwbps-media-rss.php?'
			. $params . '\'});">
			' . $link_text . ' </a>
			';
			
		return $ret;
	}
	
	function getPostTags($post_id){
	
		if(!$post_id ){
			global $wp_query;
			$post_id = $wp_query->post->ID;
		}
		$terms = wp_get_object_terms( $post_id, 'post_tag', $args ) ;
		
		if(is_array($terms)){
		
			foreach( $terms as $term ){
				
				$_terms[] = $term->name;
			
			}
		
			unset($terms);
			if( is_array($_terms)){
				$ret = implode("," , $_terms);
			} else {
				$ret = "";
			}
		}
	
		return $ret;	
	}
	
} //End of BWB_PhotoSmash Class


/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */

$bwbPS = new BWB_PhotoSmash();

function show_photosmash_gallery($gallery_params = false){
	echo get_photosmash_gallery($gallery_params);
}

function get_photosmash_gallery($gallery_params = false){
	global $bwbPS;
	$atts = array();
	if( is_array($gallery_params) ){
		$atts = $gallery_params;
	} else {
		if ( is_numeric($gallery_params) ){
			$atts = array('id' => (int)$gallery_params);
		}
	}
	return $bwbPS->shortCodeGallery($atts);
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

add_action( 'init', array(&$bwbPS, 'createTaxonomy'), 0 );

add_action('wp_head', array(&$bwbPS, 'injectBWBPS_CSS'), 10);

add_action('wp_footer', array(&$bwbPS, 'injectFooterJavascript'), 100);

add_filter('the_content',array(&$bwbPS, 'autoAddGallery'), 100);

add_shortcode('photosmash', array(&$bwbPS, 'shortCodeGallery'));

add_shortcode('psmash', array(&$bwbPS, 'shortCodes'));

if( version_compare($wp_version,"2.8", ">=" ) ){
	//Load the PhotoSmash Widget
	add_action( 'widgets_init', array(&$bwbPS, 'loadPSWidgets') );
}

?>