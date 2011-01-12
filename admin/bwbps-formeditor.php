<?php


class BWBPS_FormEditor{
	var $options;
	var $form_id = 0;
	var $message = false;
	var $msgclass= "updated fade";
	
	/**
	 * Class Constructor
	 * 
	 * @param 
	 */
	function BWBPS_FormEditor($options){
		$this->options = $options;
		
		if(isset($_REQUEST['bwbps_form_id'])){
			$this->form_id = (int)$_REQUEST['bwbps_form_id'];
		} else { $this->form_id = 0; }

				
		//Save Custom Form
		if(isset($_POST['saveBWBPSForm'])){
			$ret = $this->saveForm();
			if($ret){
				$this->form_id = $ret;
			}
		}
				
		//Delete Custom Form
		if(isset($_POST['deleteBWBPSCustomForm']) && $this->form_id){
			$this->deleteCustomForm($this->form_id);
		}

		$this->printFormEditor();
	}
	
	/**
	 * Delete Custom Form
	 * 
	 * @param 
	 */
	function deleteCustomForm($form_id){
	
		global $wpdb;
		
		//This section deletes Field settings
		check_admin_referer( 'update-photosmashform');
				
		$ret = $wpdb->query( $wpdb->prepare("DELETE FROM ".PSFORMSTABLE." WHERE form_id = "
			."%d LIMIT 1", (int)$form_id ) );
			
		if($ret){$this->message = "<b>Form deleted...</b>";
			$this->form_id = 0;
		}
		
		return;
		
	}
	
	/**
	 * Save Form
	 * 
	 * @param 
	 */						 
	
	 
	function saveForm(){
		//This section saves Form settings
		
		global $wpdb;
		check_admin_referer( 'update-photosmashform');

		$d['form_name'] = stripslashes($_POST['bwbps_formname']);
		
		if(!$this->checkName($d['form_name'])){
		
			$this->message = "Invalid Custom Form name ( ".$d['form_name']
				. " ). Use only alpha/numeric and underscore.";
				
			$this->msgclass = "error";
			return false;
			
		}
		
		$d['form'] = stripslashes($_POST['bwbps_customform']);
		
		if($this->form_id == 0){
				
				$nametest = $wpdb->get_var($wpdb->prepare('SELECT form_name FROM '
					.PSFORMSTABLE.' WHERE form_name = %s',$d['form_name']));
				
				if($nametest){
					$this->message = "<h3 style='color:red;'>Duplicate form name: ".$d['form_name']. " - form not added.</h3>";
					return false;
				}
				
				
				if($wpdb->insert(PSFORMSTABLE,$d)){
					$insert_id = $wpdb->insert_id;
					$this->message =  "<b>Form Added -> </b>".$d['form_name'];
					return $insert_id;
				} else {
					$this->message = "<h3 style='color:red;'>FAILED...form failed to insert: </h3>"
						.$d['field_name'];
				}
		} else {
			$where['form_id'] = $this->form_id;
			$wpdb->update( PSFORMSTABLE, $d, $where);
			$this->message = "<b>Form updated:  ".$d['form_name']."</b>";
		}
		
		return false;
	}
	
	//Check Name - make sure no illegal characters
	function checkName($text)
	{
		$regex = "/^([A-Za-z0-9_]+)$/";
		if (preg_match($regex, $text)) {
			return TRUE;
		} 
		else {
			return FALSE;
		}
	}
	
	
	// Used for people upgrading from 0.2.99X - allows you to display old style forms to copy
	function getCustomFormList(){
		$ret = get_option('bwbps_customformlist');
		if(!is_array($ret)){
			$ret = array('default');
		} else {
			if(!in_array('default',$ret)){
				array_unshift($ret, "default");
			}
			$ret = array_unique($ret);
		}
		return $ret;
	}
	
			
	function getCFDDL($cfList, $selected_id){
		$ret = "<option value='0'>&lt;New&gt;</option>";
		
		
		$cfList = $this->getCustomFormsList();
		
		if(is_array($cfList)){
			foreach($cfList as $row){
				if($selected_id == $row['form_id']){
					$sel = "selected='selected'";
					if($selectedCF){
						$bNoInput = true;
					}	
				}else{$sel = "";}
			
				$ret .= "<option value='".$row['form_id']."' ".$sel.">".$row['form_name']."</option>";
			}
		}
		$ret ="<select id='bwbpsCFDDL' name='bwbps_form_id' style='font-size: 14px;'>".$ret."</select>";
		
		return $ret;
	
	}
	
	function getCustomFormsList(){
		global $wpdb;
		
		$query = $wpdb->get_results("SELECT form_id, form_name FROM " . PSFORMSTABLE, ARRAY_A);
		return $query;
	}
	
	function getForm($form_id)
	{
		global $wpdb;
		
		$query = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . PSFORMSTABLE
			. " WHERE form_id = %d", (int)$form_id), ARRAY_A);
		return $query;
	}
	
	/**
	 * Print Form Editor to screen
	 * 
	 * @param 
	 */
	function printFormEditor(){
		
		if((int)$this->form_id){
			
			$cf = $this->getForm($this->form_id);
		
		}
		
		//Get the custom fields
		$custfieldlist = $this->getCustomFieldList();
		
		$cfDDL = $this->getCFDDL($this->cfList, $this->form_id);
		?>
		
		<div class=wrap>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<?php bwbps_nonce_field('update-photosmashform'); ?>
		<h2>PhotoSmash Galleries -> Custom Form</h2>
				
		<?php
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>
		
		<h3>Custom Form Editor</h3>
		<?php echo PSADVANCEDMENU; ?>
		
		<table style='margin: 10px 5px 10px 0;'>
			<tr><td>
				<table width='100%'><tr><th>Select form:</th><td>
			
			
			<?php echo $cfDDL;?> &nbsp;<input type="submit" name="show_bwbPSForm" value="<?php _e('Edit', 'bwbPS') ?>" />
			
			<?php if($this->form_id)
			 {
			?>
				<input type="submit" name="deleteBWBPSCustomForm" onclick='return bwbpsConfirmCustomForm();' value="<?php _e('Delete', 'suppleLang') ?>" />
			
			<?php 
			 }
			?>
				</td>
				</tr>
						
				<tr>
					<th>Form name:</th>
					<td>
						<input type='text' name='bwbps_formname' size='30' value='<?php
							echo htmlentities($cf['form_name'], ENT_QUOTES);
						?>' /><br />
						(Form name must be alpha-numeric or underscore -- no spaces)
					</td>
				</tr>
				</table>
</td><td></td></tr>
			<tr>
				<td style='width: 525px;' valign="top">
					<h4>HTML for Custom Form:</h4>
					<textarea name="bwbps_customform" cols="65" rows="16"><?php echo htmlentities($cf['form']);?></textarea><br/>
					
					<input type="submit" name="saveBWBPSForm" class="button-primary" tabindex="20" value="<?php _e('Save Form', 'bwbpsLang') ?>" />
					
					
					<?php 
					
					$oldCFs = $this->getCustomFormList();
					if( count($oldCFs) > 1 )
					{
						
					?>
					<input type="submit" name="bwbps_obsolete" value="<?php _e('Show Obsolete', 'bwbPS') ?>" />
					<?php
					}
					?>
					
					<p>
					(use regular HTML - PHP code does not work at this time)
					<br/>Hint: if using a table, use &lt;table class='ps-form-table'&gt; to get basic PhotoSmash form styling.  Or, you can style any way your heart desires.
					</p>
					
					<?php 
					//Show Obsolete Forms if Button pressed
					if(isset($_POST['bwbps_obsolete'])){
						$this->printObsoletedForms($oldCFs);
					}
					?>
					
				</td>
				<td  style='text-align: left;' valign="top">
					<h4>Available fields:</h4>
					<ul style='padding: 6px; background-color: #fff; border: 1px solid #d8e9ec;'>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[image_select] - <span style='font-size: 9px;'>image file selection field</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[image_select_2] - <span style='font-size: 9px;'>a 2nd image file selection field</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[allow_no_image] - <span style='font-size: 9px;'>allows you to upload without a selected image</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[caption]</li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[caption_2] - <span style='font-size: 9px;'>caption for the 2nd image</span></li>
				
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[user_name] - <span style='font-size: 9px;'>displays User's Nice Name</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[user_url] - <span style='font-size: 9px;'>displays URL from User's Profile</span></li>

						<?php if($this->options['use_urlfield']){
					?>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[url] - <span style='font-size: 9px;'>alternate user supplied URL</span></li>
					<?php
					}
				?>
				
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[img_attribution] - <span style='font-size: 9px;'>attribution field</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[img_license] - <span style='font-size: 9px;'>drop-down selection of image licenses</span></li>
																
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumbnail] - <span style='font-size: 9px;'>displays the returned thumbnail</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumbnail_2] - <span style='font-size: 9px;'>displays thumbnail for 2nd image</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[submit] - <span style='font-size: 9px;'>'submit' button</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[done] - <span style='font-size: 9px;'>'done' button to hide form</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[loading] - <span style='font-size: 9px;'>'loading' image</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[message] - <span style='font-size: 9px;'>display ajax messages</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[preview_post] - <span style='font-size: 9px;'>display Preview Post links (PhotoSmash Extend only)</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[category_name] - <span style='font-size: 9px;'>display name of current category</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[category_link] - <span style='font-size: 9px;'>displays current category link - use in href tag to make a link</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[category_id] - <span style='font-size: 9px;'>displays category id</span></li>
						
						<?php echo $custfieldlist;?>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[post_cat] - <span style='font-size: 9px;'>PS Extend users only - renders a multi-select dropdown of categories</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[post_cat1] - <span style='font-size: 9px;'>PS Extend users only - sames as post_cat - allows for multiple boxes with different parents</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[post_cat2] - <span style='font-size: 9px;'>PS Extend users only - sames as post_cat</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[post_cat3] - <span style='font-size: 9px;'>PS Extend users only - sames as post_cat</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[post_tags] - <span style='font-size: 9px;'>input box for tags (tags should be comma separated)</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[tag_dropdown] - <span style='font-size: 9px;'>drop down lists for tags - [tag_dropdown tags='my,tags,go,here' selected='my']</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[geocode] - <span style='font-size: 9px;'>adds an address box and lat/lng boxes for geocoding</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[geocode_fields] - <span style='font-size: 9px;'>adds a geocode button and lat/lng boxes and uses your custom address, locality, region, country, and postal_code fields (note those fields must have those technical names to be used) for looking up the geocode</span></li>
					</ul>
				</td>
			</tr>
		</table>
		</form>
		</div>
		<?php
	}
	
	/**
	 * Get Custom Field List
	 * 
	 * @param 
	 */
	function getCustomFieldList(){
		global $wpdb;
		
		$query = $wpdb->get_results('SELECT field_name,type FROM '.PSFIELDSTABLE);
		if($query){
			foreach($query as $row){
				$fex = $this->getFieldExplanation($row->type);
				$ret .= "<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[".$row->field_name."] - <span style='font-size: 9px; color: #21759b;'>".$fex."</span></li>";
			}
		}
		return $ret;
	}
	
	/**
	 * Get Field Explanation for display
	 * 
	 * @param 
	 */
	function getFieldExplanation($fldType){
		switch($fldType){
			case 30 :
				$ret = "hidden field with current Post's ID";
				break;
			case 35 :
				$ret = "hidden field with current Cat ID";
				break;
			case 40 :
				$ret = "drop down of WP Categories";
				break;
			default:
				$ret = "custom field";
				break;
		}
		return $ret;
	}
	
	function printObsoletedForms($oldCFs){
				
		?>
		<h3>&lt;<?php echo count($oldCFs); ?>&gt; Obsolete Forms - Copy/Paste them to new forms if needed</h3>
					
		<?php	
			foreach ( $oldCFs as $cf )
			{
				
				echo "<h4>" . $cf . "</h4>";
				
				$form = get_option('bwbps_cf_'.$cf);
				
				echo '<textarea name="obsolete" cols="65" rows="16">';
				echo htmlentities($form);
				echo '</textarea><br/>';
				
			}
	
	}
	
}


/**
	 * Adds a safe way of Adding Nonces
	 * 
	 * @param 
*/
if ( !function_exists('wp_nonce_field') ) {
        function bwbps_nonce_field($action = -1) { return; }
        $bwbps_plugin_nonce = -1;
} else {
        function bwbps_nonce_field($action = -1) { return wp_nonce_field($action); }
}

?>