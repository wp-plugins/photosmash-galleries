<?php

if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}

$nonce=$_REQUEST['_wpnonce'];
if (! wp_verify_nonce($nonce, 'bwbps-image-rating') ) die('Security check');


$bwbpsuploaddir = wp_upload_dir();

define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
define("PSIMAGESTABLE", $wpdb->prefix."bwbps_images");
define("PSFAVORITESTABLE", $wpdb->prefix.'bwbps_favorites');

class BWBPS_AJAXRateImage{
	
	var $psUploader;
	var $allowNoImg = false;
	
	var $psOptions;
	
	function BWBPS_AJAXRateImage(){
		
		$this->psOptions = $this->getPhotoSmashOptions();
		
		if(isset($_REQUEST['action']) && $_REQUEST['action']){
			$action = $_REQUEST['action'];
		
			switch ($action){
				
	
				case 'rateimage':
					$this->saveImageRating();
					break;
					
				case 'voteimage':
					$this->saveImageVote();
					break;
					
				case 'favoriteimage':
					$this->toggleFavorite();
					break;
			
				default :
					break;
			}
		
		} else {
			die("Bad request");
		}
		
	}
	
	function toggleFavorite(){
		global $wpdb;
		global $current_user;
		
		$image_id = (int)$_POST['image_id'];
		
		if(!$image_id){
			$json['message'] = 'Invalid image id.';
			$json['status'] = 0;
			echo json_encode($json);
			return;	
		}
		
		if(current_user_can('level_0')){
			$json['message'] = '';
				
			$data['user_id'] = (int)$current_user->ID;
			$data['image_id'] = $image_id;
			
			$sql = "SELECT favorite_id FROM " . PSFAVORITESTABLE 
				. " WHERE user_id = " . $data["user_id"] 
				. " AND image_id = " . $image_id;
				
			$res = $wpdb->get_var($sql);
			
			if($res){
				$sql = "DELETE FROM " . PSFAVORITESTABLE 
				. " WHERE user_id = " . $data['user_id'] 
				. " AND image_id = " . $image_id;
				
				$wpdb->query($sql);
				
				$json['status'] = 0;
				
			} else {
			
				$wpdb->insert(PSFAVORITESTABLE, $data);
				$json['status'] = 1;
			}
			
			$this->updateFavoritesCount($image_id);
			
		} else {
			$json['message'] = 'Must be logged in to add favorites.';
			$json['status'] = 0;
		}
		
		
		
		echo json_encode($json);
		return;	
	
	}
	
	function updateFavoritesCount($image_id){
			global $wpdb;
			
			$query = $wpdb->get_var("SELECT "
				. " COUNT(user_id) as cnt FROM " . PSFAVORITESTABLE 
				. " WHERE image_id = " . (int)$image_id);
			
			$upd['favorites_cnt'] = (int)$query;
		
			$where['image_id'] = (int)$image_id;		
			
			//Update Images table first...only uses image_id and poll_id in where
			$ret = $wpdb->update(PSIMAGESTABLE, $upd, $where);
			
			return $ret;
	
	}
	
	
	function getPhotoSmashOptions(){
		$bwbpsOptions = get_option('BWBPhotosmashAdminOptions');
		if($bwbpsOptions && !empty($bwbpsOptions))
		{
			//Options were found..add them to our return variable array
			foreach ( $bwbpsOptions as $key => $option ){
				$opts[$key] = $option;
			}
		} else {
			$opts = false;
		}
		return $opts;
	}
	
	/*
	 * Set IMAGE RATING
	 *
	*/	
	function saveImageRating(){
		
		require_once('bwbps-rating.php');
		
		if(!$this->psOptions['rating_allow_anon'] && !is_user_logged_in()){
		
			echo "Not logged in";
			return;
		}
		
		if(isset($_POST['rating'])){
			$score = (int)$_POST['rating'];
			
			if( $score <=5 && $score >=1 ){
				$rating = new BWBPS_Rating();
				$rating->set_score($score);
								
			} else {
				echo "Invalid score";
			}
		}	
		
	}
	
	/*
	 * Set IMAGE VOTING
	 *
	*/	
	function saveImageVote(){
		
		require_once('bwbps-rating.php');
		
		if(!$this->psOptions['rating_allow_anon'] && !is_user_logged_in()){
		
			echo "log in";
			return;
		}
		
		if(isset($_POST['rating'])){
			$score = (int)$_POST['rating'];
			
			if( $score === 1 || $score === -1 ){
				$rating = new BWBPS_Rating();
				$rating->set_score($score, true);
								
			} else {
				echo "Invalid score";
			}
		}	
		
	}
		


}

$bwbpsAjax = new BWBPS_AJAXRateImage();

?>