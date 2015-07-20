<?php

namespace RudraX\Tasks;

use RudraX\Utils\FileUtil;
use RudraX\Utils\Console;
use RudraX\Server;
use RudraX\Utils\ModuleUtil;

trait Bundlify {
	function taskBundlify($config = null) {
		return new BundlifyTask ( $config );
	}
}
/**
 *
 * @author Lalit Tanwar
 *        
 */
class BundlifyTask implements \Robo\Contract\TaskInterface {
	// configuration params
	
	use \Robo\Task\FileSystem\loadTasks;
	protected $config;
	protected $to;
	function __construct($config) {
		$this->config = array_merge_recursive(array(
				"preBundles" =>  array("webmodules/bootloader"),
				"dir" => array("lib","resources"),
				"dest" => "build/dist",
				"resourcesJson" => "resources/resource.json"
		),$config);
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
		ModuleUtil::scan_modules ();
		return $this;
	}
	function build($buildConfig = array()) {
		ModuleUtil::build_js ( $buildConfig );
		return $this;
	}
	function bundlify($bundles=null) {
		ModuleUtil::scan_modules ($this->config["dir"],$this->config["resourcesJson"]);
		ModuleUtil::bundlify ($this->config["preBundles"]);
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