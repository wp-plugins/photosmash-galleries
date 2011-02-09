<?php

class BWBPS_Info{
	
	var $message;
	var $msgclass = "updated fade";

	//Constructor
	function BWBPS_Info(){
		
		if(isset($_REQUEST['bwbpsRunDBUpdate'])){
			require_once("bwbps-init.php");
			$initer = new BWBPS_Init();
			$this->message = "<p>Database updated.</p>";
		}
		
		if(isset($_POST['bwbpsFix777'])){
			$this->fixFilePerms();
		}
		
		if(isset($_POST['bwbpsUse777'])){
			delete_option('bwbps-use777');
			add_option('bwbps-use777', '1');
		}
		
		if(isset($_POST['bwbpsDontUse777'])){
			delete_option('bwbps-use777');
		}
		
		if(isset($_POST['bwbpsUpdateTermCounts'])){
			$this->updateTagCounts();
		}
		
		$this->printInfoPage();
	}
	
	function updateTagCounts(){
	
		global $wpdb;
		
		$sql = "SELECT " . $wpdb->term_relationships . ".object_id FROM " 
			. $wpdb->term_relationships . "  JOIN " . $wpdb->term_taxonomy 
			. " ON " . $wpdb->term_relationships . ".term_taxonomy_id = " 
			. $wpdb->term_taxonomy . ".term_taxonomy_id AND " 
			. $wpdb->term_taxonomy . ".taxonomy = 'photosmash' LEFT OUTER JOIN " 
			. $wpdb->prefix."bwbps_images ON " 
			. $wpdb->prefix."bwbps_images.image_id = " 
			. $wpdb->term_relationships . ".object_id WHERE " 
			. $wpdb->prefix."bwbps_images.image_id IS NULL";
			
		$res = $wpdb->get_col($sql);
				
		if($res && is_array($res)){
		
			$sql = implode(", ", $res);
			
			$sql = "DELETE FROM " . $wpdb->term_relationships . " WHERE "
				. $wpdb->term_relationships . ".object_id IN ( " . $sql . " )";
			
			$wpdb->query($sql);
		
		}
		
		$terms = get_terms("photosmash");
		
		foreach($terms as $term){
		
			$t[] = $term->term_taxonomy_id;
		
		}
		
		if(is_array($t)){
			wp_update_term_count_now($t, 'photosmash');
		}
		
	}
	
	function fixFilePerms(){
			chmod(PSTHUMBSPATH2, 0755);
			chmod(PSIMAGESPATH2, 0755);
			chmod(PSDOCSPATH2, 0755);
			chmod(PSUPLOADPATH, 0755);
	}
	
	function printInfoPage(){
		global $wpdb;
		
		$psOptions = $this->psOptions;		
		
		//Start showing form info
		?>
		<div class=wrap>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<h2>PhotoSmash Info &amp; Trouble Shooting</h2>
		
		<?php
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'">'.$this->message.'</div>';
			}
			
			//Add Nonce for security
			bwbps_nonce_field('update-photosmashinfo');
		?>
		
		
		<h3>PhotoSmash Info</h3>
		<?php if($psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }?>
		<ul>
			<?php echo $this->getPhotoSmashInfo(); ?>
		</ul>
		
		<h3>Photo Tagging</h3>
		
		<input type='submit' name='bwbpsUpdateTermCounts' value='Update Tag Counts' /> - run this job to refresh tag counts if they are off.
		

		<h3>Database Info</h3>
		<ul>
			<?php 
				$ret = $this->getDBInfo(); 
				if(is_array($ret)){
					$ret = $this->getDBInfo(true, $ret); 	
				}
				echo($ret);
			?>
		</ul>
		<input type='submit' value="Update DB" name="bwbpsRunDBUpdate" /> Run the database Update script (Should not be necessary, but it's here just in case.)
		
		
		<h3>Server Settings</h3>
			
		<?php
		$this->getServerInfo();
		?>
		
		<br/><br/>
		<input type='submit' value="Show PHPInfo" name="bwbps_show_phpinfo" />
		<br/>
		<?php 
		if(isset($_POST['bwbps_show_phpinfo'])){
			check_admin_referer( 'update-photosmashinfo');
			phpinfo();
			
		}
		
		//close out the Wrap Div
		?>
		</form>
		<h3>Credits for Info Page</h3>
		Thanks to the eminent <a href='http://wordpress.org/extend/plugins/nextgen-gallery/'>NextGen Gallery</a> and its author, Alex Rabe, for borrowed code and ideas for this page.  NextGen is a great gallery, and totally worth your time trying out if PhotoSmash isn't for you.
		</div>
		<?php
	}
	
	function getDBInfo($isRecall = false, $my_aret=false){
		global $wpdb;
		
		$b = true;
		
		if(is_array($my_aret)){
			$aret = $my_aret;
		}
		
		
		$ret .= "<h4>Tables:</h4>
		<ul>";
		
		$aret[0] = 0;
		//Images Table
		$table_name = $wpdb->prefix . "bwbps_images";
		$ret .="
			<li>". $table_name.": ";
		
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$b = false;
			$aret[0] += 1;
			$aret[1]=" - <span style='color:red;'>was missing...update run successfully</span>";
			$ret .= "<span style='color: red;'>Missing</span></li>";
		} else {
			
			$ret .= "<span style='color: green;'>Exists</span>".$aret[1]."</li>";
			$aret[1] = '';
		}
		
		//Galleries Table
		$table_name = $wpdb->prefix . "bwbps_galleries";
		$ret .="
			<li>". $table_name.": ";
		
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$b = false;
			$aret[0] += 1;
			$aret[2]=" - <span style='color:red;'>was missing...update run successfully</span>";
			$ret .= "<span style='color: red;'>Missing</span></li>";
		} else {
			
			$ret .= "<span style='color: green;'>Exists</span>".$aret[2]."</li>";
			$aret[2] = '';
		}
		
		//Layouts Table
		$table_name = $wpdb->prefix . "bwbps_layouts";
		$ret .="
			<li>". $table_name.": ";
		
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$b = false;
				$aret[0] += 1;
			$aret[3]=" - <span style='color:red;'>was missing...update run successfully</span>";
			$ret .= "<span style='color: red;'>Missing</span></li>";
		} else {
			
			$ret .= "<span style='color: green;'>Exists</span>".$aret[3]."</li>";
			$aret[3] = '';
		}
		
		//Forms Table
		$table_name = $wpdb->prefix . "bwbps_forms";
		$ret .="
			<li>". $table_name.": ";
		
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$b = false;
				$aret[0] += 1;
			$aret[6]=" - <span style='color:red;'>was missing...update run successfully</span>";
			$ret .= "<span style='color: red;'>Missing</span></li>";
		} else {
			
			$ret .= "<span style='color: green;'>Exists</span>".$aret[6]."</li>";
			$aret[6] = '';
		}

						
		//Fields Table
		$table_name = $wpdb->prefix . "bwbps_fields";
		$ret .="
			<li>". $table_name.": ";
		
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$b = false;
			$aret[0] += 1;
			$aret[4]=" - <span style='color:red;'>was missing...update run successfully</span>";
			$ret .= "<span style='color: red;'>Missing</span></li>";
		} else {
			
			$ret .= "<span style='color: green;'>Exists</span>".$aret[4]."</li>";
			$aret[4] = '';
		}
		
		//Lookup Table
		$table_name = $wpdb->prefix . "bwbps_lookup";
		$ret .="
			<li>". $table_name.": ";
		
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$b = false;
				$aret[0] += 1;
			$aret[5]=" - <span style='color:red;'>was missing...update run successfully</span>";
			$ret .= "<span style='color: red;'>Missing</span></li>";
		} else {
			
			$ret .= "<span style='color: green;'>Exists</span>".$aret[5]."</li>";
			$aret[5] = '';
		}
		
		//Ratings Table
		$table_name = $wpdb->prefix . "bwbps_imageratings";
		$ret .="
			<li>". $table_name.": ";
		
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$b = false;
			$aret[0] += 1;
			$aret[6]=" - <span style='color:red;'>was missing...update run successfully</span>";
			$ret .= "<span style='color: red;'>Missing</span></li>";
		} else {
			
			$ret .= "<span style='color: green;'>Exists</span>".$aret[6]."</li>";
			$aret[6] = '';
		}
		
		if(!$b){
			require_once('bwbps-init.php');
			$bwbpsinit = new BWBPS_Init();						
		}
		
		if(!$isRecall && $aret[0]){
			$ret = $aret;
		}
				
		return $ret;
	}
	
	function getPhotoSmashInfo(){

		//IMAGE PATH		
		$imgpath = PSIMAGESPATH2;
		
		$ret .="<li>Image path: <span>".$imgpath."</span><br/>";
		
		if(file_exists($imgpath)){
			if(is_writable($imgpath)){
				$ret .= "<span style='color: green;'>Exists.</span>";
			} else {
				$ret .= "<span style='color: red;'>Exists - but not writeable.</span>";
				
				$this->chmod($imgpath, 0755);
				
				if(is_writable($imgpath)){
					$ret.="<br/>CHMOD attempted and succeeded.";
				}else{
					$ret.="<br/>CHMOD attempted and failed.";
				}
			}
			$b777 = substr(sprintf('%o', fileperms(PSIMAGESPATH2)), -4);
			if($b777 == "0777"){$has777 = true;}
			$ret .= " - Permissions: " .$b777;
		} else {
			mkdir(PSIMAGESPATH2, 0755);
			if(file_exists($imgpath)){
				$ret .= "<span style='color: red;'>Path originally missing.</span><span style='color: green;'> But has been added now.</span>";
			} else {
				$ret .= "<span style='color: red;'>Does not exist - tried to create but failed.</span><br/>Manually create path:  wp-content/uploads/bwbps<br/>
				Set permissions to 755.
				";
			}
			
		}
		$ret .= "</li>";
		
		//THUMBNAIL PATH
		$imgpath = PSTHUMBSPATH2;
		
		$ret .="<li>Thumbnail path: <span>".$imgpath."</span><br/>";
		
		if(file_exists($imgpath)){
			if(is_writable($imgpath)){
				$ret .= "<span style='color: green;'>Exists.</span>";
			} else {
				$ret .= "<span style='color: red;'>Exists - but not writeable.</span>";
				
				$this->chmod($imgpath);
				
				if(is_writable($imgpath)){
					$ret.="<br/>CHMOD attempted and succeeded.";
				}else{
					$ret.="<br/>CHMOD attempted and failed.";
				}
			}
			$b777 = substr(sprintf('%o', fileperms(PSTHUMBSPATH2)), -4);
						
			if($b777 <> "0755"){
				$this->fixFilePerms();
				$pmsg = " - originally set to: ".$b777.", but has been fixed";
				sleep(3);
				$b777 = substr(sprintf('%o', fileperms(PSTHUMBSPATH2)), -4);
			}
			if($b777 == "0777"){$has777 = true;}
			$ret .= " - Permissions: " .$b777 . $pmsg;
			
			

		} else {
			mkdir(PSTHUMBSPATH2, 0755);
			if(file_exists($imgpath)){
				$ret .= "<span style='color: red;'>Path originally missing.</span><span style='color: green;'> But has been added now.</span>";
				$this->chmod(PSTHUMBSPATH2);
				
				sleep(3);
				//$this->fixFilePerms();
				
			} else {
				$ret .= "<span style='color: red;'>Does not exist - tried to create but failed.</span><br/>Manually create path:  wp-content/uploads/bwbps<br/>
				Set permissions to 755.
				";
			}
		}
		$ret .= "</li>";
		
		//UPLOADS PATH
		$imgpath = PSUPLOADPATH;
		
		$ret .="<li>Uploads path: <span>".$imgpath."</span><br/>";
		
		if(file_exists($imgpath)){
			if(is_writable($imgpath)){
				$ret .= "<span style='color: green;'>Exists.</span>";
			} else {
				$ret .= "<span style='color: red;'>Exists - but not writeable.</span>";
				
				$this->chmod($imgpath, 0755);
				
				if(is_writable($imgpath)){
					$ret.="<br/>CHMOD attempted and succeeded.";
				}else{
					$ret.="<br/>CHMOD attempted and failed.";
				}
			}
			$b777 = substr(sprintf('%o', fileperms(PSUPLOADPATH)), -4);
			if($b777 == "0777"){$has777 = true;}
			$ret .= " - Permissions: " .$b777;
		} else {
			mkdir(PSUPLOADPATH, 0755);
			if(file_exists($imgpath)){
				$ret .= "<span style='color: red;'>Path originally missing.</span><span style='color: green;'> But has been added now.</span>";
				$this->chmod(PSUPLOADPATH, 0755);
			} else {
				$ret .= "<span style='color: red;'>Does not exist - tried to create but failed.</span><br/>Manually create path:  wp-content/uploads<br/>
				Set permissions to 755.
				";
			}
		}
		
		//DOCS PATH - for videos and other document uploads
		$imgpath = PSDOCSPATH2;
		
		$ret .="<li>Documents path (videos, et al): <span>".$imgpath."</span><br/>";
		
		if(file_exists($imgpath)){
			if(is_writable($imgpath)){
				$ret .= "<span style='color: green;'>Exists.</span>";
			} else {
				$ret .= "<span style='color: red;'>Exists - but not writeable.</span>";
				
				$this->chmod($imgpath, 0755);
				
				if(is_writable($imgpath)){
					$ret.="<br/>CHMOD attempted and succeeded.";
				}else{
					$ret.="<br/>CHMOD attempted and failed.";
				}
			}
			$b777 = substr(sprintf('%o', fileperms(PSDOCSPATH2)), -4);
			if($b777 == "0777"){$has777 = true;}
			$ret .= " - Permissions: " .$b777;
		} else {
			mkdir(PSDOCSPATH2, 0755);
			if(file_exists($imgpath)){
				$ret .= "<span style='color: red;'>Path originally missing.</span><span style='color: green;'> But has been added now.</span>";
				$this->chmod(PSDOCSPATH2, 0755);
			} else {
				$ret .= "<span style='color: red;'>Does not exist - tried to create but failed.</span><br/>Manually create path:  wp-content/uploads<br/>
				Set permissions to 755.
				";
			}
		}
		
		$ret .= "</li>";
		
		if($has777){
			$ret .= "<li>&nbsp;</li><li><span style='color: red;'>One or more of your folders has permissions of 0777.  This is a security risk.</span>  Click 'Set Permissions' to set to 0755, which should allow uploads and be safer. <input type='submit' name='bwbpsFix777' value='Set Permissions' /></li>";
		}
		
		$ret .= "<li style='padding: 5px; border: 1px solid #999;'><b>Permissions for Upload Folders:</b><br/><input type='submit' name='bwbpsUse777' value='Use 0777' /> <input type='submit' name='bwbpsDontUse777' value='Do Not Use 0777' /><br/><br/><span style='color: red;'>Warning: setting folder permissions to 777 is a security risk.</span><br/>If your system requires 0777 permission to allow users to upload, the 'Use 0777' setting will cause PhotoSmash to switch the folder permission at upload time and switch it back after upload, thereby minimizing the risk.  <b>Only choose 'Use 0777' if absolutely necessary</b>.<br/><br/>Current setting: ";
		
		if(get_option('bwbps-use777') == '1'){$ret .= "<span style='color:red;'>Use 0777</span>";}else{$ret .= "<span style='color: green;'>Don't use 0777</span>";}
		

		
		$ret .= "</li><li>Note: if Safe Mode is set to 'on', you will need to manually set your file permissions to 0755 or 0777 as required.  The above settings will not work automatically since Safe Mode prevents that.</li>";
		
		return $ret;
	}
	
	function getServerInfo(){
		//Thanks to Alex Rabe (NextGen Gallery) for idea and code http://alexrabe.boelinger.com/
		// and to GaMerZ for WP-ServerInfo	
		// http://www.lesterchan.net
		

	global $wpdb;
	// Get MYSQL Version
	$sqlversion = $wpdb->get_var("SELECT VERSION() AS version");
	// GET SQL Mode
	$mysqlinfo = $wpdb->get_results("SHOW VARIABLES LIKE 'sql_mode'");
	if (is_array($mysqlinfo)) $sql_mode = $mysqlinfo[0]->Value;
	if (empty($sql_mode)) $sql_mode = __('Not set', 'bwbps-lang');
	// Get PHP Safe Mode
	if(ini_get('safe_mode')) $safe_mode = __('On', 'bwbps-lang');
	else $safe_mode = __('Off', 'bwbps-lang');
	// Get PHP allow_url_fopen
	if(ini_get('allow_url_fopen')) $allow_url_fopen = __('On', 'bwbps-lang');
	else $allow_url_fopen = __('Off', 'bwbps-lang'); 
	// Get PHP Max Upload Size
	if(ini_get('upload_max_filesize')) $upload_max = ini_get('upload_max_filesize');	
	else $upload_max = __('N/A', 'bwbps-lang');
	// Get PHP Max Post Size
	if(ini_get('post_max_size')) $post_max = ini_get('post_max_size');
	else $post_max = __('N/A', 'bwbps-lang');
	// Get PHP Max execution time
	if(ini_get('max_execution_time')) $max_execute = ini_get('max_execution_time');
	else $max_execute = __('N/A', 'bwbps-lang');
	// Get PHP Memory Limit 
	if(ini_get('memory_limit')) $memory_limit = ini_get('memory_limit');
	else $memory_limit = __('N/A', 'bwbps-lang');
	// Get actual memory_get_usage
	if (function_exists('memory_get_usage')) $memory_usage = round(memory_get_usage() / 1024 / 1024, 2) . __(' MByte', 'bwbps-lang');
	else $memory_usage = __('N/A', 'bwbps-lang');
	// required for EXIF read
	if (is_callable('exif_read_data')) $exif = __('Yes', 'bwbps-lang'). " ( V" . substr(phpversion('exif'),0,4) . ")" ;
	else $exif = __('No', 'bwbps-lang');
	// required for meta data
	if (is_callable('iptcparse')) $iptc = __('Yes', 'bwbps-lang');
	else $iptc = __('No', 'bwbps-lang');
	// required for meta data
	if (is_callable('xml_parser_create')) $xml = __('Yes', 'bwbps-lang');
	else $xml = __('No', 'bwbps-lang');
	
?>
<ul>
	<li><?php _e('Operating System', 'bwbps-lang'); ?> : <span><?php echo PHP_OS; ?></span></li>
	<li><?php _e('Server', 'bwbps-lang'); ?> : <span><?php echo $_SERVER["SERVER_SOFTWARE"]; ?></span></li>
	<li><?php _e('Memory usage', 'bwbps-lang'); ?> : <span><?php echo $memory_usage; ?></span></li>
	<li><?php _e('MYSQL Version', 'bwbps-lang'); ?> : <span><?php echo $sqlversion; ?></span></li>
	<li><?php _e('SQL Mode', 'bwbps-lang'); ?> : <span><?php echo $sql_mode; ?></span></li>
	<li><?php _e('PHP Version', 'bwbps-lang'); ?> : <span><?php echo PHP_VERSION; ?></span></li>
	<li><?php _e('PHP Safe Mode', 'bwbps-lang'); ?> : <span><?php echo $safe_mode; ?></span></li>
	<li><?php _e('PHP Allow URL fopen', 'bwbps-lang'); ?> : <span><?php echo $allow_url_fopen; ?></span></li>
	<li><?php _e('PHP Memory Limit', 'bwbps-lang'); ?> : <span><?php echo $memory_limit; ?></span></li>
	<li><?php _e('PHP Max Upload Size', 'bwbps-lang'); ?> : <span><?php echo $upload_max; ?></span></li>
	<li><?php _e('PHP Max Post Size', 'bwbps-lang'); ?> : <span><?php echo $post_max; ?></span></li>
	<li><?php _e('PHP Max Script Execute Time', 'bwbps-lang'); ?> : <span><?php echo $max_execute; ?>s</span></li>
	<li><?php _e('PHP Exif support', 'bwbps-lang'); ?> : <span><?php echo $exif; ?></span></li>
	<li><?php _e('PHP IPTC support', 'bwbps-lang'); ?> : <span><?php echo $iptc; ?></span></li>
	<li><?php _e('PHP XML support', 'bwbps-lang'); ?> : <span><?php echo $xml; ?></span></li>
	</ul>
<?php
}
	
	// **************************************************************
	function chmod($filename = '', $perms = 0755) {
		// Set correct file permissions (taken from wp core)
		/* $stat = @ stat(dirname($filename));
		$perms = $stat['mode'] & 0007777;
		$perms = $perms & 0000666;
		*/
		if ( @chmod($filename, $perms) )
			return true;
			
		return false;
	}
	
	function check_safemode() {
		// Check UID in folder and Script
		// Read http://www.php.net/manual/en/features.safe-mode.php to understand safe_mode
		if ( SAFE_MODE ) {
				$message  .= '<p>SAFE MODE Restriction in effect. You may need to create the folders manually</p>';
				$message .= '<br />When safe_mode is on, PHP checks to see if the owner of the current script matches the owner of the file to be operated on by a file function or its directory';
				return $message;
			
		}
		
		return "SAFE MODE is off.  You should be ok there.";
	}
}

if ( !function_exists('wp_nonce_field') ) {
        function bwbps_nonce_field($action = -1) { return; }
        $bwbps_plugin_nonce = -1;
} else {
        function bwbps_nonce_field($action = -1) { return wp_nonce_field($action); }
}

?>