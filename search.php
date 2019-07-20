<?php
define('_VALID', true);
require 'include/config.php';
require 'include/function_global.php';
require 'include/function_smarty.php';
require 'classes/filter.class.php';
require 'classes/pagination.class.php';

$filter             = new VFilter();
$search_query       = $filter->get('search_query', 'STRING', 'GET');
$search_type        = get_request_arg('search', 'STRING');
$search_types       = array('videos', 'photos', 'users', 'games');
if ( !in_array($search_type, $search_types) ) {
    VRedirect::go($config['BASE_URL']. '/notfound/invalid_search_type');
}

$module             = 'modules/search/' .$search_type. '.php';
$module_template    = 'search_' .$search_type. '.tpl';

require $module;

$self_title         = strtoupper($search_type) . " - " . str_replace('{#search_query#}', $search_query, $seo['search_title']);

$smarty->assign('errors',$errors);
$smarty->assign('messages',$messages);
$smarty->assign('menu', 'home');
$smarty->assign('search_query', $search_query);
$smarty->assign('search_type', $search_type);
$smarty->assign('self_title', $self_title);
$smarty->assign('self_description', $seo['search_desc']);
$smarty->assign('self_keywords', $seo['search_keywords']);
$smarty->loadFilter('output', 'trimwhitespace');
$smarty->display('header.tpl');
$smarty->display('errors.tpl');
$smarty->display('messages.tpl');
$smarty->display($module_template);
$smarty->display('footer.tpl');
?>
