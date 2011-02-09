<?php

class BWBPS_UploadForm{
	var $options;
	var $stdFieldList;
	var $attFields;
	
	var $cfields;
	var $field_list;
	var $tabindex =0;
	var $cfList;
	
	function BWBPS_UploadForm($options, $cfList){
		$this->options = $options;
		$this->cfList = $cfList;
		
		$this->stdFieldList = get_option('bwbps_cf_stdfields');
		$this->attFields = $this->getFieldsWithAtts();
		
	}
	
	// These fields can have attributes in the shortcodes
	function getFieldsWithAtts(){
		return array('submit'
			, 'image_select'
			, 'image_select_2'
			, 'file_select'
			, 'video_select'
			, 'youtube_select'
			, 'done'
			, 'post_cat'
			, 'img_attribution'
			, 'img_license'
			, 'caption'
			, 'post_tags'
			, 'tag_dropdown'
			, 'preview_post'
			, 'geocode'
			, 'geocode_fields'
			);
	}
	
	function getUploadForm($g, $formName=false){
		
		if(( $formName || $this->options['use_customform'] ) && $g['cf']['form'] ){
			
			$ret = $this->getCustomForm($g,$formName);
			
		} else {

			//$g['pfx'] = "";
			
			$ret = $this->getStandardForm($g, $formName);
		}
		
		return $ret;
	}
	
	function getFormHeader($g, $formName, $bCustomForm = false){
		global $post;
		$nonce = wp_create_nonce( 'bwb_upload_photos' );
				
		$use_tb = (int)$this->psOptions['use_thickbox'];
		$use_tb = $g['use_thickbox'] == 'false' ? false : $use_tb;
		$use_tb = $g['use_thickbox'] == 'true' ? true : $use_tb;
		$use_tb = $g['form_visible'] == 'true' ? false : $use_tb;
		
		if( !$bCustomForm )
		{
			if( $g['allow_no_image'] ){
				$noimage = "<input type='hidden' id='".$g['pfx']."bwbps_allownoimg' name='bwbps_allownoimg' value='1' />";
			}
		
		
		}
		
		
		
		if( $g['using_thickbox'] )
		{
		
			$ret = '<div id="' . $g["pfx"] . 'bwbps-formcont" class="thickbox" style="display:none;">';
		
		} else {
					
			if( $g['form_isvisible'] ){
				$ret = '<div id="' . $g["pfx"] . 'bwbps-formcont">';	//Do not hide...visible is set to ON
			} else {
				$ret = '<div id="' . $g["pfx"] . 'bwbps-formcont" style="display:none;">';
			}
		}	
			$ret .= '
      	<style type="text/css">
				<!--
				#ui-datepicker-div{z-index: 199;}
				-->
		</style>
        <form id="' . $g["pfx"] . 'bwbps_uploadform" name="bwbps_uploadform" method="post" enctype="multipart/form-data" action="" style="margin:0px;" class="bwbps_uploadform">
        	<input type="hidden" id="' . $g["pfx"] . 'bwbps_ajax_nonce" name="_ajax_nonce" value="'.$nonce.'" />
        	<input type="hidden" id="' . $g["pfx"] . 'bwbps_formname" name="bwbps_formname" value="'.$formName.'" />
        	<input type="hidden" id="' . $g["pfx"] . 'bwbps_formtype" name="bwbps_formtype" value="'.(int)$g['gallery_type'].'" />
        	<input type="hidden" name="MAX_FILE_SIZE" value="'.$g["max_file_size"].'" />
        	<input type="hidden" name="bwbps_imgcaption" id="' . $g["pfx"] . 'bwbps_imgcaption" value="" />
        	<input type="hidden" name="gallery_id" id="' . $g["pfx"] . 'bwbps_galleryid" value="'.(int)$g["gallery_id"].'" />
        	<input type="hidden" name="bwbps_post_id" id="' . $g["pfx"] . 'bwbps_post_id" value="'.(int)$g["gal_post_id"].'" />
        	'.$noimage.'
        	';
			
		if($g['create_post']){
			$nonce_post = wp_create_nonce( 'bwbps_create_post' );
			$ret .= '<input type="hidden" id="' . $g["pfx"] . 'bwbps_post_nonce" name="_createpost_nonce" value="'.$nonce_post.'" />
				<input type="hidden" id="' . $g["pfx"] . 'bwbps_create_post" name="bwbps_create_post" value="'.$g['create_post'].'" />
			';
		}
		
		if($g['preview_post']){
			$ret .= "<input type='hidden' name='bwbps_preview_post' value='1' />
				";
		}
		
		if($g['cat_layout']){
			$ret .= "<input type='hidden' name='bwbps_cat_layout' value='" . $g['cat_layout'] . "' />
				";
		}
		
		if($g['post_thumbnail_meta']){
			$ret .= "
				<input type='hidden' name='bwbps_post_thumbnail_meta' value='"
				. $g['post_thumbnail_meta'] ."' />
				";
		}
		
		if($g['post_excerpt_field']){
			$ret .= "<input type='hidden' name='bwbps_post_excerpt_field' value='" . $g['post_excerpt_field'] . "' />
				";
		}
		
		if($g['post_cat_selected'] == 'post_cats' ){
			
			if(!isset($post)){
				global $post;
			}		
			
			$pcats = wp_get_post_categories($post->ID);
			
			$ret .= $this->getPostCatFieldHidden($pcats);
			
		}
		
		if( $g['post_cats'] ){
		
			$pcats = str_replace(" ","", $g['post_cats']);
			
			$pcats = explode(",", $pcats);
			$ret .= $this->getPostCatFieldHidden($pcats);
			
		}
		
		if($g['tags_for_uploads']){
		
			if($g['tags_for_uploads'] == 'tags'){ $g['tags_for_uploads'] = $g['tags']; }
			
			if($g['tags_for_uploads'] == 'post_tags'){ 
				
				$g['tags_for_uploads'] = $this->getPostTags();
			
			}
			
			if($g['tags_for_uploads']){
			$ret .= "
				<input type='hidden' name='bwbps_post_tags' value='"
				. esc_attr($g['tags_for_uploads']) ."' />
				";
			}
		}
		
		return $ret;
	}
	
	function getPostCatFieldHidden($pcats){
		if(empty($pcats)){ return; }
		if(!is_array($pcats)){
			if( !(int)trim($pcats) ){ return; }
			$pcats = array( trim($pcats) );
		}
		
		foreach($pcats as $pcat){
			if((int)trim($pcat)){
				$ret .= "<input type='hidden' name='bwbps-post-cats[]' value='"
		. (int)$pcat ."' />
		";
			}
		}
		
		return $ret;
		
	}
	
	/**
	 * Get Standard Upload Form:	
	 * 
	 * Returns the standard upload form + any custom fields if set for use
	 * @param $g: Gallery settings
	 */
	function getStandardForm($g, $formname){
		
		global $current_user; 
	
		if(!trim($formname)){ $formname = "ps-standard"; }
		$retForm = $this->getFormHeader($g, $formname, false);
		$retForm .= '
        	<table class="ps-form-table">
			<tr><th>'.$g["upload_form_caption"].'<br/>';
			
				
		$retForm .= '
			</th>
				<td align="left">
				';
		
		//Get the Upload Fields
		$retForm .= $this->getStdFormUploadFields($g);
		
		if( is_array($g['required_fields']) ){
			$reqclass = in_array("caption", $g['required_fields']) ? "ps_required_" . $g['pfx'] : "";
		} else {
			$reqclass = ""; 
		}
				
		$retForm .= '
				</td>
			</tr>
			<tr><th>Caption:</th>
				<td align="left">
					<input tabindex="50" type="text" name="bwbps_imgcaptionInput" id="' . $g["pfx"] . 'bwbps_imgcaptionInput" class="bwbps_reset ' . $reqclass . '"/>';
	
		$retForm .='
				</td>
			</tr>';
		
		
		$this->tabindex = 50;
		
		//Alternate Caption URL
		if($this->options['use_urlfield']){
			
			if( is_array($g['required_fields']) ){
				$reqclass = in_array("url", $g['required_fields']) ? "ps_required_" . $g['pfx'] : "";
			} else {
				$reqclass = ""; 
			}
		
			$retForm .= '<tr><th>Caption URL:</th>
				<td align="left">
					<input tabindex="50" type="text" name="bwbps_url" id="' . $g["pfx"] 
					. 'bwbps_url" class="bwbps_reset ' . $reqclass . '" /> Ex: http://www.mysite.com';
			
			$retForm .='
				</td>
				</tr>';
			
		}
				
		//Alternate Caption URL
		if($this->options['use_attribution']){
		
			if( is_array($g['required_fields']) ){
				$reqclass = in_array("attribution", $g['required_fields']) ? "ps_required_" . $g['pfx'] : "";
			} else {
				$reqclass = ""; 
			}
		
			$retForm .= '<tr><th>Image Attribution:</th>
				<td align="left">
					<input tabindex="50" type="text" name="bwbps_img_attribution" id="' 
					. $g["pfx"] . 'bwbps_img_attribution" class="'. $reqclass 
					. '" value="'.esc_attr($current_user->display_name).'"  /> Who took this image?';
			
			$retForm .='
				</td>
				</tr>';
				
			$retForm .= '<tr><th>Image License:</th>
				<td align="left">
				';
			
			$licopts['value'] = 0;
			$retForm .= $ret = $this->getImgLicenseDDL($g, $licopts, 50);
			
			$retForm .='
				</td>
				</tr>';
			
		}
		
		if($g['post_cat_show']){
		
			$retForm .= '<tr><th>' . $g['post_cat_show'] . ':</th>
				<td align="left">
					';
			
			$retForm .= $this->getStandardField("[post_cat]", $g);
			
			$retForm .='
				</td>
				</tr>';
		
		}
		
		if($g['post_tags'] && !$g['tags_for_uploads']){
			
			if(!$g['post_tags_label']){
				$g['post_tags_label'] = 'Tags (separate w/ commas)';
			}
					
			$retForm .= '<tr><th>' . $g['post_tags_label'] . ':</th>
				<td align="left">
					';
			
			$retForm .= $this->getStandardField("[post_tags]", $g);
			
			$retForm .='
				</td>
				</tr>';
		
		}
		
		//Add Custom Fields if use_advanced flag is set	
		if($this->options['use_customfields']){
			$retForm .= $this->getCustomFieldsForm($g);
		}
		
		// We Want custom fields (address, locality, region, post_code, country) to show up before our Geocode box
		
		if($g['geocode']){
		
			$retForm .= '<tr><th>' . $g['geocode_label'] . ':</th>
				<td align="left">
					';
			
			$retForm .= $this->getStandardField("[geocode]", $g);
			
			$retForm .='
				</td>
				</tr>';
		}
		
		if($g['geocode_fields']){
		
			$retForm .= '<tr><th>' . $g['geocode_label'] . ':</th>
				<td align="left">
					';
			
			$retForm .= $this->getStandardField("[geocode_fields]", $g);
			
			$retForm .='
				</td>
				</tr>';
		}
		
		
		//Add Submit Button
		$retForm .= '	
	        <tr><th><input type="submit" class="ps-submit" value="Submit" id="' . $g["pfx"] . 'bwbps_submitBtn" name="bwbps_submitBtn" /> ';
		
		//Figure out if need to Add Done Button
		if( $g['using_thickbox'] ){
		
			if($this->options['use_donelink']){
					$retForm .= '<a href="javascript: void(0);" onclick="tb_remove();return false;">Done</a>';
			} else {
				$retForm .= '
	        		<input type="button" class="ps-submit" value="Done" onclick="tb_remove();return false;" />
	        	';
	        }
		
		} else {
			
			if( !$g['form_isvisible'] ){
				
				if($this->options['use_donelink']){
				
					$retForm .= '<a href="javascript: void(0);" onclick="bwbpsHideUploadForm('.(int)$g["gallery_id"].',\'' . $g["pfx"] . '\');return false;">Done</a>';
					
				} else {			
				
					$retForm .= '
	        		<input type="button" class="ps-submit" value="Done" onclick="bwbpsHideUploadForm('.(int)$g['gallery_id'].',\'' . $g["pfx"] . '\');return false;" />
	        		';
	        		
	        	}
	        	
	        }	
		}
		
		$retForm .= '</th>';	//Closes out TH for Submit/Done
		
			$retForm .= '	
	        	<td>
	        		<img id="' . $g["pfx"] . 'bwbps_loading" src="'.WP_PLUGIN_URL.'/photosmash-galleries/images/loading.gif" style="display:none;" alt="loading" />	
	        	</td>
	        </tr>
	        <tr><th><span id="' . $g["pfx"] . 'bwbps_message" class="bwbps_message"></span></th>
	        <td><span id="' . $g["pfx"] . 'bwbps_result" class="bwbps_result"></span>
	        <span id="' . $g["pfx"] . 'bwbps_previewpost" class="bwbps_previewpost"></span>
	        </td>
	        </tr>
	        </table>
        </form>
      </div>
      ';
      		
		return $retForm;
	}
	
	/**
	 * Get Custom Upload Form:	
	 * 
	 * Returns the Custom upload form 
	 * @param $g: Gallery settings
	 */
	function getCustomForm(&$g, $formName=""){
	
		
		if($formName){
			//Use Supplied Custom Form name to override all others
			$customFormSpecified = true;
		}else{
			//Use Gallery specified Custom Form as next in line
			if($g['cf']['form_name']){
				$formName = trim($g['cf']['form_name']);
			} else {
				//Use the 'default' custom form as next to last resort
				$formName = 'default';
			}
		}
		
		$cf = $g['cf']['form'];
		
		$nonce = wp_create_nonce( 'bwb_upload_photos' );
		
		//Get the form header and hidden fields
		$retForm = $this->getFormHeader($g, $formName, true);
		
			
		
		//Replace Std Fld tags in Custom Form with HTML
		if(is_array($this->stdFieldList)){
			foreach($this->stdFieldList as $fname){
				unset($replace);
				unset($atts);				
						
				//Check to see if the field name is in the form at all
				if(!strpos($cf, $fname) === false){
					
				
					// Some fields can have attributes...special method for getting Attributes					
					$matches = $this->getFieldAttsMulti($cf, $fname);
					
					if($fname == 'post_cat1' || $fname == 'post_cat2' || $fname == 'post_cat3'){
						$std_name = "[post_cat]";
					} else {
						$std_name = "[".$fname."]";
					}
					
					if( is_array($matches) ){
						foreach ($matches as $atts){
						
							$atts['match_num'] = $m_num;
										
							//Get the new value for the replacement
							$replace = $this->getStandardField($std_name, $g, $atts);
								
							$m = $atts['bwbps_match'];
							
							$cf = str_replace($m, $replace, $cf);	
							$m_num++;
						}
						
					} else {
						
						$replace = $this->getStandardField($std_name, $g, $atts);
								
						$m = $matches['bwbps_match'];
							
						$cf = str_replace($m, $replace, $cf);	
					}
									
				}
				
				
			}
		}
		
		//Replace Custom Fld tags in Custom Form with HTML
		if($this->options['use_customfields'] || ($customFormSpecified && $formName)){
			//Get the custom fields
			
			unset($cfs);
			$cfs = $this->cfList;
			if($cfs){
				foreach($cfs as $fld){
					
					if(!strpos($cf, $fld->field_name) === false){
					
						$atts = $this->getFieldAtts($cf, $fld->field_name);		
						$fname = "[".$fname."]";
						
						$fldname = $atts['bwbps_match'];
							
						$ret = $this->getField($g, $fld, 50, false, false, $atts);
						$cf = str_replace($fldname, $ret, $cf);
					}
				}
			}
			
		}
		$retForm .= $cf;
		$retForm .= '
        </form>
      </div>
      ';

		return $retForm;
	}
	
	/*
	 *	Get Custom Form Definition - from database
	 *	@param $formname - retrieves by name
	 */
	function getCustomFormDef($formname = "", $formid = false)
	{
		global $wpdb;
		
		if($formname){
			$sql = $wpdb->prepare("SELECT * FROM " . PSFORMSTABLE . " WHERE form_name = %s", $formname);		
		} else {
			$sql = $wpdb->prepare("SELECT * FROM " . PSFORMSTABLE . " WHERE form_id = %d", $formid);
		}
		
		$query = $wpdb->get_row($sql, ARRAY_A);
		return $query;
		
	}
		
	/**
	 * Get the HTML for a Standard Field
	 * 
	 * Returns the Custom upload form 
	 * @param $fld - the field name that is being replaced; $g: Gallery settings;  $atts - an array of attributes that were captured from Custom Form field codes
	*/
	function getStandardField($fld, $g, $atts=false, $val=false){
		
		if($atts['tabindex']){ $tab_index = (int)$atts['tabindex'];} else { $tab_index = 50;}
		
		if($val !== false ){
			$value = ' value="' . esc_attr($val) . '" ';
		}
		
		$rfld = str_replace("[", "", $fld);
		$rfld = str_replace("]", "", $rfld);
		
		$fld_attributes = $atts['attributes'] ? " " . trim($atts['attributes']) . " " : ""; 
		
		if( $fld_attributes ){
			$fld_attributes = str_replace('\\"', "'", $fld_attributes);
			$fld_attributes = str_replace("\\'", '"', $fld_attributes);
		}
		
		if( is_array($g['required_fields']) ){
			$reqclass = in_array($rfld, $g['required_fields']) ? "ps_required_" . $g['pfx'] : "";
		} else {
			$reqclass = "";
		}
	
		switch ($fld) { 
		
			//$atts should be an array that includes $atts['gallery_type'] = (int)
			//This is what will determine what types of upload fields are returneds.
			//If not filled, then defaults to standard image upload selections
			//This drives the upload behavior on the server as well.
			case '[image_select]' :
				$ret = $this->getCFFileUploadFields($g, $atts);									
				break;
				
			case '[image_select_2]' :
				$atts['gallery_type'] = 20;
				$ret = $this->getCFFileUploadFields($g, $atts);
				break;
			
			case '[video_select]' :
				$ret = $this->getCFFileUploadFields($g, $atts);
				break;
				
			case '[file_select]' :
				$ret = $this->getCFFileUploadFields($g, $atts);
				break;
				
			case '[doc_select]' :
				$atts['gallery_type'] = 7;
				$ret = $this->getCFFileUploadFields($g, $atts);
				break;

			case '[allow_no_image]' :
				$ret = "<input type='hidden' class='". $reqclas . "' id='"
					.$g['pfx']."bwbps_allownoimg' name='bwbps_allownoimg' value='1' $fld_attributes/>";
				break;
			
			case "[submit]":
				if($atts && is_array($atts) && array_key_exists('name', $atts)){
					$submitname = $atts['name'];
				} else {
					$submitname = 'Submit';
				}
				$ret = '<input type="submit" class="ps-submit" value="'.$submitname.'" id="' . $g["pfx"] . 'bwbps_submitBtn" name="bwbps_submitBtn" ' . $fld_attributes . '/>';
				break;
				
			case "[done]":
				
				if(is_array($atts) && array_key_exists('name', $atts)){
					$donename = $atts['name'];
				} else {
					$donename = 'Done';
				}
			
				if(!$this->options['use_thickbox'] && !$g['use_thickbox']){
				
					if($this->options['use_donelink']){
						$ret .= '<a href="javascript: void(0);" onclick="bwbpsHideUploadForm('.(int)$g["gallery_id"].',\'' . $g["pfx"] . '\');return false;" ' . $fld_attributes . '>'.$donename.'</a>';
					} else {			
						$ret .= '
		        		<input type="button" class="ps-submit" value="'.$donename.'" onclick="bwbpsHideUploadForm('.(int)$g['gallery_id'].',\'' . $g["pfx"] . '\');return false;" ' . $fld_attributes . '/>
	        		';
		        	}

	        	} else {
	        	
	        		if($this->options['use_donelink']){
					$ret .= '<a href="javascript: void(0);" onclick="tb_remove();return false;" ' . $fld_attributes . '>'.$donename.'</a>';
					} else {
						$ret .= '
	        		<input type="button" class="ps-submit" value="'.$donename.'" onclick="tb_remove();return false;" ' .$fld_attributes.' />
		        	';
		        	}
	        	}
				break;
				
			case "[caption]":
				$ret = '<input tabindex="' . $tab_index . '" type="text" name="bwbps_imgcaptionInput" id="' . $g["pfx"] . 'bwbps_imgcaptionInput" class="bwbps_reset ' . $reqclass .'" ' . $value . $fld_attributes . '/>';
				break;
			
			case "[caption2]":
				$ret = '<input tabindex="' . $tab_index . '" type="text" name="bwbps_imgcaption2" id="' . $g["pfx"] . 'bwbps_imgcaptionInput" class="bwbps_reset ' . $reqclass .'" ' . $value . $fld_attributes .' />';
				break;
			
			case "[user_url]":
				global $current_user;
				$ret = "";
				if($current_user->display_name){
					
					if($this->validURL($current_user->user_url)){
						$ret = "<a href='$current_user->user_url' title='' $fld_attributes>".$current_user->display_name."</a>";
					}
				}
				break;
				
			case "[user_name]":
				global $current_user;
				$ret = "Guest";
				if($current_user->display_name){
					$ret = $current_user->display_name;
				}
				break;
				
			case "[thumbnail]":
				$ret = '<span id="' . $g["pfx"] . 'bwbps_result" class="bwbps_result"></span>';
				break;
			
			case "[preview_post]":
				$ret = '<span id="' . $g["pfx"] . 'bwbps_previewpost" class="bwbps_previewpost"></span>
				<input type="hidden" name="bwbps_preview_post" value="1" />
				';
				break;
				
			case "[thumbnail_2]":
				$ret = '<span id="' . $g["pfx"] . 'bwbps_result2" class="bwbps_result bwbps_result2"></span>';
				break;
			case "[url]":
				$ret = '<input tabindex="'. $tab_index . '" type="text" name="bwbps_url" id="' . $g["pfx"] . 'bwbps_url" class="bwbps_reset ' . $reqclass. '" ' . $value . ' ' . $fld_attributes . ' />';
				break;
			case "[loading]":
				$ret = '<img id="' . $g["pfx"] . 'bwbps_loading" src="'.WP_PLUGIN_URL.'/photosmash-galleries/images/loading.gif" style="display:none;" alt="loading" '.$fld_attributes . '/>';
				break;
			case "[message]" :
				$ret = '<span id="' . $g["pfx"] . 'bwbps_message" class="bwbps_message"></span>';
				break;
				
			case "[geocode]":
			
				$ret .= $g['geocode_description'] . '<br/><input tabindex="'. $tab_index . '" type="text" name="bwbps_addr" id="' . $g["pfx"] . 'bwbps_addr" class="bwbps_reset" size="20" />
				<input type="button" value="Geocode" onclick="bwb_gmap.geocodeAddress(jQuery(\'#' . $g["pfx"] . 'bwbps_addr\').val(), \'' . $g["pfx"] 
				. 'bwbps_lat\',\'' . $g["pfx"] . 'bwbps_lng\'); return false;" class="button">
				<br/>';
				
				$ret .= '<div style="float:left;">' . $g['latitude_label'] . ':<br/><input tabindex="'. $tab_index . '" type="text" name="bwbps_geolat" id="' . $g["pfx"] . 'bwbps_lat" class="bwbps_reset" ' . $value . ' size="10" '.$fld_attributes . '/></div> ';
				$ret .= '<div style="float:left; margin-left: 5px;">' . $g['longitude_label'] . ':<br/><input tabindex="'. $tab_index . '" type="text" name="bwbps_geolong" id="' . $g["pfx"] . 'bwbps_lng" class="bwbps_reset" ' . $value2 . ' size="10" '.$fld_attributes . '/></div><div style="clear: both;"></div>';
				
				break;
			
			case "[geocode_fields]":
			
				$ret .= $g['geocode_description'] . '
				<br/>';
				
				$ret .= '<div style="float:left;">' . $g['longitude_label'] . ':<br/><input tabindex="'. $tab_index . '" type="text" name="bwbps_geolat" id="' . $g["pfx"] . 'bwbps_lat" class="bwbps_reset" ' . $value . ' size="10" '.$fld_attributes . '/></div> ';
				$ret .= '<div style="float:left; margin-left: 5px;">' . $g['longitude_label'] 
				. ':<br/><input tabindex="'. $tab_index . '" type="text" name="bwbps_geolong" id="' 
				. $g["pfx"] . 'bwbps_lng" class="bwbps_reset" ' . $value2 
				. ' size="10" '.$fld_attributes 
				. '/> <input type="button" value="Get" onclick="bwb_gmap.geocodeAddress(bwb_gmap.getFormAddress(\'' 
				. $g["pfx"] . '\'), \'' . $g["pfx"] 
				. 'bwbps_lat\',\'' . $g["pfx"] . 'bwbps_lng\'); return false;" class="button"></div><div style="clear: both;"></div>';
				
				break;
				
			case "[img_attribution]" :
				if(!$value){
					global $current_user;
					$value = ' value="'. esc_attr($current_user->display_name) . '"';
				}
				$ret = "<input type='text' name='bwbps_img_attribution' tabindex='". $tab_index . "'" 
					. " id='" . $g["pfx"] . "bwbps_img_attribution' class='" . $reqclass . "' " 
					. $value . $fld_attributes . " />";
					
				break;
			
			case "[img_license]" :
				$opts['value'] = (int)$val;
				$ret = $this->getImgLicenseDDL($g, $opts, $tab_index);					
				break;
				
			case "[category_name]" :
				$ret = $this->getCurrentCatName();
				break;
				
			case "[category_link]" :
				$ret = $this->getCurrentCatLink();
				break;
			
			case "[category_id]" :
				$ret = $this->getCurrentCatID();
				break;
				
			case "[post_tags]" :
			
				if($atts['value'] == 'post_tags'){
					$val = $this->getPostTags();
				}
				
				$val = esc_attr($val);
				
				$ret = "<input type='text' name='bwbps_post_tags[]' tabindex='". $tab_index . "'"
					. " id='" . $g["pfx"] . "bwbps_post_tags' class='bwbps_reset ". $reqclass . "' value='" 
					. $val . "' " . $fld_attributes . "/>";
					
				break;
			
			case '[tag_dropdown]' :
				
				//Get the form element's name
				if(!$atts['name']){
					$n = 'bwbps_post_tags[]';
				} else {
					$n = esc_attr($atts['name']);
				}
				
				//Get the tag values
				if($atts['tags']){
				
					//Get array of selected values, to mark as selected in dropdown
					$tags = explode(",", $atts['tags']);
						
					unset($selmarked);
					if(is_array($tags)){
						foreach($tags as $t){
							$t = trim($t);
							if($t == $atts['selected']){ $selattr = "selected=selected"; $selmarked=true;}
							
							$r .= "<option value='" . esc_attr($t) . "' $selattr>"
								. $t . "</option>
								";
							$selattr = "";
						}
					}
				}
								
				if($atts['id']){
					$id = "id='" . esc_attr($atts['id']) . "'";
				}
				
				if($atts['onclick']){
					$onclick = "onclick='" . $atts['onclick'] . "'";
				}
				
				if($r){
					$ret = "<select $id name='$n' $onclick >" . $r . "</select>";
				}
				
				break;
				
			case "[post_cat]" :
				
				if((int)$g['post_cat_child_of']){
					$opts['child_of'] = (int)$g['post_cat_child_of'];
				} else {
					if((int)$atts['child_of']){
						$opts['child_of'] = (int)$atts['child_of'];
					}
				}
				
				if($g['post_cat_exclude']){
					$opts['exclude'] = $g['post_cat_exclude'];
				} else {
					if($atts['exclude']){
						$opts['exclude'] = $atts['exclude'];
					}
				}
				
				if($g['show_option_none']){
					$opts['show_option_none'] = $g['show_option_none'];
				} else {
					if($atts['show_option_none']){
						$opts['show_option_none'] = $atts['show_option_none'];
					}
				}				
				
				
				if($g['post_cat_selected']){
					$opts['selected'] = $g['selected'];
				} else {
					if($atts['selected']){
						$opts['selected'] = $atts['selected'];
					}
				}
				
				if($g['post_cat_depth']){
					$opts['depth'] = (int)$g['depth'];
				} else {
					if((int)$atts['depth']){
						$opts['depth'] = (int)$atts['depth'];
					}
				}
				
				if($g['post_cat_single_select']){
					$opts['name'] = 'bwbps-post-cats';
					$pc_class = "bwbps-post-cat-single";
				} else {
					if($atts['single_select']){
						$opts['name'] = 'bwbps-post-cats';
						$pc_class = "bwbps-post-cat-single";
					} else {
						$opts['name'] = 'bwbps-post-cats[]';
						$opts['id'] = 'bwbps-post-cats';
						$pc_class = "bwbps-post-cat-form";
					}
				}
				
				$opts['class'] = $pc_class;
				$opts['hide_empty'] = 0;
				$opts['echo'] = 0;
				if($val){
					$opts['selected'] = $val;
				}
				$opts['hierarchical'] = 1;
				
				
				$ret = wp_dropdown_categories($opts);
				
				if($atts['id']){ $atts['id'] = "-" . $atts['id']; }
				
				$ret = str_replace("id='bwbps-post-cats[]'", "id='bwbps-post-cats" . $atts['id'] . "'", $ret);
				break;

			default:
			
				break;
		
		}
		
		return $ret;
	
	}
	
	function getTermObjects($qtags, $select_msg = ""){
		
		if(!$qtags){ return $qtags; }		
		
		//Get Post Tags if requested
		if(!is_array($qtags) && $qtags == 'post_tags'){
		
			global $wp_query;
			$tags = wp_get_object_terms( $wp_query->post->ID, 'post_tag', $args ) ;
			return $tags;
		}
		
		//Get Slug and Name for requested tags from DB
		global $wpdb;
		$qtags = explode(",", $qtags);			
		$qtags = array_map("trim", $qtags);
		$qtags = array_map("esc_sql", $qtags);
		
		//Check for any or none
		if($select_msg){
			$any->name = $select_msg;
			$any->slug = "";
		}
		
		
		$qtags = implode("','", $qtags);
		
		$tags = $wpdb->get_results($wpdb->prepare("SELECT name, slug FROM " . $wpdb->terms . " WHERE name IN ('" . $qtags . "')"));
		
		if(isset($any)){
			array_unshift($tags, $any);
		}
		
		return $tags;
	}
	
	/**
	 * Get File Upload Fields for STANDARD FORM
	 * 
	 *  uses the gallery type to determine which fields are needed
	 * @param 
	 */
	function getStdFormUploadFields($g){
		$atts = $this->getUploadTypes($g['gallery_type']);
		$ret = $this->getFileUploadFields($g, $atts);
		return $ret;
	}
	
	/**
	 * Get File Upload Fields for STANDARD FORM
	 * 
	 *  uses the gallery type to determine which fields are needed
	 * @param 
	 */
	 //todo
	function getCFFileUploadFields($g, $attributes){
		
		if(!is_array($attributes)){ $attributes = array(''); }
		$utypes = $this->getUploadTypes($attributes['gallery_type']);
		
		$atts = array_merge($attributes, $utypes);
		
		$ret = $this->getFileUploadFields($g, $atts);
		return $ret;
	
	}
	
	/**
	 * Get and array of File Upload Fields Types from a gallery type
	 * 
	 *  uses the gallery type to determine which fields are needed
	 * @param  int $gallery_type - what type of gallery??
	 */
	function getUploadTypes($gallery_type){
	
		$atts['gallery_type'] = (int)$gallery_type;
		
		switch ((int)$gallery_type) {
			case 0 : // Photo Gallery
				$atts['images'] = 'true';
				$atts['displayed'] = 'images';
				break;
				
			case 1 : // Image Uploads + Direct Linking to Images
				$atts['images'] = 'true';
				$atts['directlink'] = 'true';
				$atts['displayed'] = 'images';
				break;
			
			case 2 : // Direct Linking to Images only
				$atts['directlink'] = 'true';
				$atts['displayed'] = 'directlink';
				break;
				
			case 3 : // YouTube gallery
				$atts['youtube'] = 'true';
				$atts['displayed'] = 'youtube';
				break;
			
			case 4 : // All Video options
				$atts['youtube'] = 'true';
				$atts['videofile'] = 'true';
				$atts['displayed'] = 'youtube';
				break;
				
			case 5 : // Video Uploads only
				$atts['videofile'] = 'true';
				$atts['displayed'] = 'videofile';
				break;
			
			case 6 : // Mixed - YouTube + Images
				$atts['images'] = 'true';
				$atts['displayed'] = 'images';
				$atts['youtube'] = 'true';
				break;
				
			
			case 20 : // Secondart Image Select...only available in custom form/upload scripts
				$atts['images2'] = 'true';
				$atts['displayed'] = 'images2';
				break;
			
			default :	// PhotoGallery
				$atts['images'] = 'true';
				$atts['displayed'] = 'images';
				break;
		}
		return $atts;
	}
	
		
	/**
	 * Get the Upload Fields (and radio selectors)
	 * 
	 * @param $atts - an array of attributes that are either included in custom form [video_select atts] or [file_select atts] tag
	 *		or calculated from Gallery Type in Standard Forms.
	 */
	function getFileUploadFields($g, $atts){
		
		if(!is_array($atts)){ $atts[] = ''; }
		
		// Get Upload fields for this Gallery Type
		
		//Standard Images
		if($atts['images'] == 'true'){
		
			$hide = ($atts['displayed'] == 'images') ? "" : ' style="display: none;"';
			$img_radio_msg = $atts['file_radio'] ? $atts['file_radio'] : 'Browse for file';
			$url_radio = $atts['url_radio'] ? $atts['url_radio'] : 'Enter URL';
			$msg = $atts['url_msg'] ? $atts['url_msg'] : 'Import Image by URL';
			
			if(!$hide){
			
				$checked = ' checked="checked" ';
				$radioclass = ' init_radio';
			
			}
			
			$checked = $hide ? "" :  ' checked="checked" ';
			
				//For Field Browse box
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioFile" name="bwbps_filetype" '.$checked
				. 'onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_file\',\'\',\'' . $g["pfx"] . '\');" class="'.$radioclass.'" value="0" /> '
				. $img_radio_msg;
				
			$inputs[] =  '<span id="' . $g["pfx"] . 'bwbps_up_file" class="' . $g["pfx"] . 'bwbps_uploadspans" '
				.$hide.'><input type="file" name="bwbps_uploadfile"'
				. 'id="' . $g["pfx"] . 'bwbps_uploadfile" class="bwbps_reset bwbps_file" /></span>';
				
				//For Image URL
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioURL" '
				. 'name="bwbps_filetype" onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_url\',\'\',\'' . $g["pfx"] . '\');" value="1" /> '
				. $url_radio;
					
					//Input box for image URL...hidden by default
			$inputs[] = '<span id="' . $g["pfx"] . 'bwbps_up_url" class="' . $g["pfx"] . 'bwbps_uploadspans" '
				. 'style="display:none;"><input type="text" name="bwbps_uploadurl" '
				. 'id="' . $g["pfx"] . 'bwbps_uploadurl" class="bwbps_reset" /> '.$msg.'</span>';
							
		}
		
		/* DO NOT ENABLE DIRECT LINKING YET...too many security issues */
		//Direct Linking Images
		if($atts['directlink'] == 'true'){
			$checked = "";
			$radioclass = "";
			$hide = $atts['displayed'] == 'directlink' ? "" : ' style="display: none;"';
			$radio_msg = $atts['directlink_radio'] ? $atts['directlink_radio'] : 'Link to Image';
			$msg = $atts['directlink_msg'] ? $atts['directlink_msg'] : 'URL of Image to link to';
			
			if(!$hide){
			
				$checked = ' checked="checked" ';
				$radioclass = ' init_radio';
			
			}

			
			//DL Radio button selector
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioDL" name="bwbps_filetype" '.$checked
					.'onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_dl\',\'\',\'' . $g["pfx"] . '\');" class="'.$radioclass.'" value="2" /> '.$radio_msg;
			
			//DL Input box
			$inputs[] =  '<span id="' . $g["pfx"] . 'bwbps_up_dl" class="' . $g["pfx"] . 'bwbps_uploadspans" '
				.$hide.'><input type="text" name="bwbps_uploaddl"'
				.' id="' . $g["pfx"] . 'bwbps_uploaddl" class="bwbps_reset" /> '.$msg.'</span>';
			
		}
		
		//Secondary Image Select
		if($atts['images2'] == 'true'){
			
			$checked = "";
			$radioclass = "";
			$hide = ($atts['displayed'] == 'images2') ? "" : ' style="display: none;"';
			$img_radio_msg = $atts['file_radio'] ? $atts['file_radio'] : 'Browse for file';
			$url_radio = $atts['url_radio'] ? $atts['url_radio'] : 'Enter URL';
			$msg = $atts['url_msg'] ? $atts['url_msg'] : 'Import Image by URL';
			
			if(!$hide){
			
				$checked = ' checked="checked" ';
				$radioclass = ' init_radio';
			
			}
			
			//For Field Browse box
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioFile2" name="bwbps_filetype2" '.$checked
				. 'onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_file2\', \'2\',\'' . $g["pfx"] . '\');" class="'.$radioclass.'" value="5" /> '
				. $img_radio_msg;
				
			$inputs[] =  '<span id="' . $g["pfx"] . 'bwbps_up_file2" class="' . $g["pfx"] . 'bwbps_uploadspans2" '
				.$hide.'><input type="file" name="bwbps_uploadfile2"'
				. 'id="' . $g["pfx"] . 'bwbps_uploadfile2" class="bwbps_reset bwbps_file" /></span>';
				
			//For Image URL
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioURL2" '
				. 'name="bwbps_filetype2" onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_url2\', \'2\',\'' . $g["pfx"] . '\');" value="6" /> '
				. $url_radio;
					
					//Input box for image URL...hidden by default
			$inputs[] = '<span id="' . $g["pfx"] . 'bwbps_up_url2" class="' . $g["pfx"] . 'bwbps_uploadspans2" '
				. 'style="display:none;"><input type="text" name="bwbps_uploadurl2" '
				. 'id="' . $g["pfx"] . 'bwbps_uploadurl2" class="bwbps_reset" /> '.$msg.'</span>';
							
		}
		
		
		//YouTube
		if($atts['youtube'] == 'true'){
		
			$checked = "";
			$radioclass = "";
			$hide = $atts['displayed'] == 'youtube' ? "" : ' style="display: none;"';
			$radio_msg = $atts['youtube_radio'] ? $atts['youtube_radio'] : 'YouTube URL';
			$msg = $atts['youtube_msg'] ? $atts['youtube_msg'] : 'Paste YouTube video URL';
			
			if(!$hide){
			
				$checked = ' checked="checked" ';
				$radioclass = ' init_radio';
			
			}
			
			//YT Radio button selector
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioYT" name="bwbps_filetype" '.$checked
					.'onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_yt\',\'\',\'' . $g["pfx"] . '\');" class="'.$radioclass.'" value="3" /> '.$radio_msg;
			
			//YT Input box
			$inputs[] =  '<span id="' . $g["pfx"] . 'bwbps_up_yt" class="' . $g["pfx"] . 'bwbps_uploadspans" '
				.$hide.'><input type="text" name="bwbps_uploadyt"'
				.' id="' . $g["pfx"] . 'bwbps_uploadyt" class="bwbps_reset" /> '.$msg.'</span>';
			
		}
					
		
		//Video File upload
		if($atts['videofile'] == 'true'){
			
			$checked = "";
			$radioclass = "";
			$hide = $atts['displayed'] == 'videofile' ? "" : ' style="display: none;"';
			$msg = $atts['video_msg'] ? $atts['video_msg'] : 'Select video file';
			$radio_msg = $atts['video_radio'] ? $atts['video_radio'] : 'Browse for video';
			
			if(!$hide){
			
				$checked = ' checked="checked" ';
				$radioclass = ' init_radio';
			
			}
			
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioVid" '.$checked
					.'name="bwbps_filetype" onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_vid\',\'\',\'' . $g["pfx"] . '\');" class="'.$radioclass.'" value="4" /> '
					.$radio_msg ;
					
			$inputs[] =  '<span id="' . $g["pfx"] . 'bwbps_up_vid" class="' . $g["pfx"] . 'bwbps_uploadspans" '
				. $hide.'><input type="file" name="bwbps_uploadvid" id="' . $g["pfx"] . 'bwbps_uploadvid" class="bwbps_reset bwbps_file" /> '
				. '</span>';

		}
		
		//General Documents - file type = 7
		if($atts['doc'] == 'true'){
			
			$checked = "";
			$radioclass = "";
			$hide = ($atts['displayed'] == 'doc') ? "" : ' style="display: none;"';
			$radio_msg = $atts['doc_radio'] ? $atts['doc_radio'] : 'Browse for document';
			
			if(!$hide){
			
				$checked = ' checked="checked" ';
				$radioclass = ' init_radio';
			
			}
			
			//For Field Browse box
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioDoc" name="bwbps_filetype" '.$checked
				. 'onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_doc\',\'\',\'' . $g["pfx"] . '\');" class="'.$radioclass.'" value="0" /> '
				. $img_radio_msg;
				
			$inputs[] =  '<span id="' . $g["pfx"] . 'bwbps_up_doc" class="' . $g["pfx"] . 'bwbps_uploadspans" '
				.$hide.'><input type="file" name="bwbps_uploaddoc"'
				. 'id="' . $g["pfx"] . 'bwbps_uploaddoc" class="bwbps_reset bwbps_file" /></span>';
		}
				
		$ret = implode("&nbsp;", $radios) . '<br/>' . implode("",$inputs);
				
		return $ret;
	}
	
	function getCustomFieldsForm($g){
		$cfs = $this->cfList;
		if(!$cfs){return '';}
		
		
		
		foreach($cfs as $fld){
			$ret .= $this->getFieldHTML($fld, $g);
		}
		return $ret;
	}
	
	function getFieldHTML($fld, $g){
		if($fld->type <> 35 && $fld->type <> 30 && $fld->type <> 6){
			//Label
			$ret = "<tr><th scope='row' class='form-field'>".$fld->label."</th>";
			//Field
			$ret .= "<td align='left'>".$this->getField($g, $fld, 50)."</td></tr>";
		} else {
			$ret = "<tr><th></th><td>".$this->getField($g, $fld, 50)."</td></tr>";
		}
		return $ret;
	}
	
	
	// GET a Comma separated string of the TAGS for the current POST
	function getPostTags(){
		
		global $wp_query;
		if(! is_object( $wp_query ) || !$wp_query->post->ID ){
			return '';
		}
		
		$terms = wp_get_object_terms( $wp_query->post->ID, 'post_tag') ;
		
		$t = '';
		if(is_array($terms)){
		
			foreach( $terms as $term ){
				
				$_terms[] = $term->name;
			
			}
			
			if(is_array($_terms)){
				$t = implode(", " , $_terms);
			}
		} 
		
		return $t;
	
	}
	
	
	//BUILD THE FORM FIELD
	function getField($g, $f, $tabindex=false, $the_value=false, $txtarea_width=false, $atts=false){
		global $wpdb;
		
		if( $the_value !== false ){
			$val = $the_value;
		} else {
			$val = esc_attr($f->default_val);
		}
		
		if($atts['tab_index']){$tabindex = $atts['tab_index']; }
		
		$fld_attributes = $atts['attributes'] ? " " . trim($atts['attributes']) . " " : ""; 
		if( $fld_attributes ){
			$fld_attributes = str_replace('\\"', "'", $fld_attributes);
			$fld_attributes = str_replace("\\'", '"', $fld_attributes);
		}
		
		//Name is field name + bwbps (prepended)
		$name = "bwbps_".$blank.$f->field_name;
		
		//Doesn't work for multi value items...build their IDs ad hoc
		$id = " id='".$g['pfx']."bwbps_".$f->field_name."'";
		
		$f->name = $name;
		
		if(!$tabindex){
			$tabindex = $this->tabindex++;
		}
		
		/*
		//adjust the id and name for Multi Value
		if($f->multi_val == 1 || $f->type == 4){
			$name .= "[]";
		}
		*/
		//Element name....works for about everything
		$ele_name = " name='$name'";
		
		if( is_array($g['required_fields']) ){
			$reqclass = in_array($f->field_name, $g['required_fields']) ? "ps_required_" . $g['pfx'] : "";
		} else {
			$reqclass = "";
		}
		
		
		$opts['field_type'] = $f->type;
		switch ($f->type){
			case 0 :	//text box
			
					if(strpos($fld_attributes, 'maxlength=') === false ){
						$maxlength = " maxlength='255' ";
					} else {
						$maxlength = "";
					}
				
					//Single Value Text Box
					$ret = "<input tabindex='".$tabindex."' ".$id
						." ".$ele_name
						." value='".esc_attr($val)
						."' type='text'  class='bwbps_text_field bwbps_reset " 
						. $reqclass . "' " . $maxlength . $fld_attributes . "/>";
				break;
			case 1 :	//textarea
			
				$textarea_rows = (int)$atts['textarea_rows'] ? (int)$atts['textarea_rows'] : 4;
								
				$ret = "<textarea tabindex='".$tabindex."' ".$id
					." ".$ele_name . " rows=". $textarea_rows ." ";
					
				if(!$txtarea_width && !$atts['textarea_cols']){
					$ret .= " cols=40 ";
				} else {
					if((int)$atts['textarea_cols']){ $ret .= " cols=" . (int)$atts['textarea_cols']; }
				}
				$ret .= " class='bwbps_reset bwbps_textarea " . $reqclass . "' " . $fld_attributes . " />"
					.esc_attr($val)."</textarea>
					";
				break;
			case 2 :	//option (ddl)
				$ret = "<select tabindex='".$tabindex."' ".$id
					." ".$ele_name." class='bwbps_select_field' " . $fld_attributes . ">";
				$ret .= "<option value=''>--Select--</option>";
				
				$opts['opentag'] = "option";	//opening tag
				$opts['closetag'] = "</option>";	//closing tag
				$opts['selected'] = 'selected';  // indicator for selected value
				$opts['defval'] = $val;
				$opts['type'] = "";	// input type (e.g. type='text')
				$opts['name'] = "";  //form field name (radioboxes need this)
				
				$ret .= $this->getFieldValueOptions($f->field_id, $opts);
				
				$ret .= "</select>";
				break;
			case 3 :	//radio
			
				$opts['opentag'] = "input ";	//opening tag
				$opts['closetag'] = "<br/>\n";	//closing tag
				$opts['selected'] = "checked='checked'";  // indicator for selected value
				$opts['defval'] = $val;
				$opts['type'] = "type='radio'";	// input type (e.g. type='text')
				$opts['style'] = "style='width:auto;'";
				$opts['name'] = "name='"."bwbps_".$blank.$f->field_name."'";  //form field name
						//radioboxes need name defined
				
				$ret = "<div>".$this->getFieldValueOptions($f->field_id, $opts)."</div>";
				
				break;
			case 4 :	//checkboxes
				
				$sql = "SELECT value FROM ".PSLOOKUPTABLE." WHERE field_id = "
					.(int)$f->field_id. " ORDER BY seq";
					
				$cbx_value = $wpdb->get_var($sql);
				
				$checked = $the_value ? " checked='checked' " : "";
			
				$ret = "<input tabindex=".$tabindex. $checked . " type='checkbox' value='" 
					. $cbx_value . "' "
					. "name='bwbps_".$blank.$f->field_name 
					. "' class='bwbps_checkbox bwbps_" . $f->field_name . "' "
					. $fld_attributes . "/>";
				
				
				break;
			case 5 :	//date picker

				if($val != '0000-00-00 00:00:00'){
					$val = date('m/d/Y',strtotime ($val));
				} else {
					$val = "";
				}
				$ret = "<input tabindex='".$tabindex."' " . $id
					. " ".$ele_name
					. " value='".esc_attr($val)
					. "' type='text' style='width:130px;' class='bwbps_reset' $fld_attributes />";
					
				$ret .= "
				<script type='text/javascript'>
					
					jQuery(document).ready(function(){

							jQuery('#" . $g['pfx'] . "bwbps_".$f->field_name."').datepicker();

					});
				</script>
				";
				break;
				
			case 6 :	//hidden
				$ret = "<input  ".$id
					." ".$ele_name
					." value='".esc_attr($val)
					."' type='hidden' size='255' />";
				break;
				
			case 30 :
				global $post;
				$ret = "<input  ".$id
					." ".$ele_name
					." value='".$post->ID
					."' type='hidden' size='20' />";
				break;
				
			case 35 :
				if ( $val === false ){
					$cur_cat_id = $this->getCurrentCatID();
				} else {
					$cur_cat_id = $val;
				}
				$ret = "<input  ".$id
					." ".$ele_name
					." value='".$cur_cat_id
					."' type='hidden' size='20' />";
				break;
				
			case 40 :
				//Category ddl
				if ( $val === false ){
					$val = (int)$this->getCurrentCatID();
				}
				
				$opts['hide_empty'] = 0;
				$opts['echo'] = 0;
				if($val){
					$opts['selected'] = $val;
				}
				$opts['hierarchical'] = 1;
				
				if($the_value !== false){
					$name = $g['pfx']. $name;
				}
				$opts['name'] = $name;
				
				$ret = wp_dropdown_categories($opts);
				break;
			
			default :
					
				break;
		}
		
		if($f->status < 1 && $this->options['use_custom_fields'] == 0){
			$warn = "<br/><span style='color: red; font-size: 10px;'>Out of date.  <a href='admin.php?page=editSuppleFormFields'>Regenerate table</a>.</span>";
		}
		
		return $ret.$warn;
	}
	
	function getCurrentCat(){
		if(is_category() || is_single()){
  			$cat = get_category(get_query_var('cat'),false);
		}
		return $cat;
	}
	
	function getCurrentCatID(){
		if(is_category() || is_single()){
			return get_query_var('cat');
		} else {
			return "";
		}
	}
	
	function getCurrentCatName(){
		$catid = get_query_var('cat');
		if(!$catid){ return "";}
		return get_cat_name(get_query_var($catid));
	}
	
	function getCurrentCatLink(){
		$catid = get_query_var('cat');
		if(!$catid){ return "";}
		return get_category_link($catid);
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
		}
	}
	
	function getImgLicenseDDL($g, $opts, $tab_index){
			
		$sel[1] = ' selected=selected';
		if($opts['value']){
			$sel[1] = '';
			$sel[$opts['value']] == ' selected=selected';
		}
					
		$ret .= "<option value='1' ".$sel[1].">None - All rights reserved</option>";
		$ret .= "<option value='0' ".$sel[0].">License unknown</option>";
		$ret .= "<option value='2' ".$sel[2].">Attribution (by)</option>";
		$ret .= "<option value='3' ".$sel[3].">Attribution Share Alike (by-sa)</option>";
		$ret .= "<option value='4' ".$sel[4].">Attribution No Derivatives (by-nd)</option>";
		$ret .= "<option value='5' ".$sel[5].">Attribution Non-commercial (by-nc)</option>";
		$ret .= "<option value='6' ".$sel[6].">Attribution Non-commercial Share Alike (by-nc-sa)</option>";
		$ret .= "<option value='7' ".$sel[7].">Attribution Non-commercial No Derivatives (by-nc-nd)</option>";
		$ret .= "<option value='8' ".$sel[8].">Public Domain</option>";
		$ret .= "<option value='9' ".$sel[9].">GNU GPL (not usually for images)</option>";
		$ret .= "<option value='10' ".$sel[10].">GNU LGPL (not usually for images)</option>";
		$ret .= "<option value='11' ".$sel[11].">BSD (not usually for images)</option>";
		
		
		$ret ="<select tabindex='" . $tab_index . "' id='" . $g["pfx"] . "bwbps_img_license' class='bwbps_ddl' name='bwbps_img_license'>".$ret."</select>";	
					
					
		return $ret;	
	
	}
	
	//Build the HTML INPUT elements for the Form
	function getFieldValueOptions($field_id, $opts)
	{
		global $wpdb;
		//Get the Value Options  	
		$sql = "SELECT * FROM ".PSLOOKUPTABLE." WHERE field_id = ".(int)$field_id. " ORDER BY seq";
		$query = $wpdb->get_results($sql);
		if(!$query ||$wpdb->num_rows == 0){return "";}
  	  	
		//Walk through our data set and create HMTL entities for each option
		foreach($query as $row){
  		
			$ret .= "<".$opts['opentag']." "
  				.$opts['name']." "
  				.$opts['type']." ".$opts['style']." "
  				."value='".str_replace("'","&#39;",$row->value)."'";
			$sel = "";
			if(is_array($opts['defval'])){
				if($opts['multi_select']){
					foreach($opts['defval'] as $v){
						if($v == $row->value){
							$sel .=" ".$opts['selected'];
						}
					}
				} else {
					if($opts['defval'][0] == $row->value){
						$sel .=" ".$opts['selected'];
					}
				}
			} else {
			
				if($opts['defval'] == $row->value){
					$ret .=" ".$opts['selected'];
				} else {$ret .= ""; }
			}
  			$ret .= $sel." "
  				.">".$row->label.$opts['closetag'];
	  	}
  		return $ret;
	}
	
	function validURL($str)
	{
		return ( ! preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $str)) ? FALSE : TRUE;
	}

	function fixBR($str){
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("\r","\n",$str);
		return  str_replace("\n",'<br/>', $str);	
	
	}
	
	
	function getFieldAtts($content, $fieldname){
				
		$pattern = '\[('.$fieldname.')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\1\])?';
		
		preg_match_all('/'.$pattern.'/s', $content,  $matches );
		
		$attr = $this->field_parse_atts($matches[2][0]);

		$attr['bwbps_match'] = $matches[0][0];
		return $attr;
				
	}
	
	function getFieldAttsMulti($content, $fieldname){
				
		$pattern = '\[('.$fieldname.')\b(.*?)(?:(\/))?\](?:(.+?)\[\/\1\])?';
		
		preg_match_all('/'.$pattern.'/s', $content,  $matches );
		if(is_array($matches)){
			$mcnt = 0;
			foreach( $matches[0] as $m ){
				
				$attr[$mcnt] = $this->field_parse_atts($matches[2][$mcnt]);

				$attr[$mcnt]['bwbps_match'] = $matches[0][$mcnt];
				$mcnt ++;
			}
		}
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


}

?>