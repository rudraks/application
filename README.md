## Create a php file in your project root
and place this application folder in your project

#### FOR Normal Project
--deploy.php
``

include "application/App.php";

$server = $argv[1]; //First argument is config
$password =  $argv[2]; /////Optionsal Password

$app = new App();

$app->push($server,$password);

``

##ON-CONSOLE 
$ php deploy.php production


### For RudraX project - 
Note:- if you are using composer then you dont need to download it manually, it will automatically get downloaded in your verndor folder, but still you need to write deploy.php pointing to your this script_folder

Here is an example with RudraX Structured Project.

--deploy.php
``

include "lib/rudrax/application/App.php";

$server = $argv[1]; //First argument is config
$password =  $argv[2];  ///Optionsal Password

$app = new App();

$app->deploy($server,$password);

``

##ON-CONSOLE 
$ php deploy.php production

