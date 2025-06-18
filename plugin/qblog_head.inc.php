<?php

/**
 *   QBlog display post-head plugin
 *   -------------------------------------------
 *   ./plugin/qblog_head.inc.php
 *   
 *   Copyright (c) 2012 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 12/07/30
 *   modified :
 *   
 *   Description
 *   
 *   Usage :
 *   
 */

function plugin_qblog_head_convert()
{
	global $vars, $script, $defaultpage, $style_name;
	global $qblog_date_format, $qblog_page_re, $qblog_defaultpage, $qblog_close, $qblog_default_cat;

	if (! is_qblog()) return '';

	$closed_msg = '';
	if ($qblog_close) {
		if (ss_admin_check()) {
			$closed_msg = '
				<div class="qblog_info alert alert-danger qblog_closed_message">
					<button class="close" data-dismiss="alert">×</button>
					<p>ブログは閉鎖されています。管理者以外のブログのトップ・個別の記事にアクセスがあった場合は、<b>トップページへ転送</b>されます。
					</p>
					<p>
						※ブログメニュー上のリストも管理者以外には表示されません。
					</p>
					<p><a href="' . $script . '?cmd=qblog#misc">ブログ設定（その他）へ</a></p>
				</div>
			';
		} else {
			redirect($defaultpage);
		}
	}

	$qt = get_qt();

	//RSSフィードを出力
	if (exist_plugin('rss')) {
		$rssurl = $script . '?cmd=rss&qblog_rss=1';
		$qt->setv_once('rss_link', $rssurl);
	}

	if (! is_bootstrap_skin()) {
		$include_bs = '
<link rel="stylesheet" href="skin/bootstrap/css/bootstrap-custom.min.css" />
<script type="text/javascript" src="skin/bootstrap/js/bootstrap.min.js"></script>';
		$qt->appendv_once('include_bootstrap_pub', 'beforescript', $include_bs);
	}

	//qblog.css を読み込む
	$head = '';
	if (file_exists(SKIN_DIR . $style_name . '/qblog.css')) {
		$head .= '<link rel="stylesheet" href="' . SKIN_DIR . $style_name . '/qblog.css">';
	} else {
		$head = '<link rel="stylesheet" href="plugin/qblog/qblog.css' . '" />';
	}

	$qt->appendv_once('qblog_beforescript', 'beforescript', $head);

	$page = $vars['page'];

	// ブログトップは<head>内の調整のみ
	if ($page === $qblog_defaultpage) {
		return $closed_msg;
	}

	//日付を取得
	$date = get_qblog_date($qblog_date_format, $page);

	$data = get_qblog_post_data($page);
	if ($vars['cmd'] == 'edit') {
		//新規ページ
		if (! $data) {
			$data['title'] = $page;
			$data['category'] = $qblog_default_cat;
		}

		$data['title'] = isset($vars['title']) && $vars['title'] ? $vars['title'] : $data['title'];
		$data['category'] = isset($vars['category']) && $vars['category'] ? $vars['category'] : $data['category'];

		if (isset($vars['qblog_date'])) {
			$date = $vars['qblog_date'];
			list($y, $m, $d) = array_pad(explode('-', $vars['qblog_date']), 3, '');
			if (checkdate($m, $d, $y)) {
				$time = mktime(0, 0, 0, $m, $d, $y);
				$date = date($qblog_date_format, $time);
			}
		}
	}

	$category_url = $script . '?' . $qblog_defaultpage . '&mode=category&catname=' . rawurlencode($data['category']);

	// アイキャッチ画像（feature_image）が設定されている場合
	$feature_image = '';
	if (trim($data['image']) !== '') {
		if (is_file(SWFU_IMAGE_DIR . $data['image'])) {
			$data['image'] = SWFU_IMAGE_DIR . $data['image'];
		}
		$feature_image = <<< EOH
	<img src="{$data['image']}" alt="{$data['title']}" class="qblog_feature_image" />
EOH;
	}

	// ブログ詳細のタイトル部分の表示
	$head = '
<style type="text/css">
#content h2.title{display:none;}
</style>
' . $closed_msg . $feature_image . '

<h2 class="qblog_post_title">' . h($data['title']) . '</h2>
<div class="title">
<span class="qblog_post_date">' . h($date) . '</span>
<a href="' . h($category_url) . '" class="qblog_category badge">' . h($data['category']) . '</a>
</div>
';

	return $head;
}

/* End of file qblog_head.inc.php */
/* Location: ./plugin/qblog_head.inc.php */