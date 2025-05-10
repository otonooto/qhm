<?php
// $Id: bugtrack.inc.php,v 1.25 2005/04/03 03:33:47 henoheno Exp $
//
// PukiWiki BugTrack plugin
//
// Copyright:
// 2002-2005 PukiWiki Developers Team
// 2002 Y.MASUI GPL2  http://masui.net/pukiwiki/ masui@masui.net

// Numbering format
define('PLUGIN_BUGTRACK_NUMBER_FORMAT', '%d'); // Like 'page/1'
//define('PLUGIN_BUGTRACK_NUMBER_FORMAT', '%03d'); // Like 'page/001'

function plugin_bugtrack_get($vname)
{
	$ret = '';
	$qm = get_qm();

	switch ($vname) {
		case 'priority_list':
			$ret = array(
				$qm->m['plg_bugtrack']['pri1'],
				$qm->m['plg_bugtrack']['pri2'],
				$qm->m['plg_bugtrack']['pri3'],
				$qm->m['plg_bugtrack']['pri4']
			);
			break;

		case 'state_list':
			$ret = array(
				$qm->m['plg_bugtrack']['stt1'],
				$qm->m['plg_bugtrack']['stt2'],
				$qm->m['plg_bugtrack']['stt3'],
				$qm->m['plg_bugtrack']['stt4'],
				$qm->m['plg_bugtrack']['stt5'],
				$qm->m['plg_bugtrack']['stt6']
			);
			break;

		case 'state_sort':
			$ret = array(
				$qm->m['plg_bugtrack']['stt2'],
				$qm->m['plg_bugtrack']['stt3'],
				$qm->m['plg_bugtrack']['stt5'],
				$qm->m['plg_bugtrack']['stt4'],
				$qm->m['plg_bugtrack']['stt1'],
				$qm->m['plg_bugtrack']['stt6']
			);
			break;

		case 'state_bgcolor':
			$ret = array('#ccccff', '#ffcc99', '#ccddcc', '#ccffcc', '#ffccff', '#cccccc', '#ff3333');
			break;

		case 'header_bgcolor':
			$ret = '#ffffcc';
			break;
	}

	return $ret;
}

// #bugtrack: Show bugtrack form
function plugin_bugtrack_convert()
{
	global $vars;

	if (PKWK_READONLY) return ''; // Show nothing

	$base = $vars['page'];
	$category = [];
	if (func_num_args()) {
		$category = func_get_args();
		$_base    = get_fullname(strip_bracket(array_shift($category)), $base);
		if (is_pagename($_base)) $base = $_base;
	}

	return plugin_bugtrack_print_form($base, $category);
}

function plugin_bugtrack_print_form($base, $category)
{
	static $id = 0;
	$qm = get_qm();
	$priority_list = plugin_bugtrack_get('priority_list');
	$state_list = plugin_bugtrack_get('state_list');


	++$id;

	$select_priority = "\n";
	$count = count($priority_list);
	$selected = '';
	for ($i = 0; $i < $count; ++$i) {
		if ($i == ($count - 1)) $selected = ' selected="selected"'; // The last one
		$priority_ls = h($priority_list[$i]);
		$select_priority .= '    <option value="' . $priority_ls . '"' .
			$selected . '>' . $priority_ls . '</option>' . "\n";
	}

	$select_state = "\n";
	for ($i = 0; $i < count($state_list); ++$i) {
		$state_ls = h($state_list[$i]);
		$select_state .= '    <option value="' . $state_ls . '">' .
			$state_ls . '</option>' . "\n";
	}

	if (empty($category)) {
		$encoded_category = '<input name="category" id="_p_bugtrack_category_' . $id .
			'" type="text" />';
	} else {
		$encoded_category = '<select name="category" id="_p_bugtrack_category_' . $id . '">';
		foreach ($category as $_category) {
			$s_category = htmlspecialchars($_category);
			$encoded_category .= '<option value="' . $s_category . '">' .
				$s_category . '</option>' . "\n";
		}
		$encoded_category .= '</select>';
	}

	$script     = get_script_uri();
	$s_base     = htmlspecialchars($base);
	$s_name     = htmlspecialchars($qm->m['plg_bugtrack']['name']);
	$s_category = htmlspecialchars($qm->m['plg_bugtrack']['category']);
	$s_priority = htmlspecialchars($qm->m['plg_bugtrack']['priority']);
	$s_state    = htmlspecialchars($qm->m['plg_bugtrack']['state']);
	$s_pname    = htmlspecialchars($qm->m['plg_bugtrack']['pagename']);
	$s_pnamec   = htmlspecialchars($qm->m['plg_bugtrack']['ntc_pagename']);
	$s_version  = htmlspecialchars($qm->m['plg_bugtrack']['version']);
	$s_versionc = htmlspecialchars($qm->m['plg_bugtrack']['ntc_version']);
	$s_summary  = htmlspecialchars($qm->m['plg_bugtrack']['summary']);
	$s_body     = htmlspecialchars($qm->m['plg_bugtrack']['body']);
	$s_submit   = htmlspecialchars($qm->m['plg_bugtrack']['submit']);
	$body = <<<EOD
<form action="$script" method="post">
 <table border="0">
  <tr>
   <th><label for="_p_bugtrack_name_$id">$s_name</label></th>
   <td><input  id="_p_bugtrack_name_$id" name="name" size="20" type="text" /></td>
  </tr>
  <tr>
   <th><label for="_p_bugtrack_category_$id">$s_category</label></th>
   <td>$encoded_category</td>
  </tr>
  <tr>
   <th><label for="_p_bugtrack_priority_$id">$s_priority</label></th>
   <td><select id="_p_bugtrack_priority_$id" name="priority">$select_priority   </select></td>
  </tr>
  <tr>
   <th><label for="_p_bugtrack_state_$id">$s_state</label></th>
   <td><select id="_p_bugtrack_state_$id" name="state">$select_state   </select></td>
  </tr>
  <tr>
   <th><label for="_p_bugtrack_pagename_$id">$s_pname</label></th>
   <td><input  id="_p_bugtrack_pagename_$id" name="pagename" size="20" type="text" />
    <small>$s_pnamec</small></td>
  </tr>
  <tr>
   <th><label for="_p_bugtrack_version_$id">$s_version</label></th>
   <td><input  id="_p_bugtrack_version_$id" name="version" size="10" type="text" />
    <small>$s_versionc</small></td>
  </tr>
  <tr>
   <th><label for="_p_bugtrack_summary_$id">$s_summary</label></th>
   <td><input  id="_p_bugtrack_summary_$id" name="summary" size="60" type="text" /></td>
  </tr>
  <tr>
   <th><label   for="_p_bugtrack_body_$id">$s_body</label></th>
   <td><textarea id="_p_bugtrack_body_$id" name="body" cols="60" rows="6"></textarea></td>
  </tr>
  <tr>
   <td colspan="2" align="center">
    <input type="submit" value="$s_submit" />
    <input type="hidden" name="plugin" value="bugtrack" />
    <input type="hidden" name="mode"   value="submit" />
    <input type="hidden" name="base"   value="$s_base" />
   </td>
  </tr>
 </table>
</form>
EOD;

	return $body;
}

// Add new issue
function plugin_bugtrack_action()
{
	global $post;

	if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');
	if ($post['mode'] != 'submit') return FALSE;

	$page = plugin_bugtrack_write(
		$post['base'],
		$post['pagename'],
		$post['summary'],
		$post['name'],
		$post['priority'],
		$post['state'],
		$post['category'],
		$post['version'],
		$post['body']
	);

	pkwk_headers_sent();
	header('Location: ' . get_script_uri() . '?' . rawurlencode($page));
	exit;
}

function plugin_bugtrack_write($base, $pagename, $summary, $name, $priority, $state, $category, $version, $body)
{
	global $post;

	$base     = strip_bracket($base);
	$pagename = strip_bracket($pagename);

	$postdata = plugin_bugtrack_template(
		$base,
		$summary,
		$name,
		$priority,
		$state,
		$category,
		$version,
		$body
	);

	$id = $jump = 1;
	$page = $base . '/' . sprintf(PLUGIN_BUGTRACK_NUMBER_FORMAT, $id);
	while (is_page($page)) {
		$id   = $jump;
		$jump += 50;
		$page = $base . '/' . sprintf(PLUGIN_BUGTRACK_NUMBER_FORMAT, $jump);
	}
	$page = $base . '/' . sprintf(PLUGIN_BUGTRACK_NUMBER_FORMAT, $id);
	while (is_page($page))
		$page = $base . '/' . sprintf(PLUGIN_BUGTRACK_NUMBER_FORMAT, ++$id);

	if ($pagename == '') {
		page_write($page, $postdata);
	} else {
		$pagename = get_fullname($pagename, $base);
		if (is_page($pagename) || ! is_pagename($pagename)) {
			$pagename = $page; // Set default
		} else {
			page_write($page, 'move to [[' . $pagename . ']]');
		}
		page_write($pagename, $postdata);
	}

	return $page;
}

// Generate new page contents
function plugin_bugtrack_template($base, $summary, $name, $priority, $state, $category, $version, $body)
{
	global $WikiName;
	$qm = get_qm();

	if (! preg_match("/^$WikiName$$/", $base)) $base = '[[' . $base . ']]';
	if ($name != '' && ! preg_match("/^$WikiName$$/", $name)) $name = '[[' . $name . ']]';

	if ($name    == '') $name    = $qm->m['plg_bugtrack']['noname'];
	if ($summary == '') $summary = $qm->m['plg_bugtrack']['nosummary'];

	return <<<EOD
* $summary

- ${_plugin_bugtrack['base']}: $base
- ${_plugin_bugtrack['name']}: $name
- ${_plugin_bugtrack['priority']}: $priority
- ${_plugin_bugtrack['state']}: $state
- ${_plugin_bugtrack['category']}: $category
- ${_plugin_bugtrack['date']}: now?
- ${_plugin_bugtrack['version']}: $version

** ${_plugin_bugtrack['body']}
$body
--------

#comment
EOD;
}

// ----------------------------------------
// BugTrack-List plugin

// #bugtrack_list plugin itself
function plugin_bugtrack_list_convert()
{
	global $script, $vars;
	$state_list = plugin_bugtrack_get('state_list');
	$state_sort = plugin_bugtrack_get('state_sort');
	$state_bgcolor = plugin_bugtrack_get('state_bgcolor');
	$header_bgcolor = plugin_bugtrack_get('header_bgcolor');

	$page = $vars['page'];
	if (func_num_args()) {
		list($_page) = func_get_args();
		$_page = get_fullname(strip_bracket($_page), $page);
		if (is_pagename($_page)) $page = $_page;
	}

	$data = [];
	$pattern = $page . '/';
	$pattern_len = strlen($pattern);
	foreach (get_existpages() as $page)
		if (strpos($page, $pattern) === 0 && is_numeric(substr($page, $pattern_len)))
			array_push($data, plugin_bugtrack_list_pageinfo($page));

	$count_list = count($state_list);

	$table = [];
	for ($i = 0; $i <= $count_list + 1; ++$i) $table[$i] = [];

	foreach ($data as $line) {
		list($page, $no, $summary, $name, $priority, $state, $category) = $line;
		foreach (array('summary', 'name', 'priority', 'state', 'category') as $item)
			$$item = htmlspecialchars($$item);
		$page_link = make_pagelink($page);

		$state_no = array_search($state, $state_sort);
		if ($state_no === NULL || $state_no === FALSE) $state_no = $count_list;
		$bgcolor = htmlspecialchars($state_bgcolor[$state_no]);

		$row = <<<EOD
 <tr>
  <td style="background-color:$bgcolor">$page_link</td>
  <td style="background-color:$bgcolor">$state</td>
  <td style="background-color:$bgcolor">$priority</td>
  <td style="background-color:$bgcolor">$category</td>
  <td style="background-color:$bgcolor">$name</td>
  <td style="background-color:$bgcolor">$summary</td>
 </tr>
EOD;
		$table[$state_no][$no] = $row;
	}

	$table_html = ' <tr>' . "\n";
	$bgcolor = htmlspecialchars($header_bgcolor);
	foreach (array('pagename', 'state', 'priority', 'category', 'name', 'summary') as $item)
		$table_html .= '  <th style="background-color:' . $bgcolor . '">' .
			htmlspecialchars($qm->m['plg_bugtrack'][$item]) . '</th>' . "\n";
	$table_html .= ' </tr>' . "\n";

	for ($i = 0; $i <= $count_list; ++$i) {
		ksort($table[$i], SORT_NUMERIC);
		$table_html .= join("\n", $table[$i]);
	}

	return '<table border="1" width="100%">' . "\n" .
		$table_html . "\n" .
		'</table>';
}

// Get one set of data from a page (or a page moved to $page)
function plugin_bugtrack_list_pageinfo($page, $no = NULL, $recurse = TRUE)
{
	global $WikiName, $InterWikiName, $BracketName;

	if ($no === NULL)
		$no = preg_match('/\/([0-9]+)$/', $page, $matches) ? $matches[1] : 0;

	$source = get_source($page);

	// Check 'moved' page _just once_
	$regex  = "/move\s*to\s*($WikiName|$InterWikiName|\[\[$BracketName\]\])/";
	$match  = [];
	if ($recurse && preg_match($regex, $source[0], $match))
		return plugin_bugtrack_list_pageinfo(strip_bracket($match[1]), $no, FALSE);

	$body = join("\n", $source);
	foreach (array('summary', 'name', 'priority', 'state', 'category') as $item) {
		$regex = '/-\s*' . preg_quote($qm->m['plg_bugtrack'][$item], '/') . '\s*:(.*)/';
		if (preg_match($regex, $body, $matches)) {
			if ($item == 'name') {
				$$item = strip_bracket(trim($matches[1]));
			} else {
				$$item = trim($matches[1]);
			}
		} else {
			$$item = ''; // Data not found
		}
	}

	if (preg_match("/\*([^\n]*)/", $body, $matches)) {
		$summary = $matches[1];
		make_heading($summary);
	}

	return array($page, $no, $summary, $name, $priority, $state, $category);
}
