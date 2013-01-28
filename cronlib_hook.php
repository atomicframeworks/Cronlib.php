<?php
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		if($_POST['delete'] == '1'){
			// If we are deleting this cronjob
			
			// Get index
			$index = intval($_POST['index']);
			
			// CSS selector is +1 to index (not 0 based)
			$selector = $index+1;
			
			// Delete directive at index
			$return = $cron->delete($index);
			
			// If we have a true return...
			if ($return == 1){
				//... then fade out the element and refresh the page
				$JS = "$('ul li:nth-of-type($selector)').fadeOut('3333');
						location.reload();";
			}
			else {			
				//... else show error notice
				$JS = "$('ul li:nth-of-type($selector) span.notice').html('Error!');";
			}
		}
		elseif($_POST['delete'] == '0'){
			// If we are NOT deleting this cronjob
			
			// If the command is inactive comment it out
			if($_POST['active'] == '0'){
				$comment = '# ';
			}
			elseif($_POST['active'] == '1'){
				$comment = '';
			}
			
			// Create the directive with the comment charcater + time + command
			$directive =  $comment . ltrim($_POST['time']) . ' ' . ltrim($_POST['command']);
			
			// Get the index as an int
			$index = intval($_POST['index']);
			
			// Our CSS selector must be the one higher than the index
			$selector = $index+1;

			// Update the index with the directive
			$return = $cron->update($index, $directive);
			
			// If the return is true show saved notice otherwise show error notice
			if ($return == 1){
				$JS = "$('ul li:nth-of-type($selector) span.notice').html('Saved!');";
			}
			else {
				$JS = "$('ul li:nth-of-type($selector) span.notice').html('Error!');";
			}
		}
		
		// Add flash notice logic to the JS
		$JS .= "
			setTimeout(function(){
				$('ul li:nth-of-type($selector) span.notice').addClass('show');
			},333);				
			setTimeout(function(){
				$('ul li:nth-of-type($selector) span.notice').removeClass('show');	
			},1777);";
		
		// Echo out the JS
		echo $JS;
		die;
	}
?>