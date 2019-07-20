<?php

define('_VALID', true);
require 'include/config.php';
require 'include/function_global.php';
require 'include/function_smarty.php';
require 'classes/pagination.class.php';

if ( $config['game_module'] == '0' ) {
        VRedirect::go($config['BASE_URL']. '/notfound/page_invalid');
}

if ( $config['game_view'] == 'registered' ) {
    require 'classes/auth.class.php';
    Auth::check_();
}

$request    = ( isset($_SERVER['REQUEST_URI']) ) ? $_SERVER['REQUEST_URI'] : NULL;
$request    = ( isset($_SERVER['QUERY_STRING']) ) ? str_replace('?' .$_SERVER['QUERY_STRING'], '', $request) : $request;
$query      = explode('/', $request);

if ( is_array($query) ) {
    foreach ( $query as $key => $value ) {
        if ( $value == 'game' ) {
            if ( isset($query[$key+1]) ) {
                $next       = $query[$key+1];
                if ( is_numeric($next) ) {
                    $gid    = $next;
                } else {
                    $module             = $next;
                    $modules_allowed    = array('edit');
                    if ( in_array($module, $modules_allowed) ) {
                        $gid            = ( isset($query[$key+2]) ) ? $query[$key+2] : NULL;
                    } else {
                        VRedirect::go($config['BASE_URL']. '/notfound/album_missing');
                    }
                }
            }
        }
    }
}

if ( !isset($gid) || !is_numeric($gid) ) {
    VRedirect::go($config['BASE_URL']. '/notfound/game_missing');
}

$gid        = intval($gid);
$sql        = "SELECT g.GID, g.UID, g.title, g.category, g.tags, g.total_plays, g.type,
                      g.addtime, g.rate, g.likes, g.dislikes, u.username, u.fname, u.photo, u.gender
               FROM game AS g, signup AS u
               WHERE g.GID = " .$gid. "
               AND g.UID = u.UID
               AND g.status = '1'
               LIMIT 1";
$rs         = $conn->execute($sql);
if ( $conn->Affected_Rows() != 1 ) {
    VRedirect::go($config['BASE_URL']. '/notfound/game_missing');
}
$game               = $rs->getrows();
$game               = $game['0'];
$game['tags']       = explode(' ', $game['tags']);
$uid                = ( isset($_SESSION['uid']) ) ? intval($_SESSION['uid']) : NULL;

if ( isset($module) ) {
    require 'modules/game/' .$module. '.php';
    $module_template    = 'game_' .$module;
} else {

$is_friend          = true;
if ( $game['type'] == 'private' && $uid != $game['UID'] ) {
    $sql = "SELECT FID FROM friends WHERE UID = " .intval($game['UID']). " AND FID = " .$uid. " AND friends_status = 'Confirmed'";
    $conn->execute($sql);
    if ( $conn->Affected_Rows() == 0 ) {
        $is_friend = false;
    }
}

$sql        = "UPDATE game SET total_plays = total_plays+1, playtime='" .date('Y-m-d H:i:s'). "' WHERE GID = " .$gid. " LIMIT 1";
$conn->execute($sql);
$sql_add    = NULL;
if ( $game['tags'] ) {
    $sql_add   .= " OR (";
    $sql_or     = NULL;    
    foreach ( $game['tags'] as $keyword ) {
        $sql_add .= $sql_or. " tags LIKE '%" .trim($conn->qStr($keyword), "'"). "%'";
        $sql_or   = " OR ";
    }
    $sql_add   .= ")";
}

$sql            = "SELECT COUNT(GID) AS total_games FROM game WHERE status = '1' AND category = '" .$game['category']. "' AND GID != " .$gid. "
                   AND ( title LIKE '%" .trim($conn->qStr($game['title']), "'"). "%' " .$sql_add. ")";
$rsc            = $conn->execute($sql);
$total_related  = $rsc->fields['total_games'];
if ( $total_related > 32 ) {
    $total_related = 32;
}
$pagination     = new Pagination(8, 'p_related_games_' .$game['GID']. '_');
$limit          = $pagination->getLimit($total_related);
$sql            = "SELECT GID, title, addtime, rate, likes, dislikes, total_plays, type FROM game
                   WHERE status = '1' AND category = '" .$game['category']. "' AND GID != " .$gid. "
                   AND ( title LIKE '%" .trim($conn->qStr($game['title']), "'"). "%' " .$sql_add. ")
                   ORDER BY addtime DESC LIMIT " .$limit;
$rs             = $conn->execute($sql);
$games          = $rs->getrows();
$page_link      = $pagination->getPagination('game');

$sql            = "SELECT COUNT(CID) AS total_comments FROM game_comments WHERE GID = " .$gid. " AND status = '1'";
$rsc            = $conn->execute($sql);
$total_comments = $rsc->fields['total_comments'];
$pagination     = new Pagination(10);
$limit          = $pagination->getLimit($total_comments);
$sql            = "SELECT c.CID, c.UID, c.comment, c.addtime, s.username, s.photo, s.gender
                   FROM game_comments AS c, signup AS s 
                   WHERE c.GID = " .$gid. " AND c.status = '1' AND c.UID = s.UID 
                   ORDER BY c.addtime DESC LIMIT " .$limit;
$rs             = $conn->execute($sql);
$comments       = $rs->getrows();
$page_link_c    = $pagination->getPagination('game', 'p_game_comments_' .$game['GID']. '_');
$page_link_cb   = $pagination->getPagination('game', 'pp_game_comments_' .$game['GID']. '_');
$start_num      = $pagination->getStartItem();
$end_num        = $pagination->getEndItem();



$self_title         = $game['title'] . $seo['game_title'];
$self_description   = $game['title'] . $seo['game_desc'];
$self_keywords      = implode(', ', $game['tags']) . $seo['game_keywords'];

$media  = $config['BASE_URL']. "/media/games/swf/".$game['GID'].".swf";
$dim    = getimagesize($media);
if (isset($dim[1]) && $dim[1]!=0)
	$aspect = $dim[0]/$dim[1];
else
	$aspect =1.5;
$smarty->assign('aspect',$aspect);

$smarty->assign('errors',$errors);
$smarty->assign('messages',$messages);
$smarty->assign('menu', 'games');
$smarty->assign('submenu', '');
$smarty->assign('play', true);
$smarty->assign('game', $game);
$smarty->assign('self_title', $self_title);
$smarty->assign('self_description', $self_description);
$smarty->assign('self_keywords', $self_keywords);
$smarty->assign('games_total', $total_related);
$smarty->assign('games', $games);
$smarty->assign('page_link', $page_link);
$smarty->assign('comments_total', $total_comments);
$smarty->assign('comments', $comments);
$smarty->assign('page_link_comments', $page_link_c);
$smarty->assign('page_link_comments_bottom', $page_link_cb);
$smarty->assign('start_num', $start_num);
$smarty->assign('end_num', $end_num);
$smarty->assign('is_friend', $is_friend);
$smarty->loadFilter('output', 'trimwhitespace');
$smarty->display('header.tpl');
$smarty->display('errors.tpl');
$smarty->display('messages.tpl');
$smarty->display('game.tpl');
$smarty->display('footer.tpl');
}
?>
