<?php require('./demo4.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Star Rating widget Demo4 Page</title>
	
	<!-- demo page css -->
	<link rel="stylesheet" type="text/css" href="css/demos.css"/>

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
		<h4>Ratings replaced by Average (<a href="demo4a.php" class="unlink">before</a>|<a href="demo4b.php" class="">after</a>)</h4>


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
