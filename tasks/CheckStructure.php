<?php
namespace RudraX\Task;

trait CheckStructure {
	function taskCheckStructure($path) {
		return new CheckStructureTask ( $path );
	}
}

class CheckStructureTask implements Robo\Contract\TaskInterface {
	// configuration params
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
		$this->_exec('ps aux');
	}
}
?>