<?php
define('_VALID', true);
require 'include/config.php';
require 'include/function_smarty.php';

$s      = ( isset($_GET['s']) && ($_GET['s'] == 'a' or $_GET['s'] == 'g') ) ? $_GET['s'] : '';

$videos = array();
$albums = array();
$games  = array();

if ($s == "g") {
	$sql            = "SELECT category_id, category_name, slug FROM game_categories ORDER BY category_name ASC";
	$rs             = $conn->execute($sql);
	$categories     = $rs->getrows();

	foreach ( $categories as $category ) {
		$sql = "SELECT * FROM game WHERE category = " .intval($category['category_id']). " AND status = 1";
		$rs = $conn->execute($sql);
		$num_rows = $rs->NumRows();
		$games[] = array('num_rows' => $num_rows);
		$rs->close();
		$smarty->assign('games', $games);
	}
} elseif ($s == "a") {
	$sql            = "SELECT CID, name, slug FROM album_categories ORDER BY name ASC";
	$rs             = $conn->execute($sql);
	$categories     = $rs->getrows();

	foreach ( $categories as $category ) {
		$sql = "SELECT * FROM albums WHERE category = " .intval($category['CID']). " AND status = 1";
		$rs = $conn->execute($sql);
		$num_rows = $rs->NumRows();
		$albums[] = array('num_rows' => $num_rows);
		$rs->close();
		$smarty->assign('albums', $albums);
	}
} else {
	$sql            = "SELECT CHID, name, slug FROM channel ORDER BY name ASC";
	$rs             = $conn->execute($sql);
	$categories     = $rs->getrows();

	foreach ( $categories as $category ) {
		$sql = "SELECT * FROM video WHERE active = '1' AND channel = " .intval($category['CHID']). " AND active = 1";
		$rs = $conn->execute($sql);
		$num_rows = $rs->NumRows();
		$videos[] = array('num_rows' => $num_rows);
		$rs->close();
	}	
}




if ($s == "a") {
	$smarty->assign('albums', $albums);
	$smarty->assign('section', "a");
}
elseif ($s == "g") {
	$smarty->assign('games', $games);
	$smarty->assign('section', "g");
}
else {
	$smarty->assign('videos', $videos);
	$smarty->assign('section', "v");
}

$smarty->assign('errors',$errors);
$smarty->assign('messages',$messages);
$smarty->assign('menu', 'categories');
$smarty->assign('catgy', true);
$smarty->assign('categories', $categories);
$smarty->assign('self_title', $seo['categories_title']);
$smarty->assign('self_description', $seo['categories_desc']);
$smarty->assign('self_keywords', $seo['categories_keywords']);
$smarty->loadFilter('output', 'trimwhitespace');
$smarty->display('header.tpl');
$smarty->display('errors.tpl');
$smarty->display('messages.tpl');
$smarty->display('categories.tpl');
$smarty->display('footer.tpl');
?>
