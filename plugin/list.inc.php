<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: list.inc.php,v 1.6 2006/05/13 07:36:41 henoheno Exp $
//
// IndexPages plugin: Show a list of page names

function plugin_list_action()
{
	global $vars, $whatsnew;
	$qm = get_qm();

	// Redirected from filelist plugin?
	$filelist = (isset($vars['cmd']) && $vars['cmd'] == 'filelist');

	return array(
		'msg' => $filelist ? $qm->m['plg_list']['title_filelist'] : $qm->m['plg_list']['title'],
		'body' => plugin_list_getlist($filelist)
	);
}

// Get a list
function plugin_list_getlist($withfilename = FALSE)
{
	global $non_list, $whatsnew, $style_name;

	$pages = array_diff(get_existpages(), array($whatsnew));
	if (! $withfilename)
		$pages = array_diff($pages, preg_grep('/' . $non_list . '/S', $pages));
	if (empty($pages)) return '';

	if (ss_admin_check()) {
		$style_name = '..';
		return plugin_list_create_html(plugin_list_array($pages), $withfilename);
	} else {
		return page_list($pages, 'read', $withfilename);
	}
}

function plugin_list_array($pages)
{
	global $script;
	$qm = get_qm();

	$symbol = ' ';
	$other = 'zz';
	$list = [];
	$cnt = 0;

	//並び替える
	foreach ($pages as $file => $page) {
		$pgdata = [];
		$pgdata['urlencoded']  = rawurlencode($page);
		$pgdata['sanitized']   = htmlspecialchars($page, ENT_QUOTES);
		$pgdata['passage'] = get_pg_passage($page, FALSE);
		$pgdata['mtime'] = format_date(filemtime(get_filename($page)));
		$pgdata['date'] = format_date_Ymdw(filemtime(get_filename($page)));
		$pgdata['time'] = format_date_His(filemtime(get_filename($page)));
		$pgdata['title'] = get_page_title($page);
		$pgdata['title'] = ($pgdata['title'] == $pgdata['sanitized']) ? '' : '（' . $pgdata['title'] . '）';
		$pgdata['filename'] = htmlspecialchars($file);
		$pgdata['tinycode'] = get_tiny_code($pgdata['sanitized']) ?? '';

		$head = (preg_match('/^([A-Za-z])/', $page, $matches)) ? $matches[1] : (preg_match('/^([ -~])/', $page, $matches) ? $symbol : $other);

		$list[$head][$page] = $pgdata;
		$cnt++;
	}
	ksort($list);

	$tmparr = isset($list[$symbol]) ? $list[$symbol] : null;
	unset($list[$symbol]);
	$list[$symbol] = $tmparr;

	$retlist = [];
	foreach ($list as $head => $pages) {
		if (is_null($pages)) {
			continue;
		}

		ksort($pages);

		if ($head === $symbol) {
			$head = $qm->m['func']['list_symbol'];
		} else if ($head === $other) {
			$head = $qm->m['func']['list_other'];
		}

		$retlist[$head] = $pages;
	}

	return $retlist;
}

/**
 * 渡されたページデータを元に高機能インデックスページを生成する
 *
 * @param assoc $pages_data ページデータ。{頭文字: {ページ名:ページデータ, ...}, ...} の連想配列
 * @param boolean $withfilename ファイル名を表示するかどうか
 * @param array $cmds 
 */
function plugin_list_create_html($pages_data, $withfilename = FALSE)
{
	global $script, $list_index;
	$html = $index = '';
	$qm = get_qm();
	$qt = get_qt();
	$qt->setv('jquery_include', true);

	// $pages_dataのArray内部を表示
	// echo '<pre>';
	// var_dump($pages_data);
	// echo '</pre>';
	// exit;

	$head_cnt = 0;
	$indexies = [];
	foreach ($pages_data as $head => $pages) {
		if (is_null($pages) || count($pages) === 0) {
			continue;
		}

		if ($list_index) {
			$head_cnt++;
			$indexies[] = '<a href="#head_' . $head_cnt . '" id="plugin_list_index_' . $head_cnt . '"><strong>' .
				$head . '</strong></a>';
			$html .= '
	<tr class="plugin_list_navi">
	<td colspan=2>
		<a href="#plugin_list_index_' . $head_cnt . '" id="head_' . $head_cnt . '"><strong>' . $head . '</strong></a>
	</td>';
		}

		foreach ($pages as $page => $data) {
			$html .= '
	<tr class="plugin_list_pagerow">
	<td class="plugin_list_pageinfo">
		<div class="plugin_list_pagename"><a href="' . h($script . '?' . $data['urlencoded']) . '">' . $data['sanitized'] . $data['title'] . '</a></div>
		<div class="plugin_list_commands">';

			$cmds = [];
			foreach (plugin_list_get_commands($page) as $cmd => $cmddata) {
				$fmt = $cmddata['format'];
				$label = $cmddata['label'];

				$cmds[] = '
				<a href="' . h(sprintf($fmt, $script, $data['urlencoded'])) . '" class="plugin_list_page_' . h($cmd) . '">' . h($label) . '</a>';
			}
			$html .= join(' | ', $cmds);

			$html .= '</div>'
				. $data['passage'];

			$page_url = $script . '?' . $data['urlencoded'];
			$tiny_url = $script . '?go=' . $data['tinycode'];
			$edit_tiny_url = $script . '?cmd=update_tinycode&page=' . $data['urlencoded'];

			$html .= '<div class="plugin_list_normal_url">通常URL：<a href="' . $page_url . '">' . $page_url . '</a></div>';
			$html .= '<div class="plugin_list_tiny_url">短縮URL：<a href="' . $tiny_url . '">' . $tiny_url . '
			<a href="' . $edit_tiny_url . '">（設定）</a></div>';

			if ($withfilename) {
				$html .= '<div class="plugin_list_filename">ファイル名：' . h($data['filename']) . '</div>   ';
			}
			$html .= '
				</td>
				<td class="plugin_list_mtime">
					' . h($data['date']) . '<br/>
					' . h($data['time']) . '
				</td>
				</tr>
			';
		}
	}

	$body = '
<div class="plugin_list">
	<h2>ページの一覧</h2>
	<div id="plugin_list_index">
	' . join(' | ', $indexies) . '
	</div>
<table cellspacing=0 style="border: 1px solid #DFDFDF;">
<thead>
	<tr>
	<td>ページ名（タイトル）　<span style="font-size: 12px;">検索：<span><input type="search" size="20" id="plugin_list_searchbox" style="width:150px" placeholder="例：FrontPage" /></td>
	<td>最終更新日</td>
	</tr>
</thead>
<tbody>
' . $html . '
</tbody>
</table>';

	$beforescript = '
<style type="text/css">
html, body {
font-size: 10px;
}
#wrapper{
	max-width: 740px;
}
#header,#navigator,#navigator2,#footer,#licence,#wrap_sidebar,#wrap_sidebar2{
	display: none;
}
#wrap_content {
	width: 100%;
}
#wrapper {
	margin-bottom: 30px;
}
.plugin_list {
	margin-bottom: 16px;
	font-size: 14px;
}
.plugin_list table {
	max-width: 740px;
}
.plugin_list td {
	border-bottom: 1px solid #DFDFDF;
}
tr.plugin_list_navi {
	text-align: center;
	background: #eee; 
}
tr.plugin_list_pagerow td {
	vertical-align: top;
}
thead td {
	background: #eee;
	padding: 4px 10px;
	font-size: 1.6rem;
	vertical-align: middle;
}
tbody td.plugin_list_pageinfo {
	display: flex;
	flex-direction: column;
	gap: 6px;
	padding-block: 10px;
	padding-inline: 12px;
}
.plugin_list_pagename {
	font-size: 16px;
	font-weight: bold;
	margin: 0;
}
.plugin_list_pagename a{
	color: inherit;
	text-decoration: none;	
}
.plugin_list_commands {
	font-size: 1.3rem;
	margin: 0;
	opacity: 0;
}
.plugin_list_normal_url,
.plugin_list_tiny_url,
.plugin_list_filename {
	margin: 0;
	font-size: 1.2rem;
	color: #666;
	max-width: 620px;
}
.plugin_list_mtime {
	font-size: 1.3rem;
	padding: 10px;
	white-space: nowrap;
	width: auto;
	text-align: right;
}
#plugin_list_index {
	text-align:center;
	margin-bottom: 14px;
}
</style>
<script type="text/javascript" src="js/jquery.searchable.js"></script>
<script type="text/javascript">
$(function(){
	$("#plugin_list_index a:nth-child(16n)").after("<br />");
	$("tr.plugin_list_pagerow").mouseenter(function(e){
		e.stopPropagation();
		$("div.plugin_list_commands", this).animate(
			{opacity:1},
			{duration: "fast"});
	});
	$("tr.plugin_list_pagerow").mouseleave(function(e){
		e.stopPropagation();
		$("div.plugin_list_commands", this).animate(
			{opacity:0},
			{duration: "fast"});
	});
	
	$("#plugin_list_searchbox")
	.searchable("div.plugin_list table > tbody > tr", {
		selector: "td:first-child:not([colspan])"
	})
	.focus().select();
});
</script>';

	$qt->appendv_once('plugin_list', 'beforescript', $beforescript);

	return $body;
}

function plugin_list_get_commands($page)
{
	$retarr = array(
		'read' => array(
			'format' => '%s?%s',
			'label' => '表示'
		),
		'edit' => array(
			'format' => '%s?cmd=edit&page=%s',
			'label' => '編集'
		),
		'diff' => array(
			'format' => '%s?cmd=diff&page=%s',
			'label' => '差分'
		),
		'backup' => array(
			'format' => '%s?cmd=backup&page=%s',
			'label' => 'バックアップ'
		),
		'rename' => array(
			'format' => '%s?cmd=rename&refer=%s',
			'label' => '名前変更'
		),
		'delete' => array(
			'format' => '%s?cmd=delete&page=%s',
			'label' => '削除'
		),
		'map' => array(
			'format' => '%s?cmd=map&refer=%s',
			'label' => 'マップ'
		),
		'template' => array(
			'format' => '%s?cmd=template&refer=%s',
			'label' => '複製'
		),
	);

	if (PKWK_READONLY) {
		return array('read' => $retarr['read']);
	}

	if (! ss_admin_check()) {
		unset($retarr['diff'], $retarr['backup'], $retarr['rename'], $retarr['map'], $retarr['template']);
		if (! check_editable($page, FALSE, FALSE)) {
			unset($retarr['edit']);
		}
	}

	return $retarr;
}
