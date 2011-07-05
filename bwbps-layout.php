<?php

class BWBPS_Layout{
	var $options;
	
	var $custFields;
	var $stdFields;
	var $psOptions;
	var $tabindex;
	var $total_records = 0;
	var $moderateNonceCount = 0;
	
	var $attachment_ids;	// Used because we need to do a get_children on the attachments to avoid a Query nightmare
	var $attachments_gotten = false;
	
	var $layouts;
	
	var $ratings;
	
	var $layoutFields;
	
	//Constructor
	function BWBPS_Layout($options, $cfList){
		$this->psOptions = $options;
		$this->custFields = $cfList;
		$this->stdFields = $this->getStandardFields();
		
		add_action('bwbps_get_attachment_link', array(&$this, 'getAttachmentPosts'));
		
	}
	
	function getStandardFields(){
		return array('caption'
			, 'full_caption'
			, 'caption_escaped'
			, 'url'
			, 'file_name'
			, 'img_attribution'
			, 'img_license'
			, 'image'
			, 'linked_image'
			, 'image_id'
			, 'image_url'
			, 'thumb'
			, 'thumbnail'
			, 'thumb_image'
			, 'thumb_url'
			, 'thumb_linktoimage'
			, 'mini'	
			, 'mini_url'	
			, 'medium'
			, 'medium_url'
			, 'gallery_id'
			, 'gallery_name'
			, 'gallery_description'
			, 'user_name'
			, 'user_url'
			, 'user_link'
			, 'user_login'
			, 'author_link'
			, 'author'
			, 'contributor'
			, 'contributor_link'
			, 'date_added'
			, 'meta_data'
			, 'exif_table'
			, 'eval'
			, 'post_id'
			, 'post_name'
			, 'post_url'
			, 'ps_rating'
			, 'bloginfo'
			, 'blog_name'
			, 'plugin_url'
			, 'piclens'
			, 'wp_attachment_link'
			, 'wp_permalink'
			, 'wp_permalink_byforce'
			, 'wp_attach_id'
			, 'tag_links'
			, 'tag_dropdown'
			, 'nav_search'
			, 'nav_gallery'
			, 'nav_search_term'
			, 'submit'
			, 'tags_has_all'
			, 'favorite'
			, 'favorite_cnt'
			, 'delete_button'
			, 'image_gallery_name'
			, 'gallery_caption'
			, 'gallery_caption_escaped'
			, 'gallery_url'
			, 'gallery_post_url'
			, 'gallery_image_count'
			, 'comments_count'
			, 'infowindow_link'
			, 'gdsr'
		);
	}
	
	// Gets all Image IDs containing the given terms - $terms is either an array of terms or string (comma separated)
	// Used for filtering queries like 'term1' AND 'term2'
	function get_objects_in_term( $terms, $object_ids = false ) {
		global $wpdb;

		if ( !is_array( $terms) )
			$terms = explode(",", $terms);

		if ( !is_array($taxonomies) )
			$taxonomies = array($taxonomies);
			
		$terms = array_map("trim", $terms);
		
		$terms = array_map("esc_sql", $terms);
															
		$terms = "'" . implode("', '", $terms) . "'";
		
		if( !empty($object_ids) ){
		
			if( is_array($object_ids)){
				$object_ids = array_map("intval", $object_ids);
				$object_ids = " AND tr.object_id IN ('" . implode("', '", $object_ids) . "') ";
			}
		
		}
		
		$sql = "SELECT DISTINCT tr.object_id, tm.name FROM $wpdb->terms AS tm INNER JOIN  $wpdb->term_taxonomy AS tt ON tm.term_id = tt.term_id AND tt.taxonomy = 'photosmash' INNER JOIN  $wpdb->term_relationships AS tr  ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tm.name IN ($terms) $object_ids ORDER BY tr.object_id";
		

		$object_ids = $wpdb->get_results($sql);

		if ( ! $object_ids )
			return array();

		return $object_ids;
	}
	
	// Makes sure all images have all the given tags ("AND" query)
	function filter_tagged_images($tags, $img_ids){
		
		
		$object_ids = $this->get_objects_in_term( $tags, $img_ids);
		
		if(empty($object_ids)){ return false; }
				
		if(is_array($object_ids)){
			
			if ( !is_array( $tags) )
			$tags = explode(",", $tags);
			
			$tag_cnt = count($tags);
			
			foreach($object_ids as $o){
				$r[$o->object_id]++;
			}
						
			if(is_array($r)){
				foreach($r as $key => $val){
					if($tag_cnt == $val){
						$imgs[] = $key;
					}
				}
			}
		}
		
		return $imgs;
	}
	
	
	//  Build the Markup that gets inserted into the Content...$g == the gallery data
	function getGallery($g, $layoutName = false, $image=false, $useAlt=false)
	{
	
		// Determine if we are limiting by images per page or specified # of images
		$g['img_perpage'] = (int)$g['img_perpage'];
		
		if( (int)$g['limit_images'] && (int)$g['img_perpage'] ){
			if( $g['limit_images'] < $g['img_perpage'] ) $g['img_perpage'] = 0;
		}
		
	
		if( $g['wp_gallery'] && (int)$g['post_id'] ){
		
			$shortcode = "[gallery id=" . (int)$g['post_id'] 
				. " " . $g['wp_gallery_params'] ."]";

			$ret = do_shortcode( $shortcode );
			
			//Need the insertion point to create a holder for adding new images.
			if( !$g['no_insertbox'] && !$g['no_form']){
				$ret .= "<div id='bwbpsInsertBox_".$g['gallery_id']."' class='bwbps-insert-box'></div>";
			}
			
			return $ret;
		}
		global $post;
		global $wpdb;
				
		$admin = current_user_can('level_10');
		
		$uploads = wp_upload_dir();
			
		//Instantiate the Ratings class - used for ratings and favorites
		if( !isset($this->ratings) ){
				require_once('bwbps-rating.php');
				$this->ratings = new BWBPS_Rating();
		}
		
		//See if they're using a Rating on this Gallery
		if($g['poll_id']){
			
			$rate = true;	//Boolean for quick reference to add ratings
			
			
			unset($rating);
			
			if(!$this->psOptions['rating_allow_anon'] && !is_user_logged_in()){
				$allow_anon = 0;
			} else {
				$allow_anon = 1;
			}
			
			$rating = array( 
				'image_id' => 0,
				'gallery_id' => $g['gallery_id'],
				'poll_id' => $g['poll_id'],
				'avg_rating' => 0,
				'rating_cnt' => 0,
				'vote_sum' => 0,
				'vote_cnt' => 0,
				'rating_position' => $g['rating_position'],
				'allow_rating' => $allow_anon
			);
		}
		
		if(!$g['no_pagination']){
			$pagenum = $this->getPageNumbers();
		
		
			//Set to page 1 if not supplied in Get or Post
	
			if(!isset($pagenum[$g['gallery_id']]) || $pagenum[$g['gallery_id']] < 1){
			
				//check if Universal Paging is in effect
				if((int)$pagenum['uni']){
					$pagenum[$g['gallery_id']] = (int)$pagenum['uni'];
				} else {
					$pagenum[$g['gallery_id']] = 1;
				}
			}	
		
			//get the pagination navigation
			if( (int)$g['img_perpage'] ){
				
				//What image # do we begin page with?
				$lastImg = $pagenum[$g['gallery_id']] * $g['img_perpage'];
				$startImg = $lastImg - $g['img_perpage'] + 1;
				$g['starting_image'] = $startImg > 1 ? ($startImg - 1) : 0;
				$startImg = 1;
				
			} 
		}

		if(!$image){
			
			$images = $this->getGalleryImages($g, true);
			
		}else{
		
			$images[] = $image;
			
		}
		
		// For TAG gallery - if tags_has_all, then filter out any images that do not have all of the tags
		if($g['gallery_type'] == 40 && $g['tags_has_all'] && !empty($images) && is_array($images)){		
		
			unset($imgids);
			
			foreach($images as $img){
					$imgids[] = $img['psimageID'];
			}
			
			unset($img);
			
			$imgs = $this->filter_tagged_images($g['tags'], $img_ids);
			
			if($imgs && is_array($imgs)){
			
				$images_temp = $images;
				unset($images);
				
				foreach($images_temp as $img){
					
					if(in_array($img['psimageID'], $imgs)){
						$images[] = $img;
					}
					
				}
				
			} else {
				unset($images);
			}
			unset($imgs);
			unset($img);
			unset($images_temp);
			
		}
	
		//Calculate Pagination variables
		$totRows = $wpdb->num_rows;	// Total # of images (total rows returned in query)
		
		if($post->photosmash == 'author' || $post->photosmash == 'tag'){
			$perma = $post->photosmash_link;
		} else {
			$perma = get_permalink($post->ID);	//The permalink for this post
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
				
			$caprel = str_replace("[gallery]","gal_"
				.$g['gallery_id']."cap",$caprel);
				
			$g['img_rel'] = str_replace("[gallery]","gal_"
				.$g['gallery_id']."",$g['img_rel']);
			
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
			$imgNum = 1;	//for use with single images and you want to use the alternating layout
		} else {
			$imgNum = 0;
		}
		
		if( !empty($images) ){
			
			
			// Prepare the way for Attachment Links (this monkey business is necessary to prevent crazy query counts
			unset( $this->attachment_ids );
			$this->attachments_gotten = false;
			foreach( $images as $imgtemp ){
				if( (int)$imgtemp['wp_attach_id'] ){
					$this->attachment_ids[] = (int)$imgtemp['wp_attach_id'];
				}		
			}
		
			//Add SetID and Layout ID for use Insert Sets - PhotoSmash Extend
			
			if(!$g['no_inserts']){
				$images[0]['pext_insert_setid'] = (int)$g['pext_insert_setid'];
				$images[0]['pext_layout_id'] = ($layout ? $layout->layout_id : false);
				$images[0]['pext_start_image'] = 1;
				$images[0]['pext_imgs_perpage'] = $g['img_perpage'];
				$images[0]['pext_page_number'] = 1;
			}
			
			$images = apply_filters('bwbps_gallery_loop', $images);
						
			
			//get the pagination navigation
			if($g['img_perpage'] && !$g['no_pagination']){
				$nav = $this->getPagingNavigation($perma, $pagenum, $this->total_records, $g, $layout);
			} else {
				$nav = "";
			}
			$startImg = 0;
			$lastImg = count($images);
			$lastImg = ($useAlt && $lastImg==1) ? 2 : $lastImg;
		
			if($this->psOptions['img_targetnew']){
				$g['url_attr']['imagetargblank'] = " target='_blank' ";
			}
			
			//Get Ratings HTML
			if(current_user_can('level_0') && ($this->psOptions['favorites'] || $layout)){
				$g['ps_favorite_html'] = $this->ratings->getFavoritesHTML($layout, (int)$this->psOptions['favorites']);
			
			}
					
			foreach($images as $image){
			
				
				if((int)$image['image_id']) { $image['psimageID'] = (int)$image['image_id']; }
				
				if($g['ps_favorite_html']){
					$image['ps_fav_html'] = str_replace("{img}",$image['psimageID'], $g['ps_favorite_html']);
					$image['ps_fav_html'] = str_replace("{gal}",$image['gallery_id'], $image['ps_fav_html']);
					$image['ps_fav_html'] = str_replace("{favstate}",(int)$image['favorite_id'] ? 1 : 0, $image['ps_fav_html']);
					$image['ps_fav_html'] = str_replace("{favcnt}",(int)$image['favorites_cnt'], $image['ps_fav_html']);
					
				} else {
					$image['ps_fav_html'] = "";
				}
				
				$imgNum++;
				//Pagination - not the most efficient, 
				//but there shouldn't be thousands of images in a gallery
				if($startImg > $imgNum || $lastImg < $imgNum){ continue;}
				
				//Handle PSmashExtend Inserts
				if( $image['pext_insert'] ){
				
					if(!$layout){
						$imageTemp .= $image['pext_insert'];
						
						$psTable .= apply_filters('bwbps_image', $imageTemp);
						unset($imageTemp);
						
					} else {
						$imageTemp .= $image['pext_insert'];
						
						if($layout->cells_perrow){
		
							$cellsInRow++;
			
							if($cellsInRow % $layout->cells_perrow == 0){
								$psTable .="<tr>".$imageTemp."</tr>";					
								$cellsInRow = 0;
								unset($imageTemp);
							}
						
						} else {
							$psTable .= $imageTemp;
							unset($imageTemp);	// Need to call separately because it needs to stay alive for CellsInRow above
						}
						
						
						
					}
					
					continue;
				
				}
								
				//Handle Rating Code
				if($rate){
					$rating['image_id'] = $image['psimageID'];
					$rating['avg_rating'] = $image['avg_rating'];
					
					//if($image['bwbps_br_rating']) $rating['avg_rating'] = $image['bwbps_br_rating'];
					
					$rating['rating_cnt'] = $image['rating_cnt'];
					$rating['votes_sum'] = $image['votes_sum'];
					$rating['votes_cnt'] = $image['votes_cnt'];
					$rating['poll_id'] = $image['poll_id'];
										
					$image['ps_rating'] = $this->ratings->get_rating($rating);
					
			
				} else {
					$image['ps_rating'] = "";
				}
			
				if( $g['suppress_no_image'] && !$image['file_type'] 
					&& !$image['file_name'] && !$image['thumb_url'] ){
					continue;	
				}
				if( !$image['file_type'] 
					&& !$image['file_name'] && $g['default_image'] 
					&& !$image['thumb_url'] ){
					$image['file_name'] = $g['default_image'];
				} 
							
				if( !$image['thumb_url'] ){
				
					if( $image['file_name'] ){
						$image['mini_url'] = PSTHUMBSURL.$image['file_name'];
						$image['thumb_url'] = PSTHUMBSURL.$image['file_name'];
						$image['medium_url'] = PSTHUMBSURL.$image['file_name'];
						$image['image_url'] = PSIMAGESURL.$image['file_name'];
					}
				
				} else {
				
					// Add the Uploads base URL to the image urls.
					// This way if the user ever moves the blog, everything might still work ;-) 
					// set $uploads at top of function...only do it once
					if(!$image['mini_url']){ $image['mini_url'] = $image['thumb_url']; }
					$image['mini_url'] = $uploads['baseurl'] . '/' . $image['mini_url'];
					$image['thumb_url'] = $uploads['baseurl'] . '/' . $image['thumb_url'];
					$image['medium_url'] = $uploads['baseurl'] . '/' . $image['medium_url'];
					$image['image_url'] = $uploads['baseurl'] . '/' . $image['image_url'];
				
				}
				
				if( $image['file_url'] && $this->psValidateURL($image['file_url']) ){
					$image['image_url'] = $image['file_url'];
				}
				
				if(!$image['mini_url']){ $image['mini_url'] = $image['thumb_url']; }
				
				if(!$image['medium_url']){ $image['medium_url'] = $image['thumb_url']; }
			
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
				
				$image['imgtitle'] = esc_attr($image['image_caption']);
				
				
				/*	CALCULATE the Image and Caption URL  */				
				$this->calculateURLs($g, $image, $perma);
				
				
				// Calculate Google Map marker if lat & long are available and gmap_id is set
				// Do this after all the other goodies are calculated
				if($g['gmap_id']){
					$this->addGoogleMapMarker($image, $g);
				}
				
								
				//Get the Layout:  Standard or Custom
				if(!$layout){
					//Standard Layout
					$imageTemp .= $this->getStandardLayout($g, $image);
					
					$psTable .= apply_filters('bwbps_image', $imageTemp);
					unset($imageTemp);
							
				} else {
					//Custom Layout
								
					if($imgNum % 2 == 0){
						$imageTemp .= $this->getCustomLayout($g, $image, $layout, true);	
					} else {
						$imageTemp .= $this->getCustomLayout($g, $image, $layout, false);	
					}
					
					if( $javascript_layout ){
						$psJavascript .= $this->getCustomLayout($g, $image, $javascript_layout, false) . "
						";
					}

					$imageTemp = apply_filters('bwbps_image', $imageTemp);

					if($layout->cells_perrow){
		
						$cellsInRow++;
		
						if($cellsInRow % $layout->cells_perrow == 0){
							$psTable .="<tr>".$imageTemp."</tr>";					
							$cellsInRow = 0;
							unset($imageTemp);
						}
					
					} else {
						$psTable .= $imageTemp;
						unset($imageTemp);	// Need to call separately because it needs to stay alive for CellsInRow above
					}
					
				}
				
				
			}
			
		} else {
			if(!$layout){
				
				$imageTemp =  "<li class='psgal_".$g['gallery_id']
					."' style='height: ".($g['thumb_height'] + 15)
					."px; margin: 15px 0;'><img alt='' 	src='"
					.WP_PLUGIN_URL."/photosmash-galleries/images/"
					."ps_blank.gif' width='1' height='"
					.$g['thumb_height']."' /></li>";
					
				$psTable .= apply_filters('bwbps_empty_gallery', $imageTemp);	//Allow people to set their own look for an empty gallery
			}
			unset($imageTemp);
		}
		
		//If using Cells Per Row (for tables in Custom Forms..a setting Advanced)
		//then clean up any left over $psTableRows
		
		
		//if($layout->cells_perrow && $psTableRow){
		if($layout->cells_perrow > 0 && $cellsInRow % $layout->cells_perrow > 0){	
			$remaining =  $cellsInRow % $layout->cells_perrow;
			if($remaining > 0){
				for($i =0; $i < $remaining; $i++){
					$imageTemp .= "<td></td>";
				}
			}
			$psTable .="<tr>".$imageTemp."</tr>";
		}
		
		//Gallery Wrapper
		
		if($rate){
				$ratings_toggle = "<a href='javascript: void(0);'"
					. " onclick='bwbpsToggleRatings(". $g['gallery_id'] 
					. "); return false;' title='Toggle image ratings'>Toggle ratings</a>";
					
				$ratetoggle = "<span class='bwbps-rating-toggle'>$ratings_toggle</span><div class='bwbps-toggle-ratings-clear' style=' margin: 0; padding: 0;'></div>";			
		}
		
		if(!$layout){
			//Standard Wrapper
			$ret = "<div class='bwbps_gallery_div' id='bwbps_galcont_".$g['gallery_id']."'>".$ratetoggle."
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
				$ret = str_replace('[gallery_description]', $g['gallery_description'], $ret);
				$ret = str_replace('[ratings_toggle]', $ratings_toggle, $ret);
				
				if($g['hide_toggle_ratings']){
					$ratetoggle = '';
				}
				
				$psTable = $ratetoggle . $psTable;
				
				if(strpos($layout->wrapper, '[gallery]')){
					$ret = str_replace('[gallery]',$psTable, $ret);
				}else {
					$ret .= $psTable;
				}
				
				//Replace Standard Fields with values

			if(!strpos($ret, 'piclens') === false){
				unset($atts);
				unset($replace);
				
				$atts = $this->getFieldAtts($ret, $fld);
				
									
				$replace = $this->getPicLensLink($g, $atts);
			
				$fld = $atts['bwbps_match'];
								
				$ret = str_replace($fld, $replace, $ret);	

			}
				
				
			} else {
				$ret = $psTable;
			}
			
			
			
			$ret .= $nav;
			
			//Add CSS
			if(trim($layout->css)){
				$ret = "<style type='text/css'>
				<!--
				".$layout->css."
				-->
				</style>".$ret;
			}
			
			//Need the insertion point to create a holder for adding new images.
			if( !$g['no_insertbox'] && !$g['no_form']){
				$ret .= "<div id='bwbpsInsertBox_".$g['gallery_id']."' class='bwbps-insert-box'></div>";
			}
		}
	
		unset( $images );
		return $ret;
	}
	
	/**
	 * Get an Array of Page numbers -> uses gallery ID as key
	 * 
	 * @param none
	 */
	function getPageNumbers(){
	
		//Check to see if Universal paging is in effect
		if($this->psOptions['uni_paging']){
			$pgal = 'uni';
		} else {
			$uline = "_";
		}
		
		$pname = trim($this->psOptions['alt_paging']) ? trim($this->psOptions['alt_paging']) 
				: 'bwbps_page';
		
		$pname .= $uline;
				
	
		if(is_array($_REQUEST)){
			foreach( $_REQUEST as $key => $option ){
			
				if( strpos($key, $pname ) !== false ){
					
					$pg_gal = $pgal ? $pgal : ( str_replace($pname,"", $key ));	// Set the index to 'uni' if Universal paging is in effect
					$pagenum[$pg_gal] = (int)$option;
				
				}
			
			}
		}
				
		if(!is_array($pagenum)){ $pagenum = array(); }
	
		return $pagenum;
	}
	
	
	
	function calculateURLs(&$g, &$image, $perma)
	{
			
		//Deal with cases where they only want links to images on Post Pages
		$filetype = (int)$image['file_type'];
		
		if($filetype == 0 || $filetype == 1 || $filetype == 4 )
		{
			//Set anchor class ... clear it for special cases below
			if($g['anchor_class']){
				$anchor_class = " class='".$g['anchor_class']."'";
			}
			$image['the_image_link'] = "<a href='"
						.$image['image_url']."'"
						.$g['url_attr']['imgrel']." title='".$image['imgtitle']."' "
						.$g['url_attr']['imagetargblank'].$anchor_class.">";
						
			$image['cap_image_link'] = "<a href='"
						.$image['image_url']."'"
						.$g['url_attr']['caprel']." title='".$image['imgtitle']."' "
						.$g['url_attr']['imagetargblank'].">";
			
			// URL when setting for Front/Cat/Archive pages to link thumbnails to the Post
			if( $this->psOptions['imglinks_postpages_only'] && (is_front_page() || is_category()))
			{
				
				if((int)$image['post_id']){
					$post_perma = get_permalink((int)$image['post_id']);
				} else {
					if((int)$image['gal_post_id']){
						$post_perma = get_permalink((int)$image['gal_post_id']);
					}
				}			
			
				if($post_perma){
					$image['imgurl'] = "<a href='".$post_perma."' title='View post'>";
					$image['imgurl_close'] = "</a>";
				}
			} else {
			// Normal URLs
											
				
				//Deal with special cases where the caption style changes 
				//the thumbnail link.
				if($g['show_imgcaption'] == 8 || $g['show_imgcaption'] == 9 || $g['show_imgcaption'] == 12 || $g['show_imgcaption'] == 13)
				{

					$imgrel = $g['url_attr']['imgrel'];

					//Submitted URLS
					if($g['show_imgcaption'] == 8 || $g['show_imgcaption'] == 9){
						if($this->validURL($image['url'])){
							$theurl = $image['url'];
							$anchor_class = "";
							$imgrel = "";
	
						} else {
							if($this->validURL($image['user_url'])){
								$theurl = $image['user_url'];
								$anchor_class = "";
								$imgrel = "";
							} else {
								
								$theurl = $image['image_url'];
								$image['special_url'] = false;
							}
						}
					} else {
						//Link to Posts
						if((int)$image['post_id']){
							$theurl = get_permalink((int)$image['post_id']);
						} else {
							$theurl = get_permalink((int)$image['gal_post_id']);
						}
						$anchor_class = "";
						$imgrel = "";
											
					}
				
					$image['imgurl'] = "<a href='".$theurl."'"
						.$imgrel." title='".$image['imgtitle']."' "
						.$g['url_attr']['imagetargblank'].$anchor_class.">";
						
					$image['capurl'] = "<a href='".$theurl."'"
						.$g['url_attr']['caprel']." title='".$image['imgtitle']."' "
						.$g['url_attr']['imagetargblank'].">";
																				
				} else {
			
					$image['imgurl'] = $image['the_image_link'];
					$image['capurl'] = $image['cap_image_link'];
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
	function getStandardLayout(&$g, &$image){
		
		if( $image['pext_insert'] ){
		
			$insertclass = ' pext_insert';
		
		}
		
		
		$ret = "<li class='psgal_".$g['gallery_id']." "
					.$g['modClass']. $insertclass 
					. "' id='psimg_".$image['psimageID']."'"
					.$g['imgsPerRowHTML'].">
					<div id='psimage_".$image['psimageID']."' "
					.$g['captionwidth']." class='bwbps_image_div'>";
					
		//Handle PSmashExtend Insert
		if( $image['pext_insert'] ){
			
			$ret .= $image['pext_insert'];
			$ret .= "</div>".$g['modMenu']."</li>";					
				
			return $ret;
		
		}
		
		$scaption =  $this->getCaption($g, $image);
		// Get File Field
		$fileField = $this->getFileField($g, $image);	
		if($fileField)
		{
			//Add Rating as an Overlay of Image if $g['rating_position'] == FALSE
			if(!$g['rating_position'] ){
				$ret .= $image['ps_fav_html'] . $image['ps_rating'].$image['imgurl'] . $fileField . $image['imgurl_close'];
			} else {
				$ret .= $image['ps_fav_html'] . $image['imgurl'] . $fileField . $image['imgurl_close'];
			}
	
		}
		
		// Get Caption
		//$scaption =  $this->getCaption($g, $image);
		if($scaption) 
		{
			if( $fileField ) { $ret .= "<br/>"; }
			$ret .= $image['capurl'] . $scaption . $image['capurl_close'];
			
		}
		
		//Add Rating After Caption if $g['rating_position'] == TRUE
		if($image['ps_rating'] && $g['rating_position']){
			$ret .= $image['ps_rating'];
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
	function getCustomLayout($g, &$image, $layout, $alt){
	
		
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
		
		// Optimizing 	- First go-around, we save matched fields in an array
		//				- Next go-around, we only search fields that were found
		if(!$layout->layout_name || empty($this->layoutFields[$layout->layout_name . $alt])){ 
			$bfirstpass = true; 
			$usedFields = $this->stdFields;	//On first pass, use full list of standard fields
		} else {
			$bfirstpass = false;
			$usedFields = $this->layoutFields[$layout->layout_name . $alt]; //future passes, only use matched fields
		}
				
		if(is_array($usedFields)){
			foreach($usedFields as $fld){
				if( !$bfirstpass || ($bfirstpass && !strpos($ret, $fld) === false)){
					if($bfirstpass){
						$this->layoutFields[$layout->layout_name . $alt][] = $fld;
					}
					unset($atts);
					unset($replace);
					unset($matches);
					unset($m);
					$m_num = 0;
					
					$matches = $this->getFieldAttsMulti($ret, $fld);
					
					if( is_array($matches) ){
						foreach ($matches as $atts){
							
							$atts['match_num'] = $m_num;												
							$replace = $this->getCFFieldHTML("[".$fld."]", $image, $g, $atts);
									
							$m = $atts['bwbps_match'];
							
							$ret = str_replace($m, $replace, $ret);	
							
							$m_num++;
						}
						
					} else {
					
						$replace = $this->getCFFieldHTML("[".$fld."]", $image, $g, $atts);
								
						$fld = $atts['bwbps_match'];
									
						$ret = str_replace($fld, $replace, $ret);
					}
	
				}
			}
		}
		
		//Replace Custom Fields with values
		
		//if($this->psOptions['use_customfields']){
		
			// Optimizing 	- First go-around, we save matched fields in an array
			//				- Next go-around, we only search fields that were found
			if(!$layout->layout_name || empty($this->layoutFields['c'.$layout->layout_name . $alt])){ 
				$bfirstpass = true; 
				$usedcustFields = $this->custFields;	//On first pass, use full list of standard fields
			} else {
				$bfirstpass = false;
				$usedcustFields = $this->layoutFields['c'.$layout->layout_name . $alt]; //future passes, only use matched fields
			}
			if(is_array($usedcustFields)){	
			  foreach($usedcustFields as $fld){
			  
			  	if( !$bfirstpass || ($bfirstpass && !strpos($ret, '['.$fld->field_name) 
			  		=== false))
			  	{
					if($bfirstpass){
						$this->layoutFields['c'.$layout->layout_name . $alt][] = $fld;
					}
						
					//Format Date if it's a date
					if( $image[$fld->field_name] && $fld->type == 5){
					
						if($image[$fld->field_name] <> "0000-00-00 00:00:00"){
						
							$val = date($this->getDateFormat()
								,strtotime ($image[$fld->field_name]));
						
						}
					} else {
						$val = $image[$fld->field_name];
					}
					
					$fld = $fld->field_name;					
					
					unset($atts);
					unset($replace);
					unset($matches);
					unset($m);
					
					$matches = $this->getFieldAttsMulti($ret, $fld);
					
					if( is_array($matches) ){
						foreach ($matches as $atts){
							$cleanVal = $val;
							$len = (int)$atts['length'];
							if( $len && strlen($cleanVal) > $len) {
								$cleanVal = substr($cleanVal, 0, $len) . "&#8230;";
							}
						
							if( ( $atts['if_before'] || $atts['if_after'] ) && !$cleanVal ) 
							{
								$tempval = "";
							} else {
								$tempval = $atts['if_before'] . $cleanVal . $atts['if_after'];
							}
					
							// Allows you to specify a field to test if it has a value...if not, then it returns ""
							if( $atts['if_field'] ){
								if(!$image[$atts['if_field']]){
									$tempval = "";
								}
							}
							
							if( $atts['if_not_field'] ){
								if($image[$atts['if_not_field']]){
									$tempval = "";
								}
							}
							
							if( !$tempval ) { $tempval = $atts['if_blank']; }
								
							$m = $atts['bwbps_match'];
							
							
							
							$ret = str_replace($m, $tempval, $ret);	
						}
						
					} else {
								
						$m = $atts['bwbps_match'];
									
						$ret = str_replace($fld, $val, $ret);
					}				
					
				}
			  }
		  }
		//}
		
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
	function getPartialLayout($g, &$image, $layoutName, $alt=false){
		$g['suppress_no_image'] = false;
		if((int)$image['image_id']) { $image['psimageID'] = (int)$image['image_id']; }
		
		$ret = $this->getGallery($g, $layoutName, $image, $alt);
		
		return $ret;
	}
	
	function getFileField($g, &$image, $is_thumb=true){
		$ftype = (int)$image['file_type'];
		
		if($is_thumb){
			$psg_imagesurl = $image['thumb_url'];
			
			//Set up thumb size
			if(!$g['thumb_aspect'] ){
				if((int)$g['thumb_height'] ){
					$imagesize = " height='" . (int)$g['thumb_height'] . "'";
				}
				if( (int)$g['thumb_width'] ){
					$imagesize .= " width='" . (int)$g['thumb_width'] . "'";
				}		
			}
		} else {
			$psg_imagesurl = $image['image_url'];
		}
			
		
		switch ( true ) {
		 
			case ( $ftype == 0 || $ftype == 1 ) :	//image
			
				if($image['image_url']){
				$ret = "<img src='".$psg_imagesurl."'".$g['imgclass']
					." alt='".$image['img_alt']."' $imagesize />";
				} else { $ret = ""; }
			
				break;
			
			case ( $ftype == 2 ) :	//direct link
							
				if( $this->psValidateURL($image['file_url']) )
				{
					$ret = "<img src='".$image['file_url']."'".$g['imgclass']
					." alt='".$image['img_alt']."' " . $imagesize . " />";
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
				
				/*  Doesn't Work
				
				$thumbheight = "";
				$thumbwidth = "";
				if($g['thumb_width']){ $width = (int)$g['thumb_width'];}
				if($g['thumb_height']){ $height = (int)$g['thumb_height'];}
				
				$width = $width ? $width : 320;
				$height = $height ? $height : 265;
				
				if( $image['file_url'] ){
					if($image['file_name']){
											
						$ret = "<img src='".$psg_imagesurl.$image['file_name']."'".$g['imgclass']
							." alt='".$image['img_alt']."' />";
						
					} else {
			
						$ret = "<img src='".BWBPSPLUGINURL."/images/no_image.gif'".$g['imgclass']
							." alt='".$image['img_alt']."' />";
					
					}
					
				} 
				
				*/
				
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
	 
	function getCFFieldHTML($fld, &$image, $g, $atts){
				
		//Set up thumb size
		if($atts['h'] || $atts['w']){
						
						if($atts['h']){
							$thumbsize = " height='" . $atts['h'] . "'";
						} else {
							$thumbsize = "";
						}
						
						if($atts['w']){
							$thumbsize .= " width='" . $atts['w'] . "'";
						}
						
						$minisize = $thumbsize;
						
		} else {
			if((int)$g['thumb_height'] ){
				$thumbsize = " height='" . (int)$g['thumb_height'] . "'";
			}
			if( (int)$g['thumb_width'] ){
				$thumbsize .= " width='" . (int)$g['thumb_width'] . "'";
			}
			
			if((int)$g['mini_height'] ){
				$minisize = " height='" . (int)$g['mini_height'] . "'";
			}
			if( (int)$g['mini_width'] ){
				$minisize .= " width='" . (int)$g['mini_width'] . "'";
			}
			
		}
		
		/* Set up image size */
		if($g['enforce_sizes']){
			if((int)$g['image_height'] ){
				$imagesize = " height=" . (int)$g['image_height'];
			}
			if( (int)$g['image_width'] ){
				$imagesize .= " width=" . (int)$g['image_width'];
			}
		}
		
		//Clean up URLs
		$image['user_url'] = esc_url($image['user_url']);
		$image['url'] = esc_url($image['url']);
			
		$ftype = (int)$image['file_type'];
		
		if($ftype == 3 && ($fld == '[thumb]' || $fld == '[thumbnail]' )) {
			$fld = '[youtube]';
		}
		if($ftype == 4 && ($fld == '[thumb]' || $fld == '[thumbnail]' )) {
			$fld = '[video]';
		}
	
		//Fix up the image Alt for images
		if( is_array($atts)){
		
			//Work with the ALT attribute in Images
			if(array_key_exists( 'alt_field', $atts ) ){
				if ( $atts['alt_field'] && $image[$atts['alt_field']] ){
					$image['img_alt'] = $image[$atts['alt_field']];
					if( $atts['before_alt'] ){
						$image['img_alt'] = $atts['before_alt'] . $image['img_alt'];
					}
					if( $atts['after_alt'] ){
						$image['img_alt'] = $atts['after_alt'] . $image['img_alt'];
					}
					$image['img_alt'] = str_replace("'","",$image['img_alt']);
				}
			} else {
				$image['img_alt'] = $image['imgtitle'];
			}
			
			//Work with the Link in Image, Thumbs, etc
			if(array_key_exists('link_to', $atts)){
				switch ($atts['link_to'] ){
				
					case "none" :
						$image['imgurl'] = "";
						$image['imgurl_close'] = "";
						break;
						
					case "post_url" :
						$image['imgurl'] = $this->getCustomFormURL($g, $image);
						break;
				
					default :
						break;
				}
				
			}
			
		} else {
			$image['img_alt'] = $image['imgtitle'];
			$atts = array();
		}
		
		switch ($fld){
			case '[image]' :
				$ret = $this->getFileField($g, $image, false);
				break;
			
			case '[image_url]' :
				
				if($image['thumb_url']){
				  
					$ret = $image['image_url'];
						
				} else { $ret = ""; }
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
				if($image['thumb_url']){
					
					$ret = $image['imgurl']."<img src='".$image['image_url']."'".$g['imgclass']
						. " alt='".$image['img_alt']."' $imagesize />"
						. $image['imgurl_close'];
						
				} else { $ret = ""; }
				break;
				
			case '[thumbnail]' :
								
				if($image['thumb_url']){
				
					$ret = $image['imgurl']."<img src='".$image['thumb_url']."'".$g['imgclass']
						." alt='".$image['img_alt']."' $thumbsize />"
						.$image['imgurl_close'];
					
				} else { $ret = ""; }
				
				break;
			
			case '[thumb]' :
				if($image['thumb_url']){
				
					$ret = $image['imgurl']."<img src='".$image['thumb_url']."'".$g['imgclass']
						." alt='".$image['img_alt']."' $thumbsize />"
						.$image['imgurl_close'];
					
				} else { $ret = ""; }
				
				break;
				
			case '[thumb_linktoimage]' :
				if($image['thumb_url']){
				
					$ret = $image['the_image_link']."<img src='".$image['thumb_url']."'".$g['imgclass']
						." alt='".$image['img_alt']."' $thumbsize />"
						.$image['imgurl_close'];
					
				} else { $ret = ""; }
				
				break;
				
				
			case '[thumb_image]' :
				if($image['thumb_url']){
								
					$ret = "<img src='".$image['thumb_url']."'".$g['imgclass']
						." alt='".$image['img_alt']."' $thumbsize />";
					
				} else { $ret = ""; }
				
				break;
			
			case '[thumb_url]' :
				if($image['thumb_url']){
				
					$ret = $image['thumb_url'];
					
				} else { $ret = ""; }
				
				break;
				
			case '[medium_url]' :
				if($image['medium_url']){
				
					$ret = $image['medium_url'];
					
				} else { $ret = ""; }
				
				break;
			
			case '[mini_url]' :
				if($image['mini_url']){
				
					$ret = $image['mini_url'];
					
				} else { $ret = ""; }
				
				break;
				
			case '[infowindow_link]' :
				if( isset($image['gmap_marker']) ){
					$name = $atts['name'] ? $atts['name'] : 'map';
					
					$class = $atts['class'] ? ' class="' .$atts['class'] . '" ' : '';
					
					$ret = "<a href='javascript: void(0);' onclick='bwb_gmap.showInfoWindow(" 
						. $image['gmap_num'] . ", " 
						. $image['gmap_marker'] . "); return false;' $class >$name</a>";
				}
				break;
				
			case '[gdsr]' :	//Yep, that's right GD Star Rating integration right here!
			
				if(is_array($atts)){
					
					//See if they're specifying something other than standard Shortcode
					if($atts['shortcode']){ $shortcode = $atts['shortcode']; }
					//See if they're using something other than double quotes for the separators in the shortcode
					if($atts['quote_char']){$quote_char = $atts['quote_char']; }else{ $quote_char='"';}
					
					foreach($atts as $key => $value){
						if( $key != 'shortcode' && $key != 'quote_char' && $key != 'bwbps_match' && $key != 'match_num'){
							$sc_atts[] = $key . "=" . $quote_char . $value . $quote_char;
						}
					}
					
					if( is_array($sc_atts)){
						$sc_att_str = implode(" ", $sc_atts);
					}
				}
				
				if( !$shortcode ){ $shortcode = 'starratingblock'; } else {
				
					$valid_scs = 'starrater,starthumbsblock,starratingblock,starrating,starcomments,starreview,starreviewmulti,starratingmulti';
					
					if( strpos($valid_scs, $shortcode) === false ){ $shortcode = 'starratingblock';}
				
				}
				
				$sc = '['. trim($shortcode) . ' post=' . $image['wp_attach_id'] . ' ' . $sc_att_str . ']';
				
				$ret = do_shortcode($sc);
				
				break;			
			
			case '[blog_name]' :
				$ret = get_bloginfo('name');
				break;
			
			case '[medium]' :
				if($image['thumb_url']){
				
					//Set up medium size
					if($g['enforce_sizes']){
						if((int)$g['medium_height'] ){
							$mediumsize = " height='" . (int)$g['medium_height'] . "'";
						}
						if( (int)$g['medium_width'] ){
							$mediumsize .= " width='" . (int)$g['medium_width'] . "'";
						}
					}
				
					$ret = $image['imgurl']."<img src='".$image['medium_url']."'".$g['imgclass']
						." alt='".$image['img_alt']."' $mediumsize />"
						.$image['imgurl_close'];
					
				} else { $ret = ""; }
				
				break;
			
			case '[mini]' :
				if($image['thumb_url']){
				 
					//Set up mini size
					if(($g['enforce_sizes'] && !$minisize ) || !$g['mini_aspect']){
						if((int)$g['mini_height'] ){
							$minisize = " height='" . (int)$g['mini_height'] . "'";
						}
						if( (int)$g['mini_width'] ){
							$minisize .= " width='" . (int)$g['mini_width'] . "'";
						}
					}
				
					$ret = $image['imgurl']."<img src='".$image['mini_url']."'".$g['imgclass']
						." alt='".$image['img_alt']."' $minisize />"
						.$image['imgurl_close'];
					
				} else { $ret = ""; }
				
				break;
				
			case '[image_id]' :
				$ret = (int)$image['psimageID'];
				break;
				
			case '[wp_attachment_link]' :
				if((int)$image['wp_attach_id']){
					$ret = $this->get_attachment_link( (int)$image['wp_attach_id'] );
				}
				
				break;
				
			case '[wp_permalink]' :
				if((int)$image['wp_attach_id']){
					$ret = get_permalink( (int)$image['wp_attach_id'] );
				}
				
				break;
				
			case '[wp_permalink_byforce]' : //usually cause somebody else's plugin is putting the whammy on the attachment_link and permalink filters
				// Really...don't use this unless you absolutely cannot get around it
				if((int)$image['wp_attach_id']){
					$i = (int)$image['wp_attach_id'];
					$att_post = get_post($i);
					$ret = $att_post->post_name;
				}
				
				break;
			
			case '[wp_attach_id]' :
				if((int)$image['wp_attach_id']){
					$ret = (int)$image['wp_attach_id'];
				}
				
				break;
			
			case '[gallery_id]' :
				$ret = $g['gallery_id'];
				break;
				
			case '[gallery_name]' :
				$ret = $g['gallery_name'];
				break;
				
			case '[gallery_description]' :
				$ret = $g['gallery_description'];
				break;
				
			case '[image_gallery_name]' :
				$ret = esc_attr($image['image_gallery_name']);
				
				$len = (int)$atts['length'];
				if( $len && strlen($ret) > $len) {
					$ret = substr($ret, 0, $len) . "&#8230;";
				}
				
				break;
				
			case '[gallery_caption]' :
				$ret = $image['gallery_caption'];
				
				$len = (int)$atts['length'];
				if( $len && strlen($ret) > $len) {
					$ret = substr($ret, 0, $len) . "&#8230;";
				}
				
				break;
			
			case '[gallery_caption_escaped]' :
				$ret = esc_attr($image['gallery_caption']);
				break;
				
			case '[gallery_url]' :

				$main_viewer = isset($atts['main']) ? (int)$this->psOptions['gallery_viewer'] : false;
				$ret = $this->getGalleryURL( (int)$image['gallery_id'], $main_viewer );
				break;
			
			case '[gallery_post_url]' :
				
				if((int)$image['gallery_post_id']){
					$ret = get_permalink( (int)$image['gallery_post_id'] );
				} else {
					$ret = $this->getGalleryURL( (int)$image['gallery_id'] );
				}
				break;
			
			case '[gallery_image_count]' :
				$ret = (int)$image['gallery_image_count'] ;
				break;	
				
			case '[piclens]' :
				$ret = $this->getPicLensLink($g, $atts);
				break;
			
			case '[caption]' :
				
				
				if( ( $atts['if_before'] || $atts['if_after'] ) && !$image['image_caption'] ) 
				{  
					break;
				}
				
				$ret = $image['image_caption'];
				
				//Adjust length if Length is given
				$len = (int)$atts['length'];
				if( $len && strlen($ret) > $len) {
					$ret = substr($ret, 0, $len) . "&#8230;";
					$blengthadjusted = true;
				} else {
					//Adjust length if Non-Post Length is given and this isn't a Post
					$len = (int)$atts['nonpost_length'];
					if( $len && strlen($ret) > $len && !is_single() && !is_page() ) {
						$ret = substr($ret, 0, $len) . "&#8230;";
						$blengthadjusted = true;
					}
				}
				
				//Add More Text
				if($blengthadjusted){
					if($atts['more_link']){
						$more = $atts['more_link'];
						if((int)$image['post_id']){
							$post_perma = get_permalink((int)$image['post_id']);
						} else {
							$post_perma = get_permalink((int)$g['post_id']);
						}
						if($post_perma){
							$more = "<a href='".$post_perma."' title='View post'>"
								.$more."</a>";
						}
						$ret .= $more;
					} else {
						if($atts['more_text'] || $atts['more']){
							$ret .= $atts['more_text'] ? $atts['more_text'] : $atts['more'];
						}
					}
				}
				
				break;
			
			case '[caption_escaped]' :
				
				
				if( ( $atts['if_before'] || $atts['if_after'] ) && !$image['image_caption'] ) 
				{  
					break;
				}
				
				$ret = $image['image_caption'];
								
				if( is_array($atts) && ((int)$atts['length'] || ((int)$atts['nonpost_length'] 
					&& !is_single()) ) )
				{
					$len = (int)$atts['nonpost_length'] ? (int)$atts['nonpost_length'] :
						(int)$atts['length'];
					
					if( strlen($ret) > $len) {
					
						$ret = substr($ret, 0, $len);
					}
				}
				$ret = esc_attr__($ret);
				break;
			
			case '[post_url]' :
				$ret = '';

				if((int)trim($image['post_id'])){
					$ret = get_permalink((int)$image['post_id']);
				} else {
					if((int)$g['post_id']){
						$ret = get_permalink((int)$g['post_id']);
					} else {
					
						//Prevents defaulting the Image URL when no post URL is available.
						if( !$atts['no_default'] ){
							$ret = $image['image_url'];
						}
					}
				}
				
				break;
				
			case '[post_id]' :
				if((int)trim($image['post_id'])){
					$ret = (int)$image['post_id'];
				} else {
					$ret = (int)$g['post_id'];
				}
				break;
			
			case '[post_name]' :
				if((int)trim($image['post_id'])){
					$ret = (int)$image['post_id'];
				} else {
					$ret = (int)$g['post_id'];
				}
				
				$ret = get_post($ret);
				$ret = $ret->post_title;
				break;
				
			case '[comments_count]' :
				if((int)trim($image['post_id'])){
					$ret = get_comments_number((int)$image['post_id']);
				}
				break;	
			
			case '[file_name]' :
				$ret = $image['image_name'];
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
				
				if( is_array($atts) && ((int)$atts['length'] 
					|| ((int)$atts['nonpost_length'] && !is_single()) ) )
				{
				
					if(((int)$atts['nonpost_length'] && !is_single())){
						$len = (int)$atts['nonpost_length'] ? (int)$atts['nonpost_length'] :
							(int)$atts['length'];
					} else {
						$len = (int)$atts['length'];
					}
					
					if( strlen($ret) > $len) {
					
						$ret = substr($ret, 0, $len);
					}
				}
				
				break;
				
			case '[contributor]' :
				
				$ret = $this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name']);
				
				if( is_array($atts) && ((int)$atts['length'] 
					|| ((int)$atts['nonpost_length'] && !is_single()) ) )
				{
				
					if(((int)$atts['nonpost_length'] && !is_single())){
						$len = (int)$atts['nonpost_length'] ? (int)$atts['nonpost_length'] :
							(int)$atts['length'];
					} else {
						$len = (int)$atts['length'];
					}
					
					if( strlen($ret) > $len) {
					
						$ret = substr($ret, 0, $len);
					}
				}
				
				break;
			
			case '[contributor_link]' :
			
				break;
				
			case '[user_login]' :
				$ret = $image['user_login'];
				break;
						
			case '[user_link]' :
				$ret = $this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name']);
				
				if( is_array($atts) && ((int)$atts['length'] 
					|| ((int)$atts['nonpost_length'] && !is_single()) ) )
				{
				
					if(((int)$atts['nonpost_length'] && !is_single())){
						$len = (int)$atts['nonpost_length'] ? (int)$atts['nonpost_length'] :
							(int)$atts['length'];
					} else {
						$len = (int)$atts['length'];
					}
					
					if( strlen($ret) > $len) {
					
						$ret = substr($ret, 0, $len);
					}
				}
				
				
				if($ret){
					if($image['user_url'] && $this->validURL($image['user_url'])){
						$ret = "<a href='".$image['user_url']."' title=''>"
							. $ret ."</a>";
					} 
				} else {
					$ret = "anonymous";
				}
				break;
				
			case '[user_url]' :
				$ret = "";
				if($image['user_url'] && $this->validURL($image['user_url'])){
					$ret =  esc_url($image['user_url']);
				}
				
				break;
				
			case '[meta_data]' :
				$ret = "";
				
				if( $image['meta_data'] && $atts['field']){
					$meta = unserialize($image['meta_data']);
				
					$ret =  stripslashes($meta[$atts['field']]);
					
					if($atts['field'] == 'created_timestamp'){
					
						$ret = date($this->getDateFormat(4)
						,$ret);
					
					}
				}
				
				break;
				
			case '[exif_table]' :	// returns a table of the Exif data...include att show_blank=true if you want to show empty fields
				$ret = "";
				
				if($atts['show_blank']) {
					if(strtolower($atts['show_blank']) == 'false' || strtolower($atts['show_blank']) == 'no'){
						$blank = false;
					} else { 
						$blank = true;
					} 
				}				
				
				if( $image['meta_data']){
					$meta = unserialize($image['meta_data']);
					
					if(is_array($meta)){
					
						foreach($meta as $key => $val){
						
							if($val || $blank){
							
								switch($key) {
									case 'focal_length' :
										$val .= " mm";
										break;
									case 'shutter_speed' :
										if(floatval($val) >= 1){
											if(floatval($val) == 1){ $s = ' second'; } else {
												$s = " seconds"; 
											}
											$val .= $s;
										} else {
										
											$v = 1 / (floatval($val));
											$val = "1/" . $v . " second";
										
										}
										
										break;
										
									case 'created_timestamp' :
										$val = date('r' ,$val);
										break;
									
									case 'aperture' :
										$val = "f/" . $val;
										break;
									
									default :
										break;
								}
								
								$key = str_replace("_", " ", $key);
							
								$ret .= "<tr><th>" . ucwords($key) . ": </th><td>" . $val . "</td></tr>";
							
							}
						
						}
					
					
					}
				}
					
					if($ret){
						$ret = "<table class='bwbps-meta-table' style='margin: 10px auto !important; text-align: left;'>" . $ret . "</table>";
					} else {
					
						if($atts['no_exif_msg']){
							$ret = $atts['no_exif_msg'];
						}
					
					}
				
				
				break;
				
			case '[img_attribution]' :
				$ret = "";
				
				if( $image['img_attribution'] ){
					$ret =  stripslashes($image['img_attribution']);
				}
				
				break;
			
			case '[img_license]' :
				$ret = "";
				
				
				switch ((int)$image['img_license']) {
					case 0 :
						$ret = 'license unknown';
						break;
					
					case 1 :
						$ret = 'All rights reserved.';
						break;
					case 2 :
						$ret = '<a href="http://creativecommons.org/about/licenses/" title="Creative Commons: Attribution">BY</a>';
						break;
					
					case 3 :
						$ret = '<a href="http://creativecommons.org/about/licenses/" title="Creative Commons: Attribution Share Alike">BY-SA</a>';
						break;
					
					case 4 :
						$ret = '<a href="http://creativecommons.org/about/licenses/" title="Creative Commons: Attribution No Derivatives">BY-ND</a>';
						break;
					
					case 5 :
						$ret = '<a href="http://creativecommons.org/about/licenses/" title="Creative Commons: Attribution Non-Commercial">BY-NC</a>';
						break;
					
					case 6 :
						$ret = '<a href="http://creativecommons.org/about/licenses/" title="Creative Commons: Attribution Non-Commercial Share Alike">BY-NC-SA</a>';
						break;
					
					case 7 :
						$ret = '<a href="http://creativecommons.org/about/licenses/" title="Creative Commons: Attribution Non-Commercial No Derivatives">BY-NC-ND</a>';
						break;
					
					case 8 :
						$ret = '<a href="http://creativecommons.org/about/licenses/" title="Creative Commons: Attribution">BY</a>';
						break;
					
					case 9 :
						$ret = '<a href="http://creativecommons.org/choose/cc-gpl" title="Creative Commons: GNU-GPL">CC-GNU-GPL</a>';
						break;
					
					case 10 :
						$ret = '<a href="http://creativecommons.org/choose/cc-lgpl" title="Creative Commons: GNU - Lesser GPL">CC-GNU-LGPL</a>';
						break;
					
					case 11 :
						$ret = '<a href="http://creativecommons.org/licenses/BSD/" title="Creative Commons: BSD">BSD</a>';
						break;
						
					default :
						$ret = "";
						break;				
				}
									
				break;
				
			
			case '[author_link]' :

				if( (int)$image['user_id'] ){
									
					$ret = get_author_posts_url($image['user_id']);
					
					if($ret){
						$name = $this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name']);
						$ret = "<a href='".$ret."' title='View all images by contributor'>".$name."</a>";
					}
				
				} else {
				
					$ret = "";
				
				}
								
				break;
				
			case '[author]' :

				if( (int)$image['user_id'] ){
									
						$ret = $this->calcUserName($image['user_login'], $image['user_nicename'], $image['display_name']);
				
				} else {
				
					$ret = "";
				
				}
								
				break;
				
			case '[bloginfo]' :

				$ret = get_bloginfo($atts['field']);

				break;
				
			case '[plugin_url]' :
				$ret = WP_PLUGIN_URL;
				break;
							
			case '[url]' :
				$ret = $image['url'];
				break;

			case '[ps_rating]' :
				
				$ret = $image['ps_rating'];
				break;
				
			case '[favorite]' :
				
				$ret = $image['ps_fav_html'];
				break;
				
			case '[favorite_cnt]' :
				
				$ret = (int)$image['favorites_cnt'];
				break;
				
			case '[delete_button]' :	// will place a Delete button if the current user is the owner of the image
				
				if( !is_user_logged_in() ){ return; }
				
				global $current_user;
				global $bwbPS;
				
				if ((int)$image['user_id'] !== (int)$current_user->ID){ return; }
				
				if(!isset($bwbPS->footerJSArray['upload_nonce'])){
					$nonce = wp_create_nonce('bwb_upload_photos');
					
					$bwbPS->addFooterJSArray("var bwbps_upload_nonce = '" . $nonce . "';", 'upload_nonce');
					
				}
				
				if($atts['button_name']){
					$btn_name = esc_attr($atts['button_name']);
				} else {
					$btn_name = 'delete';
				}
				
			    $ret = "<input class='bwbps_user_delete_button bwbps_delbtn_" 
			       	. $image['psimageID'] 
			       	."' type='button' value='$btn_name' onclick='bwbpsUserDeleteImage("
			       	.$image['psimageID'].")' />";
			    
			    break;
							
			case '[tag_links]' :
								
				if($atts['sep'] || $atts['before'] || $atts['after']){
					
					$ret = get_the_term_list($image['psimageID'], 'photosmash', $atts['before'], $atts['sep'], $atts['after']);
				} else {
					$ret = get_the_term_list($image['psimageID'], 'photosmash', '', ' ');
				}
				
				break;
				
			// PhotoSmash Extend - Extended Nav tags
			case '[tag_dropdown]' :
				
				//Get the form element's name
				if(!$atts['name']){
					$n = 'bwbps_photo_tag[]';
				} else {
					$n = esc_attr($atts['name']);
				}
				
				//Get the tag values
				if($atts['tags']){
				
					//Get array of selected values, to mark as selected in dropdown
					unset($selected);
					$fldname = str_replace("[]", "", $n);
					if(isset($_POST[$fldname])){
						if(!is_array($_POST[$fldname])){
							$selected = $_POST[$fldname];
						} else {
							$selected = $_POST[$fldname][$atts['match_num']];
						}
					} else {
						$selected = array();
					}
					
					$tags = $this->getTermObjects($atts['tags'], $atts['select_msg']);
									
					unset($selmarked);
					if(is_array($tags)){
						foreach($tags as $t){
							if($t->slug == $selected){ $selattr = "selected=selected"; $selmarked=true;}
							
							$r .= "<option value='" . esc_attr($t->slug) . "' $selattr>"
								. $t->name . "</option>
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
				
			case '[nav_search]' :
				
				$ret = '<input type="text" name="bwbps_q" size="30" maxlength="60" class="ps-ext-nav-search" value="' 
					. esc_attr( stripslashes($_POST['bwbps_q'])) . '" />';
				
				break;
			
			case '[nav_search_term]' :
				
				$ret = stripslashes($_POST['bwbps_q']);
				
				if($atts['escape']){
					$ret = esc_attr( $ret );
				}
				
				break;
			
			case '[nav_gallery]' :
				$ret = "<input type='hidden' name='bwbps_extnav_gal' value='" . (int)$atts['id'] ."' />";
				break;
				
			case '[tags_has_all]' :
				
				$ret = "<input type='hidden' name='bwbps_tags_has_all' value='true' />";
				break;
				
			case '[submit]' :
				
				if($atts['name']){
					$submitname = $atts['name'];
				} else {
					$submitname = 'Submit';
				}
				$ret = '<input type="submit" class="ps-submit ext-nav-submit" value="'.$submitname.'" name="bwbps_submit" />';
				break;
				
			case '[tag_search]' :
				$ret = '<input type="text" name="bwbps_tagsearch" class="ps-ext-nav-search" ' . $value . ' />';
				break;
							
			default :
				break;
		}
		
		if( ( $atts['if_before'] || $atts['if_after'] ) && !$ret ) 
		{
			$ret = "";
		} else {
			$ret = $atts['if_before'] . $ret . $atts['if_after'];
		}
		
		// Allows you to specify a field to test if it has a value...if not, then it returns ""
		if( $atts['if_field'] ){
			if(!$image[$atts['if_field']]){
				$ret = "";
			}
		}
		
		if( $atts['if_not_field'] ){
			if($image[$atts['if_not_field']]){
				$ret = "";
			}
		}
		
		if( !$ret ) { $ret = $atts['if_blank']; }
		
		return $ret;
	}
	
	function get_attachment_link( $attachment_id ){
		
		do_action('bwbps_get_attachment_link');  // Allows it to do a get_children on the attachment IDs to prevent query firehose
		
		return get_attachment_link( $attachment_id );
		
	}
	
	function getAttachmentPosts(){
		
		if($this->attachments_gotten){ return; }	//We've already done this so exit
		
		$this->attachments_gotten = true;
		
		if( is_array($this->attachment_ids) ){
			/* 
				If you don't do a get_children on the Attachments 
				(really, their post_parent, but we've hacked it here to make it work)
				then the Attachment posts are not in the Posts cache.
				This causes 2 queries for each Attachment link, one to retrieve the attachment, the other to
				get the post_parent.  
				This function gets all the attachments (we built an array of their IDs back in getGallery)
				and puts them into the cache via the miracle of get_children()
			*/
			$atts = get_children(array('post_parent' => null, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post__in' => $this->attachment_ids));
		}
		
	}
	
	function getGalleryURL( $gallery_id, $main_viewer = false ){
		
		if( $main_viewer ){
			
			$args = array($this->psOptions['gallery_viewer_slug'] => (int)$gallery_id );
			return add_query_arg($args, get_permalink($main_viewer));
		} else {
			$args = array($this->psOptions['gallery_viewer_slug'] => (int)$gallery_id );
			return add_query_arg($args);
		}

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
	
	function getPicLensLink($g, $atts){
	
		global $bwbPS;
		
		return $bwbPS->getPicLensLink($g, $atts);	
	}
	
	function getCustomFormURL($g, $image){
			
			
			if((int)trim($image['post_id'])){

				$imglink = get_permalink((int)$image['post_id']);
			} else {
				if((int)$g['post_id']){
					$imglink = get_permalink((int)$g['post_id']);
				} 
			}
			
			if($imglink){
				$url = "<a href='".$imglink."'"
					.$g['url_attr']['imgrel']." title='".$image['imgtitle']."' "
					.$g['url_attr']['imagetargblank'].">";	
			} else {
				$url = $image['imgurl'];
			}
			
		return $url;
	}
	
		
	/**
	 * Get Caption
	 * @return (str) containing html for an image's caption based on settings
	 *
	 * @param (object) $g - gallery definition array; (object) $image - an image object
	 */
	function getCaption($g, &$image){
		// Clean up URLs
		$image['user_url'] = esc_url($image['user_url']);
		$image['url'] = esc_url($image['url']);
		
				
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
					$image['capurl_close'] = "";
					
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
						$captionurl = "<a href='".$theurl."'"
							." title='". esc_attr(str_replace("'","",$image['image_caption']))
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
						$captionurl = "<a href='".$theurl."'"
							." title='".esc_attr(str_replace("'","",$image['image_caption']))
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
						$captionurl = "<a href='".$theurl."'"
							." title='".esc_attr(str_replace("'","",$image['image_caption']))
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
							." title='".esc_attr(str_replace("'","",$image['image_caption']))
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
								." title='". esc_attr(str_replace("'","",$image['image_caption']))
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
				
				case 10: // by Contributor (link to WP author page)				
					
					if($image['user_id']){
					
						$theurl = get_author_posts_url($image['user_id']);
						$captionurl = "<a href='".$theurl."'"
							." title='View all images by contributor'"
							.$g['url_attr']['nofollow']." $captiontargblank>";
						$closeUserURL = "</a>
						";
						$image['capurl'] = "";
						$image['capurl_close'] = "";
												
					}else{
						$captionurl = "";
						$closeUserURL = "";					
					}
					
					$scaption = "<span ".$g['url_attr']['captionclass'].">by "
						. $captionurl
						. $nicename . $closeUserURL . "</span>";
												
					break;	
					
				case 11: // Caption by Contributor (link to WP author page)
					
					$goturl = false;					
					if($image['user_id']){
					
						$theurl = get_author_posts_url($image['user_id']);
						$goturl = true;
						
					} else {
					
						if($this->validURL($image['user_url'])){
						
							$theurl = $image['user_url'];
							$goturl = true;
							
						}
					}
					
					if($goturl){
					
						$captionurl = "<a href='".$theurl."'"
							." title='". esc_attr( str_replace("'","",$image['image_caption']) )
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
				
				case 12: //No caption - Thumbnail links to Post
				
					$image['capurl'] = "";
					$image['capurl_close'] = "";
					$scaption = "";	//Close out the link from above
										
					if((int)$image['post_id']){
						$post_perma = get_permalink((int)$image['post_id']);
					} else {
						$post_perma = get_permalink((int)$image['gal_post_id']);
					}
					
					if($post_perma){
						$perma = "<a href='".$post_perma."' title='View post'>";
					}
				
									
					if($perma){
						$image['imgurl'] = $perma;
					}
																			
					break;	
				
				case 13: //Caption & Thumbnail link to Post
					if((int)$image['post_id']){
						$post_perma = get_permalink((int)$image['post_id']);
					} else {
						$post_perma = get_permalink((int)$image['gal_post_id']);
					}
					
					if($post_perma){
						$perma = "<a href='".$post_perma."' title='View post'>";
					}
				
									
					if($perma){
						$image['imgurl'] = $perma;
					}
				
					if($image['image_caption']){
					
						$image['capurl'] = $perma;
						
						$scaption = "<span ".$g['url_attr']['captionclass'] .">"
							. $image['image_caption']."</span>";
					
						$image['capurl_close'] = $image['imgurl_close'];
						
					} else {
						$image['capurl'] = "";
					}																			
					break;	
				
				case 14: //No caption - Thumbnail links to Attachment Page
				
					$image['capurl'] = "";
					$image['capurl_close'] = "";
					$scaption = "";	//Close out the link from above
										
					if((int)$image['wp_attach_id']){
						$attach_perma = $this->get_attachment_link( (int)$image['wp_attach_id']);
					}
					
					if($attach_perma){
						$perma = "<a href='".$attach_perma."' title='View post'>";
					}
				
									
					if($attach_perma){
						$image['imgurl'] = $perma;
					}
																			
					break;
					
				case 15: //Caption & Thumbnail link to Attachment Page
				
					if((int)$image['wp_attach_id']){
						$attach_perma = $this->get_attachment_link( (int)$image['wp_attach_id']);
					}
					
					if($attach_perma){
						$perma = "<a href='".$attach_perma."' title='View post'>";
					}				
									
					if($perma){
						$image['imgurl'] = $perma;
					}
				
					if($image['image_caption']){
					
						$image['capurl'] = $perma;
						
						$scaption = "<span ".$g['url_attr']['captionclass'] .">"
							. $image['image_caption']."</span>";
					
						$image['capurl_close'] = $image['imgurl_close'];
						
					} else {
						$image['capurl'] = "";
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
	function getPagingNavigation($url, $pagenum, $totalRows, $g, $layout=false){
		$rowsPerPage = $g['img_perpage'];
		
		$page = (int)$pagenum[$g['gallery_id']];
		
		if((int)$rowsPerPage < 1){return false;}
				
		$total_pages = ceil($totalRows / $rowsPerPage);
		
		//use split on ? to get the url broken between ? and rest
		
		$arrURL = split("\?",$url);
		if(count($arrURL)> 1){
			$url .= "&";			
		} else {
			$url .= "?";
		}
		
		
		if(!$this->psOptions['alt_paging'] && !$this->psOptions->uni_paging){
			$othergals = $this->getPagingForOtherGalleries($pagenum, (int)$g['gallery_id']);
		}
				
		$page_numstop = $total_pages;
		$page_numstart = 1;
		
		//TODO Use add_query_arg(array()) to build the links instead!
		
		if( (int)$_REQUEST[$this->psOptions['gallery_viewer_slug']] ){
			$viewerargs = array( $this->psOptions['gallery_viewer_slug'] => (int)$_REQUEST[$this->psOptions['gallery_viewer_slug']]);
		} else {
			$viewerargs = array();
		}
		
		//Build PREVIOUS link
		if($page > 3 && $total_pages > 5){
			
			$ptemp = 1;
			$urltemp = $this->getPagingURLArgs($g['gallery_id'], $ptemp, $viewerargs, $othergals);
			
			if(!$g['page_nofirstlast']){
				$nav[] = "<a href='$urltemp'>first</a>";
			}
			
			$frontellip = $g['page_noellipses'] ? "" : "&#8230;";
			
			
			
			if($page > ($total_pages - 3)){
				$page_numstart = $total_pages - 4;
			} else {
				$page_numstart = $page - 2;
			}
					
		}
		
		if($total_pages > 5 && $page < $total_pages - 2){
		
			if( $page > 3 ){
				if( $page + 2 < $total_pages){
					$backellip = $g['page_noellipses'] ? "" : "&#8230;";
					$page_numstop = $page + 2;
				}					
					
			} else {
				$page_numstop = 5;
				$backellip = $g['page_noellipses'] ? "" : "&#8230;";

			}
		}
		
		if($page > 1){
			
			$ptemp = $page-1;
			$urltemp = $this->getPagingURLArgs($g['gallery_id'], $ptemp, $viewerargs, $othergals);	
			$prevarrow = $g['page_arrow_left'] ? $g['page_arrow_left'] : "&#9668;";
			$nav[] = "<a href='$urltemp'>$prevarrow</a>";
			
		}
		
		if($frontellip){ $nav[] = $frontellip; }
		
		if($total_pages > 1){
			
			$icnt = 0;
			for($page_num = $page_numstart; $page_num <= $page_numstop; $page_num++){
				if($page == $page_num){ 
					$nav[] = "<span>".$page."</span>";
				}else{
					$ptemp = $page_num;
					$urltemp = $this->getPagingURLArgs($g['gallery_id'], $ptemp, $viewerargs, $othergals);	
					$nav[] = "<a href='$urltemp'>".$page_num."</a>";
				}
			}
		}
		
		if($backellip){
			$nav[] = $backellip;
		}
		
		//Build NEXT LINK
		if($page < $total_pages){
			$ptemp = $page+1;
			$urltemp = $this->getPagingURLArgs($g['gallery_id'], $ptemp, $viewerargs, $othergals);	
			$nextarrow = $g['page_arrow_right'] ? $g['page_arrow_right'] : "&#9658;";
			$nav[] = "<a href='$urltemp'>$nextarrow</a>";
		}
		
		if($total_pages > 5 && $page < ($total_pages - 2)){
			$ptemp = $total_pages;
			$urltemp = $this->getPagingURLArgs($g['gallery_id'], $ptemp, $viewerargs, $othergals);	
			
			if(!$g['page_nofirstlast']){
				$nav[] = "<a href='$urltemp'>last</a>";
			}
		}
		
		$snav = "";
		if(is_array($nav)){
			$snav = implode("",$nav);
		}
		
		if($layout && $layout->pagination_class){
			$pgnclass = $layout->pagination_class;
		} else {
			$pgnclass = "bwbps_pagination";
		}
		
		$ret = "<div class='$pgnclass'>". $snav."</div>";
		
		return $ret;
		
	}
	
	function getPagingURLArgs($gallery_id, $page, $viewerargs, $othergals){
	
		if( !$this->psOptions['uni_paging'] ){ $gid = '_' . $gallery_id; }
		
		$pname = trim($this->psOptions['alt_paging']) ? 
			trim($this->psOptions['alt_paging']) . $gid : "bwbps_page".$gid;

		$urlargs[ $pname ] = (int)$page;
		
		
		if(is_array($othergals)){
			$urlargs = array_merge($viewerargs, $urlargs, $othergals );
		} else {
			$urlargs = array_merge($viewerargs, $urlargs);
		}
			
		return add_query_arg($urlargs);	//was $url
	}
	
	//Get the paging arguments for other galleries on the page
	function getPagingForOtherGalleries($pagenum, $this_gal_id){
	
		if(is_array($pagenum)){
			foreach( $pagenum as $gal_id => $page ){
				if( $gal_id <> $this_gal_id ){
					$gal_pages['bwbps_page_'.(int)$gal_id] = (int)$page;
				}
			}
		}
		if(!is_array($gal_pages)){
			$gal_pages = array();
		}
		return $gal_pages;
	}
	
	/**
	 * Get Layout
	 * returns the custom layout from the database
	 *
	 * @param (int) $layout_id
	 */
	function getLayout($layout_id, $layout_name=false){
		global $wpdb;
		
		$layoutName = $layout_name ? strtolower($layout_name) : "psid-".$layout_id;

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
		
		$this->layouts[$layoutName] = $query;	//Cache layouts to prevent future DB calls
		
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
		global $current_user;
		
		$user_id = (int)$current_user->ID;
				
		//Set up SQL for Custom Fields
		$custdata = ", ".PSCUSTOMDATATABLE.".* ";
		
		$custDataJoin = " LEFT OUTER JOIN " . PSCUSTOMDATATABLE . " ON "
			. PSIMAGESTABLE . ".image_id = " . PSCUSTOMDATATABLE . ".image_id ";		
		
		$custDataJoin = " LEFT OUTER JOIN ". PSGALLERIESTABLE . " ON "
			. PSIMAGESTABLE . ".gallery_id = " . PSGALLERIESTABLE . ".gallery_id " 
			. $custDataJoin;
			
		$gallery_selections = ", ". PSGALLERIESTABLE . ".post_id AS gal_post_id, "
			. PSGALLERIESTABLE . ".poll_id, "
			. PSGALLERIESTABLE . ".gallery_name AS image_gallery_name, "
			. PSGALLERIESTABLE . ".caption AS gallery_caption, " 
			. PSGALLERIESTABLE . ".post_id AS gallery_post_id, " 
			. PSGALLERIESTABLE . ".img_count AS gallery_image_count ";
		
		// Add User Favorites indicator to Query if User is logged in
		if(current_user_can('level_0') && $user_id){
		
			$gallery_selections .= ", " . PSFAVORITESTABLE . ".favorite_id ";
			
			$favoriteDataJoin .= " LEFT OUTER JOIN ".PSFAVORITESTABLE
				." ON ".PSIMAGESTABLE.".image_id = "
				.PSFAVORITESTABLE.".image_id "
				." AND " . PSFAVORITESTABLE . ".user_id = " . (int)$user_id . " ";
			
		}
		
		// Calculate ORDER BY
		if( strtolower($g['sort_order']) == 'desc' || (int)$g['sort_order'] ){
			$g['sort_order'] = "DESC";
		} else {
			$g['sort_order'] = "ASC";
		}
		
		$sortorder = $g['sort_order'];
		
		
		// Bayesian Sorting from:
		// http://www.thebroth.com/blog/118/bayesian-rating
		/*
		br = ( (avg_num_votes * avg_rating) + (this_num_votes * this_rating) ) 
			/ (avg_num_votes + this_num_votes)
		*/
		
		if( (int)$g['sort_field'] == 4 || (string)$g['sort_field'] == 'rank' ){
		
			if(!$g['gallery_type'] == 99){
				// This is not a highest ranked gallery, so limit on Galelry ID
				$galid_sql = ' AND gallery_id = ' . (int)$g['gallery_id'];
			}
		
			if((int)$g['poll_id'] > -2 || (int)$g['poll_id'] == NULL ){
				$br_row = $wpdb->get_row('SELECT AVG(avg_rating) as avgrating, 
					AVG(rating_cnt) as numvotes
					FROM ' . PSIMAGESTABLE . ' WHERE 
					status = 1 ' . $galid_sql);
			}
				
			if($br_row){
				$nvotes = $br_row->numvotes;
				$arate = $br_row->avgrating;
				
				$brmagic = $arate * $nvotes;
				
				$custdata  .= ", ( ( " . $brmagic . " + ( " . PSIMAGESTABLE
					. ".rating_cnt * " . PSIMAGESTABLE . ".avg_rating ) ) / "
					. " ( " . $nvotes . " + "  . PSIMAGESTABLE
					. ".rating_cnt ) ) AS bwbps_br_rating ";
				
			} else {
			
				if((int)$g['poll_id'] == -2){
					//Use the average
					$custdata .= ", if(" . PSIMAGESTABLE . ".votes_cnt = 0, 0, " . PSIMAGESTABLE . ".votes_sum / " . PSIMAGESTABLE . ".votes_cnt) AS bwbps_br_rating ";
				} else {
					$custdata .= ", " . PSIMAGESTABLE . ".avg_rating AS bwbps_br_rating ";
				}
				
			}
			
		}
		
		if(isset($_REQUEST['bwbps_q']) ){
			if(!isset($_REQUEST['bwbps_extnav_gal']) || 
				((int)$_REQUEST['bwbps_extnav_gal'] == $g['gallery_id']))
			{
				$sqlSpecialWhere .= $this->getSearchSQL();
				if($sqlSpecialWhere){
					$g['gallery_type'] = 98;
				}
			}
		}
		
		// Calculate LIMIT
		if((int)$g['limit_images']){
		
			if((int)$g['limit_page'] && (int)$g['limit_page'] > 1){
				$limitpage = ( ((int)$g['limit_page'] -1) * (int)$g['limit_images']);
				
				$limitpage = $limitpage . ", ";
			}
			
			$limitimages = " LIMIT " . $limitpage . (int)$g['limit_images'];
		
		
		}

		switch ($g['gallery_type']){
			
			case 20:	// Random
				$sortby = 'RAND() ';
				if(!(int)$g['limit_images']){
					$limitimages = " LIMIT " . $limitpage . "8";
				}
				break;
			
			case 30:	// Recent
				$sortby = PSIMAGESTABLE.'.created_date DESC ';
				if(!(int)$g['limit_images']){
					$limitimages = " LIMIT " . $limitpage . "8";
				}
				break;
				
			case 40 :	//tag gallery
				$g['smart_gallery'] = true;
				
				if($g['tags']){
				
					if($g['tags'] == 'post_tags'){
						
						if(!isset($wp_query)){
							global $wp_query;
						}
						$terms = wp_get_object_terms( $wp_query->post->ID, 'post_tag', $args ) ;
												
						if(is_array($terms)){
						
							foreach( $terms as $term ){
								
								$_terms[] = $term->name;
							
							}
						
							unset($terms);
							if( is_array($_terms)){
								$g['tags'] = implode("," , $_terms);
							} else {
								$g['tags'] = "";
							}
						}
					
						
					}
					
					do_action('bwbps_ext_search', $g['tags']);

					$tagtemp = explode(",", $g['tags']);
				
		
					if( !is_array($tagtemp) ){
						$tagtemp = array($tagtemp);
					}
					
					$tagtemp = array_map("trim", $tagtemp);	//gets esc_sql in getSmartWhereField
															
					$g['smart_where'] = array( $wpdb->terms . '.name' => $tagtemp);
					
					$custDataJoin .= " "
						. "LEFT OUTER JOIN ". $wpdb->term_relationships 
						. " ON ". $wpdb->term_relationships . ".object_id = "
						. PSIMAGESTABLE . ".image_id "
						. "LEFT OUTER JOIN " . $wpdb->term_taxonomy
						. " ON " . $wpdb->term_taxonomy . ".term_taxonomy_id = "
						. $wpdb->term_relationships . ".term_taxonomy_id AND " 
						. $wpdb->term_taxonomy . ".taxonomy = 'photosmash' "
						. "LEFT OUTER JOIN ". $wpdb->terms 
						. " ON ". $wpdb->terms . ".term_id = "
						. $wpdb->term_taxonomy . ".term_id "
						;					
						
				} else {
					
					$g['smart_where'] = array( PSIMAGESTABLE . '.gallery_id' => (int)$g['gallery_id']) ;
					
				}
								
				$sortby = $this->getSortbyField($g, $sortorder);
				
				break;
			
			case 70 : //Favorites - gets the favorited images for the logged in user
				
				$g['smart_gallery'] = true;
				
				$sortby = $this->getSortbyField($g, $sortorder);
				
				$favoriteDataJoin = " INNER JOIN ".PSFAVORITESTABLE
				." ON ".PSIMAGESTABLE.".image_id = "
				.PSFAVORITESTABLE.".image_id "
				." AND " . PSFAVORITESTABLE . ".user_id = " . (int)$user_id . " ";
				
				break;
				
			case 71 : //Most Favorited - images with the most favorites
			
				if(!$g['smart_gallery']){
					$g['smart_gallery'] = true;
				}
				
				if(!(int)$g['limit_images']){
					$limitimages = " LIMIT " . $limitpage . "8";
				}
				
				$sqlSpecialWhere .= " AND " . PSIMAGESTABLE . ".favorites_cnt > 0 ";
				
				$sortby = $this->getSortbyField($g, $sortorder);
				
				break;
			
			case 98 : //Search - Extended Navigation (PhotoSmash Extend only)
				$sortby = $this->getSortbyField($g, $sortorder);
				
				$custDataJoin .= " "
						. "LEFT OUTER JOIN ". $wpdb->term_relationships 
						. " ON ". $wpdb->term_relationships . ".object_id = "
						. PSIMAGESTABLE . ".image_id "
						. "LEFT OUTER JOIN " . $wpdb->term_taxonomy
						. " ON " . $wpdb->term_taxonomy . ".term_taxonomy_id = "
						. $wpdb->term_relationships . ".term_taxonomy_id AND " 
						. $wpdb->term_taxonomy . ".taxonomy = 'photosmash' "
						. "LEFT OUTER JOIN ". $wpdb->terms 
						. " ON ". $wpdb->terms . ".term_id = "
						. $wpdb->term_taxonomy . ".term_id "
						;
				
				$g['smart_gallery'] = true;
				break;
			case 99 : //Highest Ranked
				
				if(!$g['smart_gallery']){
					$g['smart_gallery'] = true;
				}
				
				if(!(int)$g['limit_images']){
					$limitimages = " LIMIT " . $limitpage . "8";
				}
				
				$sortby = $this->getSortbyField($g, $sortorder);
				
				break;
				
			case 100 : // Gallery Viewer
				if(!$g['smart_gallery']){
					$g['smart_gallery'] = true;
				}
				
				$imgids = $this->getGalleryViewerImageIDs($g['gallery_ids'], $g['exclude_galleries']);
				
				if(!$imgids){ return; }
				
				$sqlSpecialWhere .= " AND " . PSIMAGESTABLE . ".image_id IN (" . $imgids . ") ";
				
				$sortby = $this->getSortbyField($g, $sortorder);

				break;
			
			default :
			
				$sortby = $this->getSortbyField($g, $sortorder);
				
				break;					
			
		}
		
		
		// Add the WHERE clause for the Smart Galleries
		if ( $g['smart_gallery'] ){
			
			if( is_array($g['smart_where'] ) ){
				$swhere[] = $this->getSmartWhereField( $g['smart_where'] );
				$sqlWhere = " WHERE " . implode( " AND ", $swhere );			
			} else {
			
				$sqlWhere = " WHERE 1=1 ";
			
			}		
		
		} else {
			
			$sqlWhere = " WHERE " . PSIMAGESTABLE . ".gallery_id = " . (int)$g['gallery_id'] ;
		
		}
		
		// Calculate paging
		if( (int)$g['limit_images'] && (int)$g['img_perpage'] ){
			if( $g['limit_images'] <= $g['img_perpage'] ){
				$g['img_perpage'] = 0;
			} else {
				$hardlimit = " LIMIT " . (int)$g['limit_images'];
			} 
		}
		
		if( (int)$g['limit_images_override'] ){
				$limitimages = ' LIMIT ' . (int)$g['limit_images_override'];
		} else {
			if( $g['img_perpage'] ){
				$limitimages = ' LIMIT ' . (int)$g['starting_image'] . ", " . $g['img_perpage'];
			}		
		}
		
		$sqlWhere .= " " . $sqlSpecialWhere;
				
		//Admins can see all images
		if(current_user_can('level_10')){
			$sql = 'SELECT DISTINCT '.PSIMAGESTABLE.'.*, '
				. PSIMAGESTABLE.'.image_id as psimageID, '
				. $wpdb->users.'.user_nicename,'
				. $wpdb->users.'.display_name,'
				. $wpdb->users.'.user_login,'
				. $wpdb->users.'.user_url' 
				. $gallery_selections
				. $custdata
				. ' FROM ' . PSIMAGESTABLE
				. ' LEFT OUTER JOIN ' . $wpdb->users . ' ON '
				. $wpdb->users .'.ID = '. PSIMAGESTABLE. '.user_id'.$custDataJoin . $favoriteDataJoin
				. $sqlWhere 
				. ' ORDER BY '.$sortby . $limitimages;	
				
			$sql_count = 'SELECT DISTINCT '.PSIMAGESTABLE.'.image_id FROM '
				.PSIMAGESTABLE.' LEFT OUTER JOIN '.$wpdb->users.' ON '
				. $wpdb->users .'.ID = '. PSIMAGESTABLE. '.user_id'.$custDataJoin . $favoriteDataJoin
				. $sqlWhere . $hardlimit;	
									
			
		} else {
			//Non-Admins can see their own images and Approved images
			$uid = (int)$user_id ? (int)$user_id : -1;
				
			$sql = 'SELECT DISTINCT ' . PSIMAGESTABLE . '.*, '
				. PSIMAGESTABLE.'.image_id as psimageID, '
				. $wpdb->users.'.user_nicename,'
				. $wpdb->users.'.display_name,'
				. $wpdb->users.'.user_login,'
				. $wpdb->users.'.user_url' . $gallery_selections
				. $custdata.' FROM '
				. PSIMAGESTABLE.' LEFT OUTER JOIN '.$wpdb->users.' ON '
				. $wpdb->users .'.ID = ' . PSIMAGESTABLE. '.user_id'.$custDataJoin . $favoriteDataJoin
				. $sqlWhere . ' AND ( ' . PSIMAGESTABLE. '.status > 0 OR ' . PSIMAGESTABLE. '.user_id = '
				. $uid.')  ORDER BY ' . $sortby . $limitimages;			
				
			$sql_count = 'SELECT DISTINCT '.PSIMAGESTABLE.'.image_id FROM '
				.PSIMAGESTABLE.' LEFT OUTER JOIN '.$wpdb->users.' ON '
				. $wpdb->users .'.ID = ' . PSIMAGESTABLE. '.user_id'.$custDataJoin . $favoriteDataJoin
				. $sqlWhere . ' AND ( ' . PSIMAGESTABLE. '.status > 0 OR ' . PSIMAGESTABLE. '.user_id = '
				.$uid.')'  . $hardlimit;
				
		}
		
		// echo $sql;
		
		// Get Count for Paging
		$this->total_records = 0;
		
		if( $g['img_perpage'] ){
			$count = $wpdb->get_results($sql_count, ARRAY_A);
		
			if($count){				
				$this->total_records = $wpdb->num_rows;
				$images = $wpdb->get_results($sql, ARRAY_A);
			}
			
		} else {
		
			$images = $wpdb->get_results($sql, ARRAY_A);
			$this->total_records = $wpdb->num_rows;
			
		}
								
		return $images;
	}
	
	function getSearchSQL(){
		global $wpdb;
		
		$q = trim(stripslashes($_REQUEST['bwbps_q']));
		
		do_action('bwbps_ext_search', $q);
			
		if(!$q){ return false; }
		
		$q = explode(" ", $q);
				
		if(is_array($q)){
		
			foreach($q as $r){
				$res[] = " (CONCAT(image_caption, image_url, img_attribution) LIKE '%" . esc_sql($r) . "%' OR " . $wpdb->terms . ".name LIKE '%" 
					. esc_sql($r) . "%' OR " . $wpdb->users. ".user_login LIKE '%" 
					. esc_sql($r) . "%') ";
			}
			
			$ret = implode(" AND ", $res);
			
			if($ret){ $ret = " AND " . $ret; }
		
		}
		
		return $ret;
	}
	
	function getSortbyField($g, $sortorder){
		global $wpdb;
		
		switch ( (string)$g['sort_field'] ){
				
			case "sequence" :
			case "1" :	// Custom Sort sequence
				$sortby = PSIMAGESTABLE.'.seq, '.PSIMAGESTABLE.'.created_date '. $sortorder;
				break;
			
			/*	-- Not implemented --
			case "custom" :
			case 2 :	// Custom Fields
			 	break;
			*/
			
			case "user" :
			case "3" :	// User IDs
				$sortby = PSIMAGESTABLE.'.user_id ' . $sortorder . ', '.PSIMAGESTABLE.'.seq';
				break;
			
			case "user_name" :
			case "6" :	// User Name
				$sortby = $wpdb->users.'.user_nicename ' . $sortorder . ', ' . $wpdb->users.'.user_login ' . $sortorder . ', '.PSIMAGESTABLE.'.seq';
				break;
			
			case "user_login" :
			case "7" :	// User Login
				$sortby = $wpdb->users.'.user_login ' . $sortorder . ', ' . $wpdb->users.'.user_nicename ' . $sortorder . ', '.PSIMAGESTABLE.'.seq';
				break;
			
			case "rank" :
			case "4" :	// Rating

				switch ((int)$g['poll_id'] ) {

					case -2 :	// Vote up /vote down
						$sortby = 'bwbps_br_rating '
							. $sortorder . ', '.PSIMAGESTABLE.'.votes_cnt ASC ';
						break;
					
					case -3 :	// Vote up
						$sortby = 'votes_sum ' 
							. $sortorder . ', '.PSIMAGESTABLE.'.votes_cnt ASC, '.PSIMAGESTABLE.'.seq';
						break;

					case -1 :
					default :	//Stars - Bayesian Ranking
						$sortby = 'bwbps_br_rating ' 
							. $sortorder . ', '.PSIMAGESTABLE.'.seq';
						break;
				}
				
				break;
			
			case "favorites" :
			case "5" :	// Favorites Count
				$sortby = PSIMAGESTABLE.'.favorites_cnt ' . $sortorder . ', '.PSIMAGESTABLE.'.seq';
				break;
			
			case "caption" :
				$sortby = PSIMAGESTABLE.'.image_caption ' . $sortorder . ', '.PSIMAGESTABLE.'.seq';
				break;
			
			case "file_name" :	// this is actually stored in the database under image_name...bummer
				$sortby = PSIMAGESTABLE.'.image_name ' . $sortorder . ', '.PSIMAGESTABLE.'.seq';
				break;
			
			default :	// When Uploaded
				$sortby = PSIMAGESTABLE.'.created_date ' 
							. $sortorder . ', '.PSIMAGESTABLE.'.seq';
				break;		
		}
		
		return $sortby;
	
	}
	
	/**
	 * Gallery Viewer - Get the Image IDs for the Gallery Viewer Galleries
	 *
	 */
	function getGalleryViewerImageIDs($galIDs = '', $galExclude = ''){
		global $wpdb;
		
		//Get SQL for WHERE IN gallery IDs
		if($galIDs){
			$ag = split(",", $galIDs);
			if(is_array($ag)){
				foreach($ag as $gal){
					if((int)trim($gal)){
						$gal_a[] = (int)trim($gal);
					}
				}
				if(is_array($gal_a)){
					$galIDWhere = " AND gallery_id IN (" . implode(",", $gal_a) . ") ";
				}
			}
		}
		// Get SQL for exlcuded Gallery IDs
		if($galExclude){
			unset($ag);
			unset($gal);
			unset($gal_a);
			
			$ag = split(",", $galExclude);
			if(is_array($ag)){
				foreach($ag as $gal){
					if((int)trim($gal)){
						$gal_a[] = (int)trim($gal);
					}
				}
				if(is_array($gal_a)){
					$galExcludeWhere = " AND gallery_id NOT IN (" . implode(",", $gal_a) . ") ";
				}
			}
		}
		
		$sql = "SELECT gallery_id, cover_imageid FROM " . PSGALLERIESTABLE . " WHERE img_count > 0 AND status = 1 AND "
			. " gallery_type < 10 $galIDWhere $galExcludeWhere ";
			
		$res = $wpdb->get_results($sql);
		
		if($res){
		
			foreach($res as $row){
			
				if(!(int)$row->cover_imageid){
					$imgid = $this->pickGalleryCoverImage($row->gallery_id, true);
					if($imgid){
						$r[] = $imgid;						
					}
				} else {
					$r[] = $row->cover_imageid;
				}
			}
			
			if(is_array($r)){
				$ret = implode(",", $r);
			}
		}
		return $ret;
	}
	
	function pickGalleryCoverImage($gal_id, $update = false){
	
		global $wpdb;
		
		$sql = "SELECT image_id FROM " . PSIMAGESTABLE 
			. " WHERE gallery_id = " . (int)$gal_id 
			. " AND status = 1 AND thumb_url <> '' ORDER BY RAND() LIMIT 1;";
			
		$ret = $wpdb->get_var($sql);
		
		if($update && $ret){
			$sql = "UPDATE " . PSGALLERIESTABLE . " SET cover_imageid = $ret WHERE gallery_id = " . (int)$gal_id;
			$wpdb->query($sql);
		}
		
		return $ret;
	
	}
	
	/**
	 * Smart Gallery Where Field
	 * validates a URL
	 *
	 * @param (str) $url
	 */
	function getSmartWhereField( $swhere ){
	
		
		if( is_array($swhere) ){
			//$key = key($swhere);
			//$val = $swhere[$key];
			
			foreach ($swhere as $key => $val){
														
				if( is_array($val) ){
				
					foreach ($val as $v){
					
						$valarray[] = esc_sql($v);	//escape the sql
						
					}
					$ret = $key . " IN ( '" . implode( "','" , $valarray) . "' ) ";
					
				} else {
					
					$ret = $key . " = '" . esc_sql($val) . "'" ;	//escape the sql
				}
							
			}
		
		}

		return $ret;
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
	
	/* Add Google Maps Markers
	 * code for Google Maps
	 *
	*/
	
	function addGoogleMapMarker(&$image, $g){
		global $bwbPS;
		
		
		//Set up criteria for creating the Map
		$map = $g['gmap_id'];
		
		$bwbPS->addMap($map, $image["geolat"], $image["geolong"] );
		
		$imap_cnt = count($bwbPS->gmaps) - 1;
		
		if( floatval($image['geolat']) && floatval($image['geolong']) ){
			
			// Get InfoWindow
			$infowindow = '';
			$gmap_layout->layout_name = 'gmap_layout88';
			if( $bwbPS->psOptions['gmap_layout'] )
			{
				$gmap_layout->layout = $bwbPS->psOptions['gmap_layout'];
			
			} else {
				$gmap_layout->layout = '<div style="padding: 5px;">[thumb]<br/>[caption]</div>';
			}
			
			$infowindow = $this->getCustomLayout($g, $image, $gmap_layout, false);	
				
			$infowindow = esc_sql(str_replace(array("\r", "\r\n", "\n"), ' ', (string)$infowindow));

				
			
			
			/*
			$marker = '["' . esc_js($image["image_caption"]) . '", ' 
				. floatval($image["geolat"]) . ', ' . floatval($image["geolong"]) 
					. ', "' . $infowindow . '"]';
			*/
					
			$marker = "bwb_markers[" . $imap_cnt . "]" 
				. ".push( bwb_gmap.addMarkerWithInfoWindow(bwb_maps[" . $imap_cnt . "], " 
				. floatval($image["geolat"]) . ", "
				. floatval($image["geolong"]) . ", '" . $infowindow . "', " . $imap_cnt . "));\nbwbcnt = bwb_markers[" . $imap_cnt . "].length - 1;\nbwbbound_" . $map . ".extend(bwb_markers[" . $imap_cnt . "][bwbcnt].getPosition());\n";
				
			$bwbPS->gmaps[$map]['markers'][] = $marker;
			
			$icnt = count($bwbPS->gmaps[$map]['markers']) - 1;
			
			$image['gmap_marker'] =  $icnt;
			$image['gmap_num'] =  $imap_cnt;
			
		}
	}
}
?>