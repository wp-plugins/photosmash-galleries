<?php

class BWBPS_UploadForm{
	var $options;
	var $cfStdFields;
	
	var $cfields;
	var $field_list;
	var $galOptions;
	var $tabindex =0;
	var $cfList;
	
	function BWBPS_UploadForm($g, $options, $cfList){
		$this->galOptions = $g;
		$this->options = $options;
		$this->cfList = $cfList;
		
		$this->cfStdFields = get_option('bwbps_cf_stdfields');
	}
	
	function getUploadForm($formName=false){
		
		if($formName || $this->options['use_customform']){
			
			$ret = $this->getCustomForm($this->galOptions,$formName);
		} else {
			
			$ret = $this->getStandardForm($this->galOptions);
		}
		
		return $ret;
	}
	
	function getFormHeader($g, $formName){
		global $post;
		$nonce = wp_create_nonce( 'bwb_upload_photos' );
		
		if($this->options['use_thickbox'] || $g['use_thickbox']){
			$ret = '<div id="bwbps-formcont" class="thickbox" style="display:none;">';
		} else {
			if($this->options['uploadform_visible'] && !$g['use_thickbox']){
				$ret = '<div id="bwbps-formcont">';	//Do not hide...visible is set to ON
			} else {
				$ret = '<div id="bwbps-formcont" style="display:none;">';
			}
		}	
			$ret .= '
      	<style type="text/css">
				<!--
				#ui-datepicker-div{z-index: 199;}
				-->
		</style>
        <form id="bwbps_uploadform" name="bwbps_uploadform" method="post" action="" style="margin:0px;">
        	<input type="hidden" id="_ajax_nonce" name="_ajax_nonce" value="'.$nonce.'" />
        	<input type="hidden" id="bwbps_formname" name="bwbps_formname" value="'.$formName.'" />
        	<input type="hidden" name="MAX_FILE_SIZE" value="'.$g["max_file_size"].'" />
        	<input type="hidden" name="bwbps_imgcaption" id="bwbps_imgcaption" value="" />
        	<input type="hidden" name="gallery_id" id="bwbps_galleryid" value="'.$g["gallery_id"].'" />
        	<input type="hidden" name="bwbps_post_id" id="bwbps_post_id" value="'.(int)$post->ID.'" />
        	';

		
		return $ret;
	}
	
	/**
	 * Get Standard Upload Form:	
	 * 
	 * Returns the standard upload form + any custom fields if set for use
	 * @param $g: Gallery settings
	 */
	function getStandardForm($g){
			
		$retForm = $this->getFormHeader($g, "ps-standard");
		$retForm .= '
        	<table class="ps-form-table">
			<tr><th>'.$g["upload_form_caption"].'<br/>(Max. allowed size: 400k)';
			
				
		$retForm .= '
			</th>
				<td align="left">
					<input type="radio" id="bwbpsSelectFileRadio" name="bwbps_fileorurl" onclick="bwbpsToggleFileOrURL(false);" value="0" /> Browse for file 
					&nbsp; <input type="radio" id="bwbpsSelectURLRadio" name="bwbps_fileorurl" onclick="bwbpsToggleFileOrURL(true);" value="1" /> Enter URL<br/> 
					<input type="file" name="bwbps_uploadfile" id="bwbps_uploadfile" />
					<span id="bwbps_uploadurlspan" style="display:none;"><input type="text" name="bwbps_uploadurl" id="bwbps_uploadurl" /> Image URL</span>
				</td>
			</tr>
			<tr><th>Caption:</th>
				<td align="left">
					<input tabindex="2" type="text" name="bwbps_imgcaptionInput" id="bwbps_imgcaptionInput" />';
	
		$retForm .='
				</td>
			</tr>';
		
		$this->tabindex = 2;
		
		//Alternate Caption URL
		if($this->options['use_urlfield']){
		
			$retForm .= '<tr><th>Caption URL:</th>
				<td align="left">
					<input tabindex="3" type="text" name="bwbps_url" id="bwbps_url" /> Ex: http://www.mysite.com';
			
			$retForm .='
				</td>
				</tr>';
			$this->tabindex=3;
		}
		
		$this->tabindex++;
		//Add Custom Fields if use_advanced flag is set	
		if($this->options['use_customfields']){
			$retForm .= $this->getCustomFieldsForm($g);
		}
		
		//Add Submit Button
		$retForm .= '	
	        <tr><th><input type="submit" class="ps-submit" value="Submit" id="bwbps_submitBtn" name="bwbps_submitBtn" /> ';
		
		//Figure out if need to Add Done Button
		if(!$this->options['use_thickbox'] && !$g['use_thickbox']){
			if(!$this->options['uploadform_visible']  && !$g['use_thickbox']){
				
				if($this->options['use_donelink']){
					$retForm .= '<a href="javascript: void(0);" onclick="bwbpsHideUploadForm('.$g["gallery_id"].');return false;">Done</a>';
				} else {			
					$retForm .= '
	        		<input type="button" class="ps-submit" value="Done" onclick="bwbpsHideUploadForm('.$g['gallery_id'].');return false;" />
	        		';
	        	}
	        }
		} else {
			if($this->options['use_donelink']){
					$retForm .= '<a href="javascript: void(0);" onclick="tb_remove();return false;">Done</a>';
			} else {
				$retForm .= '
	        		<input type="button" class="ps-submit" value="Done" onclick="tb_remove();return false;" />
	        	';
	        }
		}
		
		$retForm .= '</th>';	//Closes out TH for Submit/Done
		
			$retForm .= '	
	        	<td>
	        		<img id="bwbps_loading" src="'.WP_PLUGIN_URL.'/photosmash-galleries/images/loading.gif" style="display:none;" alt="loading" />	
	        	</td>
	        </tr>
	        <tr><th><span id="bwbps_message"></span></th>
	        <td><span id="bwbps_result"></span></td>
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
	function getCustomForm($g, $formName=""){
		
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
		
		$cf = get_option('bwbps_cf_'.$formName);
		
		//If the custom form is not defined, return the standard form
		if(!$cf || empty($cf) || !trim($cf)){
			
			//last resort...using standard form;
			return $this->getStandardForm($g);
		}
		
		$nonce = wp_create_nonce( 'bwb_upload_photos' );
		
		//Get the form header and hidden fields
		$retForm = $this->getFormHeader($g, $formName);

		//Replace Std Fld tags in Custom Form with HTML
		if(is_array($this->cfStdFields)){
			foreach($this->cfStdFields as $fname){
				$replace = false;
				$atts = false;
				
				// Some fields can have attributes...special method for getting Attributes
				if($fname == 'submit' || $fname == 'done'){
					$atts = $this->getFieldsWithAtts($cf, $fname);
					$fname = "[".$fname."]";
					$replace = $this->getStdFieldHTML($fname, $g, $atts);
					$fname = $atts['bwbps_match'];
				} else {
					$fname = "[".$fname."]";
					if(!strpos($cf, $fname) === false){		
						$replace = $this->getStdFieldHTML($fname, $g);
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
						$ret = $this->getField($fld, 50);
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
	
	function getStdFieldHTML($fld, $g, $atts=false){
		switch ($fld) {
			case '[image_select]' :
				$ret = '<input type="radio" id="bwbpsSelectFileRadio" name="bwbps_fileorurl" onclick="bwbpsToggleFileOrURL(false,\'\');" value="0" /> Browse for file 
					&nbsp; <input type="radio" id="bwbpsSelectURLRadio" name="bwbps_fileorurl" onclick="bwbpsToggleFileOrURL(true,\'\');" value="1" /> Enter URL<br/> 
					<input type="file" name="bwbps_uploadfile" id="bwbps_uploadfile" />
					<span id="bwbps_uploadurlspan" style="display:none;"><input type="text" name="bwbps_uploadurl" id="bwbps_uploadurl" /> Image URL</span>';
				break;
				
			case '[image_select_2]' :
				$ret = '<input type="radio" id="bwbpsSelectFileRadio2" name="bwbps_fileorurl2" onclick="bwbpsToggleFileOrURL(false, \'2\');" value="0" /> Browse for file 
					&nbsp; <input type="radio" id="bwbpsSelectURLRadio2" name="bwbps_fileorurl2" onclick="bwbpsToggleFileOrURL(true,\'2\');" value="1" /> Enter URL<br/> 
					<input type="file" name="bwbps_uploadfile2" id="bwbps_uploadfile2" />
					<span id="bwbps_uploadurlspan2" style="display:none;"><input type="text" name="bwbps_uploadurl2" id="bwbps_uploadurl2" /> Image URL</span>';
				break;

			
			case "[submit]":
				if(is_array($atts) && array_key_exists('name', $atts)){
					$submitname = $atts['name'];
				} else {
					$submitname = 'Submit';
				}
				$ret = '<input type="submit" class="ps-submit" value="'.$submitname.'" id="bwbps_submitBtn" name="bwbps_submitBtn" />';
				break;
				
			case "[done]":
			
				if(!$this->options['use_thickbox'] && !$g['use_thickbox']){
				
					if($this->options['use_donelink']){
						$ret .= '<a href="javascript: void(0);" onclick="bwbpsHideUploadForm('.$g["gallery_id"].');return false;">Done</a>';
					} else {			
						$ret .= '
		        		<input type="button" class="ps-submit" value="Done" onclick="bwbpsHideUploadForm('.$g['gallery_id'].');return false;" />
	        		';
		        	}

	        	} else {
	        	
	        		if($this->options['use_donelink']){
					$ret .= '<a href="javascript: void(0);" onclick="tb_remove();return false;">Done</a>';
					} else {
						$ret .= '
	        		<input type="button" class="ps-submit" value="Done" onclick="tb_remove();return false;" />
		        	';
		        	}
	        	}
				break;
				
			case "[caption]":
				$ret = '<input type="text" name="bwbps_imgcaptionInput" id="bwbps_imgcaptionInput" />';
				break;
			
			case "[caption2]":
				$ret = '<input type="text" name="bwbps_imgcaption2" id="bwbps_imgcaptionInput" />';
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
				$ret = '<span id="bwbps_result"></span>';
				break;
				
			case "[thumbnail_2]":
				$ret = '<span id="bwbps_result2"></span>';
				break;
			case "[url]":
				$ret = '<input tabindex="3" type="text" name="bwbps_url" id="bwbps_url" />';
				break;
			case "[loading]":
				$ret = '<img id="bwbps_loading" src="'.WP_PLUGIN_URL.'/photosmash-galleries/images/loading.gif" style="display:none;" alt="loading" />';
				break;
			case "[message]" :
				$ret = '<span id="bwbps_message"></span>';
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
			$ret .= "<td align='left'>".$this->getField($fld)."</td></tr>";
		} else {
			$ret = "<tr><th></th><td>".$this->getField($fld)."</td></tr>";
		}
		return $ret;
	}
	
	
	
	//BUILD THE FORM FIELD
	function getField($f, $tabindex=false){
		
		$val = $f->default_val;
		
		//Name is field name + bwbps (prepended)
		$name = "bwbps_".$blank.$f->field_name;
		
		//Doesn't work for multi value items...build their IDs ad hoc
		$id = " id='bwbps_".$f->field_name."'";
		
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
						."' type='text' maxlength='255' />";
				break;
			case 1 :	//textarea
				$ret = "<textarea tabindex='".$tabindex."' ".$id
					." ".$ele_name
					." rows=4 cols=40 />"
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
				$ret = "<input tabindex='".$tabindex."' ".$id
					." ".$ele_name
					." value='".htmlentities($val, ENT_QUOTES)
					."' type='text' style='width:130px;' />";
					
				$ret .= "
				<script type='text/javascript'>
					
					jQuery(document).ready(function(){

							jQuery('#bwbps_".$f->field_name."').datepicker();

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
				
				/*
				
				$ret = "<select tabindex='".$tabindex."' ".$id
					." ".$ele_name.">";
				$ret .= "<option value=''>--Select--</option>";
				
				$opts['opentag'] = "option";	//opening tag
				$opts['closetag'] = "</option>";	//closing tag
				$opts['selected'] = 'selected';  // indicator for selected value
				$opts['defval'] = $val;
				$opts['type'] = "";	// input type (e.g. type='text')
				$opts['name'] = "";  //form field name (radioboxes need this)
				
				*/		
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
  				."value='".htmlentities($row->value, ENT_QUOTES)."'";
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