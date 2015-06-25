<?php
namespace RudraX\Tasks;

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
		passthru("sudo mkdir build");
		passthru("sudo chmod -R 0777 build");
		//$this->taskFileSystemStack()->mkdir('build');
		$this->taskCleanDir(['build'])->run();
	}
}
?>