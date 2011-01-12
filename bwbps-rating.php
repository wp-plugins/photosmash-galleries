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
	var $rating_nonce;
	
	function BWBPS_Rating(){
			
		$this->rating_nonce = wp_create_nonce  ('bwbps-image-rating');
			
	}
	

	function set_score($score, $is_vote = false){
		global $wpdb,  $user_ID;
		
		$data['image_id'] = (int)$_REQUEST['image_id'];
		$data['gallery_id'] = (int)$_REQUEST['gallery_id'];
		$data['poll_id'] = (int)$_REQUEST['poll_id'];
		
		
		if((int)$user_ID == 0){
		
			$data['user_ip'] = $this->getUserIP();
			
			if( !$data['user_ip'] ){
				echo "Voting requires login or IP.";
				return;
			}
			
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
				$ret = $this->updateSummaries($data);
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
			
			$summaries_data = $data;
			
			$data['comment'] = '';
			
			$ret = $wpdb->insert(PSRATINGSTABLE, $data);
			$rating_id = $wpdb->insert_id;
			
			if($ret){		
			
				//Update the Summary tables
				$ret = $this->updateSummaries($summaries_data);
				echo "Vote added.";
				
			} else {
				
				echo "Vote failed.";
			
			}
						
		}
				
		return;
			

	}
	
	function updateSummaries(&$data){
	
		//Figure out Poll Type
		switch ($data['poll_id']){
			
			case -1 :
				$poll_type = "stars";
				break;
				
			case -2 :
				$poll_type = "vote";
				break;
			
			case -3 :
				$poll_type = "vote";
				break;
			
			default :
				//when extension is written, we'll need to retrieve poll type from db
				$poll_type = "stars";
				break;			
		
		}
		
		if($poll_type == "stars"){
			$this->updateStarSummaries(&$data);
		} else {
			$this->updateVoteSummaries(&$data);
		}
	
	}
	
	function updateVoteSummaries(&$data){
		global $wpdb;
			
	
		$query = $wpdb->get_row("SELECT SUM(rating) as sum_rating, "
			. " COUNT(rating) as rating_cnt FROM " . PSRATINGSTABLE 
			. " WHERE image_id = " . (int)$data['image_id'] 
			. " AND gallery_id = " . (int)$data['gallery_id'] 
			. " AND poll_id = " . (int)$data['poll_id'], ARRAY_A );
			
	
			$upd['votes_sum'] = 	$query['sum_rating'];
			$upd['votes_cnt'] = $query['rating_cnt'];
				
		
		$where['image_id'] = $data['image_id'];
		//Update Images table first...only uses image_id and poll_id in where
		$ret2 = $wpdb->update(PSIMAGESTABLE, $upd, $where);
		
		//Update Ratings Summary table
			$sum['avg_rating'] = $upd['votes_sum'];
			$sum['rating_cnt'] = $upd['votes_cnt'];
			$where['gallery_id'] = $data['gallery_id'];
			$where['poll_id'] = $data['poll_id'];
			$ret2 = $wpdb->update(PSRATINGSSUMMARYTABLE, $sum, $where);
			
			
			if(! $ret2 ){
			
				$sum['image_id'] = $data['image_id'];
				$sum['gallery_id'] = $data['gallery_id'];
				$sum['poll_id'] = $data['poll_id'];
				$wpdb->insert(PSRATINGSSUMMARYTABLE, $sum);
			
			}	
		
		return;
	}
	
	
	function updateStarSummaries(&$data){
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
				$upd['poll_id'] = $data['poll_id'];
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
		
		switch ($o_rating['poll_id']) {
		
			case 0 :
				$ret .=  $this->getRatingHTML($o_rating, $status);
				break;
			
			case -2 :
				$ret .=  $this->getVotingHTML($o_rating, 0);
				break;
				
			case -3 :
				$ret .=  $this->getVotingHTML($o_rating, 1);
				break;
			
			case -1 :
				$ret .=  $this->getRatingHTML($o_rating, $status);
				break;
		}	
		
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
	
		global $bwbPS;

		$nonce = $this->rating_nonce;
		
		$vars = "image_id=".(int)$o_rating['image_id']
			. '&gallery_id='.$o_rating['gallery_id']."&poll_id=".(int)$o_rating['poll_id']."&_wpnonce="
			.$nonce;

		$position = $o_rating['rating_position'] ? "bwbps-rating-incaption" : 'bwbps-rating bwb-top-right';
		$cur = round($o_rating["avg_rating"],0);
		$avg = round($o_rating["avg_rating"],1);
		$ret = '
		<div id="psstar-' . $o_rating['gallery_id'] . '-'.$o_rating["image_id"].'" class="'
		. $position . ' bwbps-rating-gal-' . $o_rating['gallery_id'] . '">&nbsp;</div>
		';
		
		//Add JavaScript to the PhotoSmash JS Footer 
		$bwbPS->addFooterReady('		
	jQuery("#psstar-' . $o_rating['gallery_id'] . '-'.$o_rating["image_id"].'").psrating("' .$vars. '", {maxvalue: 5, curvalue: '
		.$cur.', rating_cnt: ' .(int)$o_rating["rating_cnt"]. ', avg_rating: ' . $avg 
		. ', rating_position: ' . (int)$o_rating["rating_position"] . ', allow_rating: '
		. (int)$o_rating["allow_rating"] . '});');
		
		return $ret;
	
	}
	
	/*
	 * GET Thumbs up/down voting HTML Block
	 * Returns the rating Block
	 *
	 * @params $o_rating - an array that includes entity, gallery, poll, etc
	 * @params $status - is either the status of "already voted" or the Ratings Form
	*/
	function getVotingHTML($o_rating, $up_only) {
		
		global $bwbPS;
		
		$nonce = $this->rating_nonce;
				
		$vars = "image_id=".(int)$o_rating['image_id']
			. "&gallery_id=".$o_rating['gallery_id']."&poll_id=".(int)$o_rating['poll_id']."&_wpnonce="
			.$nonce . "&uponly=" . (int)$up_only;

		$position = $o_rating['rating_position'] ? "bwbps-voting-incaption" : 'bwbps-voting bwbps-voting-bkg bwb-top-right';
		$curvalue = (int)$o_rating["votes_sum"];
		$cnt = (int)$o_rating["votes_cnt"];
		$ret = '
		<div id="psvote-' . $o_rating['gallery_id'] . '-'.$o_rating["image_id"].'" class="'
		. $position . ' bwbps-rating-gal-' . $o_rating['gallery_id'] . '">&nbsp;</div>
		';
		
		//Add JavaScript to the PhotoSmash JS Footer 
		$bwbPS->addFooterReady('jQuery("#psvote-' . $o_rating['gallery_id'] . '-'.$o_rating["image_id"].'").psvoting("' 
		.$vars. '", {maxvalue: 1, curvalue: '
		.$curvalue.', rating_cnt: ' .$cnt. ', avg_rating: ' . $curvalue 
		. ', rating_position: ' . (int)$o_rating["rating_position"] . ', allow_rating: '
		. $o_rating["allow_rating"] . ', uponly: ' . $up_only.'});');

		return $ret;
	
	}
	
	
	/*
	 * GET Favorites HTML Block
	 * Returns the favorites Block
	 *
	 * @params $o_rating - an array that includes entity, gallery, poll, etc
	 * @params $status - is either the status of "already voted" or the Ratings Form
	*/
	function getFavoritesHTML($layout, $favpos) {
		
		global $bwbPS;
		
		$favlink = "<a class='bwbps-fav-{img} bwbps-fav-{favstate} bwbps-fav-link' href='javascript: void(0);' onclick='bwbpsSaveFavorite({img}, \"$this->rating_nonce\"); return false;' title='Click to favorite. {favcnt} favorites so far.'>fav</a>";
		if($layout){
			$f = $favlink;
		} else {
			switch ((int)$favpos){
				
					case 1 :
						$favpos = "top-left";
						break;
					case 2 :
						$favpos = "top-right";
						break;
					case 3 :
						$favpos = "bottom-left";
						break;
						
					case 4 :
						$favpos = "bottom-right";
						break;
						
					case 5 :
						$favpos = "top-midright";
						break;
					default :
						$favpos = "top-left";
						break;
			}
			
			
			$f = "<div id='psfav_{gal}_{img}' class='bwbps-fav-container bwbps-$favpos'>$favlink</div>";
		}
		return $f;
	}

	

}


