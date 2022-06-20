<?php
//Define
define("RETRO_ROBOT_DIR",          __DIR__ . "/");
define("RETRO_ROBOT_INC_DIR",      RETRO_ROBOT_DIR . "inc/");
define("RETRO_ROBOT_ADMIN_DIR",    RETRO_ROBOT_DIR . "admin/");
define("RETRO_ROBOT_OVERLAYS_DIR", RETRO_ROBOT_DIR . "overlays/");
define("RETRO_ROBOT_ROMS_DIR",     RETRO_ROBOT_DIR . "roms/");
define('DB_PATH', RETRO_ROBOT_DIR . "retrorobot.sqlite");

//Inject
include_once(RETRO_ROBOT_INC_DIR . 'key_load.php');
$retroRobotInc = array('db', 'utils');
foreach($retroRobotInc as $scriptKey) {
  $aScript = RETRO_ROBOT_ADMIN_DIR . 'retrorobot_' . $scriptKey . '.php';
  if (file_exists($aScript)) {
    require_once($aScript);
  }
}

//Start
ks::startOrRestartSession();

kdb::connect();

retrorobot_db::checkDB();
retrorobot_utils::createDirectories();
retrorobot_utils::loadMetaData();

echo('Doing stuff here');

kdb::disconnect();

ks::startOrRestartSession();

//
?>