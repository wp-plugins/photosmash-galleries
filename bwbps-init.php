<?php 

//Code for initializing BWB-PhotoSmash plugin when it is activated in Wordpress Plugins admin page


class BWBPS_Init{
	
	var $adminOptionsName = "BWBPhotosmashAdminOptions";
	
	//Constructor
	function BWBPS_Init(){
		//Create the PhotoSmash Tables if not exists
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . "bwbps_images";
				
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty($wpdb->charset) )
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				if ( ! empty($wpdb->collate) )
					$charset_collate .= " COLLATE $wpdb->collate";
			}
			
			//Create the Images table
			$sql = "CREATE TABLE " . $table_name . " (
				image_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				gallery_id BIGINT(20) NOT NULL,
				user_id BIGINT(20) NOT NULL,
				comment_id BIGINT(20) NOT NULL,
				image_name VARCHAR(250) NOT NULL,
				image_caption TEXT,
				file_name VARCHAR(255) NOT NULL,
				file_url VARCHAR(255),
				url VARCHAR(250) NOT NULL,
				updated_by BIGINT(20) NOT NULL,
				created_date DATETIME NOT NULL,
				updated_date TIMESTAMP NOT NULL,
				status TINYINT(1) NOT NULL,
				alerted TINYINT(1) NOT NULL,
				seq BIGINT(11) NOT NULL,
				avg_rating FLOAT(8,4) NOT NULL,
				rating_cnt BIGINT(11) NOT NULL,
				PRIMARY KEY   (image_id),
				INDEX (gallery_id)
				)  $charset_collate;";
			dbDelta($sql);
			
			//Create the Image Ratings table (future use)
			$table_name = $wpdb->prefix . "bwbps_imageratings";
			$sql = "CREATE TABLE " . $table_name . " (
				rating_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				image_id BIGINT(20) NOT NULL,
				user_id BIGINT(20) NOT NULL,
				rating TINYINT(1) NOT NULL,
				comment VARCHAR(250) NOT NULL,
				updated_date TIMESTAMP NOT NULL,
				status TINYINT(1) NOT NULL,
				PRIMARY KEY   (rating_id),
				INDEX (image_id)
				)  $charset_collate;";
			dbDelta($sql);
			
			//Create the Gallery Table
			$table_name = $wpdb->prefix . "bwbps_galleries";
			$sql = "CREATE TABLE " . $table_name . " (
				gallery_id BIGINT(20) NOT NULL AUTO_INCREMENT,
				post_id BIGINT(20),
				gallery_name VARCHAR(250),
				caption TEXT,
				upload_form_caption VARCHAR(250),
				contrib_role TINYINT(1) NOT NULL,
				img_rel VARCHAR(255),
				img_class VARCHAR(255),
				thumb_aspect TINYINT(1),
				thumb_width INT(4),
				thumb_height INT(4),
				show_caption TINYINT(1),
				show_userdata TINYINT(1),
				caption_template VARCHAR(255),
				show_imgcaption TINYINT(1),
				img_status TINYINT(1),
				created_date DATETIME NOT NULL,
				updated_date TIMESTAMP NOT NULL,
				status TINYINT(1),
				PRIMARY KEY   (gallery_id))
				$charset_collate
				;";
			dbDelta($sql);
						
		//Neeed to Set PS Default Options
		$this->getPSDefaultOptions();
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
				'last_alert' => 0
			);
			update_option($this->adminOptionsName, $psAdminOptions);
		}
		
		return $psAdminOptions;
	}

	
}

?>