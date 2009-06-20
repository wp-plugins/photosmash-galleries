<?php

class BWBPS_Layout{
	var $options;
	
	var $custFields;
	var $stdFields;
	var $psOptions;
	var $tabindex;
	var $moderateNonceCount = 0;
	
	var $layouts;
	
	//Constructor
	function BWBPS_Layout($options, $cfList){
		$this->psOptions = $options;
		$this->custFields = $cfList;
		$this->stdFields = $this->getStandardFields();
	}
	
	function getStandardFields(){
		return array('[caption]'
			, '[url]'
			, '[image]'
			, '[linked_image]'
			, '[image_id]'
			, '[gallery_id]'
			, '[thumbnail]'
			, '[thumb]'
			, '[contributor]'
			, '[user_name]'
			, '[user_url]'
			, '[contributor_url]'
			, '[full_caption]'
			, '[date_added]'
			, '[file_name]'
			, '[gallery_name]'
			, '[eval]'
		);
	}
	
	//  Build the Markup that gets inserted into the Content...$g == the gallery data
	function getGallery($g, $layoutName = false, $image=false, $useAlt=false)
	{
		global $post;
		global $wpdb;
				
		$admin = current_user_can('level_10');		

		if(!$image){
		
			//Determine if we need to bring back Custom Fields
			if($this->psOptions['use_customfields'] || 
				$this->psOptions['use_customform'] || $layoutName){
				
				$usecustomfields = true;
				
			} else { 
				
				$usecustomfields = false;
				
			}
		
			$images = $this->getGalleryImages($g, $usecustomfields);
			
		}else{
		
			$images[] = $image;
			
		}
	
		//Calculate Pagination variables
		$totRows = $wpdb->num_rows;	// Total # of images (total rows returned in query)

		$perma = get_permalink($post->ID);	//The permalink for this post
		$pagenum = (int)$_REQUEST['bwbps_page'];	//What Page # are we on?
		
		//Set to page 1 if not supplied in Get or Post
		if(!$pagenum || $pagenum < 1){$pagenum = 1;}	
	
		//get the pagination navigation
		if($totRows && $g['img_perpage']){
			$nav = $this->getPagingNavigation($perma, $pagenum, $totRows, $g['img_perpage']);	
				//What image # do we begin page with?
				$lastImg = $pagenum * $g['img_perpage'];
				$startImg = $lastImg - $g['img_perpage'] + 1;
			
		} else {
			$nav = "";
			$startImg = 0;
			$lastImg = $totRows + 1;
		}
	
		//Set up Attributes:  caption width, image class name, etc
		if(!$g['thumb_width'] || $g['thumb_width'] < 60){
			$g['captionwidth'] = "style='width: 80px'";
		} else {
			$g['captionwidth'] = "style='width: ".($g['thumb_width'] + 4)."px'";
		}
		//IMAGE CLASS
		if($g['img_class']){$g['imgclass'] = 
			" class='".$g['img_class']."'";} else {$g['imgclass']="";}
		
		//IMAGE REL
		if($g['img_rel']){
			
			$caprel = str_replace("[album]","[album_"
				.$g['gallery_id']."cap]",$g['img_rel']);
				
			$g['img_rel'] = str_replace("[album]","[album_"
				.$g['gallery_id']."]",$g['img_rel']);
			
			if( $caprel == $g['img_rel'] ){ $caprel .= 'cap'; }
			
			$g['url_attr']['imgrel'] = " rel='".$g['img_rel']."'";
			$g['url_attr']['caprel'] = " rel='".$caprel."'";
			
		} else {$g['url_attr']['imgrel']="";}
		
		//NO FOLLOW
		if($g['nofollow_caption']){
			$g['url_attr']['nofollow'] = " rel='external nofollow'";
		}else {$g['url_attr']['nofollow']='';}

		//CAPTION CLASS
		$g['url_attr']['captionclass']= ' class="bwbps_caption"';

		//IMAGES PER ROW
		if($g['img_perrow'] && $g['img_perrow']>0){
			$g['imgsPerRowHTML'] = " style='width: "
				.floor((1/((int)$g['img_perrow']))*100)."%;'";
		} else {
			$g['imgsPerRowHTML'] = " style='margin: 15px;'";
		}
		
		
		//Get the Custom Layout if in use
		if($layoutName){
			$layout = $this->getLayout(false, $layoutName);
		}
		if(!$layout && (int)$g['layout_id'] > -1){
			if((int)$g['layout_id'] == 0){ 
				//use the PhotoSmash Default Layout 
				if($this->psOptions['layout_id'] && $this->psOptions['layout_id'] > -1){
					$layout = $this->getLayout($this->psOptions['layout_id']);		
				} //else, just use the Standard Layout
			} else {
				$layout = $this->getLayout($g['layout_id']);
			}
		}
		
		if($useAlt){
			$imgNum = 1;
		} else {
			$imgNum = 0;
		}
		
		if($images){
			if($this->psOptions['img_targetnew']){
				$g['url_attr']['imagetargblank'] = " target='_blank' ";
			}
					
			foreach($images as $image){
				$imgNum++;
				//Pagination - not the most efficient, 
				//but there shouldn't be thousands of images in a gallery
				if($startImg > $imgNum || $lastImg < $imgNum){ continue;}
			
				$g['modMenu'] = "";
				switch ($image['status']) {
					case -1 :
						$g['modClass'] = 'ps-moderate';
						if($admin){
							$g['modMenu'] = 
								"<br/><span class='ps-modmenu' id='psmod_"
								.$image['psimageID']
								."'><input type='button' "
								."onclick='bwbpsModerateImage(\"approve\", "
								.$image['psimageID']
								.");' value='approve' class='ps-modbutton'/>"
								."<input type='button' onclick='bwbpsModerateImage(\"bury\", "
								.$image['psimageID']
								.");' value='bury' class='ps-modbutton'/></span>";
						}
						break;
	
					case -2 :
						$g['modClass'] = 'ps-buried';
						break;
					
					case -10 :	//special status that will be cleaned out periodically
						
						break;
					default :
						$g['modClass'] = '';
						break;
				}
				
				$image['imgtitle'] = str_replace("'","",$image['image_caption']);
				
				
				/*	***********		Set up the <a href....>		*************  */
				$this->calculateURLs($g, $image, $perma);
				
								
				//Get the Layout:  Standard or Custom
				if(!$layout){
					//Standard Layout
					$psTable .= $this->getStandardLayout($g, $image);
							
				} else {
					//Custom Layout
										
					if($imgNum % 2 == 0){
						$psTableRow .= $this->getCustomLayout($g, $image, $layout, true);	
					} else {
						$psTableRow .= $this->getCustomLayout($g, $image, $layout, false);	
					}
					
					if($layout->cells_perrow){
						if($imgNum % $layout->cells_perrow == 0){
							$psTable .="<tr>".$psTableRow."</tr>";
							$psTableRow = "";
							$imgNum = 0;
						}
					
					} else {
						$psTable .= $psTableRow;
						$psTableRow = "";
					}
				}
			}
			
		} else {
			if(!$layout){
				$psTable .= "<li class='psgal_".$g['gallery_id']
					."' style='height: ".($g['thumb_height'] + 15)
					."px; margin: 15px 0;'><img alt='' 	src='"
					.WP_PLUGIN_URL."/photosmash-galleries/images/"
					."ps_blank.gif' width='1' height='"
					.$g['thumb_height']."' /></li>";
			}
		}
		
		//If using Cells Per Row (for tables in Custom Forms..a setting Advanced)
		//then clean up any left over $psTableRows
		if($layout->cells_perrow && $psTableRow){
			
			$remaining =  $layout->cells_perrow - $imgNum;
			if($remaining > 0){
				for($i =0; $i < $remaining; $i++){
					$psTableRow .= "<td></td>";
				}
			}
			$psTable .="<tr>".$psTableRow."</tr>";
		}
		
		//Gallery Wrapper
		
		if(!$layout){
			//Standard Wrapper
			$ret = "<div class='bwbps_gallery_div' id='bwbps_galcont_".$g['gallery_id']."'>
			<table><tr><td>";
			
			$ret .= "<ul id='bwbps_stdgal_".$g['gallery_id']."' class='bwbps_gallery'>".$psTable;
	
			$ret .= "</ul>
				</td></tr></table>
				".$nav."</div>
				";
		} else {
			//Custom Wrapper
			if($layout->wrapper){
				$ret = $layout->wrapper;
				$ret = str_replace('[gallery_id]',$g['gallery_id'], $ret);
				$ret = str_replace('[gallery_name]',$g['gallery_name'], $ret);
				
				if(strpos($layout->wrapper, '[gallery]')){
					$ret = str_replace('[gallery]',$psTable, $ret);
				}else {
					$ret .= $psTable;
				}
				
			} else {
				$ret = $psTable;
			}
			
			//Add CSS
			if(trim($layout->css)){
				$ret = "<style type='text/css'>
				<!--
				".$layout->css."
				-->
				</style>".$ret;
			}
			
			//Need the insertion point to create a holder for adding new images.			
			$ret .= "<div id='bwbpsInsertBox_".$g['gallery_id']."' style='clear: both;'></div>";
				
			$ret .= "
				<script type='text/javascript'>
					bwbpsCustomLayout = true;
				</script>
				";
		}
		
		return $ret;
	}
	
	
	function calculateURLs(&$g, &$image, $perma)
	{
		//Deal with cases where they only want links to images on Post Pages
		$filetype = (int)$image['file_type'];
		
		if($filetype == 0 || $filetype == 1 || $filetype == 4 )
		{
			
			// URL when setting for Front/Cat/Archive pages to link thumbnails to the Post
			if( !is_single() && $this->psOptions['imglinks_postpages_only'])
			{
				$image['imgurl'] = "<a href='".$perma."'>";
				$image['imgurl_close'] = "</a>";
			} else {
			// Normal URLs
			
				//Deal with special cases where the caption style changes 
				//the thumbnail link.
				if($g['show_imgcaption'] == 8 || $g['show_imgcaption'] == 9){
					if($this->validURL($image['url'])){
						$theurl = $image['url'];
					} else {
						if($this->validURL($image['user_url'])){
							$theurl = $image['user_url'];
						} else {
							$theurl = PSIMAGESURL.$image['file_name'];
							$image['special_url'] = false;
						}
					}
				
					$image['imgurl'] = "<a href='".$theurl."'"
						.$g['url_attr']['imgrel']." title='".$image['imgtitle']."' "
						.$g['url_attr']['imagetargblank'].">";
						
					$image['capurl'] = "<a href='".$theurl."'"
						.$g['url_attr']['caprel']." title='".$image['imgtitle']."' "
						.$g['url_attr']['imagetargblank'].">";
															
				} else {
			
					$image['imgurl'] = "<a href='".PSIMAGESURL
						.$image['file_name']."'"
						.$g['url_attr']['imgrel']." title='".$image['imgtitle']."' "
						.$g['url_attr']['imagetargblank'].">";
						
					$image['capurl'] = "<a href='".PSIMAGESURL
						.$image['file_name']."'"
						.$g['url_attr']['caprel']." title='".$image['imgtitle']."' "
						.$g['url_attr']['imagetargblank'].">";
				
				}
				
				$image['imgurl_close'] = "</a>";
				$image['capurl_close'] = $image['imgurl_close'];
			}
		} else {
			$image['imgurl'] = "";
			$image['imgurl_close'] = "";
			$image['capurl'] = "";
			$image['capurl_close'] = "";
		}
		
	}
	
	
	/**
	 * Get Standard Layout
	 * @return (str) containing a single images block of code, using an LI wrapper
	 *
	 * @param (object) $g - gallery definition array; (object) $image - an image object
	 */
	function getStandardLayout($g, $image){
		$ret = "<li class='psgal_".$g['gallery_id']." "
					.$g['modClass']."' id='psimg_".$image['psimageID']."'"
					.$g['imgsPerRowHTML'].">
					<div id='psimage_".$image['psimageID']."' "
					.$g['captionwidth'].">";
					
		// Get File Field
		$fileField = $this->getFileField($g, $image);	
		if($fileField)
		{
			$ret .= $image['imgurl'] . $fileField . $image['imgurl_close'];
		}
		
		// Get Caption
		$scaption =  $this->getCaption($g, $image);
		if($scaption) 
		{
			if( $fileField ) { $ret .= "<br/>"; }
			$ret .= $image['capurl'] . $scaption . $image['capurl_close'];
		}
				
		$ret .= "</div>".$g['modMenu']."</li>";					
				
		return $ret;
	}
	
	
	
	/**
	 * Get Custom Layout
	 * @return (str) containing a single images block of code, using custom layout def
	 *
	 * @param (object) $g - gallery definition array; (object) $image - an image object
	 * @param (object) $layout - custom layout definition; 
	 * @param (bool) $alt - alternating image or regular (even or odd)
	 */
	function getCustomLayout($g, $image, $layout, $alt){
		
		if($alt){
			//Use Alternate layout
			if(trim($layout->alt_layout)){
				$ret = $layout->alt_layout;
			} else {
				$ret = $layout->layout;
			}
		}else{
		
			$ret = $layout->layout;
		}
		
		//Replace Standard Fields with values
		foreach($this->stdFields as $fld){
			
			if($fld == '[eval]'){
				$atts = $this->getFieldsWithAtts($ret, 'eval');
				
				//$replace = eval($atts['f']);
				$fname = $atts['bwbps_match'];
				$ret = str_replace($fname, $replace, $ret);

			} else {
				if(!strpos($ret, $fld) === false){
					$ret = str_replace($fld, $this->getCFFieldHTML($fld, $image, $g), $ret);
				}
			}
		}
		
		
		//Replace Custom Fields with values
		if($this->psOptions['use_customfields']){
		
		  foreach($this->custFields as $fld){
		
			if(!strpos($ret, '['.$fld->field_name.']') === false){
			
				//Format Date if it's a date
				if( $image[$fld->field_name] && $fld->type == 5){
				
					if($image[$fld->field_name] <> "0000-00-00 00:00:00"){
					
						$val = date($this->getDateFormat()
							,strtotime ($image[$fld->field_name]));
					
					}

				} else {

					$val = $image[$fld->field_name];

				}
				
				$ret = str_replace('['.$fld->field_name.']', $val, $ret);
			}
		  }
		}
		
		return $ret;
	}
	
	function getDateFormat(){
		if(!trim($this->psOptions['date_format'])){
			return "m/d/Y";
		} else {
			return $this->psOptions['date_format'];
		}	
	}
	
	
	/**
	 * Partial Layouts
	 * Usage - in posts, use a shortcode like [psmash id=230 layout=address]
	 *		 - will return the address code based on the values from image id 230
	 * @return (str) containing a block of code based on a layout
	 *
	 * @param (object) $g - gallery definition array; (object) $image - an image object
	 */
	function getPartialLayout($g, $image, $layoutName, $alt=false){
		
		return $this->getGallery($g, $layoutName, $image, $alt);
	
	}
	
	function getFileField($g, $image, $is_thumb=true){
		$ftype = (int)$image['file_type'];
		
		if($is_thumb){
			$psg_imagesurl = PSTHUMBSURL;
		} else {
			$psg_imagesurl = PSIMAGESURL;
		}
		
		switch ( true ) {
		 
			case ( $ftype == 0 || $ftype == 1 ) :	//image
			
				if($image['file_name']){
				$ret = "<img src='".$psg_imagesurl.$image['file_name']."'".$g['imgclass']
					." alt='".$image['imgtitle']."' />";
				} else { $ret = ""; }
			
				break;
			
			case ( $ftype == 2 ) :	//direct link
			
				$thumbheight = "";
				$thumbwidth = "";
				if($g['thumb_width']){ $thumbwidth = " width='".$g['thumb_width']."'";}
				if($g['thumb_height']){ $thumbheight = " width='".$g['thumb_height']."'";}
				if( $this->psValidateURL($image['file_url']) )
				{
					$ret = "<img src='".$image['file_url']."'".$g['imgclass']
					." alt='".$image['imgtitle']."' " . $thumbwidth . $thumbheight . " />";
				} else { $ret = ""; }
				
				
				break;
				
			case ( $ftype == 3 ) :	//youtube
			
				$thumbheight = "";
				$thumbwidth = "";
				if($g['thumb_width']){ $width = (int)$g['thumb_width'];}
				if($g['thumb_height']){ $height = (int)$g['thumb_height'];}
				
				$width = $width ? $width : 320;
				$height = $height ? $height : 265;
				
				if( $image['file_url'] ){
					$ret = '<span class="youtube"><object width="'.$width.'" height="'.$height.'"><param name="movie" value="http://www.youtube-nocookie.com/v/FAlWxZK-ps4&hl=en&fs=1&rel=0&color1=0xe1600f&color2=0xfebd01"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="'
					. htmlspecialchars("http://www.youtube-nocookie.com/v/" 
					. $image['file_url'] 
					. "&hl=en&fs=1&rel=0&color1=0xe1600f&color2=0xfebd01") 
					. '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$width.'" height="'.$height.'"></embed></object></span>';
				} else {
				 	$ret = "";
				}
				
				break;
			
			case ( $ftype == 4 ) :	//video
				
				
				$thumbheight = "";
				$thumbwidth = "";
				if($g['thumb_width']){ $width = (int)$g['thumb_width'];}
				if($g['thumb_height']){ $height = (int)$g['thumb_height'];}
				
				$width = $width ? $width : 320;
				$height = $height ? $height : 265;
				
				if( $image['file_url'] ){
					if($image['file_name']){
											
						$ret = "<img src='".$psg_imagesurl.$image['file_name']."'".$g['imgclass']
							." alt='".$image['imgtitle']."' />";
						
					} else {
			
						$ret = "<img src='".BWBPSPLUGINURL."/images/no_image.gif'".$g['imgclass']
							." alt='".$image['imgtitle']."' />";
					
					}
					
				} 
				
				
				break;
			
			default :
				
				break;
		}
		
		return $ret; 
	}
	
	/**
	 * Get TaggedField
	 * @return (str) containing a single images block of code, using an LI wrapper
	 *
	 * @param (object) $g - gallery definition array; (object) $image - an image object
	 */
	 
	function getCFFieldHTML($fld, $image, $g){
		
		switch ($fld){
			case '[image]' :
				$ret = $this->getFileField($g, $image, false);
				break;
				
			case '[doc]' :
				$ret = $this->getFileField($g, $image, false);
				break;
				
			case '[youtube]' :
				$ret = $this->getFileField($g, $image);
				break;
				
			case '[video]' :
				$ret = $this->getFileField($g, $image);
				break;
				
			case '[linked_image]' :
				if($image['file_name']){
				  
					$ret = $image['imgurl']."
						<img src='".PSIMAGESURL.$image['file_name']."'".$g['imgclass']
						. " alt='".$image['imgtitle']."' />"
						. $image['imgurl_close'];
						
				} else { $ret = ""; }
				break;
				
			case '[thumbnail]' :
				if($image['file_name']){
				
					$ret = $image['imgurl']."
						<img src='".PSTHUMBSURL.$image['file_name']."'".$g['imgclass']
						." alt='".$image['imgtitle']."' />"
						.$image['imgurl_close'];
					
				} else { $ret = ""; }
				
				break;
			
			case '[thumb]' :
				if($image['file_name']){
				
					$ret = $image['imgurl']."
						<img src='".PSTHUMBSURL.$image['file_name']."'".$g['imgclass']
						." alt='".$image['imgtitle']."' />"
						.$image['imgurl_close'];
					
				} else { $ret = ""; }
				
				break;
				
			case '[image_id]' :
				$ret = $image['psimageID'];
				break;
			
			case '[gallery_id]' :
				$ret = $g['gallery_id'];
				break;
				
			case '[gallery_name]' :
				$ret = $g['gallery_name'];
				break;
			
			case '[caption]' :
				$ret = $image['image_caption'];
				break;
			
			case '[file_name]' :
				$ret = $image['file_name'];
				break;
			
			case '[date_added]' :
				$ret = date($this->getDateFormat(4)
						,strtotime ($image['created_date']));
				
				break;
			
			case '[full_caption]' :
				$ret = $this->getCaption($g, $image);
				break;
			
			case '[user_name]' :
				
				$ret = $this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name']);
				
				break;
			
			case '[contributor]' :
				
				$ret = $this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name']);
				break;
			
			case '[user_url]' :
				$ret = "";
				if($this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name'])){
					if($image['user_url'] && $this->validURL($image['user_url'])){
						$ret = "<a href='".$image['user_url']."' title=''>"
							.$this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name'])."</a>";
					}
				}
				break;
			
			case '[contributor_url]' :
				$ret = "";
				if($this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name'])){
					if($image['user_url'] && $this->validURL($image['user_url'])){
						$ret = "<a href='".$image['user_url']."' title=''>"
							.$this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name'])."</a>";
					}
				}
				break;			
			
			case '[url]' :
				$ret = $image['url'];
				break;			
			
			default :
				break;
		}
		return $ret;
	}
	
		
	/**
	 * Get Caption
	 * @return (str) containing html for an image's caption based on settings
	 *
	 * @param (object) $g - gallery definition array; (object) $image - an image object
	 */
	function getCaption($g, &$image){
		//Build caption
			if($this->psOptions['caption_targetnew']){
				$captiontargblank = " target='_blank' ";
			}
			
			$nicename = $this->calcUserName($image['user_login']
				, $image['user_nicename'], $image['display_name']);

			$nicename = $nicename ? $nicename : "anonymous";
							
			switch ($g['show_imgcaption']){
				case 0:	//no caption
					$image['capurl'] = "";
					$image['imgurl_close'] = "";
					
					break;
				case 1: //caption - link to image
					
					if($image['image_caption']){
					
						$scaption = "<span ".$g['url_attr']['captionclass'] .">"
							. $image['image_caption']."</span>";
					
						$image['capurl_close'] = $image['imgurl_close'];
						
					} else {
						$image['capurl'] = "";
					}
					break;
					
				case 2: //contributor's name - link to image
				
					$scaption = "<span ".$g['url_attr']['captionclass'] .">"
						. $nicename. "</span>";
					break;
					
				case 3: //contributor's name - link to website
					
					if($this->validURL($image['user_url'])){
					
						$theurl = $image['user_url'];
						$captionurl = "
						<a href='".$theurl."'"
							." title='".str_replace("'","",$image['image_caption'])
							."' ".$g['url_attr']['nofollow']." $captiontargblank>";
						$closeUserURL = "</a>
						";
						$image['capurl'] = "";
						$image['capurl_close'] = "";
												
					}else{
						$captionurl = "";
						$closeUserURL = "";					
					}
					
					$scaption = "<span ".$g['url_attr']['captionclass'].">"
						. $captionurl
						. $nicename . $closeUserURL . "</span>";
						
					break;
					
				case 4: //caption [by] contributor's name - link to website
					if($this->validURL($image['user_url'])){
						
						$theurl = $image['user_url'];
						$captionurl = "
						<a href='".$theurl."'"
							." title='".str_replace("'","",$image['image_caption'])
							."' ".$g['url_attr']['nofollow']." $captiontargblank>";
						$closeUserURL = "</a>
						";
						$image['capurl'] = "";
						$image['capurl_close'] = "";				
						
					}else{
					
						$captionurl = "";
						$closeUserURL = "";
						
					}
					
					$scaption = "<span ".$g['url_attr']['captionclass'] .">"
						. $captionurl
						. $image['image_caption']." by "
						. $nicename . $closeUserURL . "</span>";
					
					break;
					
				case 5: //caption [by] contributor's name - link to image
				
					$scaption = "<span ".$g['url_attr']['captionclass'] .">"
						. $image['image_caption']." by "
						. $nicename 
						. "</span>";
											
					break;
					
				case 6: //caption [by] contributor's name - link to user submitted url
				
					$goturl = false;
					
					if($this->validURL($image['url'])){
						
						$theurl = $image['user_url'];
						$captionurl = "
						<a href='".$theurl."'"
							." title='".str_replace("'","",$image['image_caption'])
							."' ".$g['url_attr']['nofollow']." $captiontargblank>";
						$closeUserURL = "</a>
						";
											
						$theurl = $image['url'];
						$goturl = true;
						
					} else {
					
						if($this->validURL($image['user_url'])){
						
							$theurl = $image['user_url'];
							$goturl = true;
							
						}
					}
					
					if($goturl){
					
						$captionurl = "<a href='".$theurl."'"
							." title='".str_replace("'","",$image['image_caption'])
							."' ".$g['url_attr']['nofollow']."  $captiontargblank>";
							
						$closeUserURL = "</a>";
						
						$image['capurl'] = "";
						$image['capurl_close'] = "";
						
					}else{
					
						$captionurl = "";
						$closeUserURL = "";					
					}
					
					$scaption = "<span ".$g['url_attr']['captionclass'] .">"
						. $captionurl
						. $image['image_caption']." by "
						. $nicename . $closeUserURL . "</span>";
					
					break;
					
				case 7: //caption - link to user submitted url
					if( $image['image_caption'] ){
					
						$goturl = false;
						if($this->validURL($image['url'])){
							$theurl = $image['url'];
							$goturl = true;
						} else {
							if($this->validURL($image['user_url'])){
								$theurl = $image['user_url'];
								$goturl = true;
							}
						}
						
						if($goturl){
													
							$captionurl = "<a href='".$theurl."'"
								." title='".str_replace("'","",$image['image_caption'])
								."' ".$g['url_attr']['nofollow']."  $captiontargblank>";
								
							$closeUserURL = "</a>";
							
							$image['capurl'] = "";
							$image['capurl_close'] = "";
							
						}else{
							$captionurl = "";
							$closeUserURL = "";
						}
						
						$scaption = "<span ".$g['url_attr']['captionclass'] .">"
							. $captionurl
							. $image['image_caption']
							. $closeUserURL . "</span>";
					} else {
						$image['capurl'] = "";
						$image['capurl_close'] = "";
					}
						
					break;
				
				case 8:	//no caption - Thumbnail links to User Submitted URL
					
					$image['capurl'] = "";
					$image['capurl_close'] = "";
					$scaption = "";	//Close out the link from above
					
					break;
					
				case 9: //caption - Thumbnail & Caption link to User Submitted URL
				
					if( $image['image_caption'] ) 
					{
						$scaption = "<span ".$g['url_attr']['captionclass'] .">"
							. $image['image_caption']
							. "</span>";
					} else {
						$image['capurl'] = "";
						$image['capurl_close'] = "";
					}
															
					break;
			}
			
			return $scaption;
	}
	
	/**
	 * Get Paging Navigation
	 * @return - (str) containing a block of html that navigates through pages in a gallery
	 *
	 * @param (str) $url - current page's url, (int) $page - current page #
	 * @param (int) $totalRows - total rows in images query
	 * @param (int) $rowsPerPage - rows per page - or # of images per page
	 */
	function getPagingNavigation($url, $page, $totalRows, $rowsPerPage){
		if((int)$rowsPerPage < 1){return false;}
				
		$total_pages = ceil($totalRows / $rowsPerPage);
		
		//use split on ? to get the url broken between ? and rest
		
		$arrURL = split("\?",$url);
		if(count($arrURL)> 1){
			$url .= "&";			
		} else {
			$url .= "?";
		}
		
		//Build PREVIOUS link
		if($page > 1){
			$nav[] = "<a href='".$url."bwbps_page=".($page-1)."'>&#9668;</a>";
		}
		
		if($total_pages > 1){
			
			for($page_num = 1; $page_num <= $total_pages; $page_num++){
				if($page == $page_num){ 
					$nav[] = "<span>".$page."</span>";
				}else{
					$nav[] = "<a href='".$url."bwbps_page=".$page_num."'>".$page_num."</a>";
				}
			}
			
		}
		
		if($page < $total_pages){
			$nav[] = "<a href='".$url."bwbps_page=".($page+1)."'>&#9658;</a>";
		}
		
		$snav = "";
		if(is_array($nav)){
			$snav = implode("",$nav);
		}
		
		$ret = "<div class='bwbps_pagination'>". $snav."</div>";
		
		return $ret;
		
	}
	
	/**
	 * Get Layout
	 * returns the custom layout from the database
	 *
	 * @param (int) $layout_id
	 */
	function getLayout($layout_id, $layout_name=false){
		global $wpdb;
		
		if($layout_name){ $layoutName = $layout_name;} else { $layoutName = "psid-".$layout_id;}
		if(is_array($this->layouts)){
			if(array_key_exists($layoutName, $this->layouts)){
				return $this->layouts[$layoutName];
			}
		}
		
		if(!$layout_id){
			$sql = $wpdb->prepare('SELECT * FROM '.PSLAYOUTSTABLE
			.' WHERE layout_name = %s ', $layout_name);
		} else {
			$sql = $wpdb->prepare('SELECT * FROM '.PSLAYOUTSTABLE
			.' WHERE layout_id = %d ', $layout_id);
		}
		$query = $wpdb->get_row($sql);
		
		$this->layouts[$layoutName];	//Cache layouts to prevent future DB calls
		
		return $query;
	}
	
	/**
	 * Get Images for Gallery
	 * returns a query object containing the images in a gallery + user info
	 * for users who uploaded images
	 *
	 * @param (object) $g - the gallery definition array
	 * @param (object) $customFields - whether to bring in custom data or not
	 */
	function getGalleryImages($g, $customFields=false){
		global $wpdb;
		global $user_ID;
				
		//Set up SQL for Custom Data if in Use
		if($customFields){
			$custDataJoin = " LEFT OUTER JOIN ".PSCUSTOMDATATABLE
				." ON ".PSIMAGESTABLE.".image_id = "
				.PSCUSTOMDATATABLE.".image_id ";
			$custdata = ", ".PSCUSTOMDATATABLE.".* ";
		}
		
		$imagetablefields = array( 'seq'
			, 'updated_date'
			, 'avg_rating'
			, 'rating_cnt'
			, 'created_date'
			, 'updated_date'
			, 'user_id'
			, 'gallery_id'
			, 'image_id'
			, 'file_type'
			, 'image_caption'
		);
		
		// Calculate ORDER BY
		if( $g['sort_by'] ){
			$sortflds = split(",", $g['sort_by']);
			unset($sortby);
			foreach ( $sortflds as $sfld )
			{
				$sfld = trim($sfld);
				$tempfld = split(" ", $sfld);
				$sorder = "";
				if(count($tempfld) > 1){
					$sorder = trim($tempfld[1]);
					if($sorder == "ASC" || $sorder == "DESC" ){
						$sorder = " ".$sorder;
					}else{
						$sorder = "";
					}
				}
				if( in_array( $sfld, $imagetablefields ) ) {
					$sortby[] = PSIMAGESTABLE.$sfld.$sorder;
				}
			}
			if( is_array($sortby) ){
				$sortby = implode( ", ", $sortby );
			} else { $sortby = ""; }
		} else {
			$sortby = PSIMAGESTABLE.'.seq, '.PSIMAGESTABLE.'.created_date';
		}
		
		//Admins can see all images
		if(current_user_can('level_10')){
			$sql = $wpdb->prepare('SELECT '.PSIMAGESTABLE.'.*, '
				.PSIMAGESTABLE.'.image_id as psimageID, '
				.$wpdb->users.'.user_nicename,'
				.$wpdb->users.'.display_name,'
				.$wpdb->users.'.user_login,'
				.$wpdb->users.'.user_url'
				.$custdata.' FROM '
				.PSIMAGESTABLE.' LEFT OUTER JOIN '.$wpdb->users.' ON '
				. $wpdb->users .'.ID = '. PSIMAGESTABLE. '.user_id'.$custDataJoin
				.' WHERE gallery_id = %d ORDER BY '.$sortby, $g['gallery_id']);			
					
			
		} else {
			//Non-Admins can see their own images and Approved images
			$uid = $user_ID ? $user_ID : -1;
					
			$sql = $wpdb->prepare('SELECT '.PSIMAGESTABLE.'.*, '
				.$wpdb->users.'.user_nicename,'
				.$wpdb->users.'.display_name,'
				.$wpdb->users.'.user_login,'
				.$wpdb->users.'.user_url'
				.$custdata.' FROM '
				.PSIMAGESTABLE.' LEFT OUTER JOIN '.$wpdb->users.' ON '
				.$wpdb->users.'.ID = '. PSIMAGESTABLE . '.user_id'.$custDataJoin
				.' WHERE gallery_id = %d AND (status > 0 OR user_id = '
				.$uid.')ORDER BY '.$sortby
				, $g['gallery_id']);			
				
		}
		
		
		$images = $wpdb->get_results($sql, ARRAY_A);
				
		return $images;
	}

	/**
	 * Valid URL
	 * validates a URL
	 *
	 * @param (str) $url
	 */
	function validURL($url)
	{
		return ( ! preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url)) ? FALSE : TRUE;
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
	
	function calcUserName($loginname, $nicename = false, $displayname = false){
		if($displayname) return $displayname;
		if($nicename) return $nicename;
		return $loginname;
	}
	
	//Validate URL
	function psValidateURL($url)
	{
		return ( ! preg_match('/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url)) ? FALSE : TRUE;
	}
}
?>