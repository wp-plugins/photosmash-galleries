<?php


if(!defined("PSRATINGSTABLE")){
	define("PSRATINGSTABLE", $wpdb->prefix."bwbps_imageratings");
}

if(!defined("PSRATINGSSUMMARYTABLE")){
	define("PSRATINGSSUMMARYTABLE", $wpdb->prefix."bwbps_ratingssummary");
}

class BWBPS_Rating{

	var $average = 0;
	var $votes;
	var $status;
	
	function BWBPS_Rating(){
			
	}
	

	function set_score($score){
		global $wpdb,  $user_ID;
		
		$data['image_id'] = (int)$_REQUEST['image_id'];
		$data['gallery_id'] = (int)$_REQUEST['gallery_id'];
		$data['poll_id'] = (int)$_REQUEST['poll_id'];
		
		if((int)$user_ID == 0){
		
			$data['user_ip'] = $this->getUserIP();
			$data['user_id'] = 0;
			
			$voted = $wpdb->get_var($wpdb->prepare("SELECT image_id FROM ".PSRATINGSTABLE
				. " WHERE user_id = 0 AND image_id = %d AND gallery_id = %d AND poll_id = %d "
				. " AND user_ip = %s"
				, $data['image_id'], $data['gallery_id'], $data['poll_id'], $data['user_ip']
			));
						
		} else {
		
			$data['user_id'] = $user_ID;
			
			$voted = $wpdb->get_var($wpdb->prepare("SELECT image_id FROM ".PSRATINGSTABLE
			. " WHERE user_id = %d AND image_id = %d AND gallery_id = %d AND poll_id = %d "
				, $user_ID, $data['image_id'], $data['gallery_id'], $data['poll_id']));

		
		}
		
		
									
		if($voted){

			$update['rating'] = (int)$score;
			
			$ret = $wpdb->update(PSRATINGSTABLE, $update, $data);
						
			if($ret){				
				//Update the Summary tables
				$ret = $this->updateRatingSummary($data);
				echo "Vote updated.";
						
			} else {
			
				echo "No change.";
			}
		} else {
			//Insert rating
			
			//$data['user_id'] - user ID is set above
			$data['user_ip'] = $this->getUserIP();
			$data['rating'] = (int)$score;
			$data['status'] = (int)$status;
			
			$ret = $wpdb->insert(PSRATINGSTABLE, $data);
			$rating_id = $wpdb->insert_id;
			
			if($ret){		
			
				//Update the Summary tables
				$ret = $this->updateRatingSummary($data);
				echo "Vote added.";
				
			} else {
				
				echo "Vote failed.";
			
			}
						
		}
				
		return;
			
		
			
	}
	
	function updateRatingSummary(&$data){
		global $wpdb;
			
	
			$query = $wpdb->get_row("SELECT AVG(rating) as avg_rating, "
				. " COUNT(rating) as rating_cnt FROM " . PSRATINGSTABLE 
				. " WHERE image_id = " . (int)$data['image_id'] 
				. " AND gallery_id = " . (int)$data['gallery_id'] 
				. " AND poll_id = " . (int)$data['poll_id'], ARRAY_A );
													
			$upd['avg_rating'] = round($query['avg_rating'],2);
			
			$upd['rating_cnt'] = $query['rating_cnt'];
			
			$where['image_id'] = $data['image_id'];		
			
			//Update Images table first...only uses image_id and poll_id in where
			$ret2 = $wpdb->update(PSIMAGESTABLE, $upd, $where);
			
			//Update Ratings Summary table
			$where['gallery_id'] = $data['gallery_id'];
			$where['poll_id'] = $data['poll_id'];
			$ret2 = $wpdb->update(PSRATINGSSUMMARYTABLE, $upd, $where);
			
			
			if(! $ret2 ){
			
				$upd['image_id'] = $data['image_id'];
				$upd['gallery_id'] = $data['gallery_id'];
				$wpdb->insert(PSRATINGSSUMMARYTABLE, $upd);
			}
			
		
		return $ret;
		
	}
	
	
	/*
	 * GET IP Address
	 * returns a cleansed IP address for user
	 *
	*/
	function getUserIP(){
	
		$ip = preg_replace( '/[^0-9a-fA-F:., ]/', '',$_SERVER['REMOTE_ADDR'] );
		return $ip;	
	}
	
	
	/*
	 * GET RATING
	 * Returns the rating Block
	 *
	 * @params $o_rating - an array that includes entity, gallery, poll, etc
	*/
	function get_rating($o_rating){
		
		//$ret = "<div class='rating_wrapper'>"; 
		
		//$status = $this->getRatingForm($o_rating );
		switch ($o_rating['poll_id']) {
		
			case 0 :
				$ret .=  $this->getRatingHTML($o_rating, $status);
				break;
			
			case -2 :
				$ret .=  $this->getVotingHTML($o_rating);
				break;
			
			case -1 :
				$ret .=  $this->getRatingHTML($o_rating, $status);
				break;
		}	
		
		//$ret .= "</div>";
		return $ret;
	}

	
	/*
	 * GET RATING HTML
	 * Returns the rating Block
	 *
	 * @params $o_rating - an array that includes entity, gallery, poll, etc
	 * @params $status - is either the status of "already voted" or the Ratings Form
	*/
	function getRatingHTML($o_rating, $status){

		$nonce= wp_create_nonce  ('bwbps-image-rating');
		
		$vars = "image_id=".(int)$o_rating['image_id']
			. '&gallery_id='.$o_rating['gallery_id']."&poll_id=".(int)$o_rating['poll_id']."&_wpnonce="
			.$nonce;

		$position = $o_rating['rating_position'] ? "" : 'bwb-top-right';
		$cur = round($o_rating["avg_rating"],0);
		$avg = round($o_rating["avg_rating"],1);
		$ret = '
		<div id="psstar-'.$o_rating["image_id"].'" class="bwbps-rating '
		. $position . ' bwbps-rating-gal-' . $o_rating['gallery_id'] . '">&nbsp;</div>
		<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#psstar-'.$o_rating["image_id"].'").psrating("' .$vars. '", {maxvalue: 5, curvalue: '
		.$cur.', rating_cnt: ' .$o_rating["rating_cnt"]. ', avg_rating: ' . $avg 
		. ', rating_position: ' . $o_rating["rating_position"] . ', allow_rating: '
		. $o_rating["allow_rating"] . '});
		});
</script>';

		return $ret;
	
	}
	
	/*
	 * GET Thumbs up/down voting HTML Block
	 * Returns the rating Block
	 *
	 * @params $o_rating - an array that includes entity, gallery, poll, etc
	 * @params $status - is either the status of "already voted" or the Ratings Form
	*/
	function getVotingHTML($o_rating) {
		
		$o_rating['ps_poll_type'] = "voting";
		
		$nonce= wp_create_nonce  ('bwbps-image-rating');
		
		$vars = "image_id=".(int)$o_rating['image_id']
			. '&gallery_id='.$o_rating['gallery_id']."&poll_id=".(int)$o_rating['poll_id']."&_wpnonce="
			.$nonce;
			
		$ret = '
		<div id="ps-vote-'.$o_rating["image_id"].'" class="ps-voting">&nbsp;</div>
		<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#star-'.$o_rating["image_id"].'").rating("' .$vars. '", {maxvalue: 5, curvalue: '
		.$o_rating["avg_rating"].', totalratings: '
		.$o_rating["rating_cnt"].'});
		});
</script>';

		return $o_rating;
	
	}

}


