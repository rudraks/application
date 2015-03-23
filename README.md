## Create a php file in your project root
and place this project in your project

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


### For RudraX project

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

