<?php
include_once 'config/config.php';
$page='kitaplik';
$assetDir = '07-'.$page;
$excludeList = array(".", "..");
$path = dirname(__FILE__).'/assets/'.$assetDir.'/orig';
$files = array_diff(scandir($path), $excludeList);
include './tpl/'.$page.'.phtml';
exit;