<?php
//Importer Pages for BWB-PhotoSmash plugin


class BWBPS_Sharing{
	
	var $sharing_options;
	var $message = false;
	var $msgclass = "updated fade";
	var $h;	// variable for Helpers class
	
	//Constructor
	function BWBPS_Sharing(){
		//Get PS Defaults
		global $bwbPS;
		
		require_once(WP_PLUGIN_DIR . "/photosmash-galleries/admin/pxx-helpers.php");
		
		$this->h = new PixooxHelpers();
		
		$this->sharing_options = get_option('bwbps_sharing_options');
		
		if(isset($_POST['savesharingoptions'])){
			$this->saveSharingOptions();
		}
		
	}
	
	function saveSharingOptions(){
				
		$this->sharing_options['api_url'] = WP_PLUGIN_URL . "/photosmash-galleries/api.php";
		
		$logourl = $this->h->validURL($_POST['photosmash']['logo_url']);
		if($logourl){
			$this->sharing_options['logo_url'] = $logourl;
		}
		
		$this->sharing_options['admin_email'] = sanitize_email($_POST['photosmash']['admin_email']);
	
		if(isset($_POST['photosmash']['tags'])){
			$this->sharing_options['tags'] = wp_kses($_POST['photosmash']['tags'], array());
			$this->sharing_options['tags'] = str_replace(";", ",", $this->sharing_options['tags']);
		}
		
		$this->sharing_options['suspend_sharing'] = isset($_POST['suspend_sharing']) ? 1 : 0;
		$this->sharing_options['images_url'] = (int)$_POST['images_url'];
		$this->sharing_options['send_size'] = (int)$_POST['send_size'];
		$this->sharing_options['images_url_post_id'] = (int)$_POST['images_url_post_id'];
		
		update_option('bwbps_sharing_options', $this->sharing_options);
	
	}
			
	
	/**
	 * printImageImporter()
	 * 
	 * @access public 
	 * @prints the manage images page
	 */
	function printSharing()
	{
		global $wpdb;
		
		if(!current_user_can('level_10')){
			echo "<h3>Insufficient rights!</h3>";
			return;
		}
		
		
		if(!isset($_REQUEST['pxx_hub_status']) || $_REQUEST['pxx_hub_status'] == 'all'){ 
				$pxx_hubstatus["all"] = 'selected=selected'; 
			} else {
				$pxxhs = (int)$_REQUEST['pxx_hub_status'];
				$pxx_hubstatus["p-" . $pxxhs] = 'selected=selected';
				
				switch ($pxxhs){
					case 0 :
						$pxxstatus = "Not Applied";
						break;
					
					case 1 :
						$pxxstatus = "Sharing";
						break;
					
					case -1 :
						$pxxstatus = "Waiting";
						break;
						
					case -2 :
						$pxxstatus = "Buried";
						break;
						
					default :
						$pxxstatus = "Not Applied";
						break;
				}
				
				$pxxstatus = "<h2>Showing <span style='color: red;'>" . $pxxstatus . "</span></h2>";
					
			}
			
			$limit = (int)$_REQUEST['pxxLimit'];
			$start = (int)$_REQUEST['pxxStart'];
			
			if(!$limit){ $limit = 50; }
			if(!$start){ $start = 1; }
		

	?>		
	<div class=wrap>
		<h2>PhotoSmash Galleries</h2>
		
		<?php
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>
		
		<?php if($this->psOptions['use_advanced']) {echo PSADVANCEDMENU; } else { echo PSSTANDARDDMENU; }
			if(!isset($_POST['savesharingoptions'])){ $display = "display: none; ";}
		?>
		<hr/>
		
		<div id="bwbpsslider" class="wrap">
	<ul id="bwbpstabs">
	
				<li><a href="#sharing_settings">Settings</a></li>
				<li><a href="#manage_hubs">Sharing Hubs</a></li>
				<li><a href="#image_log">Upload Log</a></li>
					
	</ul>		
		<div id='sharing_settings' style='background-color: #fff; border: 1px solid #999; padding: 8px;'>
		<h3>Sharing Settings</h3>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<?php 
	
		echo $this->getSharingSettingsForm();
	?>
		<input name='savesharingoptions' type='submit' value='Save Settings' class="button-primary">
		<input name='bwb_selected_tab' type='hidden' value='sharing_settings' />	
	</form>
		
		</div>
		
		<div id='manage_hubs'>
		<p>
		<span style='font-size: 15px; font-weight: bold;'>Manage Sharing Hubs</span>		
		<span style='margin-left: 20px;'><input type="button" onclick="photosmash.downloadSharingHubs(); return false;" name="fetchSharingHubs" class="button" value="<?php _e('Update Hub List', 'bwbPS') ?>" /> <a href='javascript: void(0);' onclick='alert("Fetches the lastest list of Photo Sharing Hubs from the official Pixoox server."); return false;' title='Fetch lastest hub list'><img src='<?php echo BWBPSPLUGINURL;?>images/help.png' alt='Fetch latest hub list from Pixoox' /></a></span>
		</p>	
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<?php 
		$nonce = wp_create_nonce( 'bwbps-sharing' );
			echo '
		<input type="hidden" id="_bwbps-sharing" name="_nonce-bwbps-sharing" value="'.$nonce.'" />
		';
		?>
		 
		
		<div class='tablenav'>
		<span style='display:none;' class='bwbps-saving'><img src='<?php echo BWBPSPLUGINURL;?>images/wait.gif' /></span>
		
		<input type="submit" name="showPixooxHubs" class="button-primary" value="<?php _e('Show Hubs', 'bwbps') ?>" />	
		<input name='bwb_selected_tab' type='hidden' value='manage_hubs' />		
			<select name="pxx_hub_status">
				<option <?php echo $pxx_hubstatus["all"]; ?> value='all'>All</option>
				<option <?php echo $pxx_hubstatus["p-0"]; ?> value='0'>Not applied</option>
				<option <?php echo $pxx_hubstatus["p-1"]; ?> value='1'>Sharing</option>
				<option <?php echo $pxx_hubstatus["p--1"]; ?> value='-1'>Waiting</option>
				<option <?php echo $pxx_hubstatus["p--2"]; ?> value='-2'>Do Not Share</option>
				<option <?php echo $pxx_hubstatus["p--3"]; ?> value='-3'>Buried</option>
			</select>
			Search: 
			<input type='text' name='pxx_search' size=20 value='<?php  echo esc_attr($_REQUEST['pxx_search']);
			?>' />
			
			Page 
			<input type='text' name='pxxStart' size=4 value='<?php  echo $start;
			?>' />
			Per page 
			<input type='text' name='pxxLimit' size=4 value='<?php echo $limit;
			?>' />
		
		</div>
		
		<div id="pxxmenu-keeper" style='display:none;'>
		
		</div>	
		<div style='width: 98%;'>
		<table class='widefat fixed'>
		<thead><tr>
			<th style='width: 62px;'>Logo</th>
			<th style='width: 170px;'>Hub</th>
			<th>URL / Tags</th>
			<th>API URL / Pixoox Key / Email</th>
			<th style='width: 75px;'>Restricts<br/>Tags</th>
			<th style='width: 60px;'>Allows<br/>Adult</th>
		</tr></thead>
		<tbody>
		<?php 
			$hublist = $this->getSharingHubs();

			if($hublist && is_array($hublist)){
				foreach($hublist as $hub){
				
					// Calculate LOGO -- uses Gravatar from admin_email
					if( $hub->admin_email){
						$email = sanitize_email( $hub->admin_email );
						$logo = get_avatar( $email, $size = '60' ); 
					}
															
					switch ((int)$hub->hub_status){
						
						case 1 :
							$hubstatus = 'sharing';
							$hubstatus_text = $hubstatus;
							break;
						case -1 :
							$hubstatus = 'waiting';
							$hubstatus_text = $hubstatus;
							break;
						case -2 :
							$hubstatus = 'not-sharing';
							$hubstatus_text = 'not sharing';
							break;
						case -3 :
							$hubstatus = 'buried';
							$hubstatus_text = $hubstatus;
							break;
						
						default :
							$hubstatus = "not-applied";
							$hubstatus_text = 'not applied';
							break;
					}
					
					$rcats = ((int)$hub->restricts_categories)? "check" : "cross";
					$allows_adult = ((int)$hub->allows_adult)? "check" : "cross";
					
					if($hub->pixoox_key){
						$pxxkeyexists = "key exists <input type='hidden' id='pixoox-keyexists-" 
							. $hub->hub_id . "' value='1' />";
						$pxxkeycolor = "green";
					} else {
						$pxxkeyexists =  "no key";	
						$pxxkeycolor = "#cc0000";
					}
					
					
					echo "<tr id='pxxhub-" . $hub->hub_id . "' class='pxx-$hubstatus pxx-hub-row'>
						<td id='hub-logo-" . $hub->hub_id . "'>$logo</td>
						<td id='hub-name-status-" . $hub->hub_id . "'><span id='hub-name-" . $hub->hub_id . "'>" . $hub->hub_name . "</span>
						<br/><b>Status: </b><span id='hub-status-" . $hub->hub_id 
						. "' class='pxx-$hubstatus'>" . $hubstatus_text . "</span>
						<div id='pxxmenu-" . $hub->hub_id . "'></div></td>"
						. "<td><span id='hub-url-" . $hub->hub_id . "'><a target='_blank' href='" . esc_url($hub->hub_url) . "'>" 
						. esc_url($hub->hub_url) . "</a></span>
						<br/><hr/><span id='tags-" . $hub->hub_id . "'>" . esc_attr($hub->tags) . "</span></td>
						<td><span id='api-url-" . $hub->hub_id . "'>" . esc_url($hub->api_url) . "</span>
						<br/><hr/><span style='color: $pxxkeycolor ;'id='pixoox-key-" . $hub->hub_id . "'>" 
							. $pxxkeyexists . "</span><br/>" . $hub->admin_email . "
						</td>
						<td><img id='restricts-categories-" . $hub->hub_id . "' src='" . WP_PLUGIN_URL . "/photosmash-galleries/images/" 
							. $rcats . ".gif' /></td>
						<td><img id='allows-adult-" . $hub->hub_id . "' src='" . WP_PLUGIN_URL . "/photosmash-galleries/images/" 
							. $allows_adult . ".gif' /></td>
					</tr>";
				}
			}
		?>
		</tbody>
		</table>
		</div><!-- closes table_nav -->
		
	</form>
	
	</div><!-- closes manage_hubs -->
	
	<div id='image_log' style='margin-top: 20px;'>
 	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
 	<input name='bwb_selected_tab' type='hidden' value='image_log' />
 	<input type="submit" class="button-primary" value='Show Log' name='show_pxx_log' />
 	</form>
 	<?php if(isset($_POST['show_pxx_log'])) 
 		{
 			$logs = $this->getSharingLog();
 			
 			if($logs){
 				echo "<table class='widefat fixed'><thead><tr><th>Created</th><th>Hub</th><th>Status</th><th>Message</th></tr></thead><tbody>";
 				foreach($logs as $log){
 					echo "<tr><td>" . $log->created_date . "</td><td>" . $log->hub_name . " (id: " . $log->hub_id . ")</td><td>" . $log->status . "</td><td>"
 						. $log->message . "</td></tr>";
 				}
 				
 				echo "</tbody></table>";
 			
 			}
 			
 		}
 	
 	?>
 	</div>
	
	
	</div><!-- closes Wrap -->
	
	
	<script type="text/javascript">
	jQuery(document).ready(function(){
			jQuery('#bwbpsslider').tabs();	
		
		
		<?php 
		if( $_POST['bwb_selected_tab'] )
		{
			switch ($_POST['bwb_selected_tab']){
				case "image_log" :
					$tab = $_POST['bwb_selected_tab'];
					break;
				case "manage_hubs" :
					$tab = $_POST['bwb_selected_tab'];
					break;
				case "sharing_settings" :
					$tab = $_POST['bwb_selected_tab'];
					break;
				default:
					$tab = false;
					break;
			}
			if($tab){
				
				echo 'jQuery("#bwbpsslider").tabs("select","#'
					. $tab .'");';
			}
		}
		?>
		});

</script>
	
	<?php 
		// Modification MENU
		$modmenu = "
			<span id='pxxmenu' style='display:none;'>
			<a style='color:#ad0ddf;' href='javascript: void(0);' onclick='photosmash.requestSharing(); return;' title='Request sharing with Hub'>share</a> | 
			<a href='javascript: void(0);' onclick='photosmash.editHub(\"edit\"); return;'>edit</a> | 
			<a href='javascript: void(0);' title='Delete this hub' onclick='photosmash.deleteHub(); return;' >delete</a>
			<span style='display:none;' class='bwbps-saving'><img src='" 
				.  BWBPSPLUGINURL . "images/wait.gif' />
			</span>
			</span>
			";
		echo $modmenu;
		?>

 	</div><!-- closes maing Wrap class div -->
 	
<?php
	}
	
	
	function getSharingSettingsForm(){
		global $bwbPS;
		?>
				<table class="form-table">
					<tr valign="middle">
						<th scope="row">
							<label for="site_name"><?php _e("Site Name", $this->_slug); ?></label>
						</th>
						<td>
							<b><?php echo get_bloginfo('name'); ?></b>
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="site_description"><?php _e("Site Description", $this->_slug); ?></label>
						</th>
						<td>
							<b><?php echo get_bloginfo('description'); ?></b>
						</td>
					</tr>
					
					
					
					<tr valign="middle">
						<th scope="row">
							<label for="suspend_sharing"><?php _e("Suspend Sharing", $this->_slug); ?></label>
						</th>
						<td>
							<input type="checkbox" name="suspend_sharing" value="1" <?php if($this->sharing_options['suspend_sharing'] == 1 ) echo 'checked'; ?> /> (Sharing only gets loaded if you have hubs set to share...setting this will suspend sharing even if you have active hubs)
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="tags"><?php _e("Email address", $this->_slug); ?></label>
						</th>
						<td>
							<input id="admin_email" name="photosmash[admin_email]" type="text" class="" value="<?php echo esc_attr($this->sharing_options['admin_email']); ?>" size="50" />
							<p style='color: #cc0000;'>IMPORTANT: This email address will be sent to the hubs and will be used for creating a user on their WordPress sites.  If it is not unique on the Hub site, no email address will be attached to the User and you will NOT be able to manage your sites User there.  So...1) Make sure you put in an email address here; 2) Make sure it's not one that you are using for another user on the hub sites. <b>(Optional, but highly recommended: create an email address for you website that can be used specifically for these sharing purposes.  Don't forget to set a <a href='http://en.gravatar.com/'>Gravatar</a> for it!)</b>
							</p>
							<p style='color: #777;'>As with any user login system, their Admins can see your email. It will also be used for the Gravatar for your Site.  You could create a special email w/ Gravatar just for these purposes if you like.</p>
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="send_size"><?php _e("Image Size to Send", $this->_slug); ?></label>
						</th>
						<td>
							<input type="radio" name="send_size" value="1" <?php if($this->sharing_options['send_size'] == 1 ) echo 'checked'; ?> /> Thumbnail <br/>
							<input type="radio" name="send_size" value="0" <?php if(!(int)$this->sharing_options['send_size']) echo 'checked'; ?> /> Medium <br/>
							<input type="radio" name="send_size" value="2" <?php if($this->sharing_options['send_size'] == 2 ) echo 'checked'; ?> /> Large<br/>
							(It is recommended to send size 800 x 600 or smaller)
						</td>
					</tr>
					
					<tr valign="middle">
						<th scope="row">
							<label for="images_url"><?php _e("Image Links", $this->_slug); ?></label>
						</th>
						<td>
							<input type="radio" name="images_url" value="0" <?php if(!(int)$this->sharing_options['images_url']) echo 'checked'; ?> /> <?php 
							
							if( $bwbPS->psOptions['add_to_wp_media_library'] ){
								echo " WordPress Attachment Page (recommended if not using Extend)";
							} else {
								echo "Gallery Posts - post that the gallery is related to";
							}
							?><br/>
							
							<input type="radio" name="images_url" value="1" <?php if($this->sharing_options['images_url'] == 1 ) echo 'checked'; ?> /> Image Posts - the post that the image is related to<br/>
							<input type="radio" name="images_url" value="2" <?php if($this->sharing_options['images_url'] == 2 ) echo 'checked'; ?> /> Gallery Viewer (<span style='font-size: 10px; color: #333;'>
							<?php 
							if((int)$bwbPS->psOptions['gallery_viewer'] && (int)$bwbPS->psOptions['gallery_viewer'] != -1){
								$page_id = (int)$bwbPS->psOptions['gallery_viewer'];
								$page = get_page($page_id);
								if(!empty($page)){
									echo "Page: " . $page->post_title;
								}
							} else {
								echo "<span style='color:red;'>Set Gallery Viewer Page in <a href='admin.php?page=bwb-photosmash.php'>PhotoSmash Settings</a></span>";
							}
							?>
							</span>)
							 <br/>
							<input type="radio" name="images_url" value="3" <?php if($this->sharing_options['images_url'] == 3 ) echo 'checked'; ?>> Specific Post/Page (enter ID) <input id="tags" name="images_url_post_id" type="text" class="" value="<?php echo esc_attr($this->sharing_options['images_url_post_id']); ?>" size="15" /><br/>
							
							<p style='color: #777;'>Choose where you want the Sharing Hub to link your image to. "Image Posts" is ideal for those using the "Create Post on Upload" feature of <a href='http://smashly.net/photosmash-galleries/extend/' target='_blank' title='PhotoSmash Extend add-on for PhotoSmash'>PhotoSmash Extend</a>.</p>
						</td>
					</tr>
										
					<tr valign="middle">
						<th scope="row">
							<label for="tags"><?php _e("Tags", $this->_slug); ?></label>
						</th>
						<td>
							<input id="tags" name="photosmash[tags]" type="text" class="" value="<?php echo esc_attr($this->sharing_options['tags']); ?>" size="50" /> (comma separated)
							<p style='color: #777;'>Tags that describe your site and the images you'll be sharing.</p>
						</td>
					</tr>
					
					
				</table>
		<?php
	
	}
	
	function getSharingHubs(){
		global $wpdb;
		
		if(isset($_REQUEST['pxx_search'])){
			$sqlWhere = $this->getSearchString();
			if($sqlWhere){
				$sqlWhere = $sqlWhere;
			}
		}
		
		if(isset($_REQUEST['pxx_hub_status']) && $_REQUEST['pxx_hub_status'] != 'all'){
		
			$sqlWhere .= " AND hub_status = " . (int)$_REQUEST['pxx_hub_status'];
		
		}
		
		// Start / Limit
		$start = $_POST['pxxStart'];
		$limit = $_POST['pxxLimit'];
		
		if(!(int)$start){ $start = 1; }
		if(!(int)$limit){ $limit = 50; }
		$start--;
		
		$start = $limit * $start;
		
		
		$sql = "SELECT * FROM " . PSHUBSTABLE . " WHERE 1=1 " . $sqlWhere . " ORDER BY hub_id DESC LIMIT $start, $limit;";
		
  	
		$res = $wpdb->get_results($sql);
  	
		return $res;
	}
	
	function getSearchString(){
	
		$q = trim(stripslashes($_REQUEST['pxx_search']));
			
		if(!$q){ return false; }
		
		$q = explode(" ", $q);
				
		if(is_array($q)){
		
			foreach($q as $r){
				$res[] = " (CONCAT(hub_name, hub_description, tags) LIKE '%" . esc_sql($r) . "%')";
			}
			
			$ret = implode(" AND ", $res);
			
			if($ret){ $ret = " AND " . $ret; }
		
		}
		
		return $ret;
	}
	
	
	function getSharingLog(){
	
		global $wpdb;
		
		
		if((int)$_REQUEST['log_page']){ 
			$start = 25 * (int)$_REQUEST['log_page']; 
		}
		
		if( !$start ){ $start = 1; }
		$start--;
		
		$sql = "SELECT * FROM " . PSSHARINGLOGTABLE . " ORDER BY created_date DESC LIMIT " . $start . ", 25 ";
		$ret = $wpdb->get_results($sql);
		
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