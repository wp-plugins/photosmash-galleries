<?php
//Layout Editor for the PhotoSmash Galleries plugin


class BWBPS_LayoutsEditor{
	
	var $options;
	var $message = false;
	var $msgclass= "updated fade";
	var $layout_id;
	
	//Constructor
	function BWBPS_LayoutsEditor(){
				
		if(isset($_REQUEST['bwbps_layout_id'])){
			$this->layout_id = (int)$_REQUEST['bwbps_layout_id'];
		} else { $this->layout_id = 0; }
		
		//Save layout
		if(isset($_POST['saveLayout'])){
			$ret = $this->saveLayout();
			if($ret){
				$this->layout_id = $ret;
			}
		}
				
		//Save layout
		if(isset($_POST['deleteLayout'])){
			$ret = $this->deleteLayout($this->layout_id);
			$this->layout_id = 0;
		}
		
		//Load up layout defaults
		
		$this->showLayoutsForm();
		
		//$this->showFieldsTable();
	}
	
	function saveLayout(){
		global $wpdb;
		check_admin_referer( 'update-bwbpslayouts');
		$d['layout_name'] = $_POST['bwbps_layout_name'];
		
		if(!$this->checkName($d['layout_name'])){
			$this->message = 'Invalid layout name...use numbers, letters, and underscore only.';
			$this->msgclass= 'error';
			return false;
		}
		$d['cells_perrow'] = (int)$_POST['bwbps_cells_perrow'];
		$d['layout'] = $_POST['bwbps_layout'];
		$d['alt_layout'] = $_POST['bwbps_alt_layout'];
		$d['wrapper'] = $_POST['bwbps_wrapper'];
		$d['css'] = $_POST['bwbps_css'];
		$d['post_type'] = stripslashes($_POST['bwbps_post_type']."");
		
		$d['pagination_class'] = esc_attr__($_POST['bwbps_pagination_class']);
		
		//Strip slashes...I think WP adds slashes regardless, so you need to strip them
		$d['layout'] = stripslashes($d['layout']);
		
		$d['alt_layout'] = stripslashes($d['alt_layout']);
		$d['css'] = stripslashes($d['css']);
		$d['wrapper'] = stripslashes($d['wrapper']);
		$d['layout_type'] = (int)$_POST['bwbps_layout_type'];
		$d['footer_layout'] = stripslashes($_POST['bwbps_footer_layout']);
					
		
		if($this->layout_id == 0){
				
				$nametest = $wpdb->get_var($wpdb->prepare('SELECT layout_name FROM '
					.PSLAYOUTSTABLE.' WHERE layout_name = %s',$d['layout_name']));
				
				if($nametest){
					$this->message = "<h3 style='color:red;'>Duplicate layout name: ".$d['layout_name']. " - layout not added.</h3>";
					return false;
				}
				
				
				if($wpdb->insert(PSLAYOUTSTABLE,$d)){
					$insert_id = $wpdb->insert_id;
					$this->message =  "<b>Layout Added -> </b>".$d['layout_name'];
					return $insert_id;
				} else {
					$this->message = "<h3 style='color:red;'>FAILED...form failed to insert: </h3>".$d['field_name'];
				}
			}else{
				$where['layout_id'] = $this->layout_id;
				$wpdb->update( PSLAYOUTSTABLE, $d, $where);
				$this->message = "<b>Layout updated:  ".$d['layout_name']."</b>";
			}
				
	}
	
	function deleteLayout($layout_id){
		global $wpdb;
		check_admin_referer( 'update-bwbpslayouts');
		
		$ret = $wpdb->query($wpdb->prepare("DELETE FROM " . PSLAYOUTSTABLE . " WHERE layout_id = %d", $layout_id));
		
		if($ret){
			$this->message = 'Layout '. $layout_id .' deleted...';
			return true;
		} else {
			$this->message = 'Layout '. $layout_id .' NOT deleted...';
			$this->msgclass= 'error';
			return false;
		}			
	}
	
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
	
	
	/**
	 * Displays the Add/Edit form for HTML layouts
	 *
	 * layouts let you build complex HTML templates for 
	 * displaying your field data within a post
	 * 
	 */
	function showLayoutsForm(){
		global $wpdb;
		global $psmashExtend;
		$layoutsDDL = $this->getLayoutsDDL($this->layout_id);
		
		$customfieldlist = $this->getCustomFieldList();
		if($this->layout_id){
			$layoutOptions = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.PSLAYOUTSTABLE.' WHERE layout_id = %d',$this->layout_id), ARRAY_A);
			
			if($layoutOptions){
				if(get_magic_quotes_gpc()){
					$layoutOptions['layout'] = stripslashes($layoutOptions['layout']);
					$layoutOptions['alt_layout'] = stripslashes($layoutOptions['alt_layout']);
					$layoutOptions['css'] = stripslashes($layoutOptions['css']);
					$layoutOptions['lists'] = stripslashes($layoutOptions['lists']);
				}			
			}
			
		}	
		
		?>
		<div class=wrap>
		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<?php bwbps_nonce_field('update-bwbpslayouts'); ?>
		<h2>PhotoSmash -> HTML Layouts Editor</h2>
		
		<?php
			if($this->message){
				echo '<div id="message" class="'.$this->msgclass.'"><p>'.$this->message.'</p></div>';
			}
		?>
		
		<h3>Add/Edit HTML Layouts</h3>
		<?php echo PSADVANCEDMENU; ?>
<div id='poststuff'>
<div  style='width: 550px; float: left;'>
<table class="form-bwbps">
<tr>
<th><input type="submit" name="saveLayout" class="button-primary" tabindex="20" value="<?php _e('Save Layout', 'bwbpsLang') ?>" /></th> 
<td>&nbsp;</td>
</tr>

<th>Select Layout to edit:</th><td><?php echo $layoutsDDL;?>&nbsp;<input type="submit" name="showLayoutSettings" tabindex="100" value="<?php _e('Edit', 'bwbpsLang') ?>" /> &nbsp; <input type="submit" name="deleteLayout" class="" tabindex="900" value="<?php _e('Delete Layout', 'bwbpsLang') ?>" onclick='return confirm("Do you want to delete this layout?");' /></td></tr>
<tr>
	<th>Layout name:</th>
	<td>
		<input type='text' name="bwbps_layout_name" value='<?php echo esc_attr($layoutOptions['layout_name']);?>'/>
		<ol><li>Used in shortcodes to display layout: <span style='color:red;'>[photosmash layout='<?php if($layoutOptions['layout_name']){echo $layoutOptions['layout_name']; } else {
		echo 'my_layout';} ?>']</span></li>
		<li>Use letters, numbers, and underscore ( _ ) only</li></ol>
	</td>
</tr>
<tr>
	<th>Layout type:</th>
	<td>
		<select name="bwbps_layout_type">
			<option value="0" <?php if((int)$layoutOptions['layout_type'] == 0) echo 'selected=selected'; ?>>Normal gallery layout</option>
			<option value="1" <?php if((int)$layoutOptions['layout_type'] == 1) echo 'selected=selected'; ?>>PS Extend new post layout</option>
			<option value="2" <?php if((int)$layoutOptions['layout_type'] == 2) echo 'selected=selected'; ?>>PS Extend navigation layout</option>
			<option value="10" <?php if((int)$layoutOptions['layout_type'] == 10) echo 'selected=selected'; ?>>Other layouts</option>
		</select>
		Note: this does not affect the operation of layouts, only whether they show up in certain dropdowns (e.g. Layout in Gallery Settings)
	</td>
</tr>

<tr>
<th id='bwbps_layout'>HTML Layout:</th>
	<td>
		<textarea name="bwbps_layout" cols="43" rows="6"><?php echo htmlentities($layoutOptions['layout']);?></textarea>
		<br/>- Use standard HTML to format the display of images<br/>- Display custom field values with tags like: <span style='color:red;'>[my_field]</span>
	</td>
</tr>
<tr>
<th id='bwbps_alt_layout'>HTML Alternating Layout:</th>
	<td>
		<textarea name="bwbps_alt_layout" cols="43" rows="6"><?php echo htmlentities($layoutOptions['alt_layout']);?></textarea>
		<br/>- Same as above except used for every other Image.<br/>- Leave blank to use main layout for all images
	</td>
</tr>

<tr>
<th id='bwbps_wrapper'>Wrapper:</th>
	<td>
		<textarea name="bwbps_wrapper" cols="43" rows="6"><?php echo htmlentities($layoutOptions['wrapper']);?></textarea>
		<br/>- Wrapper allows you to put HTML around the gallery of images after it's complete.  You'd need it for ol, ul, and table tags, but it could be used for all kinds of style the overall gallery.<br/><br/>- Use [gallery] to set where in the wrapper the images should go.  Example:<br/>
		&lt;h2&gt;Gallery - [gallery_name]&lt;/h2&gt;<br/>
		&lt;div class='my_gallery'&gt;<br/>&nbsp;&nbsp;&nbsp;&lt;ul&gt; [gallery] &lt;/ul&gt;
		<br/>&lt;/div&gt;
		<p><b>Fields you can use</b> in the wrapper are:
		<ul>
		<li> - [gallery] - (optional) displays the actual images...will be added to bottom of wrapper if not specified</li>
		<li> - [gallery_name]</li>
		<li> - [gallery_description]</li>
		<li> - [ratings_toggle] - you'll want to turn off the default toggle in Gallery Settings</li>
		<li> - [gallery_id]</li>
		</ul></p>
	</td>
</tr>

<tr>
<th id='bwbps_cells_perrow'>Table Cells per Row:</th>
	<td>
		<?php echo $this->getCellsPerRowDDL($layoutOptions['cells_perrow']); ?>
		<br/>- If using a table, do not enter the TR in your layouts. Set the Cells per Row here and PhotoSmash will wrap them in TR tags.  You do need TABLE tags in your wrapper (but not the TR) and TD in your layout/alternating layout if using a table. Set to No Table if not using a table.
	</td>
</tr>

<tr>
<th id='bwbps_css'>CSS:</th>
	<td>
		Enter as normal CSS for use with classes/IDs in your layouts:<br/>
		<textarea name="bwbps_css" cols="43" rows="4"><?php echo htmlentities($layoutOptions['css']);?></textarea>
	</td>
</tr>

<tr>
	<th>Pagination CSS class:</th>
	<td>
		<input type='text' name="bwbps_pagination_class" value='<?php 
		
		if(!$layoutOptions['pagination_class'] ){
			echo "bwbps_pagination";
		} else {
			esc_attr_e($layoutOptions['pagination_class']);
		}
		?>'/>
		<br/>Used when paging is turned on:
		<ol><li>Standard (white text, blue background on hover): bwbps_pagination</li>
			<li>Alternate (black text, black background on hover): bwbps_pag_2</li>
		<li>Or, use your own class that you define in CSS elsewhere.</li></ol>
	</td>
</tr>

<tr>
<th id='bwbps_footer_layout'>Javascript Layout:</th>
	<td>
		<input type='text' name="bwbps_footer_layout" value='<?php esc_attr_e($layoutOptions['footer_layout']);
		?>' />
		<br/>- Enter another Custom Layout name here to create javascript for each image.  This javascript will be inserted to the footer of your page.  The &lt;script&gt; tags will be added automatically.  Use the Wrapper of your Javascript Custom Layout if you'd like to wrap your images scripts in a function, or if you need to call certain functions once.  Example of use: you could add javascript that would add your images to a Google Map!
		<br/><br/>You can override this Javascript Layout in your shortcode by using an attribute like: javascript_layout='my_js_layout' 
		<br/>You can prevent the javascript_layout from being used by adding javascript_layout='none' to your shortcode.
	</td>
</tr>	

<tr>
	<th>Post Type:</th>
	<td>
		<?php echo "type: ".$layoutOptions['post_type'];?>
		<select name="bwbps_post_type">
			<option value="0" <?php if(!$layoutOptions['post_type']) echo 'selected=selected'; ?>>default</option>
		<?php 
			$post_types=get_post_types('','names'); 
			foreach ($post_types as $post_type ) {
				if($post_type != 'revision' && $post_type != 'attachment' && $post_type != 'nav_menu_item'){
					if($layoutOptions['post_type'] == $post_type){ $selected = 'selected=selected'; }
					echo "<option value='$post_type' $selected >$post_type</option>";
				}
			 }
		
		 ?>
		 </select>
		
		<p>Post Types use with PhotoSmash Extend's New Post on Upload feature...adds a Post Type to new posts.
		</p><b>Note:</b> PhotoSmash does not implement Post Types for you...your theme or another plugin will need to do that.  This just allows you to set a Post Type on New Posts when they are created via PhotoSmash Extend.
		<br/>Post Types are well documented on the web.  Try one of these tutorials to get started:
		<ul>
		<li><a href='http://justintadlock.com/archives/2010/04/29/custom-post-types-in-wordpress'>Justin Tadlock's tutorial</a></li>
		<li><a href='http://www.wpbeginner.com/wp-tutorials/how-to-use-custom-post-types/'>WPBeginner's tutorial...links to plugins</a></li>
		<li><a href='http://net.tutsplus.com/tutorials/wordpress/rock-solid-wordpress-3-0-themes-using-custom-post-types/'>NetTuts+ tutorial</a></li>
		<li><a href='http://codex.wordpress.org/Function_Reference/register_post_type'>The Official WP Codex entry</a></li>
		</ul>
	</td>
</tr>

<tr>
<th><input type="submit" name="saveLayout" class="button-primary" tabindex="20" value="<?php _e('Save Layout', 'bwbpsLang') ?>" /></th>
<td>&nbsp;
</td>
<td>&nbsp;
</td>
</tr>
</table>
</div>
<div >
<table>
<tr>
<td  style='text-align: left;' valign="top">
					<h4>Available fields:</h4>					
					<ul style='padding: 6px; background-color: #fff; border: 1px solid #d8e9ec;'>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[gallery_name]<span style='font-size: 9px;'></span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[gallery_description]<span style='font-size: 9px;'></span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[gallery_id]<span style='font-size: 9px;'></span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[date_added] - <span style='font-size: 9px;'>date image was added</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[linked_image] - <span style='font-size: 9px;'>image with link to itself -- not sure why, but there it is ;-)</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[image_id] - <span style='font-size: 9px;'>image id - for building shortcodes in new posts</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[image] - <span style='font-size: 9px;'>image</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[image_url] - <span style='font-size: 9px;'>image url - for building a link</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumb] - <span style='font-size: 9px;'>displays the returned thumbnail with appropriate link</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumbnail] - <span style='font-size: 9px;'>same as thumb</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumb_image] - <span style='font-size: 9px;'>just the thumb image</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumb_linktoimage] - <span style='font-size: 9px;'>the thumb linking to image (always)</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[thumb_url] - <span style='font-size: 9px;'>just the thumb url - no tags</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[mini] - <span style='font-size: 9px;'>displays the returned mini image with appropriate link</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[mini_url] - <span style='font-size: 9px;'>just the mini sized image url - no tags</span></li>					
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[medium] - <span style='font-size: 9px;'>displays the medium sized thumbnail</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[medium_url] - <span style='font-size: 9px;'>displays the medium sized image's url</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[caption] - <span style='font-size: 9px;'>use length and more attributes like this: [caption length=20 more='see more'] </span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[caption_escaped] - <span style='font-size: 9px;'>same as above...escaped for use in title or alt attributes</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[full_caption] - <span style='font-size: 9px;'>caption displayed with rules set in Gallery Settings</span></li>
										
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[user_name] - <span style='font-size: 9px;'>same as contributor</span></li>
												
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[user_url] - <span style='font-size: 9px;'>displays URL from User's Profile</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[user_link] - <span style='font-size: 9px;'>displays User Name as link from Profile</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[author_link] - <span style='font-size: 9px;'>displays link to Author page for user</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[author] - <span style='font-size: 9px;'>displays Author nicename</span></li>

						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[url] - <span style='font-size: 9px;'>alternate user supplied URL</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[img_attribution] - <span style='font-size: 9px;'>attributed to?</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[img_license] - <span style='font-size: 9px;'>image license</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[file_name] - <span style='font-size: 9px;'>image's file name</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[favorite] - <span style='font-size: 9px;'>The star for allowing users to favorite an image</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[favorite_cnt] - <span style='font-size: 9px;'>How many times an image has been favorited</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[delete_button] - <span style='font-size: 9px;'>Button to allow your users to delete their images. Use attribute 'button_name' to specify the button's text.  Will default to 'delete'.</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[ps_rating] - <span style='font-size: 9px;'>Show's the rating stars (make sure you've got configured in your gallery)</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[image_id] - <span style='font-size: 9px;'>image id in database</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[wp_attachment_link] - <span style='font-size: 9px;'>WP attachment page url</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[wp_permalink] - <span style='font-size: 9px;'>WP attachment page url - because there's a bug on Pages</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[wp_attach_id] - <span style='font-size: 9px;'>WP attachment ID #</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[post_url] - <span style='font-size: 9px;'>Post's permalink (not linkified)</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[post_name] - <span style='font-size: 9px;'>Post's name (not linkified)</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[post_id] - <span style='font-size: 9px;'>Post's ID</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[comments_count] - <span style='font-size: 9px;'>Count of comments for an image's post</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[tag_links] - <span style='font-size: 9px;'>Links to the tags - to separate with commas (or other), use attribute like sep=', '. Other attributes:  before and after</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[blog_name] - <span style='font-size: 9px;'>Blog's name</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[gdsr shortcode='??'] - <span style='font-size: 9px;'>Adds a GD Star Rating block for an image. Uses the standard GDSR shortcodes (starrater, starthumbsblock, starratingblock, starrating, starcomments, starreview, starreviewmulti, starratingmulti). <b>This feature is experimental and does not fully work.</b> GDSR does not count Attachments as fully qualified posts - GDSR widgets do not incorporate ratings properly</span></li>
						
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[bloginfo field='??'] - <span style='font-size: 9px;'>displays field as delivered by the WP <a href='http://codex.wordpress.org/Function_Reference/get_bloginfo'>get_bloginfo()</a> function</span></li>												
						<?php echo $customfieldlist;?>
						
					</ul>
					
						<h3>Gallery Viewer Fields</h3>
						The Gallery Viewer is a special gallery that displays an image for each PhotoSmash Gallery that has images. To format the image blocks in this special gallery, you want to be able to get information about the Gallery that each image is pointing to.  This also has implications for other virtual galleries that show images from more than 1 gallery (random, recent, etc).
						<ul style='padding: 6px; background-color: #fff; border: 1px solid #d8e9ec;'>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[image_gallery_name] - <span style='font-size: 9px;'>The gallery name of the image's Gallery.</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[gallery_url] - <span style='font-size: 9px;'>The Gallery Viewer URL for the image's Gallery</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[gallery_post_url] - <span style='font-size: 9px;'>URL for the related post of an image's Gallery</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[gallery_image_count] - <span style='font-size: 9px;'># of images for the image's Gallery</span></li>
						</ul>
					
								<?php
						
						if( $psmashExtend->options['ext_nav'] ){
						?>
						<h3>Extended Navigation Fields</h3>
						<ul style='padding: 6px; background-color: #fff; border: 1px solid #d8e9ec;'>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[tag_dropdown] - <span style='font-size: 9px;'>include tags like:  [tag_dropdown tags='red,green,blue' select_msg='-select-']</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[tags_has_all] - <span style='font-size: 9px;'>a hidden input field that tells PhotoSmash to only get images having all tags</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[nav_search] - <span style='font-size: 9px;'>text input box to provide search functionality</span></li>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[submit] - <span style='font-size: 9px;'>submit button</span></li>
						</ul>
						<?php
						};
						
						if($psmashExtend->options['new_posts']){
						?>
						<h3>PhotoSmash Extend - New Posts</h3>
						<ul style='padding: 6px; background-color: #fff; border: 1px solid #d8e9ec;'>
						<li style='border-bottom: 1px solid #f0f0f0;padding-bottom: 3px;'>[new_gallery attributes] - <span style='font-size: 9px;'>PhotoSmash Extend only - add new galleries when creating New Posts on upload</span></li>
						</ul>
						
						
						<?php	
						}
						?>
				</td>
</tr>
</table>

</div>


</div>
</form>
<br/>
<?php
}
	
	function getLayoutsDDL($selected_layout){
		
 		global $wpdb;
 		 
		$ret = "<option value='0'>&lt;new&gt;</value>";
		
		$query = $wpdb->get_results("SELECT layout_id, layout_name FROM "
			.PSLAYOUTSTABLE." ORDER BY layout_name;");
		
		if($query){
			foreach($query as $row){
		
				if($selected_layout == $row->layout_id){$sel = "selected='selected'";}else{$sel = "";}
				$ret .= "<option value='".$row->layout_id."' ".$sel.">".$row->layout_name."</option>";
		
			}
		}
		$ret ="<select name='bwbps_layout_id' style='font-size: 14px;'>".$ret."</select>";
		return $ret;
	}
	
	function getCellsPerRowDDL($cellsPerRow){
 		global $wpdb;
		$ret = "<option value='0'>&lt;No Table&gt;</value>";
		
		for($i=1; $i<13; $i++){
				if($cellsPerRow == $i){
					$sel = "selected='selected'";
				}else{
					$sel = "";
				}
				$ret .= "<option value='".$i."' $sel>".$i."</option>";
		}
		$ret ="<select name='bwbps_cells_perrow'>".$ret."</select>";
		return $ret;
	}
	
	function showFieldsTable(){
		$fldTable = $this->getTableOfFields();
		
		echo "<h2>Table of your fields for reference:</h2>Field values can be inserted into your Layout with a short code like [field_name].  Example:<p>&lt;div&gt;I live in [my_city].&lt;/div&gt;</p>Renders:  I live in St. Louis.";
		echo $fldTable;
	}
	
	//Get a table of the created fields
	function getTableOfFields()
	{
		global $wpdb;
		$sql = "SELECT * FROM ".BWBPSFIELDSTABLE." ORDER BY form_id, seq";
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
					." - <a href='admin.php?page=editBWBPSFormFields&field_id="
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
		}
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
				$ret = "hidden field with image's Post ID";
				break;
			case 40 :
				$ret = "Drop Down list of categories";
				break;
			default:
				$ret = "custom field";
				break;
		}
		return $ret;
	}
	
}

if ( !function_exists('wp_nonce_field') ) {
        function bwbps_nonce_field($action = -1) { return; }
        $bwbps_plugin_nonce = -1;
} else {
        function bwbps_nonce_field($action = -1) { return wp_nonce_field($action); }
}
?>