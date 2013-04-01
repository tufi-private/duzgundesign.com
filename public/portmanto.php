<?php
include_once 'config/config.php';
$page='sifonyer';
$assetDir = '04-'.$page;
$galleryAncestor = 'Ürünlerimiz';
$galleryTitle = 'Portmantolar';

$excludeList = array(".", "..");
$path = dirname(__FILE__).'/assets/'.$assetDir.'/orig';
$files = array_diff(scandir($path), $excludeList);
include './tpl/portmanto.phtml';
exit;