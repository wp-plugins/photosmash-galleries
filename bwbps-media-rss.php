<?php 
//Generates a media RSS feed to support PicLens
if (!function_exists('add_action'))
{
	require_once("../../../wp-load.php");
}


/*
 *	Build the shortcode from the GET string
 *
 *	Note: we will build a shortcode and use the standard PhotoSmash shortcode function 
 *	to build the feed.  A Custom Layout will be included with PhotoSmash for Media RSS feeds
 *
 *	General attributes for the shortcode
 */
 


$aa['no_form'] = 'true';
$aa['no_gallery_header'] = 'true';
$aa['id'] = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$aa['thumb_height'] = isset($_GET['thumb_height']) ? (int)$_GET['thumb_height'] : 0;
$aa['thumb_width'] = isset($_GET['thumb_width']) ? (int)$_GET['thumb_width'] : 0;
$aa['gallery_type'] = isset($_GET['gallery_type']) ? $_GET['gallery_type'] : 0;
$aa['tags'] = isset($_GET['tags']) ? $_GET['tags'] : "";
$aa['layout'] = isset($_GET['layout']) ? $_GET['layout'] : "";
$aa['images_override'] = isset($_GET['images']) ? (int)$_GET['images'] : 25;
$aa['page'] = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$aa['no_pagination'] = 1;

$sc_atts_array = $bwbPS->filterMRSSAttsFromArray($aa, "'");

if(is_array($sc_atts_array)){
	$sc_atts .= implode(" ", $sc_atts_array);
}



$sc = "[photosmash $sc_atts ]";

//Set the file HEADER type
header("Content-Type: application/rss+xml; charset=ISO-8859-1");
echo '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
?>
    <rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/" 
        xmlns:atom="http://www.w3.org/2005/Atom">
        <channel>
<?php
	
echo do_shortcode($sc);

?>

</channel>
</rss>

<?php
die();

?>