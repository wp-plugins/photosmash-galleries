<?php 

//Code for initializing BWB-PhotoSmash plugin when it is activated in Wordpress Plugins admin page
class BWBPS_Init{
	
	var $adminOptionsName = "BWBPhotosmashAdminOptions";
	
	//Constructor
	function BWBPS_Init(){
		//Create the PhotoSmash Tables if not exists
		
		$psOptions = get_option($this->adminOptionsName);
		
		$label = $psOptions['tag_label'] ? esc_attr($psOptions['tag_label']) : "Photo tags";
		$slug = $psOptions['tag_slug'] ? $psOptions['tag_slug'] : "photo-tag";
	 	
		register_taxonomy( 'photosmash', 'post', array( 'hierarchical' => false, 'label' => __($label, 'series'), 'query_var' => 'bwbps_wp_tag', 'rewrite' => array( 'slug' => $slug ) ) );	
		 
		 $label = $psOptions['contributor_label'] ? esc_attr($psOptions['contributor_label']) : "Photo Contributors";
		 $slug = $psOptions['contributor_slug'] ? $psOptions['contributor_slug'] : "contributor";
	 	
		 register_taxonomy( 'photosmash_contributors', 'post', array( 'hierarchical' => false, 'label' => __($label, 'series'), 'query_var' => 'bwbps_contributor', 'rewrite' => array( 'slug' => $slug ) ) );
	 	
		 	global $wp_rewrite;
			$wp_rewrite->flush_rules();
		
			global $wpdb;
					
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty($wpdb->charset) ){
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
					$alter_charset = "CHARACTER SET $wpdb->charset";
				}
				if ( ! empty($wpdb->collate) )
					$charset_collate .= " COLLATE $wpdb->collate";
			}
			
			$icnt = 0;
			
										
			$table_name = $wpdb->prefix . "bwbps_images";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX image_id";
				$wpdb->query($sql);
					
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX gallery_id";
				$wpdb->query($sql);
			}
							
			
			//Create the Images table
			$table_name = $wpdb->prefix . "bwbps_images";
			$sql = "CREATE TABLE " . $table_name . " (
				image_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				gallery_id BIGINT(20) NOT NULL,
				user_id BIGINT(20) NOT NULL DEFAULT '0',
				post_id BIGINT(20),
				comment_id BIGINT(20),
				image_name VARCHAR(250),
				image_caption TEXT,
				file_type TINYINT(1),
				file_name TEXT,
				file_url TEXT,
				mini_url TEXT,
				thumb_url TEXT,
				medium_url TEXT,
				image_url TEXT,
				wp_attach_id BIGINT(11),
				url VARCHAR(250),
				custom_fields TEXT,
				meta_data TEXT,
				geolong double,
				geolat double,
				img_attribution TEXT,
				img_license TINYINT(1),
				updated_by BIGINT(20) NOT NULL DEFAULT '0',
				created_date DATETIME,
				updated_date TIMESTAMP NOT NULL,
				status TINYINT(1) NOT NULL DEFAULT '0',
				alerted TINYINT(1) NOT NULL DEFAULT '0',
				seq BIGINT(11) NOT NULL DEFAULT '0',
				favorites_cnt BIGINT(11),
				avg_rating FLOAT(8,4) NOT NULL DEFAULT '0',
				rating_cnt BIGINT(11) NOT NULL DEFAULT '0',
				votes_sum BIGINT(11) NOT NULL DEFAULT '0',
				votes_cnt BIGINT(11) NOT NULL DEFAULT '0',
				PRIMARY KEY   (image_id),
				INDEX (gallery_id)
				)  $charset_collate;";
			dbDelta($sql);
			
						
			//IMAGE CATEGORIES
			$table_name = $wpdb->prefix . "bwbps_categories";
			
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			
				//Delete the old indices
				
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX image_id";
				$wpdb->query($sql);
				
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX category_id";
				$wpdb->query($sql);
				
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX tag_name";
				$wpdb->query($sql);
			
			}
			
			$sql = "CREATE TABLE " . $table_name . " (
				id BIGINT(11) NOT NULL AUTO_INCREMENT,
				image_id BIGINT(20) NOT NULL,
				category_id BIGINT(20),
				tag_name VARCHAR(250),
				updated_date TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				INDEX (image_id),
				INDEX (category_id),
				INDEX  (tag_name)
				)  $charset_collate;";
			dbDelta($sql);
									
			//Create the Gallery Table
			$table_name = $wpdb->prefix . "bwbps_galleries";
			$sql = "CREATE TABLE " . $table_name . " (
				gallery_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				post_id BIGINT(20),
				gallery_name VARCHAR(255),
				gallery_description TEXT,
				gallery_type TINYINT(1) NOT NULL default '0',
				caption TEXT,
				add_text VARCHAR(255),
				upload_form_caption VARCHAR(255),
				contrib_role TINYINT(1) NOT NULL default '0',
				anchor_class VARCHAR(255),
				img_count BIGINT(11),
				img_rel VARCHAR(255),
				img_class VARCHAR(255),
				img_perrow TINYINT(1),
				img_perpage INT(4),
				mini_aspect TINYINT(1),
				mini_width INT(4),
				mini_height INT(4),
				thumb_aspect TINYINT(1),
				thumb_width INT(4),
				thumb_height INT(4),
				medium_aspect TINYINT(1),
				medium_width INT(4),
				medium_height INT(4),
				image_aspect TINYINT(1),
				image_width INT(4),
				image_height INT(4),
				show_caption TINYINT(1),
				nofollow_caption TINYINT(1),
				caption_template VARCHAR(255),
				show_imgcaption TINYINT(1),
				img_status TINYINT(1),
				allow_no_image TINYINT(1),
				suppress_no_image TINYINT(1),
				default_image VARCHAR(255),
				created_date DATETIME,
				updated_date TIMESTAMP NOT NULL,
				layout_id INT(4),
				use_customform TINYINT(1),
				custom_formid INT(4),
				use_customfields TINYINT(1),
				cover_imageid INT(4),
				status TINYINT(1),
				sort_field TINYINT(1),
				sort_order TINYINT(1),
				poll_id INT(4),
				rating_position INT(4),
				hide_toggle_ratings TINYINT(1),
				pext_insert_setid INT(4),
				max_user_uploads INT(4),
				uploads_period INT(4),
				PRIMARY KEY  (gallery_id)) $charset_collate;";
			dbDelta($sql);
					
					
			// fix character set for columns in tables prior to 0.7.03
			$sql = "ALTER TABLE $table_name MODIFY gallery_name VARCHAR(255) $alter_charset";
			$wpdb->query($sql);
			
			$sql = "ALTER TABLE $table_name MODIFY caption TEXT $alter_charset";
			$wpdb->query($sql);
			
			$sql = "ALTER TABLE $table_name MODIFY add_text VARCHAR(255) $alter_charset";
			$wpdb->query($sql);
			
			$sql = "ALTER TABLE $table_name MODIFY upload_form_caption VARCHAR(255) $alter_charset";
			$wpdb->query($sql);
			
			$sql = "ALTER TABLE $table_name MODIFY anchor_class VARCHAR(255) $alter_charset";
			$wpdb->query($sql);
			
			$sql = "ALTER TABLE $table_name MODIFY img_rel VARCHAR(255) $alter_charset";
			$wpdb->query($sql);
			
			$sql = "ALTER TABLE $table_name MODIFY img_class VARCHAR(255) $alter_charset";
			$wpdb->query($sql);
			
			$sql = "ALTER TABLE $table_name MODIFY caption_template VARCHAR(255) $alter_charset";
			$wpdb->query($sql);
			
			$sql = "ALTER TABLE $table_name MODIFY default_image VARCHAR(255) $alter_charset";
			$wpdb->query($sql);
			
			
			//Drop Old Index
			$table_name = $wpdb->prefix . "bwbps_imageratings";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {

				//Image Ratings Table
				$sql = "ALTER TABLE " . $wpdb->prefix."bwbps_imageratings ".
					"DROP INDEX image_id";
				$wpdb->query($sql);
			}
			
			//Create the IMAGE RATINGS table (future use)
			$table_name = $wpdb->prefix . "bwbps_imageratings";
			$sql = "CREATE TABLE " . $table_name . " (
				rating_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				image_id BIGINT(20) NOT NULL,
				gallery_id BIGINT(20),
				poll_id BIGINT(20),
				user_id BIGINT(20),
				user_ip VARCHAR(30) ,
				rating TINYINT(1),
				comment VARCHAR(250),
				updated_date TIMESTAMP NOT NULL,
				status TINYINT(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (rating_id),
				INDEX (image_id)
				)  $charset_collate;";
			dbDelta($sql);
					
			
			/* 
			* RATINGS SUMMARY
			* Summarizes ratings by Image, Gallery, Poll
			*/
			
			$table_name = $wpdb->prefix . "bwbps_ratingssummary";			
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			
				//Delete the old indices
				
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX image_id";
				$wpdb->query($sql);
				
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX gallery_poll";
				$wpdb->query($sql);
			
			}
			
			//create the table
			$sql = "CREATE TABLE " . $table_name . " (
				rating_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				image_id BIGINT(20) NOT NULL,
				gallery_id BIGINT(20),
				poll_id BIGINT(20),
				avg_rating FLOAT(8,4) NOT NULL,
				rating_cnt BIGINT(11) NOT NULL,
				updated_date TIMESTAMP NOT NULL,
				PRIMARY KEY  (rating_id),
				INDEX (image_id),
				INDEX gallery_poll (gallery_id, poll_id)
				)  $charset_collate;";
			dbDelta($sql);
			
			/* 
			* Favorites
			* Table for Collecting User Favorites
			*/
			
			$table_name = $wpdb->prefix . "bwbps_favorites";			
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			
				//Delete the old indices
				
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX image_id";
				$wpdb->query($sql);
				
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX user_id";
				$wpdb->query($sql);
			
			}
			
			//create the table
			$sql = "CREATE TABLE " . $table_name . " (
				favorite_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				image_id BIGINT(20) NOT NULL,
				user_id BIGINT(20) NOT NULL,
				updated_date TIMESTAMP NOT NULL,
				PRIMARY KEY  (favorite_id),
				INDEX (image_id),
				INDEX (user_id)
				)  $charset_collate;";
			dbDelta($sql);
			
			/* 
			* LAYOUTS
			* Create the PhotoSmash HTML layouts table
			* HTML layouts are templates that lets you
			* create a predefined HTML layout with shortcodes to display
			* galleries
			*/
			$sql = "CREATE TABLE " . $wpdb->prefix."bwbps_layouts (
				layout_id INT(11) NOT NULL AUTO_INCREMENT,
				layout_name VARCHAR(30) ,
				layout_type TINYINT NOT NULL default '0',
				layout TEXT ,
				alt_layout TEXT ,
				wrapper TEXT ,
				cells_perrow TINYINT NOT NULL default '0',
				css TEXT ,
				pagination_class VARCHAR(255),
				lists VARCHAR(255) ,
				post_type VARCHAR(20),
				fields_used TEXT,
				footer_layout TEXT,
				PRIMARY KEY  (layout_id)
				)  $charset_collate;";
			dbDelta($sql);
			
			
			$wpdb->query("UPDATE " . $wpdb->prefix."bwbps_layouts SET layout_type = 0 WHERE layout_type = NULL");
			
			
			/* Create the CUSTOM FORMS table
			* Custom forms let you create any form layout you can imagine...I think
			* Code safe!
			*/
			$sql = "CREATE TABLE " . $wpdb->prefix."bwbps_forms (
				form_id INT(11) NOT NULL AUTO_INCREMENT,
				form_name VARCHAR(30) ,
				form TEXT ,
				css TEXT ,
				fields_used TEXT,
				category TINYINT(1),
				PRIMARY KEY  (form_id)
				)  $charset_collate;";
			dbDelta($sql);
			
			
			//FIELDS
			//Create the Fields table
			$sql = "CREATE TABLE " . $wpdb->prefix."bwbps_fields (
				field_id INT(11) NOT NULL AUTO_INCREMENT,
				form_id INT(4) NOT NULL default '0',
				field_name VARCHAR(50) ,
				label VARCHAR(255) ,
				type INT(4) ,
				numeric_field TINYINT(1) NOT NULL default '0',
				multi_val TINYINT(1) NOT NULL,
				default_val varchar(255),
				auto_capitalize TINYINT(1),
				keyboard_type TINYINT(1),
				html_filter TINYINT(1),
				date_format TINYINT(1),
				seq INT(4) ,
				status TINYINT(1) NOT NULL ,
				PRIMARY KEY  (field_id)
				)  $charset_collate;";
			
			dbDelta($sql);
			
			//Drop Old Index
			$table_name = $wpdb->prefix . "bwbps_lookup";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			
				$sql = "ALTER TABLE " . $wpdb->prefix."bwbps_lookup ".
					"DROP INDEX field_id";
				$wpdb->query($sql);
				
			}
			
			//LOOKUP
			//Create the Custom Data Lookup Table
			$sql = "CREATE TABLE " . $wpdb->prefix."bwbps_lookup (
				id INT(11) NOT NULL AUTO_INCREMENT,
				field_id INT(4) ,
				value VARCHAR(255) ,
				label VARCHAR(255) ,
				seq INT(4) ,
				PRIMARY KEY   (id),
				INDEX (field_id)
				)  $charset_collate;";
			dbDelta($sql);
			
			//Drop Old Index
			$table_name = $wpdb->prefix . "bwbps_customdata";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			
				$sql = "ALTER TABLE " . $wpdb->prefix."bwbps_customdata ".
					"DROP INDEX image_id";
				$wpdb->query($sql);
				
			}
			
			//CUSTOMDATA
			//SQL for table creation & updating
			$sql = "CREATE TABLE " . $wpdb->prefix."bwbps_customdata (
				id INT(11) NOT NULL AUTO_INCREMENT,
				image_id INT(11) NOT NULL,
				updated_date TIMESTAMP NOT NULL, 
				bwbps_status TINYINT(1) NOT NULL default '0',
				PRIMARY KEY  (id),
				INDEX (image_id)
				)  $charset_collate;";
			dbDelta($sql);
			
			// The Sharing API morphed into the Mobile API...tables are not needed
			
			$wpdb->query("DROP TABLE if exists " . $wpdb->prefix . "bwbps_sharinghubs");
			
			
			
			$wpdb->query("DROP TABLE if exists " . $wpdb->prefix . "bwbps_sharinglog");
			
			
			//PARAMS TABLE
			$table_name = $wpdb->prefix . "bwbps_params";
			
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
			
				//Delete the old indices
				
				$sql = "ALTER TABLE " . $table_name .
					" DROP INDEX param_group";
				$wpdb->query($sql);
			
			}
			
			$sql = "CREATE TABLE " . $table_name . " (
				id BIGINT(11) NOT NULL AUTO_INCREMENT,
				param_group VARCHAR(20),
				param VARCHAR(100),
				num_value FLOAT,
				text_value VARCHAR(255),
				user_ip VARCHAR(30),
				updated_date TIMESTAMP NOT NULL,
				PRIMARY KEY  (id),
				INDEX (param_group)
				)  $charset_collate;";
			dbDelta($sql);
						
		//Load Preloaded Layouts, Forms, etc
		$this->insertPreloads();
						
		//Neeed to Set PS Default Options
		$this->getPSDefaultOptions();
	}
	
	
	function insertPreloads(){
		
		global $wpdb;
		
		//Preload the Standard Widget layout
		if(!$wpdb->get_var("SELECT layout_id FROM " 
			. $wpdb->prefix."bwbps_layouts WHERE layout_name = 'Std_Widget'")){
		
			$d['layout_name'] = 'Std_Widget';
			
			$d['cells_perrow'] = 0;
			$d['layout'] = "
			<div class='bwbps_image'>[thumb_linktoimage]</div>
			";
			$d['alt_layout'] = "";
			$d['wrapper'] = "";
			$d['css'] = "";
			
			$d['pagination_class'] = "bwbps_pagination";
						
			$wpdb->insert($wpdb->prefix."bwbps_layouts", $d);
					
		}
		
		unset($d);
		
		//Preload the Media RSS layout
		if(!$wpdb->get_var("SELECT layout_id FROM " 
			. $wpdb->prefix."bwbps_layouts WHERE layout_name = 'media_rss'")){
		
			$d['layout_name'] = 'media_rss';
			
			$d['cells_perrow'] = 0;
			$d['layout'] = "
<item>
	<title><![CDATA[[caption]]]></title>
	<description><![CDATA[]]></description>
	<link><![CDATA[]]></link>
	<media:content url='[image_url]' medium='image' />
	<media:title><![CDATA[[caption]]]></media:title>
	<media:description><![CDATA[]]></media:description>
	<media:thumbnail url='[thumb_url]' width='100' height='75' />
	<media:keywords><![CDATA[]]></media:keywords>
	<media:copyright><![CDATA[Copyright (c) [blog_name]]]></media:copyright>
</item>
			";
			$d['alt_layout'] = "";
			$d['wrapper'] = "";
			$d['css'] = "";
			
			$d['pagination_class'] = "bwbps_pagination";
						
			$wpdb->insert($wpdb->prefix."bwbps_layouts", $d);
					
		}
		
		unset($d);

		//Preload the Gallery Viewer Layout
		if(!$wpdb->get_var("SELECT layout_id FROM " 
			. $wpdb->prefix."bwbps_layouts WHERE layout_name = 'gallery_viewer'")){
		
			$d['layout_name'] = 'gallery_viewer';
			
			$d['cells_perrow'] = 0;
			$d['layout'] = "
<div class='bwbps_galviewer'>
	<div class='bwbps_galviewer_head'>
		<a href='[gallery_url]' title='Gallery: 
		[image_gallery_name]'>
		[image_gallery_name length=16] ([gallery_image_count])</a>
	</div>
	<div class='bwbps_image'>
		<a href='[gallery_url]' title='Gallery: 
		[image_gallery_name]'>
		[thumb_image]</a>
	</div>
</div>
			";
			$d['alt_layout'] = "";
			$d['wrapper'] = "<h2>Galleries:</h2>";
			$d['css'] = "";
			
			$d['pagination_class'] = "bwbps_pag_2";
						
			$wpdb->insert($wpdb->prefix."bwbps_layouts", $d);
					
		}
		
		unset($d);
		
		//Preload the Gallery Layouts for gallery viewer
		if(!$wpdb->get_var("SELECT layout_id FROM " 
			. $wpdb->prefix."bwbps_layouts WHERE layout_name = 'gallery_view_layout'")){
		
			$d['layout_name'] = 'gallery_view_layout';
			
			$d['cells_perrow'] = 0;
			$d['layout'] = "
<li class='psgal_[gallery_id]'>
	<div class='bwbps_image bwbps_relative'>
		<a rel='lightbox[album_[gallery_id]]' href='[image_url]' title='[caption_escaped]'>[thumb_image]</a>
[ps_rating]
		<div class='bwbps_postlink_top_rt bwbps_postlink'>
			<a href='[post_url]' title='Visit image page.'>
				<img src='[plugin_url]/photosmash-galleries/images/post-out.png' />
			</a>
		</div>
	</div>
	<div style='clear: both;'>
		<a rel='lightbox[caption_[gallery_id]]' href='[image_url]' title='[caption_escaped]'>
			[caption length=20]
		</a>
	</div>
</li>
			";
			$d['alt_layout'] = "";
			$d['wrapper'] = "<span style='float:right;'>[piclens]</span><div class='clear'></div>
<h3>Gallery: [gallery_name]</h3>
<div class='bwbps_gallery_container0'>
<ul class='bwbps_gallery'>
[gallery]
</ul>
<div style='clear:both;'></div>
</div>
";
			$d['css'] = "";
			
			$d['pagination_class'] = "bwbps_pag_2";
						
			$wpdb->insert($wpdb->prefix."bwbps_layouts", $d);
					
		}
		
		unset($d);
		
		//Preload the Image Viewer Layout
				
			$d['layout_name'] = 'image_view_layout';
			
			$d['cells_perrow'] = 0;
			$d['layout'] = "
<div class='bwbps_galviewer' style='width:100%; text-align: center;'>
	<div class=''>
		<a rel='lightbox[album_[gallery_id]]' href='[image_url]' title='[caption_escaped]'>[medium]</a>
	</div>
	<div style='clear: both;'>
			[caption]
	</div>
	<h3 style='width: 100%; text-align: center;'>Meta Data</h3>
	<table class='bwbps-meta-table' style='margin: 10px auto !important; text-align: left;'>
		<tr><th>Contributor:</th><td>[author_link]</td></tr>
		<tr><th>Date added:</th><td>[date_added]</td></tr>
		<tr><th>Related Post:</th><td><a href='[post_url]'>[post_name]</a></td></tr>
		<tr><th>Attribution:</th><td>[img_attribution]</td></tr>
		<tr><th>License:</th><td>[img_license]</td></tr>
	</table>
	<h3 style='width: 100%; text-align: center;'>EXIF Data</h3>
	[exif_table no_exif_msg='No EXIF data available' show_blank=false]
</div>
";
			$d['alt_layout'] = "";
			$d['wrapper'] = "";
			$d['css'] = "";
			
			$d['pagination_class'] = "bwbps_pag_2";
		
		// Had a bug in the html in 0.7.03...must update it
		if(!$wpdb->get_var("SELECT layout_id FROM " 
			. $wpdb->prefix."bwbps_layouts WHERE layout_name = 'image_view_layout'")){
						
			$wpdb->insert($wpdb->prefix."bwbps_layouts", $d);
					
		} else {
			
			
			$where['layout_name'] = 'image_view_layout'; 
			$wpdb->update($wpdb->prefix."bwbps_layouts", $d, $where);
		}
		
		unset($d);
		
		//Preload Pixoox Sharing Hub
		if(!$wpdb->get_var("SELECT hub_id FROM " 
			. $wpdb->prefix."bwbps_sharinghubs WHERE hub_name = 'Pixoox'")){
		
			$d['hub_name'] = 'Pixoox';
			$d['hub_description'] = 'The Official PhotoSmash sharing hub';
			$d['tags'] = 'wordpress, photosmash, photo sharing';
			$d['hub_url'] = 'http://pixoox.com';
			$d['api_url'] = 'http://pixoox.com/api/';
			$d['allows_adult'] = 0;
			$d['admin_email'] = 'api@pixoox.com';
			$d['restricts_categories'] = 1;
						
			$wpdb->insert($wpdb->prefix."bwbps_sharinghubs", $d);
					
		}
		
		unset($d);
		
	}
	
	//Returns an array of default options
	function getPSDefaultOptions()
	{
		$psOptions = get_option($this->adminOptionsName);
		if(!empty($psOptions))
		{
			//Options were found..add them to our return variable array
			foreach ( $psOptions as $key => $option ){
				$psAdminOptions[$key] = $option;
			}
		}else{
			$psAdminOptions = array(
				'auto_add' => 0,
				'img_perrow' => 0,
				'img_perpage' => 0,
				'thumb_width' => 110,
				'thumb_height' => 110,
				'img_rel' => 'lightbox',
				'add_text' => 'Add Photo',
				'gallery_caption' => 'PhotoSmash Gallery',
				'upload_form_caption' => 'Select an image to upload:',
				'img_class' => 'ps_images',
				'img_alerts' => 3600,
				'show_caption' => 1,
				'show_imgcaption' => 1,
				'contrib_role' => 10,
				'img_status' => 0,
				'last_alert' => 0,
				'use_advanced' => 0,
				'use_customform' => 0
			);
			update_option($this->adminOptionsName, $psAdminOptions);
		}
		
		return $psAdminOptions;
	}

	
}

?>