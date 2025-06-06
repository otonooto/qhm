<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: comment.inc.php,v 1.36 2006/01/28 14:54:51 teanan Exp $
// Copyright (C)
//   2002-2005 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// Comment plugin

define('PLUGIN_COMMENT_DIRECTION_DEFAULT', '1'); // 1: above 0: below
define('PLUGIN_COMMENT_SIZE_MSG',  70);
define('PLUGIN_COMMENT_SIZE_NAME', 15);

// ----
define('PLUGIN_COMMENT_FORMAT_MSG',  '$msg');
define('PLUGIN_COMMENT_FORMAT_NAME', '[[$name]]');
define('PLUGIN_COMMENT_FORMAT_NOW',  '&new{$now};');
define('PLUGIN_COMMENT_FORMAT_STRING', "\x08MSG\x08 -- \x08NAME\x08 \x08NOW\x08");

function plugin_comment_action()
{
	global $script, $vars, $now;
	$qm = get_qm();

	if (PKWK_READONLY) die_message($qm->m['fmt_err_pkwk_readonly']);

	if (! isset($vars['msg'])) return array('msg' => '', 'body' => ''); // Do nothing

	$vars['msg'] = str_replace("\n", '', $vars['msg']); // Cut LFs
	$head = '';
	$match = [];
	if (preg_match('/^(-{1,2})-*\s*(.*)/', $vars['msg'], $match)) {
		$head        = &$match[1];
		$vars['msg'] = &$match[2];
	}
	if ($vars['msg'] == '') return array('msg' => '', 'body' => ''); // Do nothing

	$comment  = str_replace('$msg', $vars['msg'], PLUGIN_COMMENT_FORMAT_MSG);
	if (isset($vars['name']) || ($vars['nodate'] != '1')) {
		$_name = (! isset($vars['name']) || $vars['name'] == '') ? $qm->m['plg_comment']['no_name'] : $vars['name'];
		$_name = ($_name == '') ? '' : str_replace('$name', $_name, PLUGIN_COMMENT_FORMAT_NAME);
		$_now  = ($vars['nodate'] == '1') ? '' :
			str_replace('$now', $now, PLUGIN_COMMENT_FORMAT_NOW);
		$comment = str_replace("\x08MSG\x08",  $comment, PLUGIN_COMMENT_FORMAT_STRING);
		$comment = str_replace("\x08NAME\x08", $_name, $comment);
		$comment = str_replace("\x08NOW\x08",  $_now,  $comment);
	}
	$comment = '-' . $head . ' ' . $comment;

	$postdata    = '';
	$comment_no  = 0;
	$above       = (isset($vars['above']) && $vars['above'] == '1');
	foreach (get_source($vars['refer']) as $line) {
		if (! $above) $postdata .= $line;
		if (preg_match('/^#comment/i', $line) && $comment_no++ == $vars['comment_no']) {
			if ($above) {
				$postdata = rtrim($postdata) . "\n" .
					$comment . "\n" .
					"\n";  // Insert one blank line above #commment, to avoid indentation
			} else {
				$postdata = rtrim($postdata) . "\n" .
					$comment . "\n"; // Insert one blank line below #commment
			}
		}
		if ($above) $postdata .= $line;
	}

	$title = $qm->m['fmt_title_updated'];
	$body = '';
	if (md5(@join('', get_source($vars['refer']))) != $vars['digest']) {
		$title = $qm->m['plg_comment']['title_collided'];
		$body  = $qm->m['plg_comment']['wng_collided'] . make_pagelink($vars['refer']);
	}

	page_write($vars['refer'], $postdata);

	$retvars['msg']  = $title;
	$retvars['body'] = $body;

	$vars['page'] = $vars['refer'];

	return $retvars;
}

function plugin_comment_convert()
{
	global $vars, $digest;
	static $numbers = [];
	static $comment_cols = PLUGIN_COMMENT_SIZE_MSG;
	$qm = get_qm();

	if (PKWK_READONLY) return ''; // Show nothing

	if (! isset($numbers[$vars['page']])) $numbers[$vars['page']] = 0;
	$comment_no = $numbers[$vars['page']]++;

	$options = func_num_args() ? func_get_args() : [];
	if (in_array('noname', $options)) {
		$nametags = '<label for="_p_comment_comment_' . $comment_no . '">' .
			$qm->m['plg_comment']['label'] . '</label>';
	} else {
		$nametags = '<label for="_p_comment_name_' . $comment_no . '">' .
			$qm->m['plg_comment']['label_name'] . '</label>' .
			'<input type="text" name="name" id="_p_comment_name_' .
			$comment_no .  '" size="' . PLUGIN_COMMENT_SIZE_NAME .
			'" />' . "\n";
	}
	$nodate = in_array('nodate', $options) ? '1' : '0';
	$above  = in_array('above',  $options) ? '1' : (in_array('below', $options) ? '0' : PLUGIN_COMMENT_DIRECTION_DEFAULT);

	$script = get_script_uri();
	$s_page = htmlspecialchars($vars['page']);
	$string = <<<EOD
<br />
<form action="$script" method="post">
 <div>
  <input type="hidden" name="plugin" value="comment" />
  <input type="hidden" name="refer"  value="$s_page" />
  <input type="hidden" name="comment_no" value="$comment_no" />
  <input type="hidden" name="nodate" value="$nodate" />
  <input type="hidden" name="above"  value="$above" />
  <input type="hidden" name="digest" value="$digest" />
  $nametags
  <input type="text"   name="msg" id="_p_comment_comment_{$comment_no}" size="$comment_cols" />
  <input type="submit" name="comment" value="{$qm->m['plg_comment']['btn_comment']}" />
 </div>
</form>
EOD;

	return $string;
}
