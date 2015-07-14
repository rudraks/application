<?php

namespace RudraX\Tasks;

use RudraX\Utils\ResourceUtil;
use RudraX\Utils\FileUtil;
use RudraX\Utils\Console;
use RudraX\Server;
use RudraX\Utils\ModuleUtil;

trait Builder {
	function taskBuilder($config = null) {
		return new BuilderTask ( $config );
	}
}
/**
 *
 * @author Lalit Tanwar
 *        
 */
class BuilderTask implements \Robo\Contract\TaskInterface {
	// configuration params
	
	use \Robo\Task\FileSystem\loadTasks;
	protected $config;
	protected $to;
	function __construct($config) {
		$this->config = $config;
	}
	function clean() {
		try {
			$this->taskCleanDir ( [ 
					'build' 
			] )->run ();
		} catch ( \Exception $e ) {
			Console::error ( "Cannot Clean Directory" );
		}
		return $this;
	}
	function scan() {
		ResourceUtil::scan_modules ();
		ModuleUtil::scan_modules ();
		return $this;
	}
	function build($buildConfig = array()) {
		ResourceUtil::build_js ( $buildConfig );
		return $this;
	}
	function compile() {
		passthru ( "compass compile" );
		return $this;
	}
	function deploy($configName) {
		Server::deploy ( $configName );
		return $this;
	}
	// must implement Run
	function run() {
	}
}
?>