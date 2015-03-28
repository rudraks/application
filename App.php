<?php

class App {

	function push($server="production",$password = null){
		$config = parse_ini_file("deploy.ini",true);
		$prog_dir = realpath(dirname(__FILE__))."/";
		if(empty($server)){
			$server="production";
		}
		echo "Script Dir :".$prog_dir."\n";
		echo "##### config #############";
		foreach ($config[$server] as $key=>$value){
			echo "[".$key."] = ".$value."\n";
		}
		echo "##########################";
		
		passthru("php ".$prog_dir."/git-deploy deploy.ini");
	}

	function  deploy ($server="production",$password = null){

		$config = parse_ini_file("deploy.ini",true);
		$prog_dir = realpath(dirname(__FILE__))."/";
		
		if(empty($server)){
			$server="production";
		}
		
		$this->println("Script Dir :".$prog_dir);
		$this->drawLine();
		foreach ($config[$server] as $key=>$value){
			echo "[".$key."] = ".$value."\n";
		}
		$this->drawLine();

		if($password==null){
			$this->println("Enter Password");
			$stdin = fopen('php://stdin', 'r');
			$password = trim(fgets($stdin));
			fclose($stdin);
		}
		
		//Set Default Configs for main directory
		$config[$server]["clean_directories"] = array('build');
		$config[$server]["ignore_files"] = array('lib/*','build/*');
		$config[$server]["pass"] =$password;
		
		//Empty build folder
		$this->delete_dir("build");
		if(!mkdir("build/deploy",0777)){
			$this->println("driectory problem");
		}
		
		//Write Config File for main project
		$this->write_ini_file($config, "build/deploy/deploy.ini", true);

		$this->println("Executing::"."php ".$prog_dir."git-deploy build/deploy.ini");
		
		passthru("php ".$prog_dir."/git-deploy build/deploy/deploy.ini");
		unlink("build/deploy/deploy.ini");

		//No need o delete lib and buld in library cud be harmful
		unset($config[$server]["clean_directories"]);
		unset($config[$server]["ignore_files"]);

		//Upload Libraries..
		chdir("lib");
		$vendors = array_filter(glob('*'), 'is_dir');
		$this->println("Libraries to upload:");
		print_r($vendors);
		
		$host_path = $config[$server]['path'];
		foreach ($vendors as $vendor){
			if($vendor!="composer"){
				chdir($vendor);
				$this->println("Directory:".$vendor);
				$libs = array_filter(glob('*'), 'is_dir');
				$this->println("Directory:LIST".implode(",", $libs));
				foreach ($libs as $lib){
					if(!($vendor == "rudrax" && $lib == "application")){
						$this->drawLine();
						chdir($lib);
						$this->println("DIR::".getcwd());
						$lib_ini_file = "../../../build/deploy/".$vendor."-".$lib.".ini";
						$config[$server]['path'] = $host_path."lib/".$vendor."/".$lib;
						$config[$server]['pass'] = $password;
						$this->write_ini_file($config, $lib_ini_file, true);
						$this->create_remote($config[$server]);
						$this->println("Executing:"."php ".$prog_dir."git-deploy ".$lib_ini_file);
						passthru("php ".$prog_dir."/git-deploy ".$lib_ini_file);
						unlink($lib_ini_file);
						chdir("..");
					}
				}
				chdir("..");
			}
		}
		$this->delete_dir("build");
	}
	
	function drawLine(){
		echo "\n###################################################################################################\n";
	}
	function println($msg){
		echo "\n".$msg."\n";
	}

	function create_remote($config){

		// set up basic connection
		$conn_id = ftp_connect($config['host']);
		// login with username and password
		$login_result = ftp_login($conn_id, $config['user'], $config['pass']);

		// try to create the directory $dir
		try{
			ftp_mkdir($conn_id, $config['path']);
		} catch (Exception $e){
			echo "There was a problem while creating ".$config['path']."\n";
		}
		// close the connection
		ftp_close($conn_id);
	}

	function write_ini_file($assoc_arr, $path, $has_sections=FALSE) {
		$content = "";
		if ($has_sections) {
			foreach ($assoc_arr as $key=>$elem) {
				$content .= "[".$key."]\n";
				foreach ($elem as $key2=>$elem2) {
					if(is_array($elem2))
					{
						for($i=0;$i<count($elem2);$i++)
						{
							$content .= $key2."[] = \"".$elem2[$i]."\"\n";
						}
					}
					else if($elem2=="") $content .= $key2." = \n";
					else $content .= $key2." = \"".$elem2."\"\n";
				}
			}
		}
		else {
			foreach ($assoc_arr as $key=>$elem) {
				if(is_array($elem))
				{
					for($i=0;$i<count($elem);$i++)
					{
						$content .= $key."[] = \"".$elem[$i]."\"\n";
					}
				}
				else if($elem=="") $content .= $key." = \n";
				else $content .= $key." = \"".$elem."\"\n";
			}
		}

		if (!$handle = fopen($path, 'w')) {
			return false;
		}

		$success = fwrite($handle, $content);
		fclose($handle);

		return $success;
	}


	function delete_dir($path){
		if (is_dir($path) === true){
			$files = array_diff(scandir($path), array('.', '..','.gitignore'));

			foreach ($files as $file){
				$this->delete_dir(realpath($path) . '/' . $file);
			}

			return;// rmdir($path);
		}

		else if (is_file($path) === true)
		{
			chmod($path, 0777);
			return unlink($path);
		}

		return false;
	}
}
function process_error_backtrace($errno, $errstr, $errfile, $errline, $errcontext) {
	if(!(error_reporting() & $errno))
		return;
	switch($errno) {
		case E_WARNING      :
		case E_USER_WARNING :
		case E_STRICT       :
		case E_NOTICE       :
		case E_USER_NOTICE  :
			$type = 'warning';
			$fatal = false;
			break;
		default             :
			$type = 'fatal error';
			$fatal = true;
			break;
	}
	$trace = array_reverse(debug_backtrace());
	array_pop($trace);
	if(php_sapi_name() == 'cli') {
		echo 'Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ':' . "\n";
		foreach($trace as $item)
			echo '  ' . (isset($item['file']) ? $item['file'] : '<unknown file>') . ' ' . (isset($item['line']) ? $item['line'] : '<unknown line>') . ' calling ' . $item['function'] . '()' . "\n";
	} else {
		echo '<p class="error_backtrace">' . "\n";
		echo '  Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ':' . "\n";
		echo '  <ol>' . "\n";
		foreach($trace as $item)
			echo '    <li>' . (isset($item['file']) ? $item['file'] : '<unknown file>') . ' ' . (isset($item['line']) ? $item['line'] : '<unknown line>') . ' calling ' . $item['function'] . '()</li>' . "\n";
		echo '  </ol>' . "\n";
		echo '</p>' . "\n";
	}
	if(ini_get('log_errors')) {
		$items = array();
		foreach($trace as $item)
			$items[] = (isset($item['file']) ? $item['file'] : '<unknown file>') . ' ' . (isset($item['line']) ? $item['line'] : '<unknown line>') . ' calling ' . $item['function'] . '()';
		$message = 'Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ': ' . join(' | ', $items);
		error_log($message);
	}
	if($fatal)
		exit(1);
}

set_error_handler('process_error_backtrace');