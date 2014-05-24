<?php

$GLOBALS['update_interval'] = 60;
$GLOBALS['github_username'] = 'ekuiter';
$GLOBALS['github_password'] = '***REMOVED***';
$GLOBALS['youtube_username'] = 'ekuiter';
$GLOBALS['googleplay_apps'] = array('ek.ebersuche', 'ek.hcp.templog', 'ek.waeco.templog');

if (strstr($_SERVER['HTTP_HOST'], 'localhost')) {
  error_reporting(-1);
  ini_set('display_errors', 1); 
} else {
  error_reporting(0);
  ini_set('display_errors', 0);
}
date_default_timezone_set('Europe/Berlin');#

require_once 'include/Feed.class.php';
require_once 'include/FolderGrid.class.php';
require_once 'include/LatestContent.class.php';
require_once 'include/PageRenderer.class.php';
require_once 'include/Renderer.class.php';

new Renderer();

?>
