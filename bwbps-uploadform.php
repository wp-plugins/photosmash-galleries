<?php

class BWBPS_UploadForm{
	var $options;
	var $stdFieldList;
	
	var $cfields;
	var $field_list;
	var $tabindex =0;
	var $cfList;
	
	function BWBPS_UploadForm($options, $cfList){
		$this->options = $options;
		$this->cfList = $cfList;
		
		$this->stdFieldList = get_option('bwbps_cf_stdfields');
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
	
	function getFormHeader($g, $formName){
		global $post;
		$nonce = wp_create_nonce( 'bwb_upload_photos' );
				
		$use_tb = (int)$this->psOptions['use_thickbox'];
		$use_tb = $g['use_thickbox'] == 'false' ? false : $use_tb;
		$use_tb = $g['use_thickbox'] == 'true' ? true : $use_tb;
		$use_tb = $g['form_visible'] == 'true' ? false : $use_tb;
		
		
		
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
        <form id="' . $g["pfx"] . 'bwbps_uploadform" name="bwbps_uploadform" method="post" action="" style="margin:0px;" class="bwbps_uploadform">
        	<input type="hidden" id="' . $g["pfx"] . 'bwbps_ajax_nonce" name="_ajax_nonce" value="'.$nonce.'" />
        	<input type="hidden" id="' . $g["pfx"] . 'bwbps_formname" name="bwbps_formname" value="'.$formName.'" />
        	<input type="hidden" id="' . $g["pfx"] . 'bwbps_formtype" name="bwbps_formtype" value="'.(int)$g['gallery_type'].'" />
        	<input type="hidden" name="MAX_FILE_SIZE" value="'.$g["max_file_size"].'" />
        	<input type="hidden" name="bwbps_imgcaption" id="' . $g["pfx"] . 'bwbps_imgcaption" value="" />
        	<input type="hidden" name="gallery_id" id="' . $g["pfx"] . 'bwbps_galleryid" value="'.(int)$g["gallery_id"].'" />
        	<input type="hidden" name="bwbps_post_id" id="' . $g["pfx"] . 'bwbps_post_id" value="'.(int)$post->ID.'" />
        	';

		
		return $ret;
	}
	
	/**
	 * Get Standard Upload Form:	
	 * 
	 * Returns the standard upload form + any custom fields if set for use
	 * @param $g: Gallery settings
	 */
	function getStandardForm($g, $formname){
		if(!trim($formname)){ $formname = "ps-standard"; }
		$retForm = $this->getFormHeader($g, $formname);
		$retForm .= '
        	<table class="ps-form-table">
			<tr><th>'.$g["upload_form_caption"].'<br/>';
			
				
		$retForm .= '
			</th>
				<td align="left">
				';
		
		//Get the Upload Fields
		$retForm .= $this->getStdFormUploadFields($g);
				
		$retForm .= '
				</td>
			</tr>
			<tr><th>Caption:</th>
				<td align="left">
					<input tabindex="50" type="text" name="bwbps_imgcaptionInput" id="' . $g["pfx"] . 'bwbps_imgcaptionInput" class="bwbps_reset"/>';
	
		$retForm .='
				</td>
			</tr>';
		
		$this->tabindex = 50;
		
		//Alternate Caption URL
		if($this->options['use_urlfield']){
		
			$retForm .= '<tr><th>Caption URL:</th>
				<td align="left">
					<input tabindex="50" type="text" name="bwbps_url" id="' . $g["pfx"] . 'bwbps_url" class="bwbps_reset" /> Ex: http://www.mysite.com';
			
			$retForm .='
				</td>
				</tr>';
			
		}
		
		//Add Custom Fields if use_advanced flag is set	
		if($this->options['use_customfields']){
			$retForm .= $this->getCustomFieldsForm($g);
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
	        <tr><th><span id="' . $g["pfx"] . 'bwbps_message"></span></th>
	        <td><span id="' . $g["pfx"] . 'bwbps_result"></span></td>
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
			if($g['custom_formname']){
				$formName = trim($g['custom_formname']);
			} else {
				//Use the 'default' custom form as next to last resort
				$formName = 'default';
			}
		}
		
		$cf = $g['cf']['form'];
		
		$nonce = wp_create_nonce( 'bwb_upload_photos' );
		
		//Get the form header and hidden fields
		$retForm = $this->getFormHeader($g, $formName);
		
		
		//Replace Std Fld tags in Custom Form with HTML
		if(is_array($this->stdFieldList)){
			foreach($this->stdFieldList as $fname){
				unset($replace);
				unset($atts);
				
				// Some fields can have attributes...special method for getting Attributes
				if($fname == 'submit' || $fname == 'done' 
					|| $fname == 'image_select' || $fname == 'video_select' || $fname == 'file_select'
					|| $fname == 'image_select_2' )
				{
				
					$atts = $this->getFieldsWithAtts($cf, $fname);
					
					$fname = "[".$fname."]";
					
					$replace = $this->getStandardField($fname, $g, $atts);
					
					$fname = $atts['bwbps_match'];
					
				} else {
				
					$fname = "[".$fname."]";
					
					if(!strpos($cf, $fname) === false){		
					
						$replace = $this->getStandardField($fname, $g);
					}	
				}
				if($replace){
					$cf = str_replace($fname, $replace, $cf);
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
					$fldname = "[".$fld->field_name."]";
					if(!strpos($cf, $fldname) === false){
						$ret = $this->getField($g, $fld, 50);
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
	function getStandardField($fld, $g, $atts=false){
	
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
				$ret = "<input type='hidden' name='bwbps_allownoimg' value='1' />";
				break;
			
			case "[submit]":
				if($atts && is_array($atts) && array_key_exists('name', $atts)){
					$submitname = $atts['name'];
				} else {
					$submitname = 'Submit';
				}
				$ret = '<input type="submit" class="ps-submit" value="'.$submitname.'" id="' . $g["pfx"] . 'bwbps_submitBtn" name="bwbps_submitBtn" />';
				break;
				
			case "[done]":
				
				if(is_array($atts) && array_key_exists('name', $atts)){
					$donename = $atts['name'];
				} else {
					$donename = 'Done';
				}
			
				if(!$this->options['use_thickbox'] && !$g['use_thickbox']){
				
					if($this->options['use_donelink']){
						$ret .= '<a href="javascript: void(0);" onclick="bwbpsHideUploadForm('.(int)$g["gallery_id"].',\'' . $g["pfx"] . '\');return false;">'.$donename.'</a>';
					} else {			
						$ret .= '
		        		<input type="button" class="ps-submit" value="'.$donename.'" onclick="bwbpsHideUploadForm('.(int)$g['gallery_id'].',\'' . $g["pfx"] . '\');return false;" />
	        		';
		        	}

	        	} else {
	        	
	        		if($this->options['use_donelink']){
					$ret .= '<a href="javascript: void(0);" onclick="tb_remove();return false;">'.$donename.'</a>';
					} else {
						$ret .= '
	        		<input type="button" class="ps-submit" value="'.$donename.'" onclick="tb_remove();return false;" />
		        	';
		        	}
	        	}
				break;
				
			case "[caption]":
				$ret = '<input type="text" name="bwbps_imgcaptionInput" id="' . $g["pfx"] . 'bwbps_imgcaptionInput" class="bwbps_reset" />';
				break;
			
			case "[caption2]":
				$ret = '<input type="text" name="bwbps_imgcaption2" id="' . $g["pfx"] . 'bwbps_imgcaptionInput" class="bwbps_reset" />';
				break;
			
			case "[user_url]":
				global $current_user;
				$ret = "";
				if($current_user->display_name){
					
					if($this->validURL($current_user->user_url)){
						$ret = "<a href='$current_user->user_url' title=''>".$current_user->display_name."</a>";
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
				$ret = '<span id="' . $g["pfx"] . 'bwbps_result"></span>';
				break;
				
			case "[thumbnail_2]":
				$ret = '<span id="' . $g["pfx"] . 'bwbps_result2"></span>';
				break;
			case "[url]":
				$ret = '<input tabindex="3" type="text" name="bwbps_url" id="' . $g["pfx"] . 'bwbps_url" class="bwbps_reset" />';
				break;
			case "[loading]":
				$ret = '<img id="' . $g["pfx"] . 'bwbps_loading" src="'.WP_PLUGIN_URL.'/photosmash-galleries/images/loading.gif" style="display:none;" alt="loading" />';
				break;
			case "[message]" :
				$ret = '<span id="' . $g["pfx"] . 'bwbps_message"></span>';
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

			default:
			
				break;
		
		}
		
		return $ret;
	
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
				. 'id="' . $g["pfx"] . 'bwbps_uploadfile" class="bwbps_reset" /></span>';
				
				//For Image URL
			$radios[] = '<input type="radio" id="' . $g["pfx"] . 'bwbpsUpRadioURL" '
				. 'name="bwbps_filetype" onclick="bwbpsSwitchUploadField(\'' . $g["pfx"] . 'bwbps_up_url\',\'\',\'' . $g["pfx"] . '\');" value="1" /> '
				. $url_radio;
					
					//Input box for image URL...hidden by default
			$inputs[] = '<span id="' . $g["pfx"] . 'bwbps_up_url" class="' . $g["pfx"] . 'bwbps_uploadspans" '
				. 'style="display:none;"><input type="text" name="bwbps_uploadurl" '
				. 'id="' . $g["pfx"] . 'bwbps_uploadurl" class="bwbps_reset" /> '.$msg.'</span>';
							
		}

		//Direct Linking Images
		if($atts['directlink'] == 'true'){
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
				. 'id="' . $g["pfx"] . 'bwbps_uploadfile2" class="bwbps_reset" /></span>';
				
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
				. $hide.'><input type="file" name="bwbps_uploadvid" id="' . $g["pfx"] . 'bwbps_uploadvid" class="bwbps_reset" /> '
				. '</span>';

		}
		
		//General Documents - file type = 7
		if($atts['doc'] == 'true'){
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
				. 'id="' . $g["pfx"] . 'bwbps_uploaddoc" class="bwbps_reset" /></span>';
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
	
	
	
	//BUILD THE FORM FIELD
	function getField($g, $f, $tabindex=false){
		
		$val = $f->default_val;
		
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
		
		
		$opts['field_type'] = $f->type;
		switch ($f->type){
			case 0 :	//text box
				
					//Single Value Text Box
					$ret = "<input tabindex='".$tabindex."' ".$id
						." ".$ele_name
						." value='".htmlentities($val, ENT_QUOTES)
						."' type='text' maxlength='255' class='bwbps_reset' />";
				break;
			case 1 :	//textarea
				$ret = "<textarea tabindex='".$tabindex."' ".$id
					." ".$ele_name
					." rows=4 cols=40 class='bwbps_reset' />"
					.htmlentities($val, ENT_QUOTES)."</textarea>
					";
				break;
			case 2 :	//option (ddl)
				$ret = "<select tabindex='".$tabindex."' ".$id
					." ".$ele_name.">";
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
				$opts['opentag'] = "input tabindex=".$tabindex ;	//opening tag
				$opts['closetag'] = "<br/>";	//closing tag
				$opts['selected'] = 'checked="checked"';  // indicator for selected value
				$opts['defval'] = $val;
				$opts['type'] = "type='checkbox'";	// input type (e.g. type='text')
				$opts['multi_select'] = true;
				$opts['style'] = "style='width:auto;'";
				$opts['name'] = "name='"."bwbps_".$blank.$f->field_name."[]'";  //form field name
				
				$ret = $this->getFieldValueOptions($f->field_id, $opts);
				$ret .= "<input type='hidden' name='subtle_nonsense'/>";
				break;
			case 5 :	//date picker
				if($val){
					$val = date('m/d/Y',strtotime ($val));
				}
				$ret = "<input tabindex='".$tabindex."' " . $id
					. " ".$ele_name
					. " value='".htmlentities($val, ENT_QUOTES)
					. "' type='text' style='width:130px;' class='bwbps_reset' />";
					
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
					." value='".htmlentities($val, ENT_QUOTES)
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
				$cur_cat_id = $this->getCurrentCatID();
				$ret = "<input  ".$id
					." ".$ele_name
					." value='".$cur_cat_id
					."' type='hidden' size='20' />";
				break;
			case 40 :
				//Category ddl
				$val = (int)$this->getCurrentCatID();
				
				$opts['hide_empty'] = 0;
				$opts['echo'] = 0;
				if($val){
					$opts['selected'] = $val;
				}
				$opts['hierarchical'] = 1;
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


}

?>