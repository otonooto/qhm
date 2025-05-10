<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: showrss.inc.php,v 1.11 2003/12/03 12:30:02 arino Exp $
//
// modified by PANDA <panda@arino.jp>
//


////////////////////////////////////////////////
//
//  showrss2 -> showrss として使うことにしました
//		2010 1/7 by hokuken.com


/**
 *
 * showrss プラグイン
 * 
 * ライセンスは PukiWiki 本体と同じく GNU General Public License (GPL) です。
 * http://www.gnu.org/licenses/gpl.txt
 *
 * pukiwiki用のプラグインです。
 * pukiwiki1.3.2以上で動くと思います。
 * 
 * 今のところ動作させるためにはPHP の xml extension が必須です。PHPに組み込まれてない場合はそっけないエラーが出ると思います。
 * 正規表現 or 文字列関数でなんとかならなくもなさげなんですが需要ってどれくらいあるのかわからいので保留です。
 * mbstring もあるほうがいいです。
 * 
 * ない場合は、 jcode.phps をちょこっといじって mb_convert_encoding という関数を宣言しておけばとりあえずそれっぽく変換できるかもです。
 * http://www.spencernetwork.org/
 * 
 * ご連絡先:
 * do3ob wiki   ->   http://do3ob.com/
 * email        ->   hiro_do3ob@yahoo.co.jp
 * 
 * 避難所       ->   http://do3ob.s20.xrea.com/
 *
 * version: Id:showrss.inc.php,v 1.40 2003/03/18 11:52:58 hiro Exp
 * 
 */

////////////////////
// 2004/05/25 modified by パイン <pine@phantom2k.no-ip.org>
// 表示行数も選択できるようにした
//

// showrssプラグインが使用可能かどうかを表示
function plugin_showrss_action()
{
	$qm = get_qm();

	$xml_extension = extension_loaded('xml');
	$mbstring_extension = extension_loaded('mbstring');

	$xml_msg      = $xml_extension      ? $qm->m['plg_showrss']['ex_xml_loaded'] : 'COLOR(RED){' . $qm->m['plg_showrss']['err_ex_xml_load'] . '}';
	$mbstring_msg = $mbstring_extension ? $qm->m['plg_showrss']['ex_mbs_loaded'] : 'COLOR(RED){' . $qm->m['plg_showrss']['err_ex_mbs_load'] . '}';

	$showrss_info = '';
	$showrss_info .= "| xml parser | $xml_msg |\n";
	$showrss_info .= "| multibyte | $mbstring_msg |\n";

	return array('msg' => 'showrss_info', 'body' => convert_html($showrss_info));
}

function plugin_showrss_convert()
{
	global $script;
	$qm = get_qm();
	$qt = get_qt();

	if (func_num_args() == 0) {
		// 引数がない場合はエラー
		return $qm->replace('fmt_err_cvt', 'showrss', $qm->m['plg_showrss']['err_noparams']);
	}
	if (!extension_loaded('xml')) {
		// xml 拡張機能が有効でない場合。
		return $qm->replace('fmt_err_cvt', 'showrss', $qm->m['plg_showrss']['err_ex_xml_load']);
	}

	//---- キャッシュのための処理を登録 -----
	if ($qt->create_cache) {
		$args = func_get_args();
		return $qt->get_dynamic_plugin_mark(__FUNCTION__, $args);
	}
	//------------------------------------

	$array = func_get_args();
	$rssurl = $tmplname = $showline = $usecache = $usetimestamp = '';

	switch (func_num_args()) {
		case 5:
			$usetimestamp = trim($array[4]);
		case 4:
			$usecache = $array[3];
		case 3:
			$showline = trim($array[2]);
		case 2:
			$tmplname = strtolower(trim($array[1]));
		case 1:
			$rssurl = trim($array[0]);
	}

	//自己参照を防止する
	$p = parse_url($rssurl);
	if (strpos($p['query'], 'cmd=blog_rss') === FALSE && strpos(dirname($rssurl) . '/index.php', $script) === 0) {
		return '<strong style="color:red">Error</strong><br />#showrss: 自分自身のRSSは読み込めません。#recent をお使いください。';
	}

	// RSS パスの値チェック
	if (!is_url($rssurl)) {
		return $qm->replace('fmt_err_cvt', 'showrss', $qm->replace('plg_showrss.err_syntax', h($rssurl)));
	}

	$class = "ShowRSS_html_$tmplname";
	if (!class_exists($class)) {
		$class = 'ShowRSS_html';
	}

	list($rss, $time) = plugin_showrss_get_rss($rssurl, $usecache);
	if ($rss === FALSE) {
		return $qm->replace('fmt_err_cvt', 'showrss', $qm->m['plg_showrss.err_cannot_get']);
	}

	$obj = new $class($rss, $showline);

	$timestamp = '';
	if ($usetimestamp > 0) {
		$time = get_date('Y/m/d H:i:s', $time);
		$timestamp = "<p style=\"font-size:10px; font-weight:bold\">Last-Modified:$time</p>";
	}
	return $obj->toString($timestamp);
}
// rss配列からhtmlを作る
class ShowRSS_html
{
	var $items = [];
	var $class = '';
	var $i = 0;

	function ShowRSS_html($rss, $showlines)
	{
		foreach ($rss as $date => $items) {
			foreach ($items as $item) {
				if ($showlines and $showlines <= $this->i) {
					break;
				}
				$link = $item['LINK'];
				$title = $item['TITLE'];
				$passage = get_passage($item['_TIMESTAMP']);
				$link = "<a href=\"$link\" title=\"$title $passage\">$title</a>";
				$this->items[$date][] = $this->format_link($link);
				$this->i++;
			}
		}
	}
	function format_link($link)
	{
		return "$link<br />\n";
	}
	function format_list($date, $str)
	{
		return $str;
	}
	function format_body($str)
	{
		return $str;
	}
	function toString($timestamp)
	{
		$retval = '';
		foreach ($this->items as $date => $items) {
			$retval .= $this->format_list($date, join('', $items));
		}
		$retval = $this->format_body($retval);
		return <<<EOD
<div{$this->class}>
$retval$timestamp
</div>
EOD;
	}
}
class ShowRSS_html_menubar extends ShowRSS_html
{
	var $class = ' class="small"';

	function format_link($link)
	{
		return "<li>$link</li>\n";
	}
	function format_body($str)
	{
		return "<ul class=\"recent_list\">\n$str</ul>\n";
	}
}
class ShowRSS_html_recent extends ShowRSS_html
{
	var $class = ' class="small"';

	function format_link($link)
	{
		return "<li>$link</li>\n";
	}
	function format_list($date, $str)
	{
		return "<strong>$date</strong>\n<ul class=\"recent_list\">\n$str</ul>\n";
	}
}
// rssを取得する
function plugin_showrss_get_rss($target, $usecache)
{
	$buf = '';
	$time = NULL;
	if ($usecache) {
		// 期限切れのキャッシュをクリア
		plugin_showrss_cache_expire($usecache);

		// キャッシュがあれば取得する
		$filename = CACHE_DIR . encode($target) . '.tmp';
		if (is_readable($filename)) {
			$buf = join('', file($filename));
			$time = filemtime($filename) - LOCALZONE;
		}
	}
	if ($time === NULL) {
		// rss本体を取得
		$data = http_request($target);
		if ($data['rc'] !== 200) {
			return array(FALSE, 0);
		}
		$buf = $data['data'];
		$time = UTIME;
		// キャッシュを保存
		if ($usecache) {
			$fp = fopen($filename, 'w');
			fwrite($fp, $buf);
			fclose($fp);
		}
	}

	// parse
	$obj = new ShowRSS_XML();
	return array($obj->parse($buf), $time);
}
// 期限切れのキャッシュをクリア
function plugin_showrss_cache_expire($usecache)
{
	$expire = $usecache * 60 * 60; // Hour

	$dh = dir(CACHE_DIR);
	while (($file = $dh->read()) !== FALSE) {
		if (substr($file, -4) != '.tmp') {
			continue;
		}
		$file = CACHE_DIR . $file;
		$last = time() - filemtime($file);

		if ($last > $expire) {
			unlink($file);
		}
	}
	$dh->close();
}
// rssを取得・配列化
class ShowRSS_XML
{
	var $items;
	var $item;
	var $is_item;
	var $tag;

	function parse($buf)
	{
		$qm = get_qm();

		// 初期化
		$this->items = [];
		$this->item = [];
		$this->is_item = FALSE;
		$this->tag = '';

		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array(&$this, 'start_element'), array(&$this, 'end_element'));
		xml_set_character_data_handler($xml_parser, array(&$this, 'character_data'));

		if (!xml_parse($xml_parser, $buf, 1)) {
			return $qm->replace(
				'plg_showrss.err_xml',
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser),
				$buf
			);
		}
		xml_parser_free($xml_parser);

		return $this->items;
	}
	function escape($str)
	{
		// RSS中の "&lt; &gt; &amp;" などを 一旦 "< > &" に戻し、 ＜ "&amp;" が "&amp;amp;" になっちゃうの対策
		// その後もっかい"< > &"などを"&lt; &gt; &amp;"にする  ＜ XSS対策？
		$str = strtr($str, array_flip(get_html_translation_table(ENT_COMPAT)));
		$str = h($str);

		// 文字コード変換
		$str = mb_convert_encoding($str, SOURCE_ENCODING, 'auto');

		return trim($str);
	}

	// タグ開始
	function start_element($parser, $name, $attrs)
	{
		if ($this->is_item) {
			$this->tag = $name;
		} else if ($name == 'ITEM') {
			$this->is_item = TRUE;
		}
	}
	// タグ終了
	function end_element($parser, $name)
	{
		if (!$this->is_item or $name != 'ITEM') {
			return;
		}
		$item = array_map(array(&$this, 'escape'), $this->item);

		$this->item = [];

		if (array_key_exists('DC:DATE', $item)) {
			$time = plugin_showrss_get_timestamp($item['DC:DATE']);
		} else if (array_key_exists('PUBDATE', $item)) {
			$time = plugin_showrss_get_timestamp($item['PUBDATE']);
		} else if (
			array_key_exists('DESCRIPTION', $item)
			and ($description = trim($item['DESCRIPTION'])) != ''
			and ($time = strtotime($description)) != -1
		) {
			$time -= LOCALZONE;
		} else {
			$time = time() - LOCALZONE;
		}
		$item['_TIMESTAMP'] = $time;
		$date = get_date('Y-m-d', $item['_TIMESTAMP']);

		$this->items[$date][] = $item;
		$this->is_item = FALSE;
	}
	// キャラクタ
	function character_data($parser, $data)
	{
		if (!$this->is_item) {
			return;
		}
		if (!array_key_exists($this->tag, $this->item)) {
			$this->item[$this->tag] = '';
		}
		$this->item[$this->tag] .= $data;
	}
}
function plugin_showrss_get_timestamp($str)
{
	if (($str = trim($str)) == '') {
		return UTIME;
	}
	if (!preg_match('/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})(([+-])(\d{2}):(\d{2}))?/', $str, $matches)) {
		$time = strtotime($str);
		return ($time == -1) ? UTIME : $time - LOCALZONE;
	}
	$str = $matches[1];
	$time = strtotime($matches[1] . ' ' . $matches[2]);
	if (!empty($matches[3])) {
		$diff = ($matches[5] * 60 + $matches[6]) * 60;
		$time += ($matches[4] == '-' ? $diff : -$diff);
	}
	return $time;
}
