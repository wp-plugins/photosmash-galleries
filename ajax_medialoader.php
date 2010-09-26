<?php

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
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
		
		if($_POST['action'] == 'getmedia'){
			$this->getMediaGalleryVideos();
		} else {
			$this->getMediaGalleryForm();
		}
					
	}
	
	function getMediaGalleryForm(){
		
		$ret = $this->getMediaGalleryVideos();
		
		$image_id = (int)$_GET["image_id"];
		
		?>
		<script type="text/javascript">
		//<![CDATA[
		var ps_imgid = <?php echo (int)$image_id; ?>;
		//]]>
		</script>
		<?php		
		//$this->getHeader($image_id);
		echo "
		<h3>Click file name to select the file from your WP Media Library:</h3>
		<div>
		<form onsubmit='bwbpsGetMediaGalURL(); return false;'>
		Show <input id='bwbps_fileurlrecs' type='text' size=4 value='20' name='recs' /> recs.
		Starting at <input id='bwbps_fileurlstart' type='text' size=4 value='1' name='start' /> &nbsp; 
		Search <input id='bwbps_fileurlsearch' type='text' size=20 value='' name='search_term' /> <select id='bwbps_filetype'><option value='video' selected=selected>Video</option><option value='pdf'>PDF</option><option value='image'>Image</option><option value='non-image'>Non-image</option><option value='any'>Any</option></select><input type='submit' value='Get Media' name='callback'  /></form></div>
		<table class='widefat' id='bwbps_fileurl_table'>" . $ret . "</table>
		
		";
		
		return;	
	
	}
			
			
	//Get Media Gallery videos
	function getMediaGalleryVideos(){
		
		global $wpdb;
		
		$start = (int)$_POST['start'];
		$recs = (int)$_POST['recs'];
		
		$filetype = $_POST['filetype'];
		
		switch ($filetype){
			case 'video' :
				break;
			case 'pdf' :
				$filetype = 'application/pdf';
				break;
				
			case 'image' :
				$filetype = 'image';
				break;
			case 'any' :
				$filetype = '';
				break;
			case 'non-image' :
				$filetype = 'image';
				$not = " NOT ";
				break;
			default :
				$filetype = 'video';
				break;
		
		}
		
		$filetype = esc_sql(strtolower($filetype));
		
		if(!$recs){ $recs = 20; }
		
		if($start > 0){
			$start--;
		} else {
			$start = 0;
		}
		
		if(isset($_POST['search_term']) && $_POST['search_term']){
			
			$search = esc_sql( stripslashes( $_POST['search_term'] ) );
			
			$sql = "SELECT post_name, guid, post_mime_type FROM " . $wpdb->posts 
				. " WHERE post_mime_type $not LIKE '" . $filetype . "%' AND (post_name LIKE '%" . $search
				. "%' OR post_mime_type LIKE '%$search%' OR guid LIKE '%$search%') AND post_type = 'attachment' ORDER BY post_mime_type, post_name LIMIT $start, $recs";
				
			
		} else {
			
			$sql = "SELECT post_name, guid, post_mime_type FROM " . $wpdb->posts 
				. " WHERE post_mime_type $not LIKE '" . $filetype . "%' AND post_type = 'attachment' ORDER BY post_mime_type, post_name LIMIT $start, $recs";
				
		}
		
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
		
		$ret = "<thead><tr><th>File name</th><th>URL</th><th>File type</th></tr></thead>" . $ret;
		
		
		if($_POST['action'] == 'getmedia'){
			
			
			echo $ret;
			return;
		
		}
		return $ret;

	}
	
}

$bwbMediaLoader = new BWBPS_MEDIALOADER();

?>