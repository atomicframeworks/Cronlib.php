<?php

	// Require the phpseclib SSH2 library
	require_once('Net/SSH2.php');
	
	// Require the RSA library (can remove if not using key to auth)
	require_once('Crypt/RSA.php');

	////
	//  Cronlib Class -
	//  	The Crontab Manager
	
	class Cronlib {
				
		////
		//  Flag - All Cronlib directives must be between the flag string
		public $flag = "# Cronlib\n";
		
		////
		//  IP - The IP of the server to edit crontabs on
		public $ip;
		
		////
		//  Port - The SSH port of the server to edit crontabs on
		public $port;
		
		////
		//  Username - Username to edit crontabs on
		//  	Note: Leaving blank will use HTTP auth
		public $username;
		
		////
		//  Password - Password for the above user
		public $password;
		
		////
		//  Key File - Path to RSA private key file (not required) for passwordless auth
		public $key_file;
		
		////
		//  Key Password - Password for the key (if required)
		public $key_password;
		
		////
		// Construct - Init a new instance of Cronlib
		
		public function __construct($ip = '127.0.0.1', $port = '22', $options = array() ){
			// Set IP & port
			$this->ip = $ip;
			$this->port = $port;
			
			// Loop and set any valid options
			foreach($options as $key => $value){
				if( property_exists($this, $key) ){
					$this->$key = $value;
				}
			}
		}

		////
		//  Jobs - Read Crontab and return parsed (array) / formatted Cronlib directives
		
		public function jobs(){
			// Read the crontab, parse, and format directives
			return $this->format_directives(
						$this->get_cronlib() 
					);
		}
		
		
		////
		//  Create Cronlib - Write only to Cronlib section of crontab
		//		Passing an int for offset will insert at that index (0 based)
		// 		Passing nothing for offset will append the element - passing 0 will insert at the start
		//		Passing null as the directive will only add the flags
		//		Passing an empty string as the directive will add the empty string
		
		public function create($cronlib = null, $offset = null){
			// Get crontab
			$crontab = $this->get_crontab();
			
			// Get the search flag
			$flag = $this->flag;
			
			// If we are creating nothing try to just install the flags
			if (!isset($cronlib)){
				// If there are no Cronlib flags then append them
				if(!preg_match("/${flag}(.*)${flag}/msU", $crontab)){
					// Make sure crontab has no newline
					$crontab =  rtrim($crontab, "\n");
					// Append to crontab
					$new_crontab = $crontab . PHP_EOL . $flag . $flag;
				}
				else {
					// Skip writing flags because they already exist
					// This is the only time $new_crontab will be empty
					$new_crontab = '';
				}
			}
			// If there are Cronlib flags
			else if(preg_match("/${flag}(.*)${flag}/msU", $crontab)){
				// Match Cronlib directives using preg
				// 		preg flags - msU
				// 		m (PCRE_MULTILINE)
				// 		s (PCRE_DOTALL) - dot metacharacter in the pattern matches all characters, including newlines
				// 		U (PCRE_UTF8)
				preg_match("/${flag}(.*)${flag}/msU", $crontab, $matches);
				// If there is a match
				if ( !empty($matches[1]) ){
					// Trim whitespace off end of match
					$matches[1] = trim($matches[1]);
					// Parse out directives in to array
					$directives = preg_split("/(\r\n|\n|\r)/", $matches[1]);
				}
				// If offset is null append
				if(is_null($offset)){
					// If Cronlib is string convert to array
					if(is_string($cronlib)){
						$cronlib = array($cronlib);					
					}
					// Merge in directives
					array_splice($directives, count($directives), 0, $cronlib);
				}
				// If offset is int splice at offset
				else if (is_int($offset)){
					// If Cronlib is string convert to array
					if(is_string($cronlib)){
						$cronlib = array($cronlib);
					}
					// Splice the new directives in at offset
					array_splice($directives, $offset, 0, $cronlib);
				}
				// Make sure directives have newline
				foreach($directives as &$directive){
					$directive = rtrim($directive, "\n");
					$directive .= "\n";
				}
				// Implode directives to string with newlines
				$directives = implode('', $directives);

				// Create new crontab
				// - Match & append Cronlib directives using preg
				$new_crontab = preg_replace("/${flag}(.*)${flag}/msU", $flag.$directives.$flag, $crontab);
			}
			// No Cronlib flags found - install and add new directives
			else {
				// If Cronlib is string convert to array
				if(is_string($cronlib)){
					$directives = array($cronlib);
				}
				else if (is_array($cronlib)){
					$directives = $cronlib;
				}
				// Make sure directives have newline
				foreach($directives as &$directive){
					$directive = rtrim($directive, "\n");
					$directive .= "\n";
				}
				$directives = implode('', $directives);
				// Make sure crontab has no newline
				$crontab =  rtrim($crontab, "\n");
				// Append to crontab
				$new_crontab = $crontab . PHP_EOL . $flag . $directives . $flag;
			}
			// Write new crontab
			if (!empty($new_crontab)){
				$this->write_crontab($new_crontab);
			}
			else{
			    trigger_error("No new Crontab string to write", E_USER_WARNING);
			}
		}
		
		
		////
		//  Read - Parse Crontab and return (array) the selected Cronlib directives from the crontab
		//		Note: Will read from length number from offset (0 based)
		//			  A blank offset will result in all elements
		//			  A blank length will result in 1 element
		//		Note: If there is no directive at offset then return an empty array
		
		public function read($offset = null, $length = 1){
			// Return array to hold job directives
			$return = array();
			// Get Cronlib array
			$cronlib_array = $this->get_cronlib();
			// If offset is null return all directives
			if (is_null($offset)){
				$return = $cronlib_array;
			}
			// If offset is an int return the slice
			else if(is_int($offset)){
				$return = array_slice($cronlib_array, $offset, $length);
			}
			else{
			    trigger_error("Offset must be an int or null", E_USER_WARNING);
			}
			return $return;
		}
		
		
		////
		//  Update - Update at offset (int) with directive (string)
		//			Returns true if Cronlib rule existed and was updated.
		//			Returns false if there was no rule to update
		
		public function update($offset = 0, $directive = ''){
			// Set default return
			$return = false;
			// Get crontab
			$crontab = $this->get_crontab();
			// Get the search flag
			$flag = $this->flag;
			// Get Cronlib array
			$directives = $this->parse_crontab($crontab, $flag);
			
			if(isset($directives[$offset])){	
				$directives[$offset] = $directive;
				
				// Make sure directives have newline
				foreach($directives as &$directive){
					$directive = rtrim($directive, "\n");
					$directive .= "\n";
				}
				// Implode directives to string with newlines
				$directives = implode('', $directives);
				// Create new crontab
				// - Match & append Cronlib directives using preg
				$new_crontab = preg_replace("/${flag}(.*)${flag}/msU", $flag.$directives.$flag, $crontab);
				// Write new Crontab
				$this->write_crontab($new_crontab);
				// Update return to true
				$return = true;
			}
			else{
			    trigger_error("Directive #$offset is null - nothing to update.", E_USER_WARNING);
			}
			return $return;
		}
		
		////
		//  Delete Cronlib - Array Splice out # - # of Cronlib directives
		//		Works like array splice - (0 based)
		//		Passing delete(2,3) will start at the 3rd element and delete 3 elements (including 3rd)
		// 		Passing no offset will clear out all directives and flags
		// 		If length is specified and is positive, then that many elements will be removed.
		
		public function delete($offset = null, $length = 1){
			// Set default return
			$return = false;
			// Get crontab
			$crontab = $this->get_crontab();
			// Get the search flag
			$flag = $this->flag;
				
			// If no offset delete everything (including flags)
			if (is_null($offset)){
				// Erase all Cronlib directives
				$new_crontab = preg_replace("/${flag}(.*)${flag}/msU", '', $crontab);
				// Update return to true
				$return = true;
			}
			else if (is_int($length) && is_int($offset)){	
				// Get Cronlib array
				$cronlib_array = $this->parse_crontab($crontab, $flag);
				// Splice out the deleted directives
				array_splice($cronlib_array, $offset, $length);
				// Make sure directives have newline
				foreach($cronlib_array as &$directive){
					$directive = rtrim($directive, "\n");
					$directive .= "\n";
				}
				$new_crontab = implode('', $cronlib_array);
				// Erase all Cronlib directives
				$new_crontab = preg_replace("/${flag}(.*)${flag}/msU", "$flag$new_crontab$flag", $crontab);
				// Update return to true
				$return = true;
			}
			else{
			    trigger_error("Offset and length must be either null or integer.", E_USER_WARNING);
			}
			// If we have a true return then write to crontab
			if($return == true){
				// Write new crontab
				$this->write_crontab($new_crontab);	
			}
			return $return;
		}
		
		////
		//  Move - Move a Cronlib directive from offset_from  (int) to offset_to (int)
		//		Returns true if the directive is moved and false if not moved
		
		public function move($offset_from = null, $offset_to = null){
			// Set default return
			$return = false;
			
			// If the offsets are set and ints
			if ((isset($offset_from) && is_int($offset_from)) 
			&&	(isset($offset_to) && is_int($offset_to)) 
			){
				// Get crontab
				$crontab = $this->get_crontab();
				// Get the search flag
				$flag = $this->flag;
				// Get Cronlib array
				$cronlib_array = $this->parse_crontab($crontab, $flag);
				// Make sure we have a directive to splice out
				if (isset($cronlib_array[$offset_from])){
					// Splice out the selected directive
					$directive = array_splice($cronlib_array, $offset_from, 1);
					// Splice the directive in at location
				    array_splice($cronlib_array, $offset_to, 0, $directive);
					// Make sure directives have newline
					foreach($cronlib_array as &$directive){
						$directive = rtrim($directive, "\n");
						$directive .= "\n";
					}
					$new_crontab = implode('', $cronlib_array);
					// Erase all Cronlib directives
					$new_crontab = preg_replace("/${flag}(.*)${flag}/msU", "$flag$new_crontab$flag", $crontab);				
					// Write new crontab
					$this->write_crontab($new_crontab);
					// Update return to true
					$return = true;
				}
				else {
					// There is nothing set for the rule trying to be moved
				    trigger_error("Directive #$offset_from is null - nothing to move.", E_USER_WARNING);
				}
			}
			else{
				// Both offsets are required and must be int
			    trigger_error("Both offset from and to parameters must be non-empty integers.", E_USER_WARNING);				
			}
			return $return;
		}
		
		
		////
		//  Connect - SSH2 connect to a server and return the handle
		
		private function connect(){
			// Return the contents of crontab
			
			$ssh = new Net_SSH2( $this->ip, $this->port );
			
			// Use private key if available
			if( !empty($this->key_file) ){
				$rsa = new Crypt_RSA();
				$rsa->loadKey( file_get_contents($this->key_file) );
				
				// Use private key if available
				if( !empty($this->key_password) ){
					$rsa->$rsasetPassword($this->key_password);
				}
				
				// If we cannot login then exit
				if (!$ssh->login($this->username, $rsa)) {
					//$logs = $ssh->getLog();
					//print_r($logs);
					exit('Auth Failed: Type- Key');
				}
			}
			else{
				// Use class credentials if available
				if( !empty($this->username) ){
					// If we cannot login then exit
					if (!$ssh->login($this->username, $this->password)) {
						//$logs = $ssh->getLog();
						//print_r($logs);
						exit('Auth Failed: Type- Username & password');
					}
				}
				// Else use http auth
				else{
					// Set auth header if no user
					if (!isset($_SERVER['PHP_AUTH_USER']) ||
						empty($_SERVER['PHP_AUTH_USER'])
						) {
					    header('WWW-Authenticate: Basic realm="Cronlib SSL"');
					    header('HTTP/1.0 401 Unauthorized');
					    echo 'You must provide valid credentials.';
					    exit;
					} 
					else {
						// If we cannot login then exit
						if (!$ssh->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
							//$logs = $ssh->getLog();
							//print_r($logs);
							exit('Auth Failed: Type- Username & password');
						}
					}
				}
			}			
			// Return the SSH connection handle
			return $ssh;
		}
		
		
		////
		//  Read Crontab - Read and return (string) contents of the crontab
		
		private function get_crontab(){
			// Get connection handle
			$ssh = $this->connect();
			// Return the contents of crontab
			return $ssh->exec('crontab -l');
		}
		
		
		////
		//  Write Crontab - Write (string) contents of the crontab
		
		private function write_crontab($string){
			// Get connection handle
			$ssh = $this->connect();
			// Escape double quotes for echoing to terminal
			$string = str_replace('"', '\"', $string);
			// Removing any trailing newlines
			$string = rtrim($string, "\n");
			// Write to crontab
			$ssh->exec("echo \"$string\" > crnc.tmp; crontab crnc.tmp; rm crnc.tmp");
		}
		
		
		////
		//  Get Cronlib - Parse Crontab and return (string) the Cronlib directives from the crontab
		//		Note: Get Cronlib should only be used if we do not already have the crontab or flags.  
		//		If we have either it will be more efficient to use parse_crontab($contab, $flag);
		
		private function get_cronlib(){
			// Return string to hold job directives
			$directives = '';
			
			// Get the search flag
			$flag = $this->flag;
			
			// Get the Crontab string
			$crontab_string = $this->get_crontab();
			
			// Match Cronlib directives using preg
			// 		preg flags - msU
			// 		m (PCRE_MULTILINE)
			// 		s (PCRE_DOTALL) - dot metacharacter in the pattern matches all characters, including newlines
			// 		U (PCRE_UTF8)
			preg_match("/${flag}(.*)${flag}/msU", $crontab_string, $matches);
			
			// If we have a directive match set it
			if ( !empty($matches[1]) ){
				$directives = $matches[1];
				// Trim any whitespace
				$directives = trim($directives);
				// Parse out directives in to array
				$directives = preg_split("/(\r\n|\n|\r)/", $directives);				
			}
			else {
				    trigger_error("No Cronlib directives found", E_USER_WARNING);
			}
			return $directives;
		}
		
		
		////
		//  Parse flags - Parse Crontab (string)  using flag (string) and return (array) the Cronlib directives
		//		Note: This will be faster than Get Cronlib if you already have the crontab
		private function parse_crontab($crontab_string = '', $flag = ''){
			// Return string to hold job directives
			$directives = '';
			
			// Get the search flag
			if(empty($flag)){
				$flag = $this->flag;
			}
	
			// Match Cronlib directives using preg
			// 		preg flags - msU
			// 		m (PCRE_MULTILINE)
			// 		s (PCRE_DOTALL) - dot metacharacter in the pattern matches all characters, including newlines
			// 		U (PCRE_UTF8)
			preg_match("/${flag}(.*)${flag}/msU", $crontab_string, $matches);
			
			// If we have a directive match set it
			if ( !empty($matches[1]) ){
				$directives = $matches[1];
				// Trim any whitespace
				$directives = trim($directives);
				// Parse out directives in to array
				$directives = preg_split("/(\r\n|\n|\r)/", $directives);				
			}
			return $directives;
		}

		
		////
		//  Format Directives - Parse directives (array) and return (array) the Cronlib directives
		
		private function format_directives($directives){
			// Array to hold all parsed job directives
			$jobs = array();
			
			// Loop through directives
			foreach($directives as $cronjob){		
				// Split the cronjob line by whitespace
				$job_array = preg_split('/\s+/', $cronjob);
		
				// Slice out the first 5 ( time directives )
				$job_time_array = array_slice($job_array, 0, 5);
				
				// Check if commented out (inactive)
				if ( strstr($job_time_array[0], '#') ){
					// Remove the comment
					$cronjob = ltrim($cronjob, '#');
					// Trim any possible whitespace before time
					$cronjob = ltrim($cronjob);
					$job_array = preg_split('/\s+/', $cronjob);
					$job_time_array = array_slice($job_array, 0, 5);
					$active = false;
				}
				else{
					$active = true;
				}
				
				// Check if predefined cron schedule
				if( strstr($job_time_array[0], '@') ){
					$job_time_array = array($job_time_array[0],'','','','');
					if ($job_time_array[0] == '@yearly' || $job_time_array[0] == '@annually'){
						// Run once a year, midnight, Jan. 1st
						$job_time_array = array('0', '0', '1', '1', '*');
					}
					else if ($job_time_array[0] == '@monthly'){
						// Run once a month, midnight, first of month
						$job_time_array = array('0', '0', '1', '*', '*');					
					}
					else if ($job_time_array[0] == '@weekly'){
						// Run once a week, midnight on Sunday
						$job_time_array = array('0', '0', '*', '*', '0');
					}
					else if ($job_time_array[0] == '@daily'){
						// Run once a day, midnight
						$job_time_array = array('0', '0', '*', '*', '*');
					}
					else if ($job_time_array[0] == '@hourly'){
						// Run once an hour, beginning of hour
						$job_time_array = array('0', '*', '*', '*', '*');						
					}
					else if ($job_time_array[0] == '@reboot'){
						// Run at startup
						$job_time_array = array('@reboot', '', '', '', '');
					}
          // Predefined so we have 1 place of time (an @schedule string )
  				// Slice out everything after the time ( commands )
  				$job_command_string = implode(' ', array_slice($job_array, 1));
				}
        else {
          // Not predefined meaning we have 5 places of time
  				// Slice out everything after the time ( commands )
  				$job_command_string = implode(' ', array_slice($job_array, 5));
        }
					
				// Package times and command in to single array
				$job = array($job_time_array, $job_command_string, $active);
				
				array_push($jobs, $job);
			}
			return $jobs;
		}
	// End Cronlib Class
	}
?>
