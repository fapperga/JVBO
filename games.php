<?php
define('_VALID', true);
require 'include/config.php';
require 'include/function_global.php';
require 'include/function_smarty.php';
require 'classes/pagination.class.php';

$slug = get_request_arg('games', 'STRING');
if ($slug != '') {
	$sql            = "SELECT category_id FROM game_categories WHERE slug = '".$slug."' LIMIT 1";
	$rs             = $conn->execute($sql);
	if ($conn->Affected_Rows()) {
		$cat_id         = $rs->fields['category_id'];
	}
} else {
	$cat_id = NULL;
}

if ( $config['game_module'] == '0' ) {
	VRedirect::go($config['BASE_URL']. '/notfound/page_invalid');
}

$sql            = "SELECT * FROM game_categories WHERE status = '1' ORDER BY category_name ASC";
$rs             = $conn->execute($sql);
$categories     = $rs->getrows();

$type           = ( isset($_GET['type']) && ($_GET['type'] == 'private' or $_GET['type'] == 'public') ) ? $_GET['type'] : NULL;
if ($cat_id) {
	$category  = $cat_id;
} else {
	$category       = ( isset($_GET['c']) ) ? intval($_GET['c']) : 0;
}
$orders         = array('bp', 'mr', 'mp', 'tr', 'md', 'tf');
$order          = ( isset($_GET['o']) && in_array($_GET['o'], $orders) ) ? $_GET['o'] : 'bp';
$timeframes     = array('t', 'w', 'm', 'a');
$timeframe      = ( isset($_GET['t']) && in_array($_GET['t'], $timeframes) ) ? $_GET['t'] : 'a';

$sql_add        = NULL;
$sql_add_count  = NULL;
$sql_delim      = ' WHERE ';
$title_t        = NULL;
$title_c        = NULL;
$title_o        = NULL;
$title_p        = NULL;

if ( $type != '' ) {
    $title_p        = ' '.ucfirst(($type == 'private') ? $lang['global.private'] : $lang['global.public']);
    $sql_add        = $sql_delim. " type = '" .$type. "'";
    $sql_add_count  = $sql_delim. " type = '" .$type. "'";
    $sql_delim      = ' AND';
}

switch ( $timeframe ) {
    case 't':
        $title_t         = $lang['global.todays'];
        $sql_add        .= $sql_delim. " DATE_FORMAT(adddate, '%y-%m-%d') = DATE_FORMAT(NOW(), '%y-%m-%d')";
        $sql_add_count  .= $sql_delim. " DATE_FORMAT(adddate, '%y-%m-%d') = DATE_FORMAT(NOW(), '%y-%m-%d')";
        $sql_delim       = ' AND';
        break;
    case 'w':
        $title_t         = $lang['global.this_weeks'];
        $sql_add        .= $sql_delim. " DATE_FORMAT(adddate, '%y-%u') = DATE_FORMAT(NOW(), '%y-%u')";
        $sql_add_count  .= $sql_delim. " DATE_FORMAT(adddate, '%y-%u') = DATE_FORMAT(NOW(), '%y-%u')";
        $sql_delim       = ' AND';
        break;
    case 'm':
        $title_t         = $lang['global.this_months'];
        $sql_add        .= $sql_delim. " DATE_FORMAT(adddate, '%m') = DATE_FORMAT(NOW(), '%m')";
        $sql_add_count  .= $sql_delim. " DATE_FORMAT(adddate, '%m') = DATE_FORMAT(NOW(), '%m')";
        $sql_delim       = ' AND';
        break;
}

if ( $category ) {
    $sql_add        .= $sql_delim. " category = " .$conn->qStr($category);
    $sql_add_count  .= $sql_delim. " category = " .$conn->qStr($category);
    $sql_delim       = ' AND';
    foreach ( $categories as $categ ) {
        if ( $categ['category_id'] == $category ) {
            $title_c = ' ' .$categ['category_name'];
            break;
        }
    }
}

$sql_add       .= $sql_delim. " status = '1'";
$sql_add_count .= $sql_delim. " status = '1'";


switch ( $order ) {
    case 'bp':
        $title_o  = ' '.$lang['global.being_played'];
        $sql_add .= ' ORDER BY playtime DESC';
        break;
    case 'mr':
        $title_o  = ' '.$lang['global.most_recent'];
        $sql_add .= ' ORDER BY addtime DESC';
        break;
    case 'mp':
        $title_o  = ' '.$lang['global.most_played'];
        $sql_add .= ' ORDER BY total_plays DESC';
        break;
    case 'tr':
        $title_o  = ' '.$lang['global.top_rated'];
        $sql_add .= ' ORDER BY rate DESC';
        break;
    case 'md':
        $title_o  = ' '.$lang['global.most_commented'];
        $sql_add .= ' ORDER BY total_comments DESC';
        break;
    case 'tf':
        $title_o  = ' '.$lang['global.top_favorites'];
        $sql_add .= ' ORDER BY total_favorites DESC';
        break;
}

$sql            = "SELECT count(GID) AS total_games FROM game" .$sql_add_count;
$rsc            = $conn->execute($sql);
$total          = $rsc->fields['total_games'];
$pagination     = new Pagination($config['games_per_page']);
$limit          = $pagination->getLimit($total);
$sql            = "SELECT * FROM game" .$sql_add. " LIMIT " .$limit;
$rs             = $conn->execute($sql);
$games          = $rs->getrows();

if ($slug) {
	$page_link      = $pagination->getPagination('games/'.$slug);	
	$smarty->assign('base', 'games/'.$slug);
} else {
	$page_link      = $pagination->getPagination('games');
	$smarty->assign('base', 'games');	
}

$start_num      = $pagination->getStartItem();
$end_num        = $pagination->getEndItem();

$title              = $title_t . $title_o . $title_c . $title_p;
$self_title         = $title . $seo['games_title'];
$self_description   = $title . $seo['games_desc'];
$self_keywords      = $title . $seo['games_keywords'];

$smarty->assign('errors',$errors);
$smarty->assign('messages',$messages);
$smarty->assign('menu', 'games');
$smarty->assign('categories', $categories);
$smarty->assign('type', $type);
$smarty->assign('games', $games);
$smarty->assign('games_total', $total);
$smarty->assign('start_num', $start_num);
$smarty->assign('end_num', $end_num);
$smarty->assign('page_link', $page_link);
$smarty->assign('category', $category);
$smarty->assign('timeframe', $timeframe);
$smarty->assign('order', $order);
$smarty->assign('title', $title);
$smarty->assign('self_title', $self_title);
$smarty->assign('self_description', $self_description);
$smarty->assign('self_keywords', $self_keywords);
$smarty->loadFilter('output', 'trimwhitespace');
$smarty->display('header.tpl');
$smarty->display('errors.tpl');
$smarty->display('messages.tpl');
$smarty->display('games.tpl');
$smarty->display('footer.tpl');
?>
