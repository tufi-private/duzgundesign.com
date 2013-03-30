<?php
include_once 'config/config.php';
$page='kapi';
$assetDir = '08-'.$page;
$galleryAncestor = 'Ürünlerimiz';
$galleryTitle = 'Kapı Modelleri';

$excludeList = array(".", "..");
$path = dirname(__FILE__).'/assets/'.$assetDir.'/orig';
$files = array_diff(scandir($path), $excludeList);
include './tpl/'.$page.'.phtml';
exit;