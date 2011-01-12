<?php
//Admin Pages for BWB-PhotoSmash plugin


class BWBPS_FieldEditor{
		
	var $options;
	var $message = false;
	var $msgclass = "updated fade";
	var $field_id = false;
	var $form_id = 0;
	
	//Constructor
	function BWBPS_FieldEditor($options){
		
		$this->form_id = 1;
		$options['form_id'] = 1;
		
		$this->options = $options;
				
		if(isset($_REQUEST['bwbps_field_id'])){
			$this->field_id = (int)$_REQUEST['bwbps_field_id'];
			
		} else { 
			if(isset($_GET['field_id'])){
				$this->field_id = (int)$_GET['field_id'];
			} else { 
				$this->field_id = 0; 
			}
		}
		
		/*  Multi-Form use only....NOT IMPLEMENTED
		//If form_id is missing and we have a field_id, get form_id from field
		if($this->field_id && !$options['form_id']){				
			$form_id = $wpdb->get_var("SELECT form_id FROM "
				.PSFIELDSTABLE." WHERE field_id = ".$this->field_id);
		}
		*/
						
		//Save fields
		if(isset($_POST['saveBWBPSField'])){
			$this->saveField($this->options);
		}
		
		//Delete fields
		if(isset($_POST['deleteBWBPSField'])){
			$this->deleteField($this->options);
		}
		
		//Complete Delete fields
		if(isset($_POST['completeDeleteBWBPSField'])){
			$this->deleteField($this->options, true);
		}
		
		//Generate Custom Table
		if(isset($_POST['generateBWBPSCustomTable'])){
			$this->generateCustomTable($this->options);
		}
				
		$this->printEditFieldsPage($options, $this->field_id);
	}
	
	//	Generate the CUSTOM TABLE
	//	- will update any changes to fields
	//  - does not delete fields that are dropped from field list
	//	- will create a new table if you change the Custom Table Name
	//	- does not drop orphaned tables or delete their contents
	function generateCustomTable($options)
	{
			
		//Create or update the Custom Table
		global $wpdb;
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');  // the magic file containing dbDelta()
		
		//Get the field data
		$results = $wpdb->get_results("SELECT * FROM ".PSFIELDSTABLE." ORDER BY seq;");
		
		//Error out if there are no fields
		if(!$results || $wpdb->num_rows == 0){
			$this->message .= "<p >Table generation failed.  No fields to create.</p>";
			$this->msgclass = 'error';
			return false;
		}
		
		//Here's our Custom Table Name
		$table_name = PSCUSTOMDATATABLE;
				
		//SQL for table creation & updating
		$sql = "CREATE TABLE " . $table_name . " (
			id INT(11) NOT NULL AUTO_INCREMENT,
			image_id INT(11) NOT NULL,
			updated_date TIMESTAMP NOT NULL, 
			bwbps_status TINYINT(1) NOT NULL default '0' ";
		
		foreach($results as $row)
		{
			if(!$row->multi_val){
				$ret = $this->getFieldSQL($row);
				if($ret){
					$sql .= $ret;
				}
			} else {
				//we're going to display this list so user is aware that he had multi value fields that didn't generate
				$multifields[] = $row->field_name;
			}
		}
		
		if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty($wpdb->charset) )
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				if ( ! empty($wpdb->collate) )
					$charset_collate .= " COLLATE $wpdb->collate";
		}	
		
		$sql .= " ,
				PRIMARY KEY  (id)
				)  $charset_collate;";
		
		//Here we go....make the table.....NOW!
		$ret = dbDelta($sql);
		
		$this->message = "<p>Table created: ".$table_name;
		//$this->message .= "<p>$sql</p>";
		if(count($multifields) > 0){
			$m = implode("</b>, <b>",$multifields);
			$this->message .= "<p>Fields set to multiple values (not included in table): <b>".$m."</b></p>";
		}
		
		
		//Set fields to show that they have been generated
		$wpdb->query("UPDATE ".PSFIELDSTABLE." SET status = 1 ");
		
		// Store custom fields as an option
		$sql = "SELECT * FROM ".PSFIELDSTABLE." WHERE status = 1 ORDER BY seq";
		$fields = $wpdb->get_results($sql);
		
		$fields = $fields ? $fields : 'none';
		
		update_option('bwbps_custom_fields', $fields);
		
		return true;
	
	}
	
	//return the sql for a field for the table generator
	function getFieldSQL($row){
		switch ($row->type) {
			case 0 :
				if($row->numeric_field == 1){
					$type = "DOUBLE";
				} else {
					$type = "VARCHAR(255)";
				}
				break;
			case 1 :
				$type = "MEDIUMTEXT";
				break;
			case 2 :
				$type = "VARCHAR(255)";
				break;
			case 3 :
				$type = "VARCHAR(255)";
				break;
			case 4 :
				$type = "VARCHAR(255)";
				break;
			case 5 :
				$type = "DATETIME";
				break;
			case 6 :
				$type = "TEXT";
				break;
			case 7 :
				$type = "VARCHAR(255)";
				break;
			case 30 :
				//Post ID - hidden field
				$type = "INT";
				break;
			case 35 :
				//Current Category ID - hidden field
				$type = "INT";
				break;
			case 40 :
				//Category drop down
				$type = "INT";
				break;
			
		}
		
		$ret = ",
				".$row->field_name." ".$type." ";
		
		return $ret;
	}
	
	function deleteField($options, $dropColumnFromTable = false)
	{
		global $wpdb;
		
		//This section deletes Field settings
		check_admin_referer( 'update-photosmashfields');
		
		if($dropColumnFromTable){
			//Drop Column from Custom Data table
			$fieldName = $wpdb->get_var("SELECT field_name FROM "
				.PSFIELDSTABLE." WHERE field_id = ".(int)$this->field_id);
				
			if($fieldName){
				$sql = "ALTER TABLE ".PSCUSTOMDATATABLE." DROP COLUMN ".$fieldName;
				$wpdb->query($sql);
			}
		}
		
		$ret = $wpdb->query("DELETE FROM ".PSFIELDSTABLE." WHERE field_id="
			.(int)$this->field_id." LIMIT 1" );
		if($ret){$this->message = "<b>Field deleted...</b>";
			$this->field_id = 0;
		}
		
		// Store custom fields as an option
		$sql = "SELECT * FROM ".PSFIELDSTABLE." WHERE status = 1 ORDER BY seq";
		$fields = $wpdb->get_results($sql);
		
		$fields = $fields ? $fields : 'none';
		
		update_option('bwbps_custom_fields', $fields);
		
		return;
	}
	
	
	function saveField($options)
	{
		global $wpdb;

		//This section saves Field settings
			check_admin_referer( 'update-photosmashfields');
			$field_id = (int)$_POST['bwbps_field_id'];
			$d['field_name'] = $_POST['bwbps_field_name'];
			
			if(!$this->checkName($d['field_name'], true)){
				return false;				
			}
			
			$d['label'] = $_POST['bwbps_label'];
			$d['type'] = (int)$_POST['bwbps_type'];
			$d['numeric_field'] = isset($_POST['bwbps_numeric_field']) ? 1 : 0;
			$d['multi_val'] = isset($_POST['bwbps_multi_val']) ? 1 : 0;
			
			/*  Multi-Value fields only...Not Implemented
			//Only allow multiple values for Textboxes, checkboxes, and Date Pickers
			if($d['multi_val'] == 1 ){
				if($d['type'] == 1 || $d['type'] == 2 || $d['type'] == 3 ){
					$d['multi_val'] = 0;
				}
			} else {
				if($d['type'] == 4){$d['multi_val'] = 1;}
			}
			*/
			
			$d['html_filter'] = (int)$_POST['bwbps_html_filter'];
						
			$d['default_val'] = $_POST['bwbps_default_val'];
			$d['auto_capitalize'] = (int)$_POST['bwbps_auto_capitalize'];
			$d['keyboard_type'] = (int)$_POST['bwbps_keyboard_type'];
			$d['seq'] = (int)(trim($_POST['bwbps_seq']));
			$d['form_id'] = $options['form_id'];
			$d['status'] = 0;
			
			
			if($field_id == 0){
				$nametest = $wpdb->get_var($wpdb->prepare('SELECT field_name FROM '
					.PSFIELDSTABLE.' WHERE field_name = %s AND form_id = %d',$d['field_name'], $d['form_id']));
				
				if($nametest){
					$this->message = "<h3 style='color:red;'>Duplicate field name: ".$d['field_name']. " - Field not added.</h3>";
					return false;
				}
				
				if($wpdb->insert(PSFIELDSTABLE,$d)){
					$insert_id = $wpdb->insert_id;
					$this->message =  "<b>Field Added -> </b>".$d['field_name'];
				} else {
					$this->message = "<h3 style='color:red;'>FAILED...field failed to insert: </h3>".$d['field_name'];
				}
			}else{
				$where['field_id'] = $field_id;
				$wpdb->update( PSFIELDSTABLE, $d, $where);
				$this->message = "<b>Field updated:  ".$d['field_name']."</b>";
				
			}
			
		if($d['type'] == 2 || $d['type'] == 3 || $d['type'] == 4){
			if($insert_id){
				$ret = $this->insertListValues($insert_id);
			}else{
				$ret = $this->insertListValues($field_id);
			}
			if($ret){
				$this->message .= " &nbsp;| &nbsp;".$ret." list values added.";
			} else {
						
				$this->message .= "<h5 style='color: red;'>List values missing.  Radio buttons, Checkboxes, and Dropdowns require list values.  Please add.</h5>";
			}
		}
		
	}
	
		
	//Insert List Values for multiple selection controls:  DropDown List, checkboxes, radio buttons
	function insertListValues($field_id)
	{
		$field_id = (int)$field_id;
		if(!$field_id){ return false; }
				
		//Get the list of values from POST
		$val = trim($_POST['bwbps_valuelist']);
		if(!$val){ return false;}
		
		//Replace funky  line breaks to \n
		$val = str_replace("\r\n","\n",$val);
		$val = str_replace("\r","\n",$val);
		
		//Explode into an array of rows based on \n
		$rows = explode("\n", $val);
		
		//Delete pre-existing values for field_id
		global $wpdb;
		$sql = "DELETE FROM " . PSLOOKUPTABLE 
			." WHERE field_id = ".$field_id;
		$wpdb->query($sql);
		
		//Walk through rows and insert values
		foreach($rows as $row)
		{
			if(trim($row)){
				//Get Value and Label...create label from value if not exists...comma separated
				$s = explode(",", $row);
				if(count($s) < 2){
					$s[1] = $s[0];
				}

				$s[0] = stripslashes($s[0]);
				$s[1] = stripslashes($s[1]);

				$s[0] = trim(str_replace("'",'&#039;',$s[0]));
				$s[0] = str_replace('"','&quot;',$s[0]);
				$s[1] = trim(str_replace("'",'&#039;',$s[1]));
				$s[1] = str_replace('"','&quot;',$s[1]);
				
				$data = array( 'field_id' => $field_id,
					'value' => $s[0],
					'label' => $s[1],
					'seq' => $icnt++
					);
				$inserts += $wpdb->insert( PSLOOKUPTABLE, $data);
			}
		}
		return $inserts;
	}
	
	//Get the list values for multiple selection controls to disply in the Field Edit screen
	function getListValuesForEditor($field_id)
	{
		global $wpdb;
		;
		$sql = $wpdb->prepare("SELECT value, label FROM ".PSLOOKUPTABLE." WHERE field_id = %d ORDER BY seq", $field_id);
		$query = $wpdb->get_results($sql,ARRAY_A);
		
		if( $query && $wpdb->num_rows > 0){
			foreach($query as $row)
			{
				$ret[] = implode(', ', $row);
			}
			$ret = implode("\n",$ret);
			return $ret;
		}
		return false;
		
	}
	
	//Returns markup for a DropDown List of existing fields
	function getFieldsDDL($form_id, $selectedField = 0)
 	{
 		global $wpdb;
 		 
		$ret = "<option value='0'>&lt;new&gt;</value>";
		
		$query = $wpdb->get_results("SELECT field_id, field_name, seq FROM "
			.PSFIELDSTABLE." WHERE status > -1 AND form_id = ".(int)$form_id." ORDER BY seq;");
		
		if($query){
			foreach($query as $row){
		
				if($selectedField == $row->field_id){$sel = "selected='selected'";}else{$sel = "";}
				$ret .= "<option value='".$row->field_id."' ".$sel.">".$row->field_name." (".$row->seq.")</option>";
		
			}
		}
		$ret ="<select id='bwbps_fieldDropDown' name='bwbps_field_id'>".$ret."</select>";
		return $ret;
	}
	
	// ***************************  ADD / EDIT FIELDS   ****************************
	
	//Disply the Add/Edit Fields Page
	function printEditFieldsPage($options, $field_id){
		global $wpdb;
				
		$fieldsDDL = $this->getFieldsDDL($options['form_id'], $field_id);
		if($field_id){
			$fieldOptions = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.PSFIELDSTABLE.' WHERE field_id = %d',$field_id), ARRAY_A);
			
			$multivalues = trim($this->getListValuesForEditor($field_id));
			
			//Alert the user if they are doing a checkbox, radio, or dropdown and don't have any values saved.
			switch ($fieldOptions['type']){
				case 0: break; case 1: break; case 5: break; case 6: break;
				case 30: break; case 35: break; case 40: break;
				default :
					if(!$multivalues)
					{
						if(!strstr($this->message,"List values missing")){
							$this->message .= "<h5 style='color: red;'>List values missing.  Radio buttons, Checkboxes, and Dropdowns require list values.  Please add.</h5>";
						}
					}
					break;
			}
		}	
		
		?>
		<div class=wrap>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<?php bwbps_nonce_field('update-photosmashfields'); ?>
		<h2>PhotoSmash Galleries -> Add/Edit Custom Fields</h2>
		
		<h3>Custom Fields Editor</h3>
		<?php echo PSADVANCEDMENU; ?>
		<?php 
			$fieldsTable = $this->getTableOfFields($options['form_id']);
			
			if($this->ungeneratedfields)
			{$this->message .= "<p style='color:red; margin-bottom: 10px;'>Custom table may be out of date.  Generate Table when finished adding/editing fields.</p>";}
			
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>
<table class="form-table"><tr>
<tr>
<th><input type="submit" name="saveBWBPSField" class="button-primary" tabindex="20" value="<?php _e('Save Field', 'bwbpsLang') ?>" /></th>
<td>
	<?php if($this->options['use_custom_fields'] == 0){
		?>
	<input type="submit" onclick='return bwbpsConfirmGenerateCustomTable();' name="generateBWBPSCustomTable" class="button-primary" tabindex="30" value="<?php _e('Generate Table', 'bwbpsLang') ?>" />
	<em>After all fields are added/changed, generate custom table.</em>
	<?php } else { echo "&nbsp;";} ?>
</td>
</tr>
<tr>
<th>Select field to edit:</th><td><?php echo $fieldsDDL;?>&nbsp;<input type="submit" name="showFieldSettings" tabindex="100" value="<?php _e('Edit', 'bwbpsLang') ?>" />
<input type="submit" name="deleteBWBPSField" onclick='return bwbpsConfirmDeleteField(false);' value="<?php _e('Delete', 'bwbpsLang') ?>" /> 
<input type="submit" name="completeDeleteBWBPSField" onclick='return bwbpsConfirmDeleteField(true);' value="<?php _e('Complete Delete', 'bwbpsLang') ?>" />

</td></tr>

<tr>
	<th>Database field name:</th>
	<td>
		<input type='text' name="bwbps_field_name" value='<?php echo $fieldOptions['field_name'];?>'/>
		<br/>Use letters, numbers, and underscore ( _ ) only
		<h4>Reserved Words (do not use these):</h4><p>
		alerted, avg_rating, caption, caption_2, category_id, category_link, category_name, comment_id, contributor, created_date, custom_fields, date_added, done, file_name, file_url, full_caption, gallery_id, gallery_name, image, image_caption, image_id, image_name, image_select, image_select_2, linked_image, loading, message, post_id, rating_cnt, seq, status, submit, thumb, thumbnail, thumbnail_2, updated_by, updated_date, url, user_id, user_name, user_url
		</p>
	</td>
</tr>
<tr>
	<th>Label:</th>
	<td>
		<input type='text' name="bwbps_label" value='<?php echo $fieldOptions['label'];?>'/>
	</td>
</tr>
<tr>
<th>Order:</th>
	<td>
			<?php 
				$nextseq = $this->getNextSeq($options['form_id']);
				if( $fieldOptions['seq'] === null)
				{$seq = $nextseq;}else{$seq = (int)$fieldOptions['seq'];}
			
			?>
			<input type='text' name="bwbps_seq" value='<?php echo $seq; ?>'/>
			Next sequence #: <?php echo $nextseq; ?>
	</td>
</tr>
<tr>
	<th>Type:</th>
	<td>
			<input type="radio" name="bwbps_type" value="0" <?php if($fieldOptions['type'] == 0) echo 'checked'; ?>>Textbox &nbsp;-&gt;&nbsp; Numeric? <input type="checkbox" name="bwbps_numeric_field" <?php if($fieldOptions['numeric_field'] == 1) echo 'checked'; ?>><br/>
			<input type="radio" name="bwbps_type" value="1" <?php if($fieldOptions['type'] == 1) echo 'checked'; ?>>Multi-line Textbox<br/>
			<input type="radio" name="bwbps_type" value="2" <?php if($fieldOptions['type'] == 2) echo 'checked'; ?>>Dropdown List<br/>
			<input type="radio" name="bwbps_type" value="3" <?php if($fieldOptions['type'] == 3) echo 'checked'; ?>>Radio Buttons<br/>
			<input type="radio" name="bwbps_type" value="4" <?php if($fieldOptions['type'] == 4) echo 'checked'; ?>>Checkbox (Use only 1 value below. Only 1 value will be saved)<br/>
			<input type="radio" name="bwbps_type" value="5" <?php if($fieldOptions['type'] == 5) echo 'checked'; ?>>Date Picker  (uses <a target='_blank' href='http://docs.jquery.com/UI/Datepicker'>jQuery UI DatePicker</a>)<br/>
			<input type="radio" name="bwbps_type" value="7" <?php if($fieldOptions['type'] == 7) echo 'checked'; ?>>URL  (validates that entry is valid url format)<br/>
			<hr/>
			<span style='color:#777;'>Special WordPress field types:</span><br/>
			<input type="radio" name="bwbps_type" value="30" <?php if($fieldOptions['type'] == 30) echo 'checked'; ?>>Post ID - hidden field with value of current Post ID<br/>
			<input type="radio" name="bwbps_type" value="35" <?php if($fieldOptions['type'] == 35) echo 'checked'; ?>>Current Category - hidden field with value of current category (Advanced usage only)<br/>
			<input type="radio" name="bwbps_type" value="40" <?php if($fieldOptions['type'] == 40) echo 'checked'; ?>>Category Drop Down - drop down of all WP categories<br/>
		<?php 
		//Hidden fields not implemented
		/*	<input type="radio" name="bwbps_type" value="6" <?php if($fieldOptions['type'] == 6) echo 'checked'; ?>>Hidden<br/> */ 		
		?>
	</td>
</tr>
	<?php /*
<tr style='display:none;'>
	<th>Allow multiple values:</th>
	<td>
		<input type="checkbox" name="bwbps_multi_val" <?php if($fieldOptions['multi_val'] == 1) echo 'checked'; ?>>
		<br/><span style='font-size: 9px; line-height: 12px;'>Fields allowing multiple values will use <b>WP Custom Fields</b><br/>even if Custom Table is selected in 'Where to store data'
		<br/>in the <a href='admin.php?page=supple-forms.php'>General Settings</a> page.  Other fields will use
		<br/><b>Custom Table as directed.<br/><br/>Only available for: Textboxes, Checkboxes, & Dates</b></span>
	</td>
</tr>
*/
	?>

<tr>
	<th>HTML filtering:</th>
	<td>
		<input type="radio" name="bwbps_html_filter" value="0" <?php if($fieldOptions['html_filter'] == 0) echo 'checked'; ?>>Filter all html<br/>
		<input type="radio" name="bwbps_html_filter" value="1" <?php if($fieldOptions['html_filter'] == 1) echo 'checked'; ?>>Allow formatting tags (b, strong, em, code)<br/>
		<input type="radio" name="bwbps_html_filter" value="2" <?php if($fieldOptions['html_filter'] == 2) echo 'checked'; ?>>Allow formatting & links & lists<br/>
		<span>Uses WordPress HTML filtering functionality (wp_kses)</span>
	</td>
</tr>
<tr>
<th>Default value:</th>
	<td>
			<input type='text' name="bwbps_default_val" value='<?php echo $fieldOptions['default_val'];?>'/>
	</td>
</tr>
<tr>
<th>Auto Capitalize:</th>
	<td>
		<em>Auto Capitalization for the Mobile Phone Apps</em><br/>
		<input type="radio" name="bwbps_auto_capitalize" value="0" <?php if($fieldOptions['auto_capitalize'] == 0) echo 'checked'; ?>>none<br/>
		<input type="radio" name="bwbps_auto_capitalize" value="1" <?php if($fieldOptions['auto_capitalize'] == 1) echo 'checked'; ?>>Capitalize Words<br/>
		<input type="radio" name="bwbps_auto_capitalize" value="2" <?php if($fieldOptions['auto_capitalize'] == 2) echo 'checked'; ?>>Capitalize sentences.<br/>
		<input type="radio" name="bwbps_auto_capitalize" value="3" <?php if($fieldOptions['auto_capitalize'] == 3) echo 'checked'; ?>>CAPITALIZE ALL LETTERS
	</td>
</tr>
<tr>
<th>Keyboard Type:</th>
	<td>
		<em>Keyboard Type for the Mobile Phone Apps</em><br/>
		<input type="radio" name="bwbps_keyboard_type" value="0" <?php if($fieldOptions['keyboard_type'] == 0) echo 'checked'; ?>> Default<br/>
		<input type="radio" name="bwbps_keyboard_type" value="1" <?php if($fieldOptions['keyboard_type'] == 1) echo 'checked'; ?>> ASCII Capable<br/>
		<input type="radio" name="bwbps_keyboard_type" value="2" <?php if($fieldOptions['keyboard_type'] == 2) echo 'checked'; ?>> Numbers and Punctuation<br/>
		<input type="radio" name="bwbps_keyboard_type" value="3" <?php if($fieldOptions['keyboard_type'] == 3) echo 'checked'; ?>> URL<br/>
		<input type="radio" name="bwbps_keyboard_type" value="4" <?php if($fieldOptions['keyboard_type'] == 4) echo 'checked'; ?>> Number Pad<br/>
		<input type="radio" name="bwbps_keyboard_type" value="5" <?php if($fieldOptions['keyboard_type'] == 5) echo 'checked'; ?>> Phone Pad<br/>
		<input type="radio" name="bwbps_keyboard_type" value="6" <?php if($fieldOptions['keyboard_type'] == 6) echo 'checked'; ?>> Name and Phone Pad<br/>
		<input type="radio" name="bwbps_keyboard_type" value="7" <?php if($fieldOptions['keyboard_type'] == 7) echo 'checked'; ?>> Email Address<br/>
		<input type="radio" name="bwbps_keyboard_type" value="8" <?php if($fieldOptions['keyboard_type'] == 8) echo 'checked'; ?>> Decimal Pad<br/>
		<input type="radio" name="bwbps_keyboard_type" value="9" <?php if($fieldOptions['keyboard_type'] == 9) echo 'checked'; ?>> Alphabet<br/>
		
	</td>
</tr>


<tr>
<th id='bwbps_valuelist'>List of values:</th>
	<td>
		Enter as:  <em>value</em><b>,</b> <em>display label</em> [new line (\n)]<br/>
		<textarea name="bwbps_valuelist" cols="35" rows="4"><?php echo htmlentities($multivalues);?></textarea>
		<br/>Required for: checkboxes, radio buttons, and dropdown lists
	</td>
</tr>
<tr>
<th><input type="submit" name="saveBWBPSField" class="button-primary" tabindex="20" value="<?php _e('Save Field', 'bwbpsLang') ?>" /></th>
<td>
	<?php if($this->options['use_custom_fields'] == 0){
		?>
	<input type="submit" onclick='return bwbpsConfirmGenerateCustomTable();' name="generateBWBPSCustomTable" class="button-primary" tabindex="30" value="<?php _e('Generate Table', 'bwbpsLang') ?>" />
	<em>After all fields are added/changed, generate custom table.</em>
	<?php } else { echo "&nbsp;";} ?>
</td>
</tr>
</table>
</form>
<br/>
<?php

if($fieldsTable){			
	echo $fieldsTable;
} else {
	echo "<h3>No fields added yet...</h3>";
}

?>
 </div>
					<?php	
	}	//End the function for printing out the Field Editor Form

	//Get a table of the created fields
	function getTableOfFields($form_id)
	{
		global $wpdb;
		$sql = "SELECT * FROM ".PSFIELDSTABLE." WHERE form_id = %d ORDER BY seq";
		$query = $wpdb->get_results($wpdb->prepare($sql, $form_id));
		
		if($this->options['use_custom_fields'] == 0 ){
			$ct = '<th scope="col" >Generated</th>';
			$b = true;
		}
		
		if($query){
			foreach($query as $row)
			{
				if($b){
					$gen = $row->status == 1 ? "<span style='color:green;'>generated</span>" : "<span style='color:red;'>not generated</span>";
					$gen = "<td>".$gen."</td>";
					if(!$row->status){$this->ungeneratedfields++;}
				}
				$multi = $row->multi_val == 0 ? 'No' : 'Yes';
				$nbr = $row->numeric_field == 0 ? 'No' : 'Yes';
				$def = $row->default_val ? $row->default_val : '&nbsp;';
				$ret .= "<tr><td>".$row->seq
					." - <a href='admin.php?page=editPSFields&field_id="
					.$row->field_id."'>"
					.$row->field_name."</a></td>"
					."<td>".$row->label."</td>"
					."<td>".$this->getControlType($row->type)."</td>"
					."<td>".$nbr."</td>"
					."<td>".$multi."</td>"
					."<td>".$def."</td>"
					.$gen."
					</tr>";
			}
		
		}
		
		
		
		return '<table class="widefat" cellspacing="0" id="bwbps-fields-table">
		<thead>
		<tr>
			<th scope="col">Field name</th>
			<th scope="col">Label</th>
			<th scope="col">Type</th>
			<th scope="col">Nbr</th>
			<th scope="col">Multi-value</th>
			<th scope="col" >Default value</th>
			'.$ct.'
		</tr>
		</thead>'.$ret.'</table>';

	}
	
	function getControlType($type)
	{
		switch ($type) {
			case 0:
				return "Textbox";
				break;
			case 1:
				return "Multi-line";
				break;
			case 2:
				return "Dropdown List";
				break;
			case 3:
				return "Radio buttons";
				break;
			case 4:
				return "Checkboxes";
				break;
			case 5:
				return "Date Picker";
				break;
			case 6:
				return "Hidden";
				break;
			case 30:
				return "Current Post ID - hidden";
				break;
			case 35:
				return "Current Category - hidden";
				break;
			case 40:
				return "Category Dropdown";
				break;
		}
	}
	
	function getNextSeq($form_id)
	{
		global $wpdb;
		$sql = "SELECT MAX(seq) FROM ".PSFIELDSTABLE." WHERE form_id = ".(int)$form_id;
		$var = $wpdb->get_var($sql);
		$var++;
		return $var;
	}
	
	
	
	function checkName($text, $setMsg=false)
	{
				
		if(stripos("|user_url|user_name|user_id|url|updated_date|updated_by|thumbnail_2|thumbnail|thumb|submit|status|seq|rating_cnt|message|loading|linked_image|image_select_2|image_select|image_name|image_id|image_caption|image|gallery_name|gallery_id|full_caption|file_url|file_name|file_name|done|date_added|custom_fields|created_date|contributor|comment_id|category_name|category_link|category_id|caption_2|caption|avg_rating|alerted|", "|" . $text . "|") === false){
			
		} else { 
			if($setMsg){
				$this->message .= "<p>Invalid field name: <b>" .htmlentities($text, ENT_QUOTES)."</b>.  Field is a <b>reserved key word</b>.</p>";
				$this->msgclass = 'error';
			}
			return false; 
		}
		
		$regex = "/^([A-Za-z0-9_]+)$/";
		if (preg_match($regex, $text)) {
			return TRUE;
		} 
		else {
			if($setMsg){
				$this->message .= "<p>Invalid field name: <b>" .htmlentities($text, ENT_QUOTES)."</b>.  Use only letters, numbers, and underscore (_).</p>";
				$this->msgclass = 'error';
			}
			return FALSE;
		}
	}
	
	
}  //closes out the class

if ( !function_exists('wp_nonce_field') ) {
        function bwbps_nonce_field($action = -1) { return; }
        $bwbps_plugin_nonce = -1;
} else {
        function bwbps_nonce_field($action = -1) { return wp_nonce_field($action); }
}


?>