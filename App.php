<?php


include_once 'tools/src/Helpers.php';

use Brunodebarros\Gitdeploy\Helpers;

class App {
	
	function push($server = "production", $password = null) {
		$config = parse_ini_file ( "deploy.ini", true );
		$prog_dir = realpath ( dirname ( __FILE__ ) ) . "/";
		if (empty ( $server )) {
			$server = "production";
		}
		
		echo "Script Dir :" . $prog_dir . "\n";
		echo "##### config #############";
		foreach ( $config [$server] as $key => $value ) {
			echo "[" . $key . "] = " . $value . "\n";
		}
		echo "##########################";
		
		passthru ( "php " . $prog_dir . "/git-deploy deploy.ini" );
	}
	
	function deploy($server = "production", $password = null) {
		$config = parse_ini_file ( "deploy.ini", true );
		$prog_dir = realpath ( dirname ( __FILE__ ) ) . "/";
		
		if (empty ( $server )) {
			$server = "production";
		}
		
		self::println ( "ScriptDir: " . $prog_dir );
		self::drawLine ();
		foreach ( $config [$server] as $key => $value ) {
			echo "[" . $key . "] = " . $value . "\n";
		}
		self::drawLine ();
		
		if ($password == null && isset ( $config [$server] ["pass"] )) {
			$password = $config [$server] ["pass"];
		} else if ($password == null) {
			self::println ( "Enter Password" );
			$stdin = fopen ( 'php://stdin', 'r' );
			$password = trim ( fgets ( $stdin ) );
			fclose ( $stdin );
		}
		
		// Set Default Configs for main directory
		//$config [$server] ["clean_directories"] = array_merge($config [$server] ["clean_directories"],array (
		//		'build' 
		//));
		$config [$server] ["ignore_files"] = array_merge($config [$server] ["ignore_files"],array (
				'lib/*'
				//,'build/*' 
		));
		$config [$server] ["upload_untracked"] = array_merge($config [$server] ["upload_untracked"],array (
				'lib/composer','lib/autoload.php'
		));
		$config [$server] ["pass"] = $password;
		
		self::drawLine ();
		print_r($config [$server]);
		self::drawLine ();
		
		// Empty build folder
		// $this->delete_dir("build");
		if (! mkdir ( "build/deploy", 0777 )) {
			self::println ( "Error: driectory problem, while creating build/deploy" );
		}
		
		// Write Config File for main project
		$this->write_ini_file ( $config, "build/deploy/deploy.ini", true );
		
		self::println ( "Executing: " . "php " . $prog_dir . "git-deploy build/deploy.ini" );
		
		passthru ( "php " . $prog_dir . "/git-deploy build/deploy/deploy.ini" );
		unlink ( "build/deploy/deploy.ini" );
		
		// No need o delete lib and buld in library cud be harmful
		unset ( $config [$server] ["clean_directories"] );
		unset ( $config [$server] ["ignore_files"] );
		unset ( $config [$server] ["upload_untracked"] );
		
		
		// Upload Libraries..
		chdir ( "lib" );
		$vendors = array_filter ( glob ( '*' ), 'is_dir' );
		self::println ( "Libraries to upload:" );
		print_r ( $vendors );
		try {
			$vendors = array_filter ( glob ( '*' ), 'is_dir' );
			self::println ( "Libraries to upload:" );
			print_r ( $vendors );
			
			$host_path = $config [$server] ['path'];
			foreach ( $vendors as $vendor ) {
					chdir ( $vendor );
					self::println ( "Directory: " . $vendor );
					$libs = array_filter ( glob ( '*' ), 'is_dir' );
					self::println ( "Directorirs: " . implode ( ",", $libs ) );
					foreach ( $libs as $lib ) {
						if (! ($vendor == "rudrax" && $lib == "application")) {
							self::drawLine ();
							chdir ( $lib );
							self::println ( "Package: " . $vendor . "/" . $lib );
							$lib_ini_file = "../../../build/deploy/" . $vendor . "-" . $lib . ".ini";
							$config [$server] ['path'] = $host_path . "lib/" . $vendor . "/" . $lib;
							$config [$server] ['pass'] = $password;
							$this->write_ini_file ( $config, $lib_ini_file, true );
							$this->create_remote ( $config [$server] );
							self::println ( "Executing: " . "php " . $prog_dir . "git-deploy " . $lib_ini_file );
							passthru ( "php " . $prog_dir . "/git-deploy " . $lib_ini_file );
							unlink ( $lib_ini_file );
							chdir ( ".." );
						}
					}
					chdir ( ".." );
			}
			$this->delete_dir ( "build" );
		} catch ( Exception $e ) {
			self::println ( "Error: Some error in libraries uploaded" );
		}
	}
	public static function drawLine() {
		echo "\n###################################################################################################\n";
	}
	public static function println($msg) {
		Helpers::logmessage ( $msg );
	}
	function create_remote($config, $path = null) {
		self::println ( "Creating Remote : " . $config ['path'] );
		// set up basic connection
		$conn_id = ftp_connect ( $config ['host'] );
		// login with username and password
		$login_result = ftp_login ( $conn_id, $config ['user'], $config ['pass'] );
		
		$mypath = ($path != null) ? $path : $config ['path'];
		// try to create the directory $dir
		try {
			if(!ftp_is_dir($conn_id,$mypath)){
				$folders = explode ( "/", $mypath );
				$new_path = "";
				foreach ( $folders as $key => $folder ) {
					$new_path = $new_path . $folder;
					if (!ftp_is_dir($conn_id, $new_path) && ! ftp_mkdir ( $conn_id, $new_path )) {
						self::println ( "Error: There might be problem while creating " . $new_path );
					}
					$new_path = $new_path . "/";
				}
			}
		} catch ( Exception $e ) {
			self::println ( "Error: There was a problem while creating " . $mypath );
		}
		// close the connection
		ftp_close ( $conn_id );
	}
	function write_ini_file($assoc_arr, $path, $has_sections = FALSE) {
		$content = "";
		if ($has_sections) {
			foreach ( $assoc_arr as $key => $elem ) {
				$content .= "[" . $key . "]\n";
				foreach ( $elem as $key2 => $elem2 ) {
					if (is_array ( $elem2 )) {
						for($i = 0; $i < count ( $elem2 ); $i ++) {
							$content .= $key2 . "[] = " . $elem2 [$i] . "\n";
						}
					} else if ($elem2 == "")
						$content .= $key2 . " = \n";
					else
						$content .= $key2 . " = " . $elem2 . "\n";
				}
			}
		} else {
			foreach ( $assoc_arr as $key => $elem ) {
				if (is_array ( $elem )) {
					for($i = 0; $i < count ( $elem ); $i ++) {
						$content .= $key . "[] = \"" . $elem [$i] . "\"\n";
					}
				} else if ($elem == "")
					$content .= $key . " = \n";
				else
					$content .= $key . " = \"" . $elem . "\"\n";
			}
		}
		
		if (! $handle = fopen ( $path, 'w' )) {
			return false;
		}
		
		$success = fwrite ( $handle, $content );
		fclose ( $handle );
		
		return $success;
	}
	function delete_dir($path) {
		if (is_dir ( $path ) === true) {
			$files = array_diff ( scandir ( $path ), array (
					'.',
					'..',
					'.gitignore' 
			) );
			
			foreach ( $files as $file ) {
				$this->delete_dir ( realpath ( $path ) . '/' . $file );
			}
			
			return; // rmdir($path);
		} 

		else if (is_file ( $path ) === true) {
			chmod ( $path, 0777 );
			return unlink ( $path );
		}
		
		return false;
	}
}

function ftp_is_dir($ftp, $dir) {
	$pushd = ftp_pwd ( $ftp );

	if ($pushd !== false && @ftp_chdir ( $ftp, $dir )) {
		ftp_chdir ( $ftp, $pushd );
		return true;
	}

	return false;
}

function process_error_backtrace($errno, $errstr, $errfile, $errline, $errcontext) {
	if (! (error_reporting () & $errno))
		return;
	switch ($errno) {
		case E_WARNING :
		case E_USER_WARNING :
		case E_STRICT :
		case E_NOTICE :
		case E_USER_NOTICE :
			$type = 'warning';
			$fatal = false;
			break;
		default :
			$type = 'fatal error';
			$fatal = true;
			break;
	}
	$trace = array_reverse ( debug_backtrace () );
	array_pop ( $trace );
	if (php_sapi_name () == 'cli') {
		if ($type == "warning" && strpos ( $errstr, "create directory: File exists" ) != false) {
			App::println ( "Warning: Cannot Create Directory, Exists" );
		} else {
			App::drawLine ();
			echo 'Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ':' . "\n";
			foreach ( $trace as $item )
				echo '  ' . (isset ( $item ['file'] ) ? $item ['file'] : '<unknown file>') . ' ' . (isset ( $item ['line'] ) ? $item ['line'] : '<unknown line>') . ' calling ' . $item ['function'] . '()' . "\n";
			App::drawLine ();
		}
	} else {
		echo '<p class="error_backtrace">' . "\n";
		echo '  Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ':' . "\n";
		echo '  <ol>' . "\n";
		foreach ( $trace as $item )
			echo '    <li>' . (isset ( $item ['file'] ) ? $item ['file'] : '<unknown file>') . ' ' . (isset ( $item ['line'] ) ? $item ['line'] : '<unknown line>') . ' calling ' . $item ['function'] . '()</li>' . "\n";
		echo '  </ol>' . "\n";
		echo '</p>' . "\n";
	}
	if (ini_get ( 'log_errors' )) {
		$items = array ();
		foreach ( $trace as $item )
			$items [] = (isset ( $item ['file'] ) ? $item ['file'] : '<unknown file>') . ' ' . (isset ( $item ['line'] ) ? $item ['line'] : '<unknown line>') . ' calling ' . $item ['function'] . '()';
		$message = 'Backtrace from ' . $type . ' \'' . $errstr . '\' at ' . $errfile . ' ' . $errline . ': ' . join ( ' | ', $items );
		error_log ( $message );
	}
	if ($fatal)
		exit ( 1 );
}

set_error_handler ( 'process_error_backtrace' );