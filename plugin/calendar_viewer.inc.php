<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: calendar_viewer.inc.php,v 1.34 2005/06/05 12:46:59 henoheno Exp $
//
// Calendar viewer plugin - List pages that calendar/calnedar2 plugin created
// (Based on calendar and recent plugin)

// Page title's date format
//  * See PHP date() manual for detail
//  * '$\w' = weeklabel defined in $_msg_week
define(
	'PLUGIN_CALENDAR_VIEWER_DATE_FORMAT',
	//	FALSE         // 'pagename/2004-02-09' -- As is
	//	'D, d M, Y'   // 'Mon, 09 Feb, 2004'
	//	'F d, Y'      // 'February 09, 2004'
	//	'[Y-m-d]'     // '[2004-02-09]'
	'Y/n/j ($\w)' // '2004/2/9 (Mon)'
);

/*
 ** pagename
 * - A working root of calendar or calendar2 plugin
 *   pagename/2004-12-30
 *   pagename/2004-12-31
 *   ...
 *
 ** (yyyy-mm|n|this)
 * this    - Show 'this month'
 * yyyy-mm - Show pages at year:yyyy and month:mm
 * n       - Show first n pages
 * x*n     - Show first n pages from x-th page (0 = first)
 *
 ** [mode]
 * past   - Show today, and the past below. Recommended for ChangeLogs and diaries (default)
 * future - Show today, and the future below. Recommended for event planning and scheduling
 * view   - Show all, from the past to the future
 *
 ** [separater]
 * - Specify separator of yyyy/mm/dd
 * - Default: '-' (yyyy-mm-dd)
 *
 * TODO
 *  Stop showing links 'next month' and 'previous month' with past/future mode for 'this month'
 *    #calendar_viewer(pagename,this,past)
 */

function plugin_calendar_viewer_convert()
{
	global $vars, $get, $post, $script, $weeklabels;
	$qm = get_qm();

	static $viewed = [];

	if (func_num_args() < 2)
		return $qm->replace('fmt_err_cvt', 'calendar_viewer', $qm->m['plg_calendar_viewer']['err_usage']);

	$func_args = func_get_args();

	// Default values
	$pagename    = $func_args[0];	// 基準となるページ名
	$page_YM     = '';	// 一覧表示する年月
	$limit_base  = 0;	// 先頭から数えて何ページ目から表示するか (先頭)
	$limit_pitch = 0;	// 何件づつ表示するか
	$limit_page  = 0;	// サーチするページ数
	$mode        = 'past';	// 動作モード
	$date_sep    = '-';	// 日付のセパレータ calendar2なら '-', calendarなら ''

	// Check $func_args[1]
	$matches = [];
	if (preg_match('/[0-9]{4}' . $date_sep . '[0-9]{2}/', $func_args[1])) {
		// 指定年月の一覧表示
		$page_YM     = $func_args[1];
		$limit_page  = 31;
	} else if (preg_match('/this/si', $func_args[1])) {
		// 今月の一覧表示
		$page_YM     = get_date('Y' . $date_sep . 'm');
		$limit_page  = 31;
	} else if (preg_match('/^[0-9]+$/', $func_args[1])) {
		// n日分表示
		$limit_pitch = $func_args[1];
		$limit_page  = $func_args[1];
	} else if (preg_match('/(-?[0-9]+)\*([0-9]+)/', $func_args[1], $matches)) {
		// 先頭より数えて x ページ目から、y件づつ表示
		$limit_base  = $matches[1];
		$limit_pitch = $matches[2];
		$limit_page  = $matches[1] + $matches[2]; // 読み飛ばす + 表示する
	} else {
		return $qm->replace('fmt_err_cvt', 'calendar_viewer', $qm->m['plg_calendar_viewer']['err_param2']);
	}

	// $func_args[2]: Mode setting
	if (isset($func_args[2]) && preg_match('/^(past|view|future)$/si', $func_args[2]))
		$mode = $func_args[2];

	// $func_args[3]: Change default delimiter
	if (isset($func_args[3])) $date_sep = $func_args[3];

	// Avoid Loop etc.
	if (isset($viewed[$pagename])) {
		$s_page = htmlspecialchars($pagename);
		return "#calendar_viewer(): You already view: $s_page<br />";
	} else {
		$viewed[$pagename] = TRUE; // Valid
	}

	// 一覧表示するページ名とファイル名のパターン　ファイル名には年月を含む
	if ($pagename == '') {
		// pagename無しのyyyy-mm-ddに対応するための処理
		$pagepattern     = '';
		$pagepattern_len = 0;
		$filepattern     = encode($page_YM);
		$filepattern_len = strlen($filepattern);
	} else {
		$pagepattern     = strip_bracket($pagename) . '/';
		$pagepattern_len = strlen($pagepattern);
		$filepattern     = encode($pagepattern . $page_YM);
		$filepattern_len = strlen($filepattern);
	}

	// ページリストの取得
	$pagelist = [];
	if ($dir = @opendir(DATA_DIR)) {
		$_date = get_date('Y' . $date_sep . 'm' . $date_sep . 'd');
		$page_date  = '';
		while ($file = readdir($dir)) {
			if ($file == '..' || $file == '.') continue;
			if (substr($file, 0, $filepattern_len) != $filepattern) continue;

			$page      = decode(trim(preg_replace('/\.txt$/', ' ', $file)));
			$page_date = substr($page, $pagepattern_len);

			// Verify the $page_date pattern (Default: yyyy-mm-dd).
			// Past-mode hates the future, and
			// Future-mode hates the past.
			if ((plugin_calendar_viewer_isValidDate($page_date, $date_sep) == FALSE) ||
				($page_date > $_date && ($mode == 'past')) ||
				($page_date < $_date && ($mode == 'future'))
			)
				continue;

			$pagelist[] = $page;
		}
	}
	closedir($dir);

	if ($mode == 'past') {
		rsort($pagelist);	// New => Old
	} else {
		sort($pagelist);	// Old => New
	}

	// Include start
	$tmppage     = $vars['page'];
	$return_body = '';

	// $limit_page の件数までインクルード
	$tmp = max($limit_base, 0); // Skip minus
	while ($tmp < $limit_page) {
		if (! isset($pagelist[$tmp])) break;

		$page = $pagelist[$tmp];
		$get['page'] = $post['page'] = $vars['page'] = $page;

		// 現状で閲覧許可がある場合だけ表示する
		if (check_readable($page, FALSE, FALSE)) {
			$body = convert_html(get_source($page));
		} else {
			$body = $qm->replace('plg_calendar_viewer.ntc_restrict', $page);
		}

		$r_page = rawurlencode($page);

		if (PLUGIN_CALENDAR_VIEWER_DATE_FORMAT !== FALSE) {
			$time = strtotime(basename($page)); // $date_sep must be assumed '-' or ''!
			if ($time == -1) {
				$s_page = htmlspecialchars($page); // Failed. Why?
			} else {
				$week   = $weeklabels[date('w', $time)];
				$s_page = htmlspecialchars(str_replace(
					array('$w'),
					array($week),
					date(PLUGIN_CALENDAR_VIEWER_DATE_FORMAT, $time)
				));
			}
		} else {
			$s_page = htmlspecialchars($page);
		}

		if (PKWK_READONLY) {
			$link   = $script . '?' . $r_page;
		} else {
			$editable = check_editable($page, FALSE, FALSE);
			// 管理ログイン後
			if ($editable === TRUE) {
				$link   = $script . '?cmd=edit&amp;page=' . $r_page;
			} else {
				$link   = $script . '?' . $r_page;
			}
		}
		$link   = '<a href="' . $link . '">' . $s_page . '</a>';

		$head   = '<h2 class="calender">' . $link . '</h2>' . "\n";
		$return_body .= $head . $body;

		++$tmp;
	}

	// ここで、前後のリンクを表示
	// ?plugin=calendar_viewer&file=ページ名&date=yyyy-mm
	$enc_pagename = rawurlencode(substr($pagepattern, 0, $pagepattern_len - 1));

	if ($page_YM != '') {
		// 年月表示時
		$date_sep_len = strlen($date_sep);
		$this_year    = substr($page_YM, 0, 4);
		$this_month   = substr($page_YM, 4 + $date_sep_len, 2);

		// 次月
		$next_year  = $this_year;
		$next_month = $this_month + 1;
		if ($next_month > 12) {
			++$next_year;
			$next_month = 1;
		}
		$next_YM = sprintf('%04d%s%02d', $next_year, $date_sep, $next_month);

		// 前月
		$prev_year  = $this_year;
		$prev_month = $this_month - 1;
		if ($prev_month < 1) {
			--$prev_year;
			$prev_month = 12;
		}
		$prev_YM = sprintf('%04d%s%02d', $prev_year, $date_sep, $prev_month);
		if ($mode == 'past') {
			$right_YM   = $prev_YM;
			$right_text = $prev_YM . '&gt;&gt;'; // >>
			$left_YM    = $next_YM;
			$left_text  = '&lt;&lt;' . $next_YM; // <<
		} else {
			$left_YM    = $prev_YM;
			$left_text  = '&lt;&lt;' . $prev_YM; // <<
			$right_YM   = $next_YM;
			$right_text = $next_YM . '&gt;&gt;'; // >>
		}
	} else {
		// n件表示時
		if ($limit_base <= 0) {
			$left_YM = ''; // 表示しない (それより前の項目はない)
		} else {
			$left_YM   = $limit_base - $limit_pitch . '*' . $limit_pitch;
			$left_text = sprintf($qm->m['plg_calendar_viewer']['left'], $limit_pitch);
		}
		if ($limit_base + $limit_pitch >= count($pagelist)) {
			$right_YM = ''; // 表示しない (それより後の項目はない)
		} else {
			$right_YM   = $limit_base + $limit_pitch . '*' . $limit_pitch;
			$right_text = sprintf($qm->m['plg_calendar_viewer']['right'], $limit_pitch);
		}
	}

	// ナビゲート用のリンクを末尾に追加
	if ($left_YM != '' || $right_YM != '') {
		$s_date_sep = htmlspecialchars($date_sep);
		$left_link = $right_link = '';
		$link = $script . '?plugin=calendar_viewer&amp;mode=' . $mode .
			'&amp;file=' . $enc_pagename . '&amp;date_sep=' . $s_date_sep . '&amp;';
		if ($left_YM != '')
			$left_link = '<a href="' . $link .
				'date=' . $left_YM . '">' . $left_text . '</a>';
		if ($right_YM != '')
			$right_link = '<a href="' . $link .
				'date=' . $right_YM . '">' . $right_text . '</a>';
		// past modeは<<新 旧>> 他は<<旧 新>>
		$return_body .=
			'<div class="calendar_viewer">' .
			'<span class="calendar_viewer_left">'  . $left_link  . '</span>';
		if ($left_link != '') $return_body .= ' | ';
		$return_body .=
			'<span class="calendar_viewer_right">' . $right_link . '</span>' .
			'</div>';
	}

	$get['page'] = $post['page'] = $vars['page'] = $tmppage;

	return $return_body;
}

function plugin_calendar_viewer_action()
{
	global $vars, $get, $post, $script;

	$date_sep = '-';

	$return_vars_array = [];

	$page = strip_bracket($vars['page']);
	$vars['page'] = '*';
	if (isset($vars['file'])) $vars['page'] = $vars['file'];

	$date_sep = $vars['date_sep'];

	$page_YM = $vars['date'];
	if ($page_YM == '') $page_YM = get_date('Y' . $date_sep . 'm');
	$mode = $vars['mode'];

	$args_array = array($vars['page'], $page_YM, $mode, $date_sep);
	$return_vars_array['body'] = call_user_func_array('plugin_calendar_viewer_convert', $args_array);

	//$return_vars_array['msg'] = 'calendar_viewer ' . $vars['page'] . '/' . $page_YM;
	$return_vars_array['msg'] = 'calendar_viewer ' . htmlspecialchars($vars['page']);
	if ($vars['page'] != '') $return_vars_array['msg'] .= '/';
	if (preg_match('/\*/', $page_YM)) {
		// うーん、n件表示の時はなんてページ名にしたらいい？
	} else {
		$return_vars_array['msg'] .= htmlspecialchars($page_YM);
	}

	$vars['page'] = $page;
	return $return_vars_array;
}

function plugin_calendar_viewer_isValidDate($aStr, $aSepList = '-/ .')
{
	$matches = [];
	if ($aSepList == '') {
		// yyymmddとしてチェック（手抜き(^^;）
		return checkdate(substr($aStr, 4, 2), substr($aStr, 6, 2), substr($aStr, 0, 4));
	} else if (preg_match("/^([0-9]{2,4})[$aSepList]([0-9]{1,2})[$aSepList]([0-9]{1,2})$/", $aStr, $matches)) {
		return checkdate($matches[2], $matches[3], $matches[1]);
	} else {
		return FALSE;
	}
}
