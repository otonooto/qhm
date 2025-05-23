<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: pcomment.inc.php,v 1.43 2005/10/04 14:31:22 henoheno Exp $
//
// pcomment plugin - Show/Insert comments into specified (another) page
//
// Usage: #pcomment([page][,max][,options])
//
//   page -- An another page-name that holds comments
//           (default:PLUGIN_PCOMMENT_PAGE)
//   max  -- Max number of recent comments to show
//           (0:Show all, default:PLUGIN_PCOMMENT_NUM_COMMENTS)
//
// Options:
//   above -- Comments are listed above the #pcomment (added by chronological order)
//   below -- Comments are listed below the #pcomment (by reverse order)
//   reply -- Show radio buttons allow to specify where to reply

define('PLUGIN_PCOMMENT_NUM_COMMENTS',     10); // Default 'latest N posts'
define('PLUGIN_PCOMMENT_DIRECTION_DEFAULT', 1); // 1: above 0: below
define('PLUGIN_PCOMMENT_SIZE_MSG',  70);
define('PLUGIN_PCOMMENT_SIZE_NAME', 15);

// Auto log rotation
define('PLUGIN_PCOMMENT_AUTO_LOG', 0); // 0:off 1-N:number of comments per page

// Update recording page's timestamp instead of parent's page itself
define('PLUGIN_PCOMMENT_TIMESTAMP', 0);

// ----
define('PLUGIN_PCOMMENT_FORMAT_NAME',	'[[$name]]');
define('PLUGIN_PCOMMENT_FORMAT_MSG',	'$msg');
define('PLUGIN_PCOMMENT_FORMAT_NOW',	'&new{$now};');

// "\x01", "\x02", "\x03", and "\x08" are used just as markers
define(
	'PLUGIN_PCOMMENT_FORMAT_STRING',
	"\x08" . 'MSG' . "\x08" . ' -- ' . "\x08" . 'NAME' . "\x08" . ' ' . "\x08" . 'DATE' . "\x08"
);

function plugin_pcomment_action()
{
	global $vars;

	if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');

	if (! isset($vars['msg']) || $vars['msg'] == '') return [];
	$refer = isset($vars['refer']) ? $vars['refer'] : '';

	$retval = plugin_pcomment_insert();
	if ($retval['collided']) {
		$vars['page'] = $refer;
		return $retval;
	}

	pkwk_headers_sent();
	header('Location: ' . get_script_uri() . '?' . rawurlencode($refer));
	exit;
}

function plugin_pcomment_convert()
{
	global $vars;
	$qm = get_qm();
	$qt = get_qt();

	$ret = '';

	$params = array(
		'noname' => FALSE,
		'nodate' => FALSE,
		'below' => FALSE,
		'above' => FALSE,
		'reply' => FALSE,
		'_args' => []
	);

	// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
	$args = func_get_args(); // with array_walk()
	array_walk($args, 'plugin_pcomment_check_arg', $params);

	$vars_page = isset($vars['page']) ? $vars['page'] : '';
	$page  = (isset($params['_args'][0]) && $params['_args'][0] != '') ? $params['_args'][0] :
		$qm->replace('plg_pcomment.page', strip_bracket($vars_page));
	$count = (isset($params['_args'][1]) && $params['_args'][1] != '') ? $params['_args'][1] : 0;
	if ($count == 0 && $count !== '0')
		$count = PLUGIN_PCOMMENT_NUM_COMMENTS;

	//キャッシュのために、追加
	if (!in_array(strip_bracket($page), $qt->get_rel_pages()))
		$qt->set_rel_page(strip_bracket($page));

	$_page = get_fullname(strip_bracket($page), $vars_page);
	if (!is_pagename($_page))
		return sprintf($qm->m['plg_pcomment']['err_pagename'], htmlspecialchars($_page));

	$dir = PLUGIN_PCOMMENT_DIRECTION_DEFAULT;
	if ($params['below']) {
		$dir = 0;
	} elseif ($params['above']) {
		$dir = 1;
	}

	list($comments, $digest) = plugin_pcomment_get_comments($_page, $count, $dir, $params['reply']);

	if (PKWK_READONLY) {
		$form_start = $form = $form_end = '';
	} else {
		// Show a form

		if ($params['noname']) {
			$title = $qm->m['plg_pcomment']['comment'];
			$name = '';
		} else {
			$title = $qm->m['plg_pcomment']['btn_name'];
			$name = '<input type="text" name="name" size="' . PLUGIN_PCOMMENT_SIZE_NAME . '" />';
		}

		$radio   = $params['reply'] ?
			'<input type="radio" name="reply" value="0" tabindex="0" checked="checked" />' : '';
		$comment = '<input type="text" name="msg" size="' . PLUGIN_PCOMMENT_SIZE_MSG . '" />';

		$s_page   = htmlspecialchars($page);
		$s_refer  = htmlspecialchars($vars_page);
		$s_nodate = htmlspecialchars($params['nodate']);
		$s_count  = htmlspecialchars($count);

		$form_start = '<form action="' . get_script_uri() . '" method="post">' . "\n";
		$form = <<<EOD
  <div>
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="plugin" value="pcomment" />
  <input type="hidden" name="refer"  value="$s_refer" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="nodate" value="$s_nodate" />
  <input type="hidden" name="dir"    value="$dir" />
  <input type="hidden" name="count"  value="$count" />
  $radio $title $name $comment
  <input type="submit" value="{$qm->m['plg_pcomment']['btn_comment']}" />
  </div>
EOD;
		$form_end = '</form>' . "\n";
	}

	if (! is_page($_page)) {
		$link   = make_pagelink($_page);
		$recent = $qm->m['plg_pcomment']['nothing'];
	} else {
		$msg    = ($qm->m['plg_pcomment']['all'] != '') ? $qm->m['plg_pcomment']['all'] : $_page;
		$link   = make_pagelink($_page, $msg);
		$recent = ! empty($count) ? sprintf($qm->m['plg_pcomment']['recent'], $count) : '';
	}

	if ($dir) {
		return '<div>' .
			'<p>' . $recent . ' ' . $link . '</p>' . "\n" .
			$form_start .
			$comments . "\n" .
			$form .
			$form_end .
			'</div>' . "\n";
	} else {
		return '<div>' .
			$form_start .
			$form .
			$comments . "\n" .
			$form_end .
			'<p>' . $recent . ' ' . $link . '</p>' . "\n" .
			'</div>' . "\n";
	}
}

function plugin_pcomment_insert()
{
	global $script, $vars, $now;
	$qm = get_qm();

	$refer = isset($vars['refer']) ? $vars['refer'] : '';
	$page  = isset($vars['page'])  ? $vars['page']  : '';
	$page  = get_fullname($page, $refer);

	if (! is_pagename($page))
		return array(
			'msg' => 'Invalid page name',
			'body' => 'Cannot add comment',
			'collided' => TRUE
		);

	check_editable($page, true, true);

	$ret = array('msg' => $qm->m['fmt_title_updated'], 'collided' => FALSE);

	$msg = str_replace('$msg', rtrim($vars['msg']), PLUGIN_PCOMMENT_FORMAT_MSG);
	$name = (! isset($vars['name']) || $vars['name'] == '') ? $qm->m['fmt_no_name'] : $vars['name'];
	$name = ($name == '') ? '' : str_replace('$name', $name, PLUGIN_PCOMMENT_FORMAT_NAME);
	$date = (! isset($vars['nodate']) || $vars['nodate'] != '1') ?
		str_replace('$now', $now, PLUGIN_PCOMMENT_FORMAT_NOW) : '';
	if ($date != '' || $name != '') {
		$msg = str_replace("\x08" . 'MSG'  . "\x08", $msg,  PLUGIN_PCOMMENT_FORMAT_STRING);
		$msg = str_replace("\x08" . 'NAME' . "\x08", $name, $msg);
		$msg = str_replace("\x08" . 'DATE' . "\x08", $date, $msg);
	}

	$reply_hash = isset($vars['reply']) ? $vars['reply'] : '';
	if ($reply_hash || ! is_page($page)) {
		$msg = preg_replace('/^\-+/', '', $msg);
	}
	$msg = rtrim($msg);

	if (! is_page($page)) {
		$postdata = '[[' . htmlspecialchars(strip_bracket($refer)) . ']]' . "\n\n" .
			'-' . $msg . "\n";
	} else {
		$postdata = get_source($page);
		$count    = count($postdata);

		$digest = isset($vars['digest']) ? $vars['digest'] : '';
		if (md5(join('', $postdata)) != $digest) {
			$ret['msg']  = $qm->m['plg_pcomment']['title_collided'];
			$ret['body'] = $qm->m['plg_pcomment']['collided'];
		}

		$start_position = 0;
		while ($start_position < $count) {
			if (preg_match('/^\-/', $postdata[$start_position])) break;
			++$start_position;
		}
		$end_position = $start_position;

		$dir = isset($vars['dir']) ? $vars['dir'] : '';

		// Find the comment to reply
		$level   = 1;
		$b_reply = FALSE;
		if ($reply_hash != '') {
			while ($end_position < $count) {
				$matches = [];
				if (
					preg_match('/^(\-{1,2})(?!\-)(.*)$/', $postdata[$end_position++], $matches)
					&& md5($matches[2]) == $reply_hash
				) {
					$b_reply = TRUE;
					$level   = strlen($matches[1]) + 1;

					while ($end_position < $count) {
						if (
							preg_match('/^(\-{1,3})(?!\-)/', $postdata[$end_position], $matches)
							&& strlen($matches[1]) < $level
						) break;
						++$end_position;
					}
					break;
				}
			}
		}

		if ($b_reply == FALSE)
			$end_position = ($dir == '0') ? $start_position : $count;

		// Insert new comment
		array_splice($postdata, $end_position, 0, str_repeat('-', $level) . $msg . "\n");

		if (PLUGIN_PCOMMENT_AUTO_LOG) {
			$_count = isset($vars['count']) ? $vars['count'] : '';
			plugin_pcomment_auto_log($page, $dir, $_count, $postdata);
		}

		$postdata = join('', $postdata);
	}
	page_write($page, $postdata, PLUGIN_PCOMMENT_TIMESTAMP);

	if (PLUGIN_PCOMMENT_TIMESTAMP) {
		if ($refer != '') pkwk_touch_file(get_filename($refer));
		put_lastmodified();
	}

	return $ret;
}

// Auto log rotation
function plugin_pcomment_auto_log($page, $dir, $count, &$postdata)
{
	if (! PLUGIN_PCOMMENT_AUTO_LOG) return;

	$keys = array_keys(preg_grep('/(?:^-(?!-).*$)/m', $postdata));
	if (count($keys) < (PLUGIN_PCOMMENT_AUTO_LOG + $count)) return;

	if ($dir) {
		// Top N comments (N = PLUGIN_PCOMMENT_AUTO_LOG)
		$old = array_splice($postdata, $keys[0], $keys[PLUGIN_PCOMMENT_AUTO_LOG] - $keys[0]);
	} else {
		// Bottom N comments
		$old = array_splice($postdata, $keys[count($keys) - PLUGIN_PCOMMENT_AUTO_LOG]);
	}

	// Decide new page name
	$i = 0;
	do {
		++$i;
		$_page = $page . '/' . $i;
	} while (is_page($_page));

	page_write($_page, '[[' . $page . ']]' . "\n\n" . join('', $old));

	// Recurse :)
	plugin_pcomment_auto_log($page, $dir, $count, $postdata);
}

// Check arguments
function plugin_pcomment_check_arg($val, $key, &$params)
{
	if ($val != '') {
		$l_val = strtolower($val);
		foreach (array_keys($params) as $key) {
			if (strpos($key, $l_val) === 0) {
				$params[$key] = TRUE;
				return;
			}
		}
	}

	$params['_args'][] = $val;
}

function plugin_pcomment_get_comments($page, $count, $dir, $reply)
{
	$qm = get_qm();

	if (! check_readable($page, false, false))
		return array(str_replace('$1', $page, $qm->m['plg_pcomment']['err_restrict']));

	$reply = (! PKWK_READONLY && $reply); // Suprress radio-buttons

	$data = get_source($page);
	$data = preg_replace('/^#pcomment\(?.*/i', '', $data);	// Avoid eternal recurse

	if (! is_array($data)) return array('', 0);

	$digest = md5(join('', $data));

	// Get latest N comments
	$num  = $cnt     = 0;
	$cmts = $matches = [];
	if ($dir) $data = array_reverse($data);
	foreach ($data as $line) {
		if ($count > 0 && $dir && $cnt == $count) break;

		if (preg_match('/^(\-{1,2})(?!\-)(.+)$/', $line, $matches)) {
			if ($count > 0 && strlen($matches[1]) == 1 && ++$cnt > $count) break;

			// Ready for radio-buttons
			if ($reply) {
				++$num;
				$cmts[] = $matches[1] . "\x01" . $num . "\x02" .
					md5($matches[2]) . "\x03" . $matches[2] . "\n";
				continue;
			}
		}
		$cmts[] = $line;
	}
	$data = $cmts;
	if ($dir) $data = array_reverse($data);
	unset($cmts, $matches);

	// Remove lines before comments
	while (! empty($data) && substr($data[0], 0, 1) != '-')
		array_shift($data);

	$comments = convert_html($data);
	unset($data);

	// Add radio buttons
	if ($reply)
		$comments = preg_replace(
			'/<li>' . "\x01" . '(\d+)' . "\x02" . '(.*)' . "\x03" . '/',
			'<li class="pcmt"><input class="pcmt" type="radio" name="reply" value="$2" tabindex="$1" />',
			$comments
		);

	return array($comments, $digest);
}
