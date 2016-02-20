<?php
//turn on error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);

//set some global constants
define("DS", DIRECTORY_SEPARATOR);
define("BASE_PATH", __DIR__ . DS);
define("APP_PATH", realpath("app") . DS);
define("CORE_PATH", APP_PATH . "core" . DS);

//and we're off
require_once(CORE_PATH . DS . "app.php");
App::getInstance()->run();

// TODO: create event listeners/triggers (hooks)
// TODO: update docblocks on all core files
// TODO: database driver PDO
// TODO: XSS and CSRF
// TODO: Templating
// TODO: view Core_Router class for more todo's
// TODO: Git Repo
// TODO: Visuals
// TODO: assets/styling
// TODO: finish business logic requirements from G2
// TODO: more http statuses and pages (500, 503, method not implmented, etc.)