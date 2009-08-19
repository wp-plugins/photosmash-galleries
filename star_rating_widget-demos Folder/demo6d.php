<?php require('./demo6.php'); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Star Rating widget Demo6 Page</title>
	
	<!-- demo page css -->
	<link rel="stylesheet" type="text/css" href="css/demos.css"/>
	<style type="text/css">
		#container { position:relative; height:30px; }
		#container > * { position:absolute; height:30px; left:0; top:0; z-index: 1; overflow:hidden; }

		#loader { display:none; padding-left:20px; background:url(css/crystal-arrows.gif) no-repeat center left; }
	</style>
	
	<!-- demo page js -->
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/ui.core.min.js"></script>

	
	<!-- Star Rating widget stuff here... -->
	<script type="text/javascript" src="js/ui.stars.js"></script>
	<link rel="stylesheet" type="text/css" href="css/crystal-stars.css"/>

	<script type="text/javascript">
		// slowEach() plugin by "Michael Geary"
		// http://groups.google.com/group/jquery-en/browse_thread/thread/1790787be89b92bc/876c4ba8f01d8443#msg_72c18cfa10b7d7b6
		// callback2 - fired after last element, can be used to build functions chain (added by Orkan)
		jQuery.fn.slowEach = function(interval, callback, callback2) {
			var items = this, i = 0;
			if(!items.length) return;
			function next() {
				(callback.call(items[i], i, items[i]) !== false && ++i < items.length) ? setTimeout(next, interval) : callback2 && callback2.call(items, i, items);
			}
			next();
		};
		
		$(function(){
			$("#rat").children().not(":radio").hide();
			
			// Create stars
			$("#rat").stars({
				callback: function(ui, type, value)
				{
					var arr = ui.$stars.find("a");

					arr.slowEach(100, 
						function(){ $(this).animate({top: "28px"}, 300) }, 
						function()
						{
							$("#loader").fadeIn(function()
							{
								$.post("demo6.php", {rate: value}, function(db)
								{
									ui.select(Math.round(db.avg));
									$("#avg").text(db.avg);
									$("#votes").text(db.votes);

									$("#loader").fadeOut(function(){ 
										arr.slowEach(100, function(){ $(this).animate({top: 0}, 300) });
									});

								}, "json");
							});
						});
				},
				oneVoteOnly: true
			});
		});
	</script>

</head>

<body>


	<div class="pageDesc">
		<p>
			Another example of Star widget being replaced by <b>&quot;Average rating&quot;</b><br>
			Added alternate CSS file and some fancy transition effects.
		</p>
		<p>
			NOTE: The same PHP script is used to handle both AJAX and non-AJAX requests.<br>
			Check the source for more info and comments. You'll need a PHP server to run this demo.
		</p>
	</div>


	<div class="pageBody">
		<h4>Crystal (<a href="demo6a.php" class="">before</a>|<a href="demo6b.php" class="">after1</a>|<a href="demo6c.php" class="">after2</a>|<a href="demo6d.php" class="unlink">after3</a>)</h4>


		<div id="container">
			
			<form id="rat" action="" method="post">

				<?php foreach ($options as $id => $rb): ?>
					<input type="radio" name="rate" value="<?php echo $id ?>" title="<?php echo $rb['title'] ?>" <?php echo $rb['checked'].' '.$rb['disabled']  ?> />
				<?php endforeach; ?>

				<?php if (!$rb['disabled']): ?>
					<input type="submit" value="Rate it!" />
				<?php endif; ?>

			</form>

			<div id="loader"><div style="padding-top: 5px;">please wait...</div></div>

		</div>


		<?php $db = get_votes() ?>
		<div>
			Item Popularity: <span id="avg"><?php echo $db['avg'] ?></span>/<strong><?php echo count($options) ?></strong>
			(<span id="votes"><?php echo $db['votes'] ?></span> vote cast)
		</div>
	</div>


</body>
</html>
