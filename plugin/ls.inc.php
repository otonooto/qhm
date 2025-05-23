<?php
/*
 * PukiWiki lsプラグイン
 *
 * CopyRight 2002 Y.MASUI GPL2
 * http://masui.net/pukiwiki/ masui@masui.net
 *
 * $Id: ls.inc.php,v 1.9 2004/07/31 03:09:20 henoheno Exp $
 */

function plugin_ls_convert()
{
	global $vars;

	$qt = get_qt();
	//---- キャッシュのための処理を登録 -----
	if ($qt->create_cache) {
		$args = func_get_args();
		return $qt->get_dynamic_plugin_mark(__FUNCTION__, $args);
	}
	//------------------------------------

	$with_title = FALSE;

	if (func_num_args()) {
		$args = func_get_args();
		$with_title = in_array('title', $args);
	}

	$prefix = $vars['page'] . '/';

	$pages = [];
	foreach (get_existpages() as $page) {
		if (strpos($page, $prefix) === 0) {
			$pages[] = $page;
		}
	}
	natcasesort($pages);

	$ls = [];
	foreach ($pages as $page) {
		$comment = '';
		if ($with_title) {
			list($comment) = get_source($page);
			// 見出しの固有ID部を削除
			$comment = preg_replace('/^(\*{1,3}.*)\[#[A-Za-z][\w-]+\](.*)$/', '$1$2', $comment);

			$comment = '- ' . preg_replace('/^[-*]+/', '', $comment);
		}
		$ls[] = "-[[$page]] $comment";
	}

	return convert_html($ls);
}
