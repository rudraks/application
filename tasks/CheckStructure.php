<?php
namespace RudraX\Tasks;

use RudraX\Utils\ResourceUtil;
use RudraX\Utils\FileUtil;
use RudraX\Utils\Console;

trait CheckStructure {
	function taskCheckStructure($path) {
		return new CheckStructureTask ( $path );
	}
}

class CheckStructureTask implements \Robo\Contract\TaskInterface {
	// configuration params
	
	use \Robo\Task\FileSystem\loadTasks;
	
	protected $path;
	protected $to;
	function __construct($path) {
		$this->path = $path;
	}
	function to($filename) {
		$this->to = $filename;
		// must return $this
		return $this;
	}
	
	// must implement Run
	function run() {
		try{
			$this->taskCleanDir(['build'])->run();
		} catch (\Exception $e){
			Console::error("Cannot Clean Directory");
		}
		ResourceUtil::build_js();
		passthru("compass compile");
	}
}
?>