<?php require('./demo4.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Star Rating widget Demo4 Page</title>
	
	<!-- demo page css -->
	<link rel="stylesheet" type="text/css" href="css/demos.css"/>
	<style type="text/css">
		.rating-L {width: 100%;}
		#rat > * {float: left; line-height: 1.4em;}
		#rating_title {padding-right: .5em;}
		#messages, #caption {padding-left: .5em;}
		#messages {color: #fd1c24;}
	</style>

	<!-- demo page js -->
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/ui.core.min.js"></script>

	
	<!-- Star Rating widget stuff here... -->
	<script type="text/javascript" src="js/ui.stars.js"></script>
	<link rel="stylesheet" type="text/css" href="css/ui.stars.css"/>

	<script type="text/javascript">
		$(function(){
			$("#rat").children().not("select, #rating_title").hide();
			
			// Create caption element
			var $caption = $('<div id="caption"/>');
			
			// Create stars
			$("#rat").stars({
				inputType: "select",
				oneVoteOnly: true,
				captionEl: $caption,
				callback: function(ui, type, value)
				{
					// Display message to the user at the begining of request
					$("#messages").text("Saving...").fadeIn(30);
					
					// Send request to the server using POST method
					/* NOTE: 
						The same PHP script is used for the FORM submission when Javascript is not available.
						The only difference in script execution is the returned value. 
						For AJAX call we expect an JSON object to be returned. 
						The JSON object contains additional data we can use to update other elements on the page.
						To distinguish the AJAX request in PHP script, check if the $_SERVER['HTTP_X_REQUESTED_WITH'] header variable is set.
						(see: demo4.php)
					*/ 
					$.post("demo4.php", {rate: value}, function(json)
					{
						// Change widget's title
						$("#rating_title").text("Average rating");
						
						// Select stars from "Average rating" control to match the returned average rating value
						ui.select(Math.round(json.avg));
						
						// Update widget's caption
						$caption.text(" (" + json.votes + " votes; " + json.avg + ")");
						
						// Display confirmation message to the user
						$("#messages").text("Rating saved (" + value + "). Thanks!").stop().css("opacity", 1).fadeIn(30);
						
						// Hide confirmation message after 2 sec...
						setTimeout(function(){
							$("#messages").fadeOut(1000)
						}, 2000);

					}, "json");
				}
			});

			// Since the <option value="3"> was selected by default, we must remove this selection from Stars.
			$("#rat").stars("selectID", -1);

			// Append caption element !after! the Stars
			$caption.appendTo("#rat");

			// Create element to use for confirmation messages
			$('<div id="messages"/>').appendTo("#rat");
		});
	</script>
	

</head>

<body>


	<div class="pageDesc">
		<p>
			Intermediate demo with Star widget being replaced by <b>&quot;Average rating&quot;</b>
		</p>
		<p>
			NOTE: The same PHP script is used to handle both AJAX and non-AJAX requests.<br>
			Check the source for more info and comments. You'll need a PHP server to run this demo.
		</p>
	</div>


	<div class="pageBody">
		<h4>Ratings replaced by Average (<a href="demo4a.php" class="">before</a>|<a href="demo4b.php" class="unlink">after</a>)</h4>

		
		<?php if (isset($post_message)): ?>
			<div class="message-box ok">Thanks, vote saved: <?php echo $post_message ?></div>
		<?php endif; ?>

		
		<div class="ratings">
			<div class="rating-L">

				<form id="rat" action="" method="post">
					
					<strong id="rating_title">Rate this: </strong>
					
					<select name="rate">
						<?php foreach (get_options() as $id => $title): ?>
							<option value="<?php echo $id ?>" <?php echo $id==3 ? 'selected="selected"' : '' ?> /><?php echo $title ?></option>
						<?php endforeach; ?>
					</select>

					<input type="submit" value="Rate it!" />

				</form>

			</div>
		</div>


	</div>


</body>
</html>
