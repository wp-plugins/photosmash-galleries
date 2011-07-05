<?php
/*
Plugin Name: PhotoSmash
Plugin URI: http://smashly.net/photosmash-galleries/
Description: PhotoSmash - user contributable photo galleries for WordPress pages and posts.  Focuses on ease of use, flexibility, and moxie. Deep functionality for developers. PhotoSmash is licensed under the GPL.
Version: 1.0.7
Author: Byron Bennett
Author URI: http://www.whypad.com/
*/

/** 
 * Copyright 2009-2011  Byron W Bennett (email: bwbnet@gmail.com)
 *
 * Icons from Silk icon set by http://famfamfam.com/lab/icons/silk/
 * Help Icon from Crystal SVG at http://kde-look.org/content/show.php?content=8341
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
define('PHOTOSMASHVERSION', '1.0.2');
define('PHOTOSMASHEXTVERSION', '1.0.2');

define('PHOTOSMASHWEBHOME', 'http://smashly.net/photosmash-galleries/');

//Database Verifications
define('PHOTOSMASHVERIFYTABLE', $wpdb->prefix.'bwbps_images');
define('PHOTOSMASHVERIFYFIELD', 'geolat');

//Set Database Table Constants
define("PSGALLERIESTABLE", $wpdb->prefix."bwbps_galleries");

define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
define("PSFAVORITESTABLE", $wpdb->prefix.'bwbps_favorites');
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");
define("PSCATEGORIESTABLE", $wpdb->prefix."bwbps_categories");

define("PSLAYOUTSTABLE", $wpdb->prefix."bwbps_layouts");
define("PSFORMSTABLE", $wpdb->prefix."bwbps_forms");
define("PSFIELDSTABLE", $wpdb->prefix."bwbps_fields");
define("PSLOOKUPTABLE", $wpdb->prefix."bwbps_lookup");
define("PSHUBSTABLE", $wpdb->prefix."bwbps_sharinghubs");
define("PSSHARINGLOGTABLE", $wpdb->prefix."bwbps_sharinglog");
define("PSPARAMSTABLE", $wpdb->prefix."bwbps_params");

define("PSPIXOOXAPIURL", "http://pixoox.com/api/");

//Set the Upload Path
define('PSBLOGURL', get_bloginfo('wpurl')."/");
define('PSUPLOADPATH', WP_CONTENT_DIR .'/uploads');

define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSIMAGESPATH2',PSUPLOADPATH."/bwbps");
define('PSIMAGESURL', content_url("/uploads/bwbps/") );

define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define('PSTHUMBSPATH2',PSUPLOADPATH."/bwbps/thumbs");
define('PSTHUMBSURL',PSIMAGESURL."thumbs/");

define('PSDOCSPATH',PSUPLOADPATH."/bwbps/docs/");
define('PSDOCSPATH2',PSUPLOADPATH."/bwbps/docs");
define('PSDOCSURL',PSIMAGESURL."docs/");

define('PSTABLEPREFIX', $wpdb->prefix."bwbps_");
define('PSTEMPLATESURL',content_url("/themes/") );

define('BWBPSPLUGINURL',plugins_url("/photosmash-galleries/") );

define('PSADVANCEDMENU', "<a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a> | <a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a> | <a href='admin.php?page=managePhotoSmashImages'>Photo Manager</a> | <a href='admin.php?page=editPSForm'>Custom Forms</a> | <a href='admin.php?page=editPSFields'>Custom Fields</a> | <a href='admin.php?page=editPSHTMLLayouts'>Layouts Editor</a>
		<br/>");

define('PSSTANDARDDMENU', "<a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a> | <a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a> | <a href='admin.php?page=managePhotoSmashImages'>Photo Manager</a> | <a href='admin.php?page=editPSForm'>Custom Forms</a> | <a href='admin.php?page=editPSFields'>Custom Fields</a> | <a href='admin.php?page=editPSHTMLLayouts'>Layouts Editor</a>
		<br/>");

/*
define('PSADVANCEDMENU', "<a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a> | <a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a> | <a href='admin.php?page=managePhotoSmashImages'>Photo Manager</a> | <a href='admin.php?page=psmashSharing'>Sharing</a> | <a href='admin.php?page=editPSForm'>Custom Forms</a> | <a href='admin.php?page=editPSFields'>Custom Fields</a> | <a href='admin.php?page=editPSHTMLLayouts'>Layouts Editor</a>
		<br/>");

define('PSSTANDARDDMENU', "<a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a> | <a href='admin.php?page=editPSGallerySettings'>Gallery Settings</a> | <a href='admin.php?page=managePhotoSmashImages'>Photo Manager</a> | <a href='admin.php?page=psmashSharing'>Sharing</a>
		<br/>");
*/
		
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

	var $emailChecked;
	var $customFormVersion = 22;  //Increment this to force PS to update the Custom Fields Option
	var $adminOptionsName = "BWBPhotosmashAdminOptions";
	
	var $uploadFormCount;
	var $manualFormCount = 0;
	var $loadedGalleries;
	var $moderateNonceCount = 0;
	
	var $uploads; 	//WP uploads folder array info on uploads folder
	
	var $psAdmin;  //Admin object
	var $psImporter;	//Importer object
	var $img_funcs;	//Image Functions
	var $gal_funcs;	//Gallery Functions
	var $h;	//Helpers
	
		
	var $psOptions;
	var $psLayout;
	var $psSharing;
	
	var $sharing_options;
	var $psShareImage;
	
	var $psForm;
	
	var $shortCoded;
	var $stdFieldList;
	var $cfList;	//Object containing custom fields definitions
	var $bExcludeDatePicker;	//We walk through the custom fields and if one has DateTime, we make this false
								//bExcludeDatePicker excludes the jQuery Date Picker plugin
	
	var $galleries;
	
	var $images;
	
	var $footerJS = ""; // Load this up with Javascript...PS uses wp_footer hook to put Javascript in footer
	var $footerReady = "";
	
	var $footerJSArray;
	
	var $footerJSReadyArray;
	
	var $count = 0;
	
	var $galViewerCount = 0;
	
	var $postGalleries;
	
	var $loadGoogleMaps = false;
	var $skipGoogleAPI = false;
	var $gmaps;
	var $placedMaps;	// Array that stores list of map DIV IDs already placed...to prevent duplicates
	
	var $api;	// Holds the Mobile API object
	
	//Constructor
	function BWB_PhotoSmash(){
		
		$this->uploads = wp_upload_dir();
				
		$this->psOptions = $this->getPSOptions();
		
		$this->psOptions['gallery_viewer_slug'] = $this->psOptions['gallery_viewer_slug'] 
				? $this->psOptions['gallery_viewer_slug'] : 'psmash-gallery';
				
		$this->loadCustomFormOptions();
		
		/*	Code for uploading without AJAX...doesn't work
		*
		if(isset($_POST['bwbps_submitBtn'])){
			include_once("ajax_upload.php");
		}
		*
		*/
		
		//Helpers
		if(!class_exists(PixooxHelpers))
		{
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/pxx-helpers.php");
		}
		$this->h = new PixooxHelpers();
		
		//Add actions for Contributor Gallery
		if( $this->psOptions['contrib_gal_on'] ){
			add_filter('the_posts',  array(&$this,'displayContributorGallery') ); 
		}
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/image-functions.php");
		$this->img_funcs = new BWBPS_ImageFunc($this->psOptions);		
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/gallery-functions.php");
		$this->gal_funcs = new BWBPS_GalleryFunc($this->psOptions);
		
		add_filter('the_permalink',array(&$this,'fixSpecialGalleryLinks') );
		
		//Add action for Tags Gallery
		add_filter('the_posts', array(&$this, 'displayTagGallery') );
		
		// PhotoSmash API
		if( is_admin() && (int)$this->psOptions['api_enabled'] ){
			add_action( 'wp_ajax_photosmash_api', array($this,'loadAPI') );
			add_action( 'wp_ajax_nopriv_photosmash_api', array($this, 'loadAPI') );
		}

	}
	
	/*
	 * Loads the PhotoSmash API - primarily for uploads and other actions from Mobile Devices
	*/
	function loadAPI(){
		if(!isset($this->api)){
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/api-mobile.php");
			$this->api = new PhotoSmash_Mobile_API();
		}	
		
		die("api problem...");
	}
	
	/**
         * Adds Settings link to plugins panel grid
    */
    function add_settings_link($links, $file) {
        static $this_plugin;
        if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

        if ($file == $this_plugin){
            $settings_link = '<a href="admin.php?page=bwb-photosmash.php">'.__("Settings", "photosmash-galleries").'</a>';
            array_unshift($links, $settings_link);
            
             $settings_link = '<a href="http://smashly.net/photosmash-galleries/tutorials/">'.__("Tutorials", "photosmash-galleries").'</a>';
            array_unshift($links, $settings_link);
            
            
        }
        return $links;
    }
	
	function loadCustomFormOptions(){
		$this->stdFieldList = $this->getstdFieldList();
		$this->cfList = $this->getCustomFields();
		
		//Figure out if there are any DateTime custom fields and don't exclude if there are
		$this->bExcludeDatePicker = true;
		if($this->cfList){
			foreach($this->cfList as $cf){
				if( $cf->type == 5){
					$this->bExcludeDatePicker = false;
				}
			}	
		}
		
	}
	
	//Called when plugin is activated
	function init(){
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-init.php");
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
		
		if( !$ps['api_url'] ){
			$ps['api_url'] = admin_url('admin-ajax.php');
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
				'max_file_size' => 0,
				'mini_aspect' => 0,
				'mini_width' => 125,
				'mini_height' => 125,
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
				'sort_order' => 1,
				'contrib_gal_on' => 0,
				'suppress_contrib_posts' => 0,
				'poll_id' => 0,
				'favorites' => 0,
				'rating_position' => 0,
				'rating_allow_anon' => 0,
				'mod_send_msg' => 0,
				'mod_approve_msg' => "Thanks for submitting your image to [blogname]! It has been accepted and is now visible in the appropriate galleries.",
				'mod_reject_message' => "Sorry, the image you submitted to [blogname] has been reviewed, but did not meet our submission guidelines.  Please review our guidelines to see what types of images we accept.  We look forward to your future submissions.",
				'version' => PHOTOSMASHVERSION,
				'tb_height' => 390,
				'tb_width' => 545,
				'gmap_width' => 450,
				'gmap_height' => 350,
				'gmap_js' => false,
				'gmap_layout' => '',
				'auto_maptowidget' => 0,
				'tags_mapid' => false
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
			'tag_dropdown',
			'bloginfo',
			'plugin_url',
			'preview_post'
			
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
		
			$menu_logo = plugins_url( "/photosmash-galleries/images/psmash.png" );
			
			add_menu_page('PhotoSmash', 'PhotoSmash', 9, basename(__FILE__), array(&$bwbPS, 'loadAdminPage'),$menu_logo);
			
			add_submenu_page(basename(__FILE__), __('PhotoSmash Settings'), __('PhotoSmash Settings'), 9,  basename(__FILE__), array(&$bwbPS, 'loadAdminPage'));
			
			add_submenu_page(basename(__FILE__), __('Gallery Settings'), __('Gallery Settings'), 9,  
			'editPSGallerySettings', array(&$bwbPS, 'loadGallerySettings'));
			
			add_submenu_page(basename(__FILE__), __('Photo Manager'), __('Photo Manager'), 9,  
			'managePhotoSmashImages', array(&$bwbPS, 'loadPhotoManager'));
			
			add_submenu_page(basename(__FILE__), __('Image Importer'), __('Import Photos'), 9,  
			'importPSImages', array(&$bwbPS, 'loadImageImporter'));
			
			
				add_submenu_page(basename(__FILE__), __('PS Form Editor')
					, __('Custom Forms'), 9, 'editPSForm'
					, array(&$bwbPS, 'loadFormEditor'));
					
				add_submenu_page(basename(__FILE__), __('PS Field Editor')
					, __('Custom Fields'), 9, 'editPSFields'
					, array(&$bwbPS, 'loadFieldEditor'));
				
				add_submenu_page(basename(__FILE__), __('PS Layouts Editor')
					, __('Layouts Editor'), 9, 'editPSHTMLLayouts'
					, array(&$bwbPS, 'loadLayoutsEditor'));
					
			add_submenu_page(basename(__FILE__), __('Plugin Info'), __('Plugin Info'), 9,  
			'psInfo', array(&$bwbPS, 'loadPsInfo'));
				
		}
		
	}
	
	//Prints out the Admin Options Page
	function loadAdminPage(){
		
		if(!$this->psAdmin){
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printGeneralSettings();
	}
	
	function loadGallerySettings(){
		
		if(!$this->psAdmin){
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printGallerySettings();
		return true;
	}
	
	function loadPhotoManager(){
	
		if(!$this->psAdmin){
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-admin.php");
			$this->psAdmin = new BWBPS_Admin();
		}
		$this->psAdmin->printManageImages();
		
		return true;
	}
	
	function loadPhotoSharing(){
	
		if(!$this->psSharing){
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-sharing.php");
			$this->psSharing = new BWBPS_Sharing();
		}
		$this->psSharing->printSharing();
		
		return true;
	}
	
	
	
	function loadImageImporter(){
	
		if(!$this->psImporter){
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-importer.php");
			$this->psImporter = new BWBPS_Importer($this->psOptions);
		}
		$this->psImporter->printImageImporter();
		
		return true;
	
	}
	
	function loadLayoutsEditor(){
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-layouts.php");
		$layouts = new BWBPS_LayoutsEditor();		
		return true;
	}
	
	function loadFieldEditor(){
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-fieldeditor.php");
		$fieldEditor = new BWBPS_FieldEditor($this->psOptions);		
		return true;
	}
	
	function loadFormEditor(){
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-formeditor.php");
		$psform = new BWBPS_FormEditor($this->psOptions);		
		return true;
	}
	
	function loadPSInfo(){
				
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/bwbps-info.php");
		$ts = new BWBPS_Info();			
		return true;
	}		
		
/* ******************   End of Admin Section ******************************** */
	
/*	****************************************  Gallery Code  *************************************** */
//	AutoAdd Galleries
//	Is fired after Shortcodes are Processed
function autoAddGallery($content='')
{
	global $post;
	
	//Is fired after Shortcodes are Processed
	if($this->psOptions['gallery_viewer'] && (int)$this->psOptions['gallery_viewer'] != -1 && is_page($this->psOptions['gallery_viewer'])){
		if($this->galViewerCount){ return $content; }
		$this->galViewerCount++;
		$content .= do_shortcode("[photosmash gallery_viewer=true image_layout='image_view_layout']");
		return $content;
	}
	
	if(is_array($this->shortCoded) && in_array($post->ID, $this->shortCoded)){
		return $content;
	}
		
	//Determine if Auto-add is set up...add it to top or bottom if so
	$psoptions = $this->psOptions;// Get PhotoSmash defaults
	if($psoptions['auto_add']){
	
		//Give a hook so people can check categories or tags, etc and skip the gallery if desired
		$display_gallery = apply_filters('bwbps_gallery_exit', true);
		
		if(!$display_gallery){ return $content; }
	
	
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
	
	$this->emailChecked = true;
	
	//This does the alert if it is set to alert immediately
	if( $this->psOptions['img_alerts'] == -1 && (int)get_option('BWBPhotosmashNeedAlert') ){
		$this->img_funcs->sendNewImageAlerts(true);
		return;
	}
	
	//This is the timer for sending Alerts 
		if( $this->psOptions['img_alerts'] > 0 ){
			
			$time = time();
			
			$last_alert = get_option('BWBPhotosmashLastAlert');
			
			if($time - $last_alert > $this->psOptions['img_alerts'])
			{
				$this->img_funcs->sendNewImageAlerts();
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
		global $current_user;
		
		/*
	
		//Memory Usage code - to check if we have leaks - uncomment the one at top of this function also
		echo "<h3>Please note that testing is in process to make PhotoSmash better...pardon the interruptions</h3>";
		if (function_exists('memory_get_usage')){
		$memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . __(' MByte', 'bwbps-lang');
		_e('Memory usage', 'bwbps-lang');
		echo $memory_usage;
		}
		
*/
		// Check Email Alerts
		if(!$this->emailChecked){
			$this->checkEmailAlerts();
		}
		
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
			'gallery_viewer' => false,
			'gallery_view_layout' => false,
			'image_layout' => 'image_view_layout',	//For use with Gallery Viewer ?psmash-image=ID# (shows a single image
			'exclude_galleries' => false,	// Gallery IDs to exclude in a Gallery Viewer
			'gallery_ids' => false,	// Gallery IDs to include in a Gallery Viewer
			'before_gallery' => '',	// Text/html to place before the gallery
			'after_gallery' => '', // Text/html to place after the gallery
			'wp_gallery' => false,	// Display the gallery using the WordPress Gallery instead - only displays images that are in the Media Library for the gallery's post ID
			'wp_gallery_params' => '',	// additional shortcode parameters for using the WP [gallery] shortcode to disply the gallery
			'id' => false,		// The Gallery ID (or use name, or rely on the Post ID to find linked gallery)
			'name' => false,	// Uses the Gallery's Name to retrieve the Gallery
			'use_post_id' => false,	// Uses the post ID from the loop instead of the Post ID from the Gallery
			'form' => false,
			'max_user_uploads' => 0,	// Limits the # of uploads to gallery by users
			'sort_field' => false,
			'sort_order' => false,
			'no_gallery_header' => false,
			'form_alone' => false,
			'no_gallery' => false,
			'gallery_type' => false,
			'no_form' => false,
			'no_pagination' => false,
			'page_arrow_right' => false,
			'page_arrow_left' => false,
			'page_noellipses' => false,
			'page_nofirstlast' => false,
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
			'images_override' => 0,	// This will override the image per page limit set in Gallery Settings
			'page' => 0,
			'thumb_height' => 0,
			'thumb_width' => 0,
			'any_height' => 0,
			'any_width' => 0,
			'no_inserts' => false,
			'no_signin_msg' => false,
			'where_gallery' => false, // This is used with Random/Recent Galleries to limit selection to a single gallery
			'create_post' => false,	// Give a Custom Layout name to turn on creating new posts with PExt
			'preview_post' => false,
			'cat_layout' => false,	// Give a prefix to be used with the first Category ID to determine what layout should be used...it will default back to the layout specified in create_post if Cat Layout is not found...e.g.  cat_layout='postcat' ...it will look for a custom layout called postcat_##  where ## is the id of the first category in the upload
			'post_cat_child_of' => false,  // Only include categories that are children of ## category
			'post_cat_exclude' => false,
			'post_cat_show' => false,	// Supply this with a value that evaluates to true (e.g. something other than 0 or false or '') and it turns on the post categories selection box and is used as the LABEL for the field
			'post_cat_depth' => 0,
			'post_cats' => false,	//  Supply this with category id's to set for new post (comma separated)
			'post_cat_selected' => false,
			'post_cat_single_select' => false,	// make this 1 to turn off multi-select
			'post_thumbnail_meta' => false,	// use this as the name of the post meta (custom field) for post thumbnail
			'post_tags' => false,	// whether or not to show an input box for tags (comma separated)
			'post_tags_label' => false,
			'post_excerpt_field' => false,	// For use with PExtend - will use this field to create an excerpt when creating new post
			'tags_has_all' => false,	// Enter true to display only images have all tags
			'piclens' => false, 	// Enter true to include a link to a piclens slideshow
			'piclens_link' => '',	// Defaults to "Start Slideshow " with a little icon
			'piclens_class' => '',	// Defaults to "alignright"
			'gmap' => false,		// Provide a map ID to attach geocoded images to -- use 'post' for a 'postmap_##' where ## is post ID -- use the 'post' option if you're manually placing the DIV with [photosmash_map id='post']; use 'widget' for a map that will be specified in a widget; you also use Mappress provided maps, just use the same ID for both; you can place a map DIV with [photosmash_map id='mymap'], just match the id up with what you use in that shortcode; set to 'true' to use the Gallery specific map (cannot place map with [photosmash_map] under this option
			'gmap_div' => false,	// use this to specify that you want to automatically add the gmap div before or after values can be 'before' or 'after' as in before or after the gallery
			'gmap_div_height' => 0, 
			'gmap_div_width' => 0,
			'gmap_skip_api' => false, // skip loading the Google API
			'geocode' => false, // set to 'true' to add Geocode block to your Standard Form
			'geocode_fields' => false, // set to 'true' to add Geocode block to your Standard Form - this block will use address, locality, region, postal_code, country (custom fields) in the geocoding
			'geocode_label' => false,
			'geocode_description' => false,
			'latitude_label' => false,
			'longitude_label' => false,
			'required_fields' => false
			
		),$atts));
		
		if($this->psOptions['auto_maptowidget']){ $gmap = 'gmap_widget'; }
		if($gmap == 'widget'){ $gmap = 'gmap_widget'; }
		$gmap_id = $gmap;
		
		//Special kind of gallery...the Gallery Viewer...Ooooo
		if($gallery_viewer ){
			$gallery_type = 100;
		}
		
		//These galleries are 
		if( $gallery_type == 9 || $gallery_type == 'post_author' ){
			$gallery_type=9;
			if( !current_user_can('level_10') && $current_user->ID != $post->post_author ){
				$no_form=true;
			}
				
		}
				
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
		
		$image = false;	// You can now include an Image ID and return a single image
		if( (int)$image_id ){
			if(is_array($this->images)){
				if(!array_key_exists($image_id, $this->images)){
					$this->images[$image_id] = $this->getImage($image_id);
				} 
			}else{
				$this->images[$image_id] = $this->getImage($image_id);
			}
			
			// Set the ID for the Gallery to be loaded
			if( is_array($this->images[$image_id]) && (int)$this->images[$image_id]['gallery_id'] ){
				$id = $this->images[$image_id]['gallery_id'];
				$image = $this->images[$image_id];
			}
		}
						
		$galparms = $atts;
		$galparms['gallery_id'] = (int)$id;
		$galparms['photosmash'] = $galparms['gallery_id'];
		$galparms['wp_gallery_params'] = esc_attr($wp_gallery_params);
		
		if($name){
			$galparms['gallery_name'] = $name;
		}
		
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
			
			case 'normal' :
				$galparms['gallery_type'] = 'normal';
				break;
			
			case 'contributor' :
				
				$id = 0;
				$galparms['gallery_type'] = 10;	
				
				if(!$name){
					$galparms['gallery_name'] = 'Contributor Gallery';
				}
			
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
			
			case 'favorites' :
			case 70 :
				$galparms['gallery_type'] = 70;	
				$no_form = true;
				
				break;
				
			case 'most_favorited' :
			case 71 :	
				$galparms['gallery_type'] = 71;
				$galparms['sort_field'] = 5;
				$galparms['sort_order'] = 1;
				$no_form = true;
													
				break;
				
			case 100 :	// Gallery Viewer gallery
			
				$galviewerurl = get_permalink($post->ID);
				
				if((int)$_REQUEST['psmash-image']){
				
					$ret = do_shortcode("[psmash id=" . (int)$_REQUEST['psmash-image']
						. " layout='$image_layout']");
					
					return $ret;
				
				}
				
				if((int)$_REQUEST[$this->psOptions['gallery_viewer_slug']]){
				
					if(!$before_gallery){
					
					$before_gallery = "<h1 class='bwbps_h1 gallery_viewer_head'> <a href='" . $galviewerurl . "'>Back to " . get_bloginfo('name') . " - Gallery Viewer</a></h1>";
					
					}
					
					$galparms['gallery_id'] = (int)$_REQUEST[$this->psOptions['gallery_viewer_slug']];
					
					if($gallery_view_layout){
						$layout = $gallery_view_layout;
					} else {
						$layout = 'gallery_view_layout';
					}
					
					$galparms['gallery_type'] = 'normal';
				
				} else {
					
					if(!$before_gallery){
					
					$before_gallery = "<h1 class='bwbps_h1 gallery_viewer_head'>" .get_bloginfo('name') . " - <a href='" . $galviewerurl . "'>Gallery Viewer</a></h1>";
					}
					
					$galparms['gallery_type'] = 100;
					$no_form = true;
					if(!$layout){ $layout = 'gallery_viewer'; }
				
				}
				
				$this->galViewerCount++;
				
				break;
				
			case 'wp' :	// WordPress gallery
				$galparms['wp_gallery'] = true;
				break;			
				
			default :
			
				break;	
		
		}
		
		if($before_gallery='none'){ $before_gallery = ''; }
		
		
		$galparms['no_signin_msg'] = $no_signin_msg;	//used with $psOptions['upload_authmessage'] to not show signin message if this is true in shortcode

		//Get Gallery	
		$g = $this->getGallery($galparms);	//Get the Gallery params
		
		$g['no_inserts'] = $no_inserts;	//Turns off inserts for this display
		if( $g['required_fields'] ){
			$g['required_fields'] = str_replace(" ", "", $g['required_fields']);
			$g['required_fields'] = explode(",", $g['required_fields']);
		}
		
		$g['page_arrow_right'] = $page_arrow_right;
		$g['page_arrow_left'] = $page_arrow_left;
		$g['page_noellipses'] = $page_noellipses;
		$g['page_nofirstlast'] = $page_nofirstlast;
		
		//Include/Exclude Galleries for Gallery Viewer
		$g['gallery_ids'] = $gallery_ids;
		$g['exclude_galleries'] = $exclude_galleries;
		
		if( intval($max_user_uploads) ){
			$g['max_user_uploads'] = intval($max_user_uploads);
		}
		
		//Calculate GMap ID for Maps
		if( $gmap ){
		
			if($gmap_id == 'true' && !$gmap_div){ $gmap_div = 'after'; }
		
			$gmap_id = $this->calculateGMapID($gmap_id, $g['gallery_id']);
			$g['gmap_id'] = $gmap_id;
			
		}
		
		//Set Geocode for Upload form
		$g['geocode'] = $geocode ? true : false;
		$g['geocode_fields'] = $geocode_fields ? true : false;
		$g['geocode_label'] = $geocode_label ? $geocode_label : 
			($this->psOptions['geocode_label'] ? $this->psOptions['geocode_label'] : 'Geocode');
		$g['geocode_description'] = $geocode_description ? $geocode_description : 
			($this->psOptions['geocode_description'] ? $this->psOptions['geocode_description'] : 'Use address field(s) for geocode lookup');
		$g['latitude_label'] = $latitude_label ? $latitude_label : 
			($this->psOptions['latitude_label'] ? $this->psOptions['latitude_label'] : 'Latitude');
		$g['longitude_label'] = $longitude_label ? $longitude_label : 
			($this->psOptions['longitude_label'] ? $this->psOptions['longitude_label'] : 'Longitude');
			
		
		//These galleries are Author Posts
		if( $g['gallery_type'] == 9 ){
			if( !current_user_can('level_10') && $current_user->ID != $post->post_author ){
				$no_form=true;
			}
				
		}
		
		// Figure out Which Post ID to use
		if( $use_post_id ){
			$g['gal_post_id'] = (int)$post->ID;
		} else {
			$g['gal_post_id'] = (int)$g['post_id'] ? (int)$g['post_id'] : (int)$post->ID;
		}
		
		//Set up for a Tag driven gallery
			$g['tags'] = $tags ? $tags : false;
		
		//Set
		if($tags_for_uploads){ $g['tags_for_uploads'] = $tags_for_uploads; }
		
		//PhotoSmash Extend Variables used in Post on Upload (creating new Posts on Uploads)
		$g['create_post'] = $create_post;
		$g['preview_post'] = $preview_post;
		$g['cat_layout'] = $cat_layout ? $cat_layout : "postcat";
		$g['post_cat_child_of'] = $post_cat_child_of;
		$g['post_cat_exclude'] = $post_cat_exclude;
		$g['post_cat_show'] = $post_cat_show;
		$g['post_cat_depth'] = $post_cat_depth;
		$g['post_cat_selected'] = $post_cat_selected;
		$g['post_cats'] = $post_cats;
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
					
			if((int)$where_gallery ){
				$g['smart_where'] = array ( PSIMAGESTABLE.".gallery_id" => (int)$where_gallery );
			}
			
			$no_form = true;
			
		} else {
			
			// Work out Sorting from shortcode/$_REQUEST
			$gid = (int)$g['gallery_id'];
			if( isset($_REQUEST['ps_sort_field' .  $gid ]) ){
				$sort_field = wp_kses(  $_REQUEST['ps_sort_field' .  $gid ], array() );
			}
			
			if( isset($_REQUEST['ps_sort_order' .  $gid ]) ){
				$sort_order = wp_kses(  $_REQUEST['ps_sort_order' .  $gid ], array() );
			}
			
			if($sort_field){
				$g['sort_field'] = $sort_field;
			}
			
			if($sort_order){
				$g['sort_order'] = $sort_order;
			}
			
		}
		
		if( strtolower($g['sort_order']) == 'desc' || (int)$g['sort_order'] ){
			$g['sort_order'] = "DESC";
		} else {
			$g['sort_order'] = "ASC";
		}
		
		// WordPress Gallery
		if( $g['gallery_type'] == 97 ){ 
			// catches cases when Gallery Type is set to WP Gallery in the Gallery Settings
			// (as opposed to shortcode)
			
			if( $galparms['gallery_type'] != 'normal' ){
				$g['wp_gallery'] = true;
			}
			$g['gallery_type'] = 0; 
		}
		
		$g['limit_images'] = (int)$images;
		$g['limit_page'] = (int)$page;
		$g['limit_images_override'] = (int)$images_override;
		
		if($thumb_height){
			$g['thumb_height'] = (int)$thumb_height;
		}
		
		if($thumb_width){
			$g['thumb_width'] = (int)$thumb_width;
		}
		
		if($any_height){
			$g['thumb_height'] = (int)$any_height;
			$g['mini_height'] = (int)$any_height;
			$g['medium_height'] = (int)$any_height;
		}
		
		if($any_width){
			$g['thumb_width'] = (int)$any_width;
			$g['mini_width'] = (int)$any_width;
			$g['medium_width'] = (int)$any_width;
		}
		
		
				
		$g['use_thickbox'] = $thickbox;
		$g['form_visible'] = $form_visible;
		
		$g['no_pagination'] = $no_pagination;
		$g['no_form'] = $no_form;
		
		if(isset($_POST['bwbps_q']) ){
			if(!isset($_POST['bwbps_extnav_gal']) || 
				((int)$_POST['bwbps_extnav_gal'] == $g['gallery_id']))
			{
				$g['no_pagination'] = true;
				$g['limit_images'] = 0;
				$g['limit_page'] = 0;
				$g['limit_images_override'] = 0;
				$g['img_perpage'] = 0;
			}
		}
		
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
		
		$this->shortCoded[] = $post->ID;	//Just so Auto Add doesn't do it again
		
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
			, $this->loadedGalleries) && !$g['gallery_type'] == 20 && !$name){

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
			$ret .= $this->buildGallery($g, $skipForm, $layoutName, $formName, $image );
			
			if(!$g['no_gallery_header']){			
				$ret .= "
					<script type='text/javascript'>
						displayedGalleries += '|".$g['gallery_id']."';
					</script>
				";
			}
		}
		
		/*
	
		//Memory Usage code - to check if we have leaks - uncomment the one at top of this function also
		if (function_exists('memory_get_usage')){
		$memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . __(' MByte', 'bwbps-lang');
		_e('Memory usage', 'bwbps-lang');
		echo $memory_usage;
		}
		
*/
		
		unset($galparms);
		
	$ret = $before_gallery . $ret . $after_gallery;
		
	/* Set up Map Code */
	
	if( $gmap_id ){
		
		// Tell PhotoSmash to load the Google Maps Javascript in the Footer
		
		$this->loadGoogleMaps = true;
		
		if( $gmap_skip_api ){
			$this->skipGoogleAPI = true;
		}
		
		
	
		if( $gmap_div && (!is_array($this->placedMaps) || empty($this->placedMaps[$gmap_id])) ){
		
			$gmap_width = (int)$gmap_width ? (int)$gmap_width : 
				((int)$this->psOptions['gmap_width'] ? (int)$this->psOptions['gmap_width'] : 350);
			$gmap_height = (int)$gmap_height ? (int)$gmap_height : 
				((int)$this->psOptions['gmap_height'] ? (int)$this->psOptions['gmap_height'] : 300);
			
			$gmap_div_code = "<div id='" . $gmap_id . "' class='bwbps_gmap bwbps_gmap_" . $g['gallery_id'] 
					. "' style='width: " . $gmap_width . "px; height: " . $gmap_height . "px;'></div>";
		
			if( $gmap_div && $gmap_div != 'before' ){ $gmap_div = 'after'; }
			
			if( $gmap_div == 'before' ){
			
				$ret = $gmap_div_code . $ret;	
			
			}
			
			if( $gmap_div == 'after' ){
			
				$ret .= $gmap_div_code;	
			
			}

			$this->placedMaps[$gmap_id] = true;		// Set this to prevent duplicate divs
		}
	
	}
	
	return $ret; 
}


function getGallery($g){
	
	$g = $this->gal_funcs->getGallery($g);
	
	//Cache the new gallery
	$this->galleries[$g['gallery_id']] = $g;
	
	// Set PostGalleries ID
	if( (int)$g['gallery_type'] < 10 ){
		$gal_post_id = (int)$g['post_id'];
		if($gal_post_id){ $this->postGalleries[$gal_post_id] = $g['gallery_id']; }	
	}
	
	return $g;
}

function getAddPhotosLink(&$g, $blogname, &$formname){
	
	global $post;
	global $current_user;
	
	if($g['max_user_uploads']){
		// This limits the number of uploads a user can make to a specific gallery
		$icnt = $this->img_funcs->getUserImageCountByGallery((int)$current_user->ID, $g['gallery_id'], $g['uploads_period']);
		
		if( $icnt >= $g['max_user_uploads'] ){ return (string)$g['max_uploads_msg']; }
	}
	
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
		
		if(!(int)$g['post_id']){ $g['gal_post_id'] = $post->ID; }
		
		$ret = '<span class="bwbps_addphoto_link"><a href="TB_inline?height='
			. $this->psOptions['tb_height'] .'&amp;width=' 
			. $this->psOptions['tb_width']. '&amp;inlineId='.$g["pfx"].'bwbps-formcont" onclick="bwbpsShowPhotoUpload('.(int)$g["gallery_id"].', '.(int)$g['gal_post_id'].', \''.$g["pfx"].'\');" title="'.$blogname.' - Gallery Upload" class="thickbox">'.$g['add_text'].'</a></span>';
	
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
	
	return apply_filters('bwbps_add_photo_link', $ret);		// A filter in case somebody wants to alter the Add Photo link
}


	function getPhotoForm($g, $formName=false){	
	
		$frm = $formName ? $formName : 'std';
		
		if($this->uploadFormCount[$frm]){ return;}
		$this->uploadFormCount[$frm]++;
		
	
		if(!isset($this->psForm)){
			require_once(WP_PLUGIN_DIR . "/photosmash-galleries/bwbps-uploadform.php");
			
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

function buildGallery($g, $skipForm=false, $layoutName=false, $formName=false, $image=false)
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
		
		$ret = "<div class='bwbps-piclens-link $class'>" . $this->getPicLensLink($g, $atts) . "</div>" . $ret . "<div class='bwbps-piclens-clear'></div>";
	}
	
	if(!isset($this->psLayout)){
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/bwbps-layout.php");
		$this->psLayout = new BWBPS_Layout($this->psOptions, $this->cfList);
	}

	$ret .=	$this->psLayout->getGallery($g, $layoutName, $image);
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
				
		wp_register_script('bwbps_js', plugins_url('/photosmash-galleries/js/bwbps.js' ), array('jquery'), '1.0');
		wp_enqueue_script('bwbps_js');
		
		//enqueue jQuery Star Rating Plugin
		wp_register_script('jquery_starrating'
			, plugins_url( '/photosmash-galleries/js/star.rating.js' )
			, array('jquery'), '1.0');
		wp_enqueue_script('jquery_starrating');
		
		if(!$this->bExcludeDatePicker){
			//enqueue jQuery DatePicker
			wp_enqueue_script('jquery-ui-core');
			
			wp_register_script('jquery-datepicker'
				, plugins_url('/photosmash-galleries/js/ui.datepicker.js')
				, array('jquery'), '1.0');
			wp_enqueue_script('jquery-datepicker');
		
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
	<link rel="stylesheet" href="<?php echo plugins_url();?>/photosmash-galleries/css/bwbps.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?php echo plugins_url();?>/photosmash-galleries/css/rating.css" type="text/css" media="screen" />
	<?php 
	}
	
	if(trim($this->psOptions['css_file'])){  
	?>
	<link rel="stylesheet" href="<?php echo PSTEMPLATESURL.$this->psOptions['css_file'];?>" type="text/css" media="screen" />
	<?php } 
	
	// Add DatePicker CSS
	 if( $this->bExcludeDatePicker === false ){ 
	 ?>
	<link rel="stylesheet" type="text/css" href="<?php echo plugins_url();?>/photosmash-galleries/css/ui.core.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo plugins_url();?>/photosmash-galleries/css/ui.datepicker.css" />
	
	<?php } ?>
	
	
	<link rel="alternate" href="<?php echo plugins_url(); ?>/photosmash-galleries/bwbps-media-rss.php" type="application/rss+xml" title="" id="gallery" />

	<?php if( !$this->psOptions['exclude_piclens_js'] ) { ?>
      <script type="text/javascript" 
    src="http://lite.piclens.com/current/piclens_optimized.js"></script>
	<?php } ?>
	
    <script type="text/javascript">
	var displayedGalleries = "";
	var bwbpsAjaxURL = "<?php echo plugins_url(); ?>/photosmash-galleries/ajax.php";
	var bwbpsAjaxUserURL = "<?php echo plugins_url(); ?>/photosmash-galleries/ajax_useractions.php";
	var bwbpsAjaxRateImage = "<?php echo plugins_url(); ?>/photosmash-galleries/ajax_rateimage.php";
	var bwbpsAjaxUpload = "<?php 
		if( $this->psOptions['use_wp_upload_functions'] ){
			echo plugins_url()."/photosmash-galleries/ajax-wp-upload.php";
		} else {
			echo plugins_url()."/photosmash-galleries/ajax_upload.php";
		}
		?>";
	var bwbpsImagesURL = "<?php echo PSIMAGESURL; ?>";
	var bwbpsThumbsURL = "<?php echo PSTHUMBSURL; ?>";
	var bwbpsUploadsURL = "<?php echo $this->uploads['baseurl'] . "/"; ?>";
	var bwbpsPhotoSmashURL = "<?php echo plugins_url(); ?>/photosmash-galleries/";
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
		
		wp_register_script('google_maps_v3', "http://maps.google.com/maps/api/js?sensor=false", array('jquery', 'thickbox'), '3.0');
		wp_enqueue_script('google_maps_v3');
		
		wp_register_script('bwbps_admin_js', plugins_url('/photosmash-galleries/js/bwbps-admin.js'), array('jquery', 'thickbox'), '1.0');
		wp_enqueue_script('bwbps_admin_js');
		
		?>
		<script type="text/javascript">
		//<![CDATA[
			var bwbpsAjaxURL = "<?php echo plugins_url(); ?>/photosmash-galleries/ajax.php";
			var bwbpsAjaxMediaURL = "<?php echo plugins_url(); ?>/photosmash-galleries/ajax_medialoader.php";
			var bwbpsAjaxUpload = "<?php echo plugins_url(); ?>/photosmash-galleries/ajax_upload.php";
			var bwbpsImagesURL = "<?php echo PSIMAGESURL; ?>";
			var bwbpsThumbsURL = "<?php echo PSTHUMBSURL; ?>";
			var bwbpsPhotoSmashURL = "<?php echo plugins_url(); ?>/photosmash-galleries/";
		//]]>
		</script>
		
		<?php	
		
		wp_register_script('bwbps_maps_js', plugins_url('/photosmash-galleries/js/bwbps-maps.js'), array('jquery', 'thickbox'), '1.0');
		wp_enqueue_script('bwbps_maps_js');	
		
	}
	
	function injectAdminStyles()
	{
		wp_enqueue_style( 'bwbpstabs', plugins_url('/photosmash-galleries/css/bwbps.css'), false, '1.0', 'screen' );
		wp_enqueue_style( 'bwbpsuicore', plugins_url('/photosmash-galleries/css/ui.core.css'), false, '1.0', 'screen' );		
		wp_enqueue_style( 'bwbpsdatepicker', plugins_url('/photosmash-galleries/css/ui.datepicker.css'), false, '1.0', 'screen' );
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
						require_once(WP_PLUGIN_DIR . "/photosmash-galleries/bwbps-layout.php");
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
			
				if( !$img['thumb_url'] ){
				
					if( $img['file_name'] ){
						$img['mini_url'] = PSTHUMBSURL.$img['file_name'];
						$img['thumb_url'] = PSTHUMBSURL.$img['file_name'];
						$img['medium_url'] = PSTHUMBSURL.$img['file_name'];
						$img['image_url'] = PSIMAGESURL.$img['file_name'];
					}
				
				} else {
					$uploads = wp_upload_dir();
					// Add the Uploads base URL to the image urls.
					// This way if the user ever moves the blog, everything might still work ;-) 
					// set $uploads at top of function...only do it once
					if(!$img['mini_url']){ $img['mini_url'] = $img['thumb_url']; }
					$img['mini_url'] = $uploads['baseurl'] . '/' . $img['mini_url'];
					$img['thumb_url'] = $uploads['baseurl'] . '/' . $img['thumb_url'];
					$img['medium_url'] = $uploads['baseurl'] . '/' . $img['medium_url'];
					$img['image_url'] = $uploads['baseurl'] . '/' . $img['image_url'];
				
				}
				
			
				$imgtitle = esc_attr($img['image_caption']);
				$ret = "<img src='".$img['image_url']."' ".$img['imgclass']
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
			
				
				if( !$img['thumb_url'] ){
					
						if( $img['file_name'] ){
							$img['mini_url'] = PSTHUMBSURL.$img['file_name'];
							$img['thumb_url'] = PSTHUMBSURL.$img['file_name'];
							$img['medium_url'] = PSTHUMBSURL.$img['file_name'];
							$img['image_url'] = PSIMAGESURL.$img['file_name'];
						}
					
				} else {
					$uploads = wp_upload_dir();
						// Add the Uploads base URL to the image urls.
						// This way if the user ever moves the blog, everything might still work ;-) 
						// set $uploads at top of function...only do it once
						if(!$img['mini_url']){ $img['mini_url'] = $img['thumb_url']; }
						$img['mini_url'] = $uploads['baseurl'] . '/' . $img['mini_url'];
						$img['thumb_url'] = $uploads['baseurl'] . '/' . $img['thumb_url'];
						$img['medium_url'] = $uploads['baseurl'] . '/' . $img['medium_url'];
						$img['image_url'] = $uploads['baseurl'] . '/' . $img['image_url'];
					
				}

			
				if($this->psOptions['img_targetnew']){
					$imagetargblank = " target='_blank' ";
				}
				$imgtitle = esc_attr($img['image_caption']);
				
				if($img['img_rel']){$imgrel = " rel='".$img['img_rel']."'";} else {$imgrel="";}
				
				$imgurl = "<a href='".$img['image_url']."'"
						.$imgrel." title='".$imgtitle."' ".$imagetargblank.">";
				
				$ret = $imgurl."
					<img src='".$img['thumb_url']."'".$img['imgclass']
					." alt='".$imgtitle."' /></a>";
					
			}
		}
		
		if( $if_before && $ret ){ $ret = $if_before . $ret; }
		if( $if_after && $ret ){ $ret = $if_after . $ret; }
		
		return $ret;
	}
	
	function getImage($image_id){
		global $wpdb;
		global $current_user;
		
		$user_id = (int)$current_user->ID;
		
		//Set up SQL for Custom Data if in Use
		$custDataJoin = " LEFT OUTER JOIN ".PSCUSTOMDATATABLE
			." ON ".PSIMAGESTABLE.".image_id = "
			.PSCUSTOMDATATABLE.".image_id ";
			
		$custdata = ", ".PSCUSTOMDATATABLE.".* ";
			
		if(current_user_can('level_0') && (int)$user_id){
			$custdata .= ", " . PSFAVORITESTABLE . ".favorite_id ";
			
			$favoriteDataJoin = " LEFT OUTER JOIN ".PSFAVORITESTABLE
				." ON ".PSIMAGESTABLE.".image_id = "
				.PSFAVORITESTABLE.".image_id "
				." AND " . PSFAVORITESTABLE . ".user_id = " . (int)$user_id . " ";
			
		}
		
		
		//Admins can see all images
		if(current_user_can('level_10')){
				
			$sql = $wpdb->prepare("SELECT ".PSIMAGESTABLE.".*, "
					.PSIMAGESTABLE.".image_id as psimageID, ".PSGALLERIESTABLE.".img_class,"
					.PSGALLERIESTABLE.".img_rel, "
					.$wpdb->users.".user_nicename,"
					.$wpdb->users.".display_name,"
					.$wpdb->users.".user_login,"
					.$wpdb->users.".user_url". $custdata 
					." FROM ".PSIMAGESTABLE
					." LEFT OUTER JOIN ".PSGALLERIESTABLE." ON "
					.  PSGALLERIESTABLE.".gallery_id = ".PSIMAGESTABLE
					.".gallery_id ".$custDataJoin. " LEFT OUTER JOIN ".$wpdb->users." ON "
				.$wpdb->users.".ID = ". PSIMAGESTABLE.".user_id $favoriteDataJoin WHERE ".PSIMAGESTABLE
					.".image_id = %d", $image_id);
			
		} else {
			//Non-Admins can see their own images and Approved images
			$uid = (int)$user_id ? (int)$user_id : -1;
			
			$sql = $wpdb->prepare("SELECT ".PSIMAGESTABLE.".*, "
					.PSIMAGESTABLE.".image_id as psimageID, ".PSGALLERIESTABLE.".img_class,"
					.PSGALLERIESTABLE.".img_rel, "
					.$wpdb->users.".user_nicename,"
					.$wpdb->users.".display_name,"
					.$wpdb->users.".user_login,"
					.$wpdb->users.".user_url". $custdata 
					." FROM ".PSIMAGESTABLE
					." LEFT OUTER JOIN ".PSGALLERIESTABLE." ON "
					.PSGALLERIESTABLE.".gallery_id = ".PSIMAGESTABLE
					.".gallery_id ". $custDataJoin ." LEFT OUTER JOIN ".$wpdb->users." ON "
				.$wpdb->users.".ID = ". PSIMAGESTABLE.".user_id $favoriteDataJoin WHERE ".PSIMAGESTABLE
					.".image_id = %d AND (".PSIMAGESTABLE
					.".status > 0 OR ".PSIMAGESTABLE
					.".user_id = '"
					.$uid."')", $image_id);
			
		}
				
		$image = $wpdb->get_row($sql, ARRAY_A);
		
		if($image && is_array($image)){ $image['image_id'] = $image['psimageID']; }
				
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
	
	function verifyGalleryViewerPage(){
	
		if(isset($_REQUEST['ps_gallery_viewer'])){
			if(!(int)$_REQUEST['ps_gallery_viewer']){
				$failed = true;
			}
		}else {
			if(!$this->psOptions['gallery_viewer']){
				$failed = true;
			}
		}
		if($failed){
			echo "<div class='message error'><p>Please set Gallery Viewer Page in <a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a>. The Gallery Viewer Page is an index to your PhotoSmash galleries.</p></div>";
		}
	}
	
	// Tag and Contributor Taxonomy Galleries
	function displayTagGallery($theposts){
			
		if(!get_query_var( 'bwbps_wp_tag' ) && !get_query_var( 'bwbps_contributor' )){ return $theposts; } //leave if this isn't the tag page
		
		
		add_filter('the_excerpt',array(&$this,'fixExcerptGallery') );
		
		$d = date( 'Y-m-d H:i:s' );
		
		// Process Tag Gallery
		if(get_query_var( 'bwbps_wp_tag' ) ){ 
			$tag = $this->getRequestedTags();
			
			if($this->psOptions['tags_mapid']){
				$gmap_id = " gmap='". $this->psOptions['tags_mapid'] . "' ";
			}
			
			$newpost->post_content = "[photosmash gallery_type=tags tags='". esc_attr($tag)
				."'" . $gmap_id . " no_form=true]";
				
			$label = $this->psOptions['tag_label'] ? esc_attr($this->psOptions['tag_label']) : "Photo tags";
			$newpost->post_name = $label . '/' . $tag;
		 	$newpost->post_title = 'Images by ' . $tag;
		
		 } else {
		 // Process Contributor Gallery
		 	$tag = get_query_var( 'bwbps_contributor' );
		 	
		 	$user_info = get_userdatabylogin($tag);
		 	
		 	if(!$user_info){ return false; }
		 	$user_id = (int)$user_info->ID;
		 	
		 	$newpost->post_content = "[photosmash gallery_type=contributor author=$user_id no_form=true]";
		 	
		 	$label = $this->psOptions['contributor_slug'] ? esc_attr($this->psOptions['contributor_slug']) : "contributor";
		 	
		 	$newpost->post_name = $label . '/' . $tag;
		 	$newpost->post_title = 'Images by ' . $tag;
		 }
		
		//Create an object for a new post to un_shift onto the posts array
			$newpost->ID = -1;
			$newpost->post_author = $author;
			$newpost->post_date = $d;
			$newpost->post_date_gmt = $d;
			 
			$newpost->post_category = 0;
			$newpost->post_excerpt = '';
			$newpost->post_status = 'publish';
			$newpost->comment_status = 'closed';
			$newpost->ping_status = 'closed';
			$newpost->post_password = '';
			
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
		
		if( $wp_query->query_vars['bwbps_wp_tag'] ){
			$tag_name = $wp_query->query_vars['bwbps_wp_tag'];
				
			$url = get_term_link($tag_name, 'photosmash');
		} else {
			$tag_name = $wp_query->query_vars['bwbps_contributor'];
				
			$url = get_term_link($tag_name, 'photosmash_contributors');
		}
				
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
	
	function addMap($map, $lat = 0, $lng = 0){
		
		if( !is_array($this->gmaps) || empty($this->gmaps[ $map]) ){
		
			$this->gmaps[$map]['map_id'] = $map;
		
			if( $lat && $lng){
				$this->gmaps[$map]['init'] = "\nbwb_maps.push( bwb_gmap.showMap( '" 
					. $map . "', " .  floatval($lat) . ", " . floatval($lng) . ") );\n"
					. "bwb_markers.push( [] );\n"
					. "bwb_infowindows.push( [] );\n";
					
			}
			
		} else {
			if( empty($this->gmaps[ $map ]['init'] ) && floatval($lat) && floatval($lng) ){
				$this->gmaps[$map]['init'] = "\nbwb_maps.push( bwb_gmap.showMap( '" 
					. $map . "', " 
					.  floatval($lat) . ", " . floatval($lng) . ") );\n" 
					. "bwb_markers.push( [] );\n"
					. "bwb_infowindows.push( [] );\n";
			}
		}
	
	}
	
	function injectGMapFooterCodes(){
	
		if($this->loadGoogleMaps && !$this->skipGoogleAPI && trim($this->psOptions['gmap_js']) != 'none' ){
			
			if( $this->psOptions['gmap_js'] ){
				
			echo '
			<!-- Google Maps loaded by PhotoSmash (customized by admin) -->
			' . $this->psOptions['gmap_js'] . '
			';
				
			} else {
					
				echo '
				<!-- Google Maps API V3 loaded by PhotoSmash  -->
					<!-- Go to PhotoSmash Settings and enter "none" on the maps page to turn it off -->
					<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
					';
					
			}
		
		}	
		
		if($this->loadGoogleMaps){
			echo '
			<!-- PhotoSmash class for Google Maps  -->
			<script type="text/javascript" src="' .
			plugins_url("/photosmash-galleries/js/bwbps-maps.js" )
			. '"></script>';
			
			//Insert the code for Creating the Map Instances
			if( is_array($this->gmaps) ){
			
				echo "
			<!-- PhotoSmash JavaScript  -->
			<script type='text/javascript'>
						
			// On Document Ready
			jQuery(document).ready(function() {
						
				";
				
				// Load Map Instances - this comes from the bwbps-layout.php function addGoogleMapMarker()
				$imap_cnt = -1;
				foreach( $this->gmaps as $gmap ){

					$imap_cnt++;
					$map_id = 'bwb_maps[' . $imap_cnt . ']';
					
					if( !empty( $gmap["init"] ) ){
						
						// New Map starts here
						echo "\n// New Map starts here\n" . $gmap['init'];
						
					} else {
					
						// New Map...No markers
						echo "\n// New Map starts here\nbwb_maps.push( bwb_gmap.showMap( '" 
							. $gmap['map_id'] . "', 38, -60) );\nbwbcnt = bwb_maps.length - 1;\nbwb_maps[bwbcnt].setZoom(1);\n";
						
					}
					
					
					if( is_array( $gmap['markers'] ) ){
						// Set up Vars for Bounds and Markers array
						echo "\n// Set up Var for Bounds\nvar bwbbound_" . $gmap['map_id'] 
							. " = new google.maps.LatLngBounds();\n";
						
						// Echo the marker adding code
						echo implode("\n", $gmap['markers']);
						
						// FitBounds
						echo "\n //FitBounds\nif( bwb_markers[" . $imap_cnt . "].length > 1 ){" . $map_id . ".fitBounds(bwbbound_" 
							. $gmap["map_id"] . ");}\n\n// Map ends";
					}
				}
					
				echo "
				});
				</script>
					";
				
				}
				
		}
	
	} //Closes injectGMapFooterCodes Function
	
	
	/**
	 * Injects JavaScript into the Footer - called by PhotoSmash through wp_footer hook
	 * To use, just...global the $bwbPS object and add to either $bwbPS->addFooterJS($js) 
	 * or $bwbPS->addFooterReady($js);
	 */
	function injectFooterJavaScript(){
	
		$this->injectGMapFooterCodes();	// Load the Google Map codes
	
		if( !$this->footerJS && !$this->footerReady && !is_array($this->footerJSArray) 
			&& !is_array($this->footerJSReadyArray) ){ return; }
	
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
		
		if(is_array($this->footerJSReadyArray)){
					
			$ret .= "
			// On Document Ready
			jQuery(document).ready(function() {
			";
			
			foreach($this->footerJSReadyArray as $fjs){
			
				$ret .= $fjs;
			
			}
			
			$ret .= "
			
			});
			";
		
		}

		
		if(is_array($this->footerJSArray)){
			
			$ret .= "
			";
			
			foreach($this->footerJSArray as $fjs){
			
				$ret .= $fjs;
			
			}
			
			$ret .= "
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
	 * Adds JavaScript to an array that will be inserted into the Footer wrapped in Script tags
	 * Use this when you might be creating duplication...so you overwrite the array key with the duplicates...ex: array['key1'] = my_image;  array['key1'] = my_image; 
	 * In that example, you only wind up with key1 in there once
	 * Think google maps, etc.
	 * 
	 */
	function addFooterJSArray($js, $key=false){
		
		if($js){
		
			if($key){
			$this->footerJSArray[$key] = "
			
			".$js;
			
			} else {
			$this->footerJSArray[] = "
			
			".$js;
			}
		
		}
	}
	
	function addFooterJSReadyArray($js, $key=false){
		
		if($js){
		
			if($key){
			$this->footerJSReadyArray[$key] = "
			
			".$js;
			
			} else {
			$this->footerJSReadyArray[] = "
			
			".$js;
			}
		
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
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/widgets/bwbps-widget.php");
		register_widget( 'PhotoSmash_Widget' );
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/widgets/bwbps-tagcloud.php");
		register_widget( 'PhotoSmash_TagCloud' );
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/widgets/bwbps-mapwidget.php");
		register_widget( 'PhotoSmash_Map_Widget' );
	
	}
	
	/**
	 *	Create the Photo Tags taxonomy
	 *
	 */
	 function createTaxonomy(){
	 	$label = $this->psOptions['tag_label'] ? esc_attr($this->psOptions['tag_label']) : "Photo tags";
	 	$slug = $this->psOptions['tag_slug'] ? $this->psOptions['tag_slug'] : "photo-tag";
	 	
	 	register_taxonomy( 'photosmash', 'post', array( 'hierarchical' => false, 'label' => __($label, 'series'), 'query_var' => 'bwbps_wp_tag', 'rewrite' => array( 'slug' => $slug ) ) );
	 	
	 	$label = $this->psOptions['contributor_label'] ? esc_attr($this->psOptions['contributor_label']) : "Photo Contributors";
	 	$slug = $this->psOptions['contributor_slug'] ? $this->psOptions['contributor_slug'] : "contributor";
	 	
	 	register_taxonomy( 'photosmash_contributors', 'post', array( 'hierarchical' => false, 'label' => __($label, 'series'), 'query_var' => 'bwbps_contributor', 'rewrite' => array( 'slug' => $slug ) ) );	
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
	  * Filter Media RSS Attributes from Array
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
		if( isset($atts['images']) && (int)$atts['images'] ){
			
			$sc_atts[] = "images=" . (int)$atts['images'] . " ";
		}
		
		//images
		if( isset($atts['images_override']) && (int)$atts['images_override'] ){
			
			$sc_atts[] = "images_override=" . (int)$atts['images_override'] . " ";
		}
		
		//page
		if( isset($atts['page']) ){
			$sc_atts[] = "page=$apostrophe" . $atts['page'] . "$apostrophe";
		}
		
		//no pagination
		$sc_atts[] = "no_pagination=1";
		
		$sc_atts[] = "no_form=true";
		
		$sc_atts[] = "no_gallery_header=true";
		
		
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
		$picatts['page'] = $g['page'];
		
		
		if($g['tags'] == 'post_tags'){
			$picatts['tags'] = $this->getPostTags(0);
		} else {
			$picatts['tags'] = $g['tags'];
		}
		
		$param_array = $this->filterMRSSAttsFromArray($picatts, "");
		
		if( is_array($param_array)){
			$params = implode("&", $param_array);
			//$params = urlencode($params);
		}
				
		$ret = '<a class="piclenselink" href="javascript:PicLensLite.start({feedUrl:\'' 
			.  plugins_url() . '/photosmash-galleries/bwbps-media-rss.php?'
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
	
	
	/*		SECTION:  Media Uploader Integration
	 * 		Media Uploader Integration for Admin -> Photo Manager uploading images
	 *
	*/	
	function mediaUAddGalleryFieldToMediaUploader(){
		if(isset($_REQUEST['bwbps_galid']) && (int)$_REQUEST['bwbps_galid']){
		
			$gallery_name = wp_kses( $_REQUEST['bwbps_galname'], array() );
		
			echo "<input type='hidden' id='bwbps_mediau_galid' name='bwbps_mediau_galid' value='" . (int)$_REQUEST['bwbps_galid'] . "' />
			<input type='hidden' id='bwbps_galid' name='bwbps_galid' value='" . (int)$_REQUEST['bwbps_galid'] . "' />
			<input type='hidden' name='bwbps_galname' value='" . $gallery_name . "' />
			<div style='background-color: #eaffdf; padding: 5px; border: 1px solid #a0a0a0; margin: 3px; font-size: 14px; color: #333;'>Adding to PhotoSmash: " . $gallery_name . "</div>
			";
		
		} else {
		
			$gid = isset($_REQUEST['bwbps_mediau_galid']) ? (int)$_REQUEST['bwbps_mediau_galid'] : 0;
		
			$galleryDDL = $this->getGalleryDDL($gid, "select gallery", "", "bwbps_mediau_galid", 30, true, true);
			echo "<div style='padding: 5px; margin: 3px; font-size: 14px; color: #333;'>Add to PhotoSmash: $galleryDDL</div>";
		}
	}
	
	function mediaUAddGalleryFieldToFlashUploader(){
		
			?>
			<script type="text/javascript">
			
			if (typeof flashStartUploadFunctions == 'undefined'){

				var flashStartUploadFunctions = [];
				function addFlashStartUploadFunction( funct_name ){
					flashStartUploadFunctions.push( funct_name );
					
				}

				function runFlashStartUploadFunctions(){
					if( flashStartUploadFunctions.length > 0 ){
						var bwbfunc;
						for( bwbfunc in flashStartUploadFunctions){
							
								eval(flashStartUploadFunctions[ bwbfunc ]);
							
						}
					}
				}

			}
			
			addFlashStartUploadFunction( 'bwbpsAddGalleryToFlashUploader();' );
			
				jQuery(window).load( function() {
					swfu.settings.upload_start_handler = function(){
						runFlashStartUploadFunctions();
					}
				});
				
				function bwbpsAddGalleryToFlashUploader(){
					jQuery('#bwbps_uploaded_images', top.document).show().append('<h4>Flash upload...preview not available.</h4>');
					var gid = jQuery("#bwbps_mediau_galid_flash").val() + "";

					if( gid ){
						swfu.addPostParam('bwbps_mediau_galid', gid);
						<?php
						if(isset($_REQUEST['bwbps_galid']) ){
						?>
						swfu.addPostParam('bwbps_galid', gid);
						<?php 
						}
						?>
					}	
				}
				
			</script>
			<?php
	
		if(isset($_REQUEST['bwbps_galid']) && (int)$_REQUEST['bwbps_galid']){
			
			$this->count++;
			
			echo "
			<script type='text/javascript'>
				jQuery(window).load( function() {
				//Hide the other Media Tabs
					jQuery('#tab-type_url').hide();
					jQuery('#tab-library').hide();";
			
			//Add Image Preview if available.
			if(is_array($this->img_funcs->added_images)){
				if($this->img_funcs->added_images[0]['notimage'] == true){
					echo "
						jQuery('#bwbps_uploaded_images', top.document).show().append('<h4>Upload not an image: " . $this->img_funcs->added_images[0]['file_name'] . "</h4>');
				";	
				} else {
					echo "
						jQuery('#bwbps_uploaded_images', top.document).show().append('<img height=\"80\" width=\"80\"  src=\"" 
						. $this->img_funcs->added_images[0]['thumb_full_url'] 
						. "\" />');
						";
				}
			}
			
			$gallery_name = wp_kses( $_REQUEST['bwbps_galname'], array() );
			
			echo "
				});
			</script>
				<input type='hidden' id='bwbps_mediau_galid_flash' name='bwbps_mediau_galid' value='" . (int)$_REQUEST['bwbps_galid'] . "' />
				<div style='background-color: #eaffdf; padding: 5px; border: 1px solid #a0a0a0; margin: 3px; font-size: 14px; color: #333;'>Adding to PhotoSmash: " . $gallery_name . "</div>
			";
		
		} else {
			$gid = isset($_REQUEST['bwbps_mediau_galid']) ? (int)$_REQUEST['bwbps_mediau_galid'] : 0;
			$galleryDDL = $this->getGalleryDDL($gid, "select gallery", "", "bwbps_mediau_galid", 30, true, true, "bwbps_mediau_galid_flash");
			echo "<div style='padding: 5px; margin: 3px; font-size: 14px; color: #333;'>Add to PhotoSmash: $galleryDDL</div>";
		}
	}
		
	function mediaUImportAttachmentToGallery($attach){
	
		if(isset($_REQUEST['bwbps_mediau_galid']) && (int)$_REQUEST['bwbps_mediau_galid'])		{
						
			$gallery_id = (int)$_REQUEST['bwbps_mediau_galid'];
			
			if($gallery_id && (int)$attach){
				$json = $this->img_funcs->addAttachmentToGallery($gallery_id, (int)$attach);
			}			
		}
		return;
	}
	
	
	function mediaUGetAddedImages(){
		
		if(!isset($this->img_funcs)){ return ""; }
		
		if(is_array($this->img_funcs->added_images)){
			foreach($this->img_funcs->added_images as $img){
				$ret .= '
					jQuery("#bwbps_uploaded_images", top.document).append("<div style=\'float: left;\'><img src="' 
						. $img["thumb_full_url"] . '" height="80" width="80" /></div>); 
					';						
			}
		}
		return $ret;
	}
	
	
	
	
	//Returns markup for a DropDown List of existing Galleries
	function getGalleryDDL($selectedGallery = 0, $newtag = "New", $idPfx = "", $ddlName= "gal_gallery_id", $length = 0, $showImgCount = true, $exclude_virtual = false, $ddlID=false)
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
		
		if( !$ddlID ){ $ddlID = $idPfx . "bwbpsGalleryDDL"; }
		$ret ="<select id='" . $ddlID . "' name='$ddlName'>".$ret."</select>";		
		
		return $ret;
	}
	
	function getGalleriesQuery($exclude = false){
		global $wpdb;
		
		if($exclude){
			$excludesql = " WHERE ".PSGALLERIESTABLE.".gallery_type < 10 ";
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
	
	/*	shareImages
	 *	Share images via Pixoox
	 *
	*/
	function shareImages($image){
	
		if( !isset($this->psShareImage) ){
			
			require_once('bwbps-share.php');
			$this->psShareImage = new BWBPS_Share();
		
		}
				
		$this->psShareImage->shareImage($image);
		
		return;
		
	}
	
	
	/* Google Maps Shortcode
	 * Adds a Google Map for a gallery
	 * Grabs $this->lastGalleryID if none given
	*/
	function gmapShortCode($atts, $content=null){
		
		if(!is_array($atts)){
			$atts = array();
		}
		extract(shortcode_atts(array(
			'id' => false,
			'width' => 0,
			'height' => 0,
			'class' => '',
			'gmap_skip_api' => false
			
		),$atts));
		
		if( $gmap_skip_api ){
			$this->skipGoogleAPI = true;
		}
		
		$this->loadGoogleMaps = true;
		
		$gallery_id = false;
		
		if( $id == 'gallery' || strpos($id, '[gallery_id]') !== false ){
			$g = $this->getGallery();
			$gallery_id = $g['gallery_id'];
		}
		
		$gmap_id = $this->calculateGMapID($id, $gallery_id);
;
		if( !is_array($this->placedMaps) || empty($this->placedMaps[$gmap_id]) ){
			$width = (int)$width ? (int)$width : 
				((int)$this->psOptions['gmap_width'] ? (int)$this->psOptions['gmap_width'] : 350);
			$height = (int)$height ? (int)$height : 
				((int)$this->psOptions['gmap_height'] ? (int)$this->psOptions['gmap_height'] : 300);
			
			$content = "<div id='" . $gmap_id . "' class='bwbps_gmap bwbps_gmap_" . $g['gallery_id'] 
					. " " . $this->h->alphaNumeric($class) 
					. "' style='width: " . $width . "px; height: " . $height . "px;'></div>";
					
			$this->placedMaps[$gmap_id] = true;		// Set this to prevent duplicate divs
			
		}
		
		return $content;
	}
	
	function calculateGMapID($id = false, $gallery_id = 0){
		global $post;
		
		if(!$id || $id == 'post' || $id == 'true'){
			$id = 'post_map_' . (int)$post->ID;
		}
		
		if( $gallery_id && strpos($id, "[gallery_id]") !== false ){
			$id = str_replace("[gallery_id]", $gallery_id, $id);
		}
		
		if( (int)$id ){
			$id = 'gmap_id_' . (int)$id;
		}
		
		if( $id == 'gallery' ){
			$id = 'post_map_' . (int)$post->ID . "_" . $gallery_id;
		}
		
		$id = str_replace("-", "_", $id);
		
		$id = $this->h->alphaNumeric( $id, false, true );
		
		return $id;
	
	}

	
} //End of BWB_PhotoSmash Class


/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */
/* ***************************************************************************************** */

$bwbPS = new BWB_PhotoSmash();

//Template Tags
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

// Used for displaying a link to the Favorites Page (set page in PhotoSmash Settings
function photosmash_favlink($link_text='Favorite Images', $before='', $after=''){

	echo get_photosmash_favlink($link_text, $before, $after);

}

function get_photosmash_favlink($link_text='Favorite Images', $before='', $after=''){
	global $current_user;
	global $bwbPS;
		
	$user_id = (int)$current_user->ID;
				
	if(current_user_can('level_0') && $user_id){
		
		if($bwbPS->psOptions['favorites_page']){
			$permalink =  get_permalink( (int) $bwbPS->psOptions['favorites_page'] );
			
			if($permalink){
				$ret = $before . "<a title='View your favorite images' href='". $permalink
					. "'>$link_text</a>" . $after;
			}
		}
		
	}
	return $ret;
}

//Set up the actions!
add_action('admin_notices', array(&$bwbPS, 'verifyDatabase'));
add_action('admin_notices', array(&$bwbPS, 'verifyGalleryViewerPage'));

//Call the Function that will Add the Options Page
add_action('admin_menu', array(&$bwbPS, 'photoSmashOptionsPage'));

//Inject Admin Javascript & Styles

add_action('admin_print_scripts', array( &$bwbPS, 'injectAdminJS') );
add_action('admin_print_styles', array( &$bwbPS, 'injectAdminStyles') );

//Call the INIT function whenever the Plugin is activated
add_action('activate_photosmash-galleries/bwb-photosmash.php',
array(&$bwbPS, 'init'));


add_action('init', array(&$bwbPS, 'enqueueBWBPS'), 1);

add_action( 'init', array(&$bwbPS, 'createTaxonomy'), 0 );

add_action('wp_print_styles', array(&$bwbPS, 'injectBWBPS_CSS'));

//add_action('wp_head', array(&$bwbPS, 'injectBWBPS_CSS'), 10);

add_action('wp_footer', array(&$bwbPS, 'injectFooterJavascript'), 100);

//Media Uploader Integration
add_action('post-html-upload-ui', array(&$bwbPS, 'mediaUAddGalleryFieldToMediaUploader'), 10);
add_action('post-flash-upload-ui', array(&$bwbPS, 'mediaUAddGalleryFieldToFlashUploader'), 10);
add_action('add_attachment',array(&$bwbPS, 'mediaUImportAttachmentToGallery'), 100 );

add_filter('the_content',array(&$bwbPS, 'autoAddGallery'), 100);

add_shortcode('photosmash', array(&$bwbPS, 'shortCodeGallery'));

add_shortcode('psmash', array(&$bwbPS, 'shortCodes'));

add_shortcode('photosmash_gmap', array(&$bwbPS, 'gmapShortCode'));

add_filter('plugin_action_links', array(&$bwbPS, 'add_settings_link'), 10, 2 );

if( version_compare($wp_version,"2.8", ">=" ) ){
	//Load the PhotoSmash Widget
	add_action( 'widgets_init', array(&$bwbPS, 'loadPSWidgets') );
}

?>