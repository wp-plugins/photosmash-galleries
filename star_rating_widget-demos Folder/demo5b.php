<?php require('./demo5.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Star Rating widget Demo5 Page</title>
	
	<!-- demo page css -->
	<link rel="stylesheet" type="text/css" href="css/demos.css"/>
	<style type="text/css">
		#messages {margin-left:1em;float:left;line-height:15px;color:#fd1c24}
	</style>

	<!-- demo page js -->
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/ui.core.min.js"></script>

	
	<!-- Star Rating widget stuff here... -->
	<script type="text/javascript" src="js/ui.stars.js"></script>
	<link rel="stylesheet" type="text/css" href="css/ui.stars.css"/>

	<script type="text/javascript">
		$(function(){
			$("#avg").children().not(":input").hide();
			$("#rat").children().not("select, #messages").hide();
			
			// Create stars for: Average rating
			$("#avg").stars();
			
			// Create stars for: Rate this
			$("#rat").stars({
				inputType: "select",
				cancelShow: false,
				captionEl: $("#caption"),
				callback: function(ui, type, value)
				{
					// Disable Stars while AJAX connection is active
					ui.disable();

					// Display message to the user at the begining of request
					$("#messages").text("Saving...").stop().css("opacity", 1).fadeIn(30);
					
					// Send request to the server using POST method
					/* NOTE: 
						The same PHP script is used for the FORM submission when Javascript is not available.
						The only difference in script execution is the returned value. 
						For AJAX call we expect an JSON object to be returned. 
						The JSON object contains additional data we can use to update other elements on the page.
						To distinguish the AJAX request in PHP script, check if the $_SERVER['HTTP_X_REQUESTED_WITH'] header variable is set.
						(see: demo5.php)
					*/ 
					$.post("demo5.php", {rate: value}, function(db)
					{
							// Select stars from "Average rating" control to match the returned average rating value
							$("#avg").stars("select", Math.round(db.avg));
							
							// Update other text controls...
							$("#all_votes").text(db.votes);
							$("#all_avg").text(db.avg);
							
							// Display confirmation message to the user
							$("#messages").text("Rating saved (" + value + "). Thanks!").stop().css("opacity", 1).fadeIn(30);
							
							// Hide confirmation message and enable stars for "Rate this" control, after 2 sec...
							setTimeout(function(){
								$("#messages").fadeOut(1000, function(){ui.enable()})
							}, 2000);
					}, "json");
				}
			});

			// Since the <option value="3"> was selected by default, we must remove selection from Stars.
			$("#rat").stars("selectID", -1);

			// Create element to use for confirmation messages
			$('<div id="messages"/>').appendTo("#rat");
		});
	</script>
	

</head>

<body>


	<div class="pageDesc">
		<p>
			Advanced demo with two interacting Star widgets
		</p>
		<p>
			NOTE: The same PHP script is used to handle both AJAX and non-AJAX requests.<br>
			Check the source files for more info and comments. You'll need a PHP server to run this demo.
		</p>
	</div>


	<div class="pageBody">
		<h4>Two interacting Star widgets (<a href="demo5a.php" class="">before</a>|<a href="demo5b.php" class="unlink">after</a>)</h4>

		
		<?php if (isset($post_message)): ?>
			<div class="message-box ok">Thanks, vote saved: <?php echo $post_message ?></div>
		<?php endif; ?>

		
		<div class="ratings">
			<?php $db = get_votes(); $avg = round($db['avg']) ?>
			
			<div class="rating-L"><strong>Average rating</strong>
			<span>(<span id="all_votes"><?php echo $db['votes'] ?></span> votes; <span id="all_avg"><?php echo $db['avg'] ?></span>)</span>
				<form id="avg" style="width: 200px">
					
					<?php foreach (get_options() as $id => $title): ?>
						<input type="radio" name="rate_avg" value="<?php echo $id ?>" title="<?php echo $title ?>" disabled="disabled" <?php echo $id==$avg ? 'checked="checked"' : '' ?> />
					<?php endforeach; ?>

				</form>
			</div>


			<div class="rating-R"><strong>Rate this:</strong> <span id="caption"></span>
				<form id="rat" action="" method="post">
					
					<select name="rate">
						<?php foreach (get_options() as $id => $title): ?>
							<option <?php echo $id==3 ? 'selected="selected"' : '' ?> value="<?php echo $id ?>"><?php echo $title ?></option>
						<?php endforeach; ?>
					</select>

					<input type="submit" value="Rate it!" />

				</form>
			</div>

		</div>


	</div>


</body>
</html>
