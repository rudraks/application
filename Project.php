<?php

namespace RudraX;

include_once ("Util.php");
class Project {
	public static function setup() {
		
		self::copyRobo ();
		self::updateGitIgnore ();
	}
	
	public static function checkStructure(){
		
	}
	
	public static function copyRobo() {
		if (file_exists ( "lib/codegyre/robo/robo" )) {
			copy("lib/rudrax/application/robo.php","robo");
			chmod("robo", 0777);
			return true;
		} else {
			Util::error ( "Composer package codegyre/robo does not exits, make sure it does" );
			return false;
		}
	}
	public static function updateGitIgnore() {
		Util::log ( "Updating .gitignore file" );
		
		$handle = @fopen ( ".gitignore", "a" );
		fclose ( $handle );
		$handle = @fopen ( ".gitignore", "r+" );
		if ($handle) {
			$entries = array (
					"robo","build" ,"lib"
			);
			
			$entriesOk = array ();
			
			while ( ($buffer = fgets ( $handle, 1024 )) !== false ) {
				foreach ( $entries as $entry ) {
					if (strpos ( $buffer, "robo" ) !== false) {
						$entriesOk [$entry] = true;
					}
				}
			}
			
			foreach ( $entries as $entry ) {
				if (!isset ( $entriesOk [$entry] )) {
					Util::log ( "New Entry ::" . $entry );
					fwrite ( $handle, "\n".$entry);
				}
			}
			
			if (! feof ( $handle )) {
				Util::error ( "Error: unexpected fgets() fail" );
			}
			fclose ( $handle );
		}
	}
}