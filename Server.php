<?php

namespace RudraX;

use RudraX\Utils\Console;
use RudraX\Utils\FileUtil;

error_reporting ( E_ERROR | E_PARSE );

class Server {
	public static function deploy($configName) {
		$config = parse_ini_file ( "config/project.properties", true );
		
		$prog_dir = realpath ( dirname ( __FILE__ ) ) . "/";
		
		if (empty ( $configName )) {
			$configName = "production";
		}
		
		if (! isset ( $config [$configName] )) {
			Console::error ( "Config Does not exists in your config/project.properties" );
			return false;
		}
		
		Console::block ( "Dplooying on Server with config : " . $configName );
		$password;
		
		if (isset ( $config [$configName] ["pass"] )) {
			$password = $config [$configName] ["pass"];
		} else if ($password == null) {
			Console::println ( "Enter Password" );
			$stdin = fopen ( 'php://stdin', 'r' );
			$password = trim ( fgets ( $stdin ) );
			fclose ( $stdin );
		}
		
		$serverConfig = array_merge_recursive ( $config [$configName], array (
				"clean_directories" => array (
				),
				"ignore_files" => array (
						'lib/*',"lib/","lib"
				),
				"upload_untracked" => array (
						'lib/composer',
						'lib/autoload.php',
						'build'
				) 
		) );
		$serverConfig ["pass"] = $password;
		
		Console::line ();
		print_r ( $serverConfig );
		Console::line ();
		
		// Empty build folder
		FileUtil::build_mkdir ( "deploy" );
		
		// Write Config File for main project
		self::upload ( $prog_dir, $serverConfig, "build/deploy/deploy.ini" );
		
		// No need o delete lib and buld in library cud be harmful
		unset ( $serverConfig ["clean_directories"] );
		unset ( $serverConfig ["ignore_files"] );
		unset ( $serverConfig ["upload_untracked"] );
		
		// Upload Libraries..
		chdir ( "lib" );
		$vendors = array_filter ( glob ( '*' ), 'is_dir' );
		
		Console::line ();
		Console::println ( "Libraries to upload:" );
		print_r ( $vendors );
		Console::line ();
		
		try {
			$host_path = $serverConfig ['path'];
			foreach ( $vendors as $vendor ) {
				chdir ( $vendor );
				Console::println ( "Directory: " . $vendor );
				$libs = array_filter ( glob ( '*' ), 'is_dir' );
				Console::println ( "Directorirs: " . implode ( ",", $libs ) );
				foreach ( $libs as $lib ) {
					if (! ($vendor == "rudrax" && $lib == "application")) {
						Console::line ();
						chdir ( $lib );
						Console::println ( "Package: " . $vendor . "/" . $lib );
						$lib_ini_file = "../../../build/deploy/" . $vendor . "-" . $lib . ".ini";
						$serverConfig ['path'] = $host_path . "lib/" . $vendor . "/" . $lib;
						$serverConfig ['pass'] = $password;
						
						self::mkdir ( $serverConfig );
						
						self::upload ( $prog_dir, $serverConfig, $lib_ini_file );
						
						// $this->write_ini_file ( $config, $lib_ini_file, true );
						// Console::println ( "Executing: " . "php " . $prog_dir . "git-deploy " . $lib_ini_file );
						// passthru ( "php " . $prog_dir . "/git-deploy " . $lib_ini_file );
						// unlink ( $lib_ini_file );
						
						chdir ( ".." );
					}
				}
				chdir ( ".." );
			}
			//$this->delete_dir ( "build" );
		} catch ( Exception $e ) {
			Console::println ( "Error: Some error in libraries uploaded" );
		}
	}
	public static function upload($prog_dir, $config, $config_file_path) {
		FileUtil::write_ini_file ( array (
				"production" => $config 
		), $config_file_path, true );
		
		Console::println ( "Executing: " . "php " . $prog_dir . "git-deploy " . $config_file_path );
		passthru ( "php " . $prog_dir . "/git-deploy " . $config_file_path );
		unlink ( $config_file_path );
	}
	public static function mkdir($config, $path = null) {
		Console::println ( "Creating Remote : " . $config ['path'] );
		// set up basic connection
		$conn_id = ftp_connect ( $config ['host'] );
		// login with username and password
		$login_result = ftp_login ( $conn_id, $config ['user'], $config ['pass'] );
		
		$mypath = ($path != null) ? $path : $config ['path'];
		// try to create the directory $dir
		try {
			if (! self::is_dir ( $conn_id, $mypath )) {
				$folders = explode ( "/", $mypath );
				$new_path = "";
				foreach ( $folders as $key => $folder ) {
					$new_path = $new_path . $folder;
					if (! self::is_dir ( $conn_id, $new_path ) && ! ftp_mkdir ( $conn_id, $new_path )) {
						Console::println ( "Error: There might be problem while creating " . $new_path );
					}
					$new_path = $new_path . "/";
				}
			}
		} catch ( Exception $e ) {
			Console::println ( "Error: There was a problem while creating " . $mypath );
		}
		// close the connection
		ftp_close ( $conn_id );
	}
	public static function is_dir($ftp, $dir) {
		$pushd = ftp_pwd ( $ftp );
		
		if ($pushd !== false && @ftp_chdir ( $ftp, $dir )) {
			ftp_chdir ( $ftp, $pushd );
			return true;
		}
		
		return false;
	}
}