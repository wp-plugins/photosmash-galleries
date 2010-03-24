<?php

class BWBPS_SaveCustomFields{
		
	var $field_id = false;
	var $form_id = 0;
	
	function BWBPS_SaveCustomFields(){
	}
	
	function saveCustomFields($image_id, $id_prefix = ''){
		global $wpdb;
		
		$fields = $this->getCustomFields();
		if(!$fields ||$wpdb->num_rows == 0){return "";}

		return $this->saveFieldData($fields, $image_id, $id_prefix);
		
	}

	//Get the Custom Fields Query Results
	function getCustomFields(){
		
		global $wpdb;
		$sql = "SELECT * FROM ".PSFIELDSTABLE." WHERE status = 1 ORDER BY seq";
		
		$query = $wpdb->get_results($sql);
		return $query;
		
	}
	
	// ********************************************
	// *	Save Custom Field Data
	// ********************************************
	function saveFieldData($fields, $image_id, $id_prefix = '')
	{
		global $wpdb;
		
		
		//Get allowable HTML for filtering
		$tags = $this->getFilterArrays();
		
		foreach($fields as $f){
			
			$val = null;
				
			if(isset($_POST[ $id_prefix . "bwbps_".$blank.$f->field_name])){
				$val = $_POST[ $id_prefix . "bwbps_".$blank.$f->field_name]; 
			}
				
			$val = $this->formatSaveVal($f, $val, $tags);
			$data[$f->field_name] = $val;
			
		}
		
		$data['bwbps_status'] = 1;
		
		$where = array('image_id' => (int)$image_id );
		
		$res = $wpdb->update(PSCUSTOMDATATABLE, $data, $where);
		if(!$res){
			//Insert the data if no record was updated.
			$data['image_id'] = (int)$image_id;
			
			$reccnt = $wpdb->get_var("SELECT COUNT(image_id) as cnt FROM "
				. PSCUSTOMDATATABLE . " WHERE image_id = ".$data['image_id']);
			
			if( $reccnt <> 1){
				//delete existing records based on POST ID...must change when go to 
				//forms that don't rely on POST ID
				$wpdb->query("DELETE FROM ".PSCUSTOMDATATABLE." WHERE image_id = $image_id");
				$wpdb->insert(PSCUSTOMDATATABLE, $data);
			}
			
		}
		
		return $data;

	}

	
	function formatSaveVal($field, $val,$tags){
		//If it's a numeric field type
		if($field->numeric_field == 1 && $field->type == 0)
		{	
			$val = (double)(trim($val));	
		} else{
			if(get_magic_quotes_gpc()){
				$val = stripslashes($val);
			}
			
			
			// FILTER USER INPUT
			//Use wp_kses to strip tags...based on field level setting
			$i = (int)$field->html_filter;
			
			if($i < 3){
				$val = wp_kses($val,$tags[$i]);
			} else {
				$val = wp_kses($val,$tags[2]);
			}
		}
		//Format for date:
		if($field->type == 5 ){
				if(trim($val)){
					$val = date('Y-m-d',strtotime ($val));
				} else {
					$val = null;
				}
		}
		
		return $val;
	}
	
	
	function getFilterArrays(){
	
		//Allowable tag arrays for use with wp_kses
	 	//Allow formatting
	 	 $tags[0] = array('b' => array());
		 $tags[1] = array(
			'abbr' => array(
				'title' => array()
				),
			'acronym' => array(
				'title' => array()
				),
			'code' => array(),
			'em' => array(),
			'strong' => array(),
			'b' => array()
		);
		
		//Allow links and lists + formatting
	  	$tags[2] = array(
			'a' => array( 
				'href' => array(), 
				'title' => array(), 
				'rel' => array(),
				'target' => array(),
				'id'  => array(),
				'class' => array(),
				'style' => array()
				),
			'ul' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
				), 
			'ol' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
				),
			'li' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
				), 
			'abbr' => array(
				'title' => array()
				),
			'acronym' => array(
				'title' => array()
				),
			'code' => array(),
			'em' => array(),
			'strong' => array(),
			'b' => array(),
			'div' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
			),
			'p' => array(
				'id' => array(),
				'class' => array(),
				'style' => array()
				),
			'br' => array(),
			'hr' => array()
			
		);
		return $tags;
	}	
}