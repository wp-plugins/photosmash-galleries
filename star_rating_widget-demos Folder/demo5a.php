<?php require('./demo5.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Star Rating widget Demo5 Page</title>
	
	<!-- demo page css -->
	<link rel="stylesheet" type="text/css" href="css/demos.css"/>

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
		<h4>Two interacting Star widgets (<a href="demo5a.php" class="unlink">before</a>|<a href="demo5b.php" class="">after</a>)</h4>

		
		<?php if (isset($post_message)): ?>
			<div class="message-box ok">Thanks, vote saved: <?php echo $post_message ?></div>
		<?php endif; ?>

		
		<div class="ratings">
			<?php $db = get_votes(); $avg = round($db['avg']) ?>
			
			<div class="rating-L">
				<strong>Average rating</strong>
				<span>(<span id="all_votes"><?php echo $db['votes'] ?></span> votes; <span id="all_avg"><?php echo $db['avg'] ?></span>)</span>
				
				<form id="avg" style="width: 200px">
					
					<?php foreach (get_options() as $id => $title): ?>
						<input type="radio" name="rate_avg" value="<?php echo $id ?>" title="<?php echo $title ?>" disabled="disabled" <?php echo $id==$avg ? 'checked="checked"' : '' ?> />
					<?php endforeach; ?>

				</form>
			</div>


			<div class="rating-R">
				<strong>Rate this:</strong>
				<span id="caption"></span>

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
