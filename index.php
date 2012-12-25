<?
	require_once('Cronlib.php');
	require_once('cronlib_hook.php');
?>
<!DOCTYPE html>
<html>
	<!-- Start Head -->
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<meta http-equiv="Cache-Control" content="no-cache" />
		
		<title>Crontabs</title>
		
		<!-- jQuery -->	
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		
		<!-- Main CSS -->
		<link rel="stylesheet" type="text/css" href="/assets/css/main.css">
	</head>
	<!-- End Head -->
	<!-- Start Body -->
	<body>
		<!-- Start Main Content 
		
		
		#       *     *     *   *    *        command to be executed
		#       -     -     -   -    -
		#       |     |     |   |    |
		#       |     |     |   |    +----- day of week (0 - 6) (Sunday=0)
		#       |     |     |   +------- month (1 - 12)
		#       |     |     +--------- day of month (1 - 31)
		#       |     +----------- hour (0 - 23)
		#       +------------- min (0 - 59)
		-->
		
		<div class="content">
			<div class="banner">
				<a href="/" class="banner" title="Crontabs">
					Crontabs
				</a>
			</div>
			<ul>
			<?php
			
				// Some optional options
				$options = array (
					'username' => '',
					'password' => '',
					'key_file' => '',
					'key_password' => ''
				);
				
				// Create a new instance of Cronlib
				// Change out the ip (127.0.0.1) and port (22) for the remote server you wish to manage
				$cron = new Cronlib('127.0.0.1', '22', $options);
				
				foreach( $cron->jobs() as $key => $job ){
					if ($job[2] == true){
						$class = 'active';
						$active = '1';
					}
					else{
						$class = '';
						$active = '0';
					}
					$time = implode($job[0],' ');
					echo "<li class=\"transition $class\">";
						echo "<span class=\"key\">$key.)</span>";
						echo '<div class="lock"></div>';
						echo '<div class="deep-focus">';
							echo '<form name="crontab'.time().'" method="post"  action="/">';
								echo "<input type=\"hidden\" name=\"index\" value=\"$key\">";
								echo '<input type="text" name="delete" class="trash transition" value="0">';
								echo '<div class="time">';								
									echo '<input type="text" tabindex="'.$key.'1" size="'.(strlen($time)+1).'" name="time" class="transition" value="'.$time.'">';
								
									//echo "<input type=\"text\" name=\"minutes\" value=\"{$job[0][0]}\"><input type=\"text\" name=\"hours\" value=\"{$job[0][1]}\"><input type=\"text\" name=\"days_of_month\" value=\"{$job[0][2]}\"><input type=\"text\" name=\"months\" value=\"{$job[0][3]}\"><input type=\"text\" name=\"days_of_week\" value=\"{$job[0][4]}\">";
								
								echo '</div>';
								echo '<div class="command">';
									echo '<input type="text" tabindex= "'.$key.'2" size="'.(strlen($job[1])+1).'" name="command" class="transition" value="'.$job[1].'">';
								echo '</div>';
								echo '<div class="utils" tabindex= "-1">';
									echo "<div class=\"switch $class\">";
									echo '<span class="switch-label">Active:</span>';
										echo '<span class="thumb"></span>';
										echo "<input type=\"hidden\" name=\"active\" value=\"$active\">";
									echo '</div>';
									echo '<input class="transition" type="submit" tabindex= "'.$key.'4" value="Submit">';
								echo '</div>';
							echo '</form>';
							echo '<span class="notice transition"></span>';
						echo '</div>';
					echo '</li>';
				}
			?>
			</ul>
	 	</div>
	 	<!-- End Main Content -->
		<!-- Start Footer -->
		<div class='footer'>
			
		</div>
		<!-- End Footer -->
		<script src="assets/js/main.js"></script>
	</body>
	<!-- End Body -->
</html>