<?php
include_once 'config/config.php';
$page='banyo';
$assetDir = '02-'.$page;
$galleryAncestor = 'Ürünlerimiz';
$galleryTitle = 'Banyo Dolapları';

$excludeList = array(".", "..");
$path = dirname(__FILE__).'/assets/'.$assetDir.'/orig';
$files = array_diff(scandir($path), $excludeList);
include './tpl/'.$page.'.phtml';
exit;