<?php
include_once 'config/config.php';
$page='tv';
$assetDir = '05-'.$page;
$galleryAncestor = 'Ürünlerimiz';
$galleryTitle = 'TV Üniteleri';

$excludeList = array(".", "..");
$path = dirname(__FILE__).'/assets/'.$assetDir.'/orig';
$files = array_diff(scandir($path), $excludeList);
include './tpl/'.$page.'.phtml';
exit;