<?php


class BWBPS_FormEditor{
	var $options;
	var $message = false;
	var $msgclass= "updated fade";
	var $cfList;
	var $newForm = false;
	var $selectedCF = false;
	
	/**
	 * Class Constructor
	 * 
	 * @param 
	 */
	function BWBPS_FormEditor($options){
		$this->options = $options;
		
		if(isset($_POST['bwbps_customformname'])){
			$this->selectedCF = trim($_POST['bwbps_customformname']);
		}
		
		$this->cfList = $this->getCustomFormList();
		
		//Save Custom Form
		if(isset($_POST['saveBWBPSForm'])){
			$cftosave = trim($_POST['bwbps_formtosave']);
			
			if($cftosave){ $this->selectedCF = $cftosave; }
			$this->saveForm();
		}
		
		if(isset($_POST['deleteBWBPSCustomForm']) && $this->selectedCF){
			$this->deleteCustomForm($this->selectedCF);
		}

		$this->printFormEditor();
	}
	
	/**
	 * Delete Custom Form
	 * 
	 * @param 
	 */
	function deleteCustomForm($cf){
		if(!trim($cf)){ return;}
		
		delete_option('bwbps_cf_'.$cf);
		$this->message = 'Custom Form deleted: '.$cf;
		$this->cfList = array_diff($this->cfList, array($cf));
		$this->saveCustomFormList($this->cfList);
		$this->selectedCF = false;
	}
	
	/**
	 * Save Form
	 * 
	 * @param 
	 */
	function saveForm(){
		//This section saves Form settings
		check_admin_referer( 'update-photosmashform');
		
		if($this->selectedCF){
			$cfname = $this->selectedCF;
		} else {
			if(isset($_POST['bwbps_customforminput']) && $_POST['bwbps_customforminput']){
				$cfname = trim($_POST['bwbps_customforminput']);
				$bnew = true;
			} else {
				$this->message = "Missing Custom Form name. Please enter a name for new Custom Forms.";
				$this->msgclass = "error";
				return false;		
			}
		}
		
		$this->selectedCF = $cfname;
		
		if(!$this->checkName($cfname)){
			
			$this->message = "Invalid Custom Form name ( ".$cfname. " ). Use only alpha/numeric and underscore.";
			$this->msgclass = "error";
			return false;
		}
		
		
		$cf = $_POST['bwbps_customform'];
		if(get_magic_quotes_gpc()){
			$cf = stripslashes($cf);
		}
		
		$optcf = get_option('bwbps_cf_'.$cfname);
		
		if(!isset($optcf) && !get_option('bwbps_cf_'.$cfname)){
			add_option('bwbps_cf_'.$cfname, $cf);
			$this->message = "<b>Custom Form - added</b>";
		} else {
			update_option('bwbps_cf_'.$cfname, $cf);
			$this->message = "<b>Custom Form - updated</b>";
		}
		
		//Add new forms to the CF List
		if(!in_array($cfname, $this->cfList)){
			$this->cfList[] = $cfname;
			$this->saveCustomFormList($this->cfList);
		}	
	}
	
	
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
	
	function saveCustomFormList($a){
	
		$optcf = get_option('bwbps_customformlist');
		
		if(!isset($optcf)){
			
			add_option('bwbps_customformlist', $a);
		} else {
			update_option('bwbps_customformlist', $a);
		}
	}
	
	function checkName($text)
	{
		$regex = "/^([A-Za-z0-9_\/]+)$/";
		if (preg_match($regex, $text)) {
			return TRUE;
		} 
		else {
			return FALSE;
		}
	}
	
	function getCFDDL($cfList, $selectedCF){
		$ret = "<option value='0'>&lt;New&gt;</option>";
		
		
		if(is_array($cfList)){
			foreach($cfList as $row){
				if($selectedCF == $row){
					$sel = "selected='selected'";
					if($selectedCF){
						$bNoInput = true;
					}	
				}else{$sel = "";}
			
				$ret .= "<option value='".$row."' ".$sel.">".$row."</option>";
			}
		}
		$ret ="<select id='bwbpsCFDDL' name='bwbps_customformname' style='font-size: 14px;'>".$ret."</select>";
		if(!$bNoInput){
			$this->newForm = true;
		}
		return $ret;
	
	}
	
	/**
	 * Print Form Editor to screen
	 * 
	 * @param 
	 */
	function printFormEditor(){
		//Get the data
		$cf = get_option('bwbps_cf_'.$this->selectedCF);
		
		//Get the custom fields
		$custfieldlist = $this->getCustomFieldList();
		
		$cfDDL = $this->getCFDDL($this->cfList, $this->selectedCF);
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
				<table width='100%'><tr><td><b>Select form:</b></td><td>
			
			
			<?php echo $cfDDL;?> &nbsp;<input type="submit" name="show_bwbPSForm" value="<?php _e('Edit', 'bwbPS') ?>" />
			
			<?php if(!$this->newForm){
				?>
				<input type="submit" name="deleteBWBPSCustomForm" onclick='return bwbpsConfirmCustomForm();' value="<?php _e('Delete', 'suppleLang') ?>" />
			<?php 
			}
			?>
				</td>
				</tr>
				<?php if( $this->newForm ){
					?>
					<tr>
						<td><b>New form name:</b></td>
						<td>
							<input type='text' name='bwbps_customforminput' size='30' value='' />
						</td>
					</tr>
				<?php 
				} else {
				?>
					<tr><td>Editing form:</td>
						<td><b><?php echo $this->selectedCF; ?></b>
						<input type='hidden' name='bwbps_formtosave' value='<?php 
							echo $this->selectedCF;
							?>' />
						</td>
					</tr>
				<?php
				}
				?>
				</table>
</td><td></td></tr>
			<tr>
				<td style='width: 525px;' valign="top">
					<h4>HTML for Custom Form:</h4>
					<textarea name="bwbps_customform" cols="65" rows="16"><?php echo htmlentities($cf);?></textarea><br/>
					
					<input type="submit" name="saveBWBPSForm" class="button-primary" tabindex="20" value="<?php _e('Save Form', 'bwbpsLang') ?>" />
					(use regular HTML - PHP code does not work at this time)
					<br/>Hint: if using a table, use &lt;table class='ps-form-table'&gt; to get basic PhotoSmash form styling.  Or, you can style any way your heart desires.
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
												
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumbnail] - <span style='font-size: 9px;'>displays the returned thumbnail</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumbnail_2] - <span style='font-size: 9px;'>displays thumbnail for 2nd image</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[submit] - <span style='font-size: 9px;'>'submit' button</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[done] - <span style='font-size: 9px;'>'done' button to hide form</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[loading] - <span style='font-size: 9px;'>'loading' image</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[message] - <span style='font-size: 9px;'>display ajax messages</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[category_name] - <span style='font-size: 9px;'>display name of current category</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[category_link] - <span style='font-size: 9px;'>displays current category link - use in href tag to make a link</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[category_id] - <span style='font-size: 9px;'>displays category id</span></li>
						
						<?php echo $custfieldlist;?>
					</ul>
				</td>
			</tr>
		</table>
		</form>
		Preview:
		<div>
		<?php echo $cf;?>
		</div>
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
				$ret = "*** not implemented yet ***";
				break;
			default:
				$ret = "custom field";
				break;
		}
		return $ret;
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