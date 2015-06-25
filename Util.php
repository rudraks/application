<?php 

namespace RudraX;

include_once 'tools/src/Helpers.php';

use Brunodebarros\Gitdeploy\Helpers;

class Util extends Helpers {
	
	public static function line() {
		echo "\n###################################################################################################\n";
	}
	
	public static function log($msg) {
		parent::logmessage ( $msg );
	}
	
}


?>
