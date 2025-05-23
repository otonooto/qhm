<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: init.php,v 1.46 2006/06/11 15:04:27 henoheno Exp $
// Copyright (C)
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// Init PukiWiki here

// PukiWiki version / Copyright / Licence

define('S_VERSION', '1.4.7');
define('QHM_VERSION', '8.0.0');  // version
define('QHM_OPTIONS', 'update=download; support=false; banner=true');
define(
	'S_COPYRIGHT',
	'powered by <strong><a href="https://github.com/otonooto/qhm">QHM</a> ' . QHM_VERSION . '</strong><br />' .
		' based on <a href="http://pukiwiki.sourceforge.jp/">PukiWiki</a> ' . S_VERSION . ' ' .
		' License is <a href="http://www.gnu.org/licenses/gpl.html">GPL</a>.'
);

// SWFU Library
define('SWFU_DIR', './swfu/');
define('SWFU_TEXTSQL_PATH', SWFU_DIR . 'cheetan/db/textsql.php');
define('SWFU_IMAGEDB_PATH', SWFU_DIR . 'data/image.txt');
define('SWFU_IMAGE_DIR', SWFU_DIR . 'd/');

// URLs
define('QHM_HOME', 'https://github.com/otonooto/qhm');

/////////////////////////////////////////////////
// Init server variables

$server_keys = [
	'SCRIPT_NAME',
	'SERVER_ADMIN',
	'SERVER_NAME',
	'SERVER_PORT',
	'SERVER_SOFTWARE'
];

// 定数として定義し、不要な変数を削除
foreach ($server_keys as $key) {
	if (array_key_exists($key, $_SERVER)) {
		define($key, $_SERVER[$key]);
		unset($_SERVER[$key]); // 環境変数を削除
	}
}

// `SCRIPT_NAME` が定義されていない場合のフォールバック処理
// Web環境とCLI環境のどちらでも動作 し、静的解析の問題も回避
if (!defined('SCRIPT_NAME')) {
	define('SCRIPT_NAME', $_SERVER['PHP_SELF'] ?? basename(__FILE__));
}
if (!defined('SERVER_ADMIN')) {
	define('SERVER_ADMIN', $_SERVER['PHP_SELF'] ?? basename(__FILE__));
}
if (!defined('SERVER_NAME')) {
	define('SERVER_NAME', $_SERVER['PHP_SELF'] ?? basename(__FILE__));
}
if (!defined('SERVER_PORT')) {
	define('SERVER_PORT', $_SERVER['PHP_SELF'] ?? basename(__FILE__));
}
if (!defined('SERVER_SOFTWARE')) {
	define('SERVER_SOFTWARE', $_SERVER['PHP_SELF'] ?? basename(__FILE__));
}


/////////////////////////////////////////////////
// Init global variables

$foot_explain = [];	// Footnotes
$related      = [];	// Related pages
$head_tags    = [];	// XHTML tags in <head></head>

/////////////////////////////////////////////////
// Time settings

define('LOCALZONE', date('Z'));
define('UTIME', time() - LOCALZONE);
define('MUTIME', getmicrotime());

/////////////////////////////////////////////////
// Require INI_FILE

define('INI_FILE',  DATA_HOME . 'pukiwiki.ini.php');
$die = '';
if (! file_exists(INI_FILE) || ! is_readable(INI_FILE)) {
	$die .= 'File is not found. (INI_FILE)' . "\n";
} else {
	require(INI_FILE);
}
if ($die) die_message(nl2br("\n\n" . $die));

/////////////////////////////////////////////////
//load QHM Messages
//Load QHM Template
require_once(LIB_DIR . 'qhm_message.php');
require_once(LIB_DIR . 'qhm_template.php');
$qm = QHM_Message::get_instance();
$qt = QHM_Template::get_instance();
$qt->create_cache = false;



/////////////////////////////////////////////////
// INI_FILE: 言語設定

define('SOURCE_ENCODING', 'UTF-8');
define('CONTENT_CHARSET', 'UTF-8');

// おかしな設定のサーバー対策
if (
	ini_get('mbstring.encoding_translation') && strtoupper(ini_get('mbstring.internal_encoding')) != SOURCE_ENCODING
) {
	define('WARNING_OF_ENCODING', 1);
}

$lang = isset($qm->m['mb_language']) ? $qm->m['mb_language'] : 'Japanese';
define('MB_LANGUAGE', $lang);
mb_language(MB_LANGUAGE);
mb_internal_encoding(SOURCE_ENCODING);
mb_regex_encoding(SOURCE_ENCODING);
mb_detect_order('auto');

/////////////////////////////////////////////////
// INI_FILE: Require LANG_FILE

define('LANG_FILE_HINT', DATA_HOME . LANG . '.lng.php');	// For encoding hint
define('LANG_FILE',      DATA_HOME . UI_LANG . '.lng.php');	// For UI resource
$die = '';
foreach (array('LANG_FILE_HINT', 'LANG_FILE') as $langfile) {
	if (! file_exists(constant($langfile)) || ! is_readable(constant($langfile))) {
		$die .= 'File is not found or not readable. (' . $langfile . ')' . "\n";
	} else {
		require_once(constant($langfile));
	}
}
if ($die) die_message(nl2br("\n\n" . $die));

/////////////////////////////////////////////////
// LANG_FILE: Init encoding hint

define('PKWK_ENCODING_HINT', isset($_LANG['encode_hint'][LANG]) ? $_LANG['encode_hint'][LANG] : '');
unset($_LANG['encode_hint']);

/////////////////////////////////////////////////
// LANG_FILE: Init severn days of the week

$weeklabels = $_msg_week;

/////////////////////////////////////////////////
// INI_FILE: Init $script

$default_script = $script;
if (!isset($script) || $script == '') {
	$script = get_script_uri(); // Init automanually
} else {
	get_script_uri($script); // Init matically
}

//ssl接続用の$scriptを生成
if (! isset($script_ssl) || $script_ssl == '') {
	$script_ssl = preg_replace('/^http:/', 'https:', $script);
}

//is_httpsメソッド用など
$init_scripts = array('normal' => $script, 'ssl' => $script_ssl);


/////////////////////////////////////////////////
// INI_FILE: $agents:  UserAgentの識別

$ua = 'HTTP_USER_AGENT';
$user_agent = $matches = [];

$user_agent['agent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
if (isset(${$ua})) unset(${$ua});
if (isset($_SERVER[$ua])) unset($_SERVER[$ua]);
if (isset($HTTP_SERVER_VARS[$ua])) unset($HTTP_SERVER_VARS[$ua]);
unset($ua);

foreach ($agents as $agent) {
	if (preg_match($agent['pattern'], $user_agent['agent'], $matches)) {
		$user_agent['profile'] = isset($agent['profile']) ? $agent['profile'] : '';
		$user_agent['name']    = isset($matches[1]) ? $matches[1] : '';	// device or browser name
		$user_agent['vers']    = isset($matches[2]) ? $matches[2] : ''; // 's version
		break;
	}
}
unset($agents, $matches);

// Profile-related init and setting
define('UA_PROFILE', isset($user_agent['profile']) ? $user_agent['profile'] : '');

define('UA_INI_FILE', DATA_HOME . UA_PROFILE . '.ini.php');
if (! file_exists(UA_INI_FILE) || ! is_readable(UA_INI_FILE)) {
	die_message('UA_INI_FILE for "' . UA_PROFILE . '" not found.');
} else {
	require(UA_INI_FILE); // Also manually
}

define('UA_NAME', isset($user_agent['name']) ? $user_agent['name'] : '');
define('UA_VERS', isset($user_agent['vers']) ? $user_agent['vers'] : '');
define('UA_FULL', isset($user_agent['agent']) ? $user_agent['agent'] : '');
unset($user_agent);	// Unset after reading UA_INI_FILE

/////////////////////////////////////////////////
// ディレクトリのチェック

$die = '';
foreach (array('DATA_DIR', 'DIFF_DIR', 'BACKUP_DIR', 'CACHE_DIR') as $dir) {
	if (! is_writable(constant($dir)))
		$die .= 'Directory is not found or not writable (' . $dir . ')' . "\n";
}

// 設定ファイルの変数チェック
$temp = '';
foreach (
	array(
		'rss_max',
		'page_title',
		'note_hr',
		'related_link',
		'show_passage',
		'rule_related_str',
		'load_template_func'
	) as $var
) {
	if (! isset(${$var})) $temp .= '$' . $var . "\n";
}
if ($temp) {
	if ($die) $die .= "\n";	// A breath
	$die .= 'Variable(s) not found: (Maybe the old *.ini.php?)' . "\n" . $temp;
}

$temp = '';
foreach (array('LANG', 'PLUGIN_DIR') as $def) {
	if (! defined($def)) $temp .= $def . "\n";
}
if ($temp) {
	if ($die) $die .= "\n";	// A breath
	$die .= 'Define(s) not found: (Maybe the old *.ini.php?)' . "\n" . $temp;
}

if ($die) die_message(nl2br("\n\n" . $die));
unset($die, $temp);

/////////////////////////////////////////////////
// 必須のページが存在しなければ、空のファイルを作成する

foreach (array($defaultpage, $whatsnew, $interwiki) as $page) {
	if (! is_page($page)) touch(get_filename($page));
}

/////////////////////////////////////////////////
// 外部からくる変数のチェック

// Prohibit $_GET attack
foreach (array('msg', 'pass') as $key) {
	if (isset($_GET[$key])) die_message('Sorry, already reserved: ' . $key . '=');
}

// Expire risk
unset($HTTP_GET_VARS, $HTTP_POST_VARS);	//, 'SERVER', 'ENV', 'SESSION', ...
unset($_REQUEST);	// Considered harmful

// Remove null character etc.
$_GET    = input_filter($_GET);
$_POST   = input_filter($_POST);
$_COOKIE = input_filter($_COOKIE);

// 文字コード変換 ($_POST)
// <form> で送信された文字 (ブラウザがエンコードしたデータ) のコードを変換
// POST method は常に form 経由なので、必ず変換する
//
if (isset($_POST['encode_hint']) && $_POST['encode_hint'] != '') {
	// do_plugin_xxx() の中で、<form> に encode_hint を仕込んでいるので、
	// encode_hint を用いてコード検出する。
	// 全体を見てコード検出すると、機種依存文字や、妙なバイナリ
	// コードが混入した場合に、コード検出に失敗する恐れがある。
	$encode = mb_detect_encoding($_POST['encode_hint']);
	mb_convert_variables(SOURCE_ENCODING, $encode, $_POST);
} else if (isset($_POST['charset']) && $_POST['charset'] != '') {
	// TrackBack Ping で指定されていることがある
	// うまくいかない場合は自動検出に切り替え
	if (mb_convert_variables(
		SOURCE_ENCODING,
		$_POST['charset'],
		$_POST
	) !== $_POST['charset']) {
		mb_convert_variables(SOURCE_ENCODING, 'auto', $_POST);
	}
} else if (! empty($_POST)) {
	// 全部まとめて、自動検出／変換
	mb_convert_variables(SOURCE_ENCODING, 'auto', $_POST);
}

// 文字コード変換 ($_GET)
// GET method は form からの場合と、<a href="http://script/?key=value> の場合がある
// <a href...> の場合は、サーバーが rawurlencode しているので、コード変換は不要
if (isset($_GET['encode_hint']) && $_GET['encode_hint'] != '') {
	// form 経由の場合は、ブラウザがエンコードしているので、コード検出・変換が必要。
	// encode_hint が含まれているはずなので、それを見て、コード検出した後、変換する。
	// 理由は、post と同様
	$encode = mb_detect_encoding($_GET['encode_hint']);
	mb_convert_variables(SOURCE_ENCODING, $encode, $_GET);
}


/////////////////////////////////////////////////
// QUERY_STRINGを取得

// cmdもpluginも指定されていない場合は、QUERY_STRINGを
// ページ名かInterWikiNameであるとみなす
$arg = '';
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
	$arg = &$_SERVER['QUERY_STRING'];
} else if (isset($_SERVER['argv']) && ! empty($_SERVER['argv'])) {
	$arg = &$_SERVER['argv'][0];
}
if (PKWK_QUERY_STRING_MAX && strlen($arg) > PKWK_QUERY_STRING_MAX) {
	// Something nasty attack?
	pkwk_common_headers();
	sleep(1);	// Fake processing, and/or process other threads
	echo ('Query string too long');
	exit;
}
$arg = input_filter($arg); // \0 除去

// unset QUERY_STRINGs
foreach (array('QUERY_STRING', 'argv', 'argc') as $key) {
	if (isset(${$key})) unset(${$key});
	if (isset($_SERVER[$key])) unset($_SERVER[$key]);
	if (isset($HTTP_SERVER_VARS[$key])) unset($HTTP_SERVER_VARS[$key]);
}
// $_SERVER['REQUEST_URI'] is used at func.php NOW
if (isset($REQUEST_URI)) unset($REQUEST_URI);
if (isset($HTTP_SERVER_VARS['REQUEST_URI'])) unset($HTTP_SERVER_VARS['REQUEST_URI']);

// mb_convert_variablesのバグ(?)対策: 配列で渡さないと落ちる
$arg = array($arg);
mb_convert_variables(SOURCE_ENCODING, 'auto', $arg);
$arg = $arg[0];

/////////////////////////////////////////////////
// QUERY_STRINGを分解してコード変換し、$_GET に上書き

// URI を urlencode せずに入力した場合に対処する
$matches = [];
foreach (explode('&', $arg) as $key_and_value) {
	if (
		preg_match('/^([^=]+)=(.+)/', $key_and_value, $matches) &&
		mb_detect_encoding($matches[2]) != 'ASCII'
	) {
		$_GET[$matches[1]] = $matches[2];
	}
}
unset($matches);

/////////////////////////////////////////////////
// GET, POST, COOKIE

$get    = &$_GET;
$post   = &$_POST;
$cookie = &$_COOKIE;

// GET + POST = $vars
if (empty($_POST)) {
	$vars = &$_GET;  // Major pattern: Read-only access via GET
} else if (empty($_GET)) {
	$vars = &$_POST; // Minor pattern: Write access via POST etc.
} else {
	$vars = array_merge($_GET, $_POST); // Considered reliable than $_REQUEST
}

// 入力チェック: 'cmd=' and 'plugin=' can't live together
if (isset($vars['cmd']) && isset($vars['plugin']))
	die('Using both cmd= and plugin= is not allowed');

// 入力チェック: cmd, plugin の文字列は英数字以外ありえない
foreach (array('cmd', 'plugin') as $var) {
	$regex = version_compare(PHP_VERSION, 5.3, '<')
		? '/^\w+$/'
		: '/^\w+(?:\/\w+)?$/';
	if (isset($vars[$var]) && ! preg_match($regex, $vars[$var]))
		unset($get[$var], $post[$var], $vars[$var]);
}

// 整形: page, strip_bracket()
if (isset($vars['page'])) {
	$get['page'] = $post['page'] = $vars['page']  = strip_bracket($vars['page']);
} else {
	$get['page'] = $post['page'] = $vars['page'] = '';
}

// 整形: msg, 改行を取り除く
if (isset($vars['msg'])) {
	$get['msg'] = $post['msg'] = $vars['msg'] = str_replace("\r", '', $vars['msg']);
}

// 後方互換性 (?md5=...)
if (
	isset($get['md5']) && $get['md5'] != '' &&
	! isset($vars['cmd']) && ! isset($vars['plugin'])
) {
	$get['cmd'] = $post['cmd'] = $vars['cmd'] = 'md5';
}

// TrackBack Ping
if (isset($vars['tb_id']) && $vars['tb_id'] != '') {
	$get['cmd'] = $post['cmd'] = $vars['cmd'] = 'tb';
}

// cmdもpluginも指定されていない場合は、QUERY_STRINGをページ名かInterWikiNameであるとみなす
if (! isset($vars['cmd']) && ! isset($vars['plugin'])) {

	if (isset($vars['go'])) { // by HOKUKEN.COM
		$t = get_tiny_page($vars['go']);
		header('Location: ' . $script . '?' . rawurlencode($t));
		exit;
	}

	$get['cmd']  = $post['cmd']  = $vars['cmd']  = 'read';

	if ($arg == '') $arg = $defaultpage;
	$arg = rawurldecode($arg);
	$arg = strip_bracket($arg);
	$arg = input_filter($arg);
	$arg = strip_adcode($arg);
	$get['page'] = $post['page'] = $vars['page'] = $arg;
}

/////////////////////////////////////////////////
// 初期設定($WikiName,$BracketNameなど)
// $WikiName = '[A-Z][a-z]+(?:[A-Z][a-z]+)+';
// $WikiName = '\b[A-Z][a-z]+(?:[A-Z][a-z]+)+\b';
// $WikiName = '(?<![[:alnum:]])(?:[[:upper:]][[:lower:]]+){2,}(?![[:alnum:]])';
// $WikiName = '(?<!\w)(?:[A-Z][a-z]+){2,}(?!\w)';

// BugTrack/304暫定対処
$WikiName = '(?:[A-Z][a-z]+){2,}(?!\w)';

// $BracketName = ':?[^\s\]#&<>":]+:?';
$BracketName = '(?!\s):?[^\r\n\t\f\[\]<>#&":]+:?(?<!\s)';

// InterWiki
$InterWikiName = '(\[\[)?((?:(?!\s|:|\]\]).)+):(.+)(?(1)\]\])';

// 注釈
$NotePattern = '/\(\(((?:(?>(?:(?!\(\()(?!\)\)(?:[^\)]|$)).)+)|(?R))*)\)\)/x';

/////////////////////////////////////////////////
// 初期設定(ユーザ定義ルール読み込み)
require(DATA_HOME . 'rules.ini.php');

/////////////////////////////////////////////////
// 初期設定(その他のグローバル変数)

// 現在時刻
$now = format_date(UTIME);

// 日時置換ルールを$line_rulesに加える
if ($usedatetime) $line_rules += $datetime_rules;
unset($datetime_rules);

// フェイスマークを$line_rulesに加える
if ($usefacemark) $line_rules += $facemark_rules;
unset($facemark_rules);

// 実体参照パターンおよびシステムで使用するパターンを$line_rulesに加える
//$entity_pattern = '[a-zA-Z0-9]{2,8}';
$entity_pattern = trim(join('', file(CACHE_DIR . 'entities.dat')));

$line_rules = array_merge(array(
	'&amp;(#[0-9]+|#x[0-9a-f]+|' . $entity_pattern . ');' => '&$1;',
	"\r"          => '<br />' . "\n",	/* 行末にチルダは改行 */
), $line_rules);
