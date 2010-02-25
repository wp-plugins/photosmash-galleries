<?php

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}

if(!function_exists('json_encode')){
	require("classes/JSON.php");
}

$bwbpsuploaddir = wp_upload_dir();

//Set the Upload Path
define('PSBLOGURL', get_bloginfo('wpurl')."/");
define('PSUPLOADPATH', $bwbpsuploaddir['basedir']);

define('PSIMAGESPATH',PSUPLOADPATH."/bwbps/");
define('PSIMAGESPATH2',PSUPLOADPATH."/bwbps");
define('PSIMAGESURL',WP_CONTENT_URL."/uploads/bwbps/");

define('PSTHUMBSPATH',PSUPLOADPATH."/bwbps/thumbs/");
define('PSTHUMBSPATH2',PSUPLOADPATH."/bwbps/thumbs");
define('PSTHUMBSURL',PSIMAGESURL."thumbs/");

define('PSDOCSPATH',PSUPLOADPATH."/bwbps/docs/");
define('PSDOCSPATH2',PSUPLOADPATH."/bwbps/docs");
define('PSDOCSURL',PSIMAGESURL."docs/");

define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
define("PSCUSTOMDATATABLE", $wpdb->prefix."bwbps_customdata");
define("PSCATEGORIESTABLE", $wpdb->prefix."bwbps_categories");


class BWBPS_MEDIALOADER{
	
	var $psUploader;
	var $allowNoImg = false;
	var $psOptions;
	
	function BWBPS_MEDIALOADER(){
		
		if(!current_user_can('level_10')){
			wp_die ( __('You are not allowed to access this page.') );
		}
		
		$this->getMediaGalleryVideos();
					
	}
			
			
	//Get Media Gallery videos
	function getMediaGalleryVideos(){
		
		global $wpdb;
		
		if(isset($_POST['search_term'])){
			
			$search = esc_sql( stripslashes( $_POST['search_term'] ) );
			
			$sql = "SELECT post_name, guid, post_mime_type FROM " . $wpdb->posts 
				. " post_mime_type LIKE 'video%' AND post_name LIKE '%" . $search
				. "%' ORDER BY post_type, post_name";
		} else {
			
			$sql = "SELECT post_name, guid, post_mime_type FROM " . $wpdb->posts 
				. " WHERE post_mime_type LIKE 'video%' ORDER BY post_type, post_name";
				
		}
		
		wp_enqueue_script('jquery');
		
		$image_id = (int)$_GET["image_id"];
			
		$res = $wpdb->get_results($sql);
				
		if($res){
			
			foreach($res as $row){
				$ret .= "<tr><td><a href='javascript: void(0);' onclick='jQuery(\"#fileurl_\" + ps_imgid).val(\"" 
				. esc_attr($row->guid) . "\"); tb_remove();; "
				. " return false;'>" 
					. $row->post_name . "</a></td><td>" . $row->guid ."</td><td>"
					. $row->post_mime_type . "</td></tr>";
					
			}
		
		}
		
		?>
		<script type="text/javascript">
		//<![CDATA[
		var ps_imgid = <?php echo (int)$image_id; ?>;
		//]]>
		</script>
		<?php		
		//$this->getHeader($image_id);
		echo "
		<h3>Click file name to select:</h3>
		<table class='widefat'><thead><tr><th>File name</th><th>URL</th><th>File type</th></tr></thead>" . $ret . "</table>
		
		";
			
		return;

	}
	
	function getHeader($imgid){
	
		?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"  dir="ltr" lang="en-US"<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php bloginfo('name'); ?> - Choose File URL</title>
<link rel='stylesheet' href='<?php bloginfo('url'); ?>/wp-admin/load-styles.php?c=1&amp;dir=ltr&amp;load=global,wp-admin,media' type='text/css' media='all' />
<script type="text/javascript">
//<![CDATA[
var ps_imgid = <?php echo (int)$imgid; ?>;
//]]>
</script>
<?php wp_head(); ?>

</head>
<body>
<?php
		return;
	}
}

$bwbMediaLoader = new BWBPS_MEDIALOADER();

?>