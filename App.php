<?php

class App {



	function  deploy ($server="production",$password = null){

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

		if($password==null){
			echo "Enter Password\n";
			$stdin = fopen('php://stdin', 'r');
			$password = trim(fgets($stdin));
			fclose($stdin);
		}
		
		//Set Default Configs for main directory
		$config[$server]["clean_directories"] = 'build';
		$config[$server]["ignore_files"] = 'lib/*';
		$config[$server]["pass"] =$password;
		
		//Empty build folder
		$this->delete_dir("build");
		
		//Write Config File for main project
		$this->write_ini_file($config, "build/deploy.ini", true);

		echo "Executing::"."php ".$prog_dir."git-deploy build/deploy.ini\n";
		
		passthru("php ".$prog_dir."/git-deploy build/deploy.ini");

		//No need o delete lib and buld in library cud be harmful
		unset($config[$server]["clean_directories"]);
		unset($config[$server]["ignore_files"]);
		
		
		//Upload Libraries..
		chdir("lib");

		$vendors = array_filter(glob('*'), 'is_dir');
		$host_path = $config[$server]['path'];
		foreach ($vendors as $vendor){
			if($vendor!="composer"){
				chdir($vendor);
				$libs = array_filter(glob('*'), 'is_dir');
				foreach ($libs as $lib){
					chdir($lib);
					echo $vendor."/".$lib."\n";
					$lib_ini_file = "../../../build/data-".$vender."-".$lib.".ini";
					$config[$server]['path'] = $host_path."lib/".$vendor."/".$lib;
					$config[$server]['pass'] = $password;
					$this->write_ini_file($config, $lib_ini_file, true);
					$this->create_remote($config[$server]);
					echo "Executing:"."php ".$prog_dir."git-deploy ".$lib_ini_file."\n";
					passthru("php ".$prog_dir."/git-deploy ".$lib_ini_file);
					chdir("..");
				}
				chdir("..");
			}
		}
		$this->delete_dir("build");
	}
	

	function create_remote($config){

		// set up basic connection
		$conn_id = ftp_connect($config['host']);
		// login with username and password
		$login_result = ftp_login($conn_id, $config['user'], $config['pass']);

		// try to create the directory $dir
		if (ftp_mkdir($conn_id, $config['path'])) {
			echo "successfully created $dir\n";
		} else {
			echo "There was a problem while creating $dir\n";
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
			$files = array_diff(scandir($path), array('.', '..'));

			foreach ($files as $file){
				
				$this->delete_dir(realpath($path) . '/' . $file);
			}

			return rmdir($path);
		}

		else if (is_file($path) === true)
		{
			chmod($path, 0777);
			return unlink($path);
		}

		return false;
	}
}