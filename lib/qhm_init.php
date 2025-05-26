<?php
//-------------------------------------------------
// QHM Initialization program for skin (output)
// This file is required lib/html.php
//
// QHMの編集モードで使う変数などを初期化、設定
// 最後に、ヘッダーの出力までを担当する
//

// --- モデル
enum ToolName: string
{
	case CONFIG_LINK = 'configlink';
	case EDITBOX_LINK = 'editboxlink';
	case EDIT_LINK = 'editlink';
	case REF_LINK = 'reflink';
	case PAGE_LINK = 'pagelink';
	case DIFF_LINK = 'difflink';
	case BACKUP_LINK = 'backuplink';
	case RENAME_LINK = 'renamelink';
	case DEL_LINK = 'dellink';
	case MAP_LINK = 'maplink';
	case COPY_LINK = 'copylink';
	case SHARE_LINK = 'sharelink';
	case QBLOG_NEW_LINK = 'qblognewlink';
	case PASSWORD_LINK = 'passwordlink';
	case UPDATE_LINK = 'updatelink';
}

class ToolEx
{
	public readonly string $name;
	public readonly string $link;
	public readonly string $style;
	public readonly string $class;
	public bool $visible;

	public function __construct(
		string $name,
		string $link,
		string $style = '',
		string $class = '',
		bool $visible = TRUE
	) {
		$this->name = $name;
		$this->link = $link;
		$this->style = $style;
		$this->class = $class;
		$this->visible = $visible;
	}
}

class Tool extends ToolEx
{
	public ?array $sub;

	public function __construct(
		string $name,
		string $link,
		string $style = '',
		string $class = '',
		bool $visible = true,
		?array $sub = null
	) {
		parent::__construct($name, $link, $style, $class, $visible);
		$this->sub = $sub; // ✅ `null` または `array` を持てるように
	}
}

class ToolCollection
{
	private array $tools;

	public function __construct(array $tools = [])
	{
		$this->tools = $tools;
	}

	public function getTool(ToolName $toolName): ?Tool
	{
		return $this->tools[$toolName->value] ?? null; // ✅ 文字列キーを安全に扱う
	}

	public function removeTool(ToolName $toolName): void
	{
		unset($this->tools[$toolName->value]); // ✅ 安全に削除！
	}

	public function getAllTools(): array
	{
		return $this->tools; // ✅ ツール一覧を取得！
	}
}

// --- イニシャライズ
global $layout_pages;
global $qblog_defaultpage, $qblog_menubar, $qblog_title;

$is_setting = ((isset($vars['cmd']) && $vars['cmd'] == 'qhmsetting') || (isset($vars['plugin']) && $vars['plugin'] == 'qhmsetting'));
//$no_toolmenu = ($is_setting OR array_key_exists($_page, $layout_pages));
$no_toolmenu = array_key_exists($_page, $layout_pages);

if (isset($vars['disable_toolmenu']) && $vars['disable_toolmenu']) {
	$is_setting = TRUE;
}

//---- set ini values for template engine
$qt->setv('version', QHM_VERSION);
$qt->setv('keywords', $keywords);
$qt->setv('description', $description);
$qt->setv('custom_meta', $custom_meta);
$qt->setv('head_tag', $head_tag);
$qt->setv('modifierlink', $modifierlink);
$qt->setv('modifier', $modifier);
$qt->setv('owneraddr', $owneraddr);
$qt->setv('ownertel', $ownertel);
$qt->getv('beforescript') ? '' : $qt->setv('beforescript', '');
$qt->getv('main_visual') ? '' : $qt->setv('main_visual', '');
$qt->getv('lastscript') ? '' : $qt->setv('lastscript', '');
$qt->setv('_page', $_page);
$qt->setv('_script', $script);
$qt->setv('auth_link', ($qhm_adminmenu <= 1) ? ('<a href="' . h($script . '?cmd=qhmauth') . '" class="qhm-auth-link">QHM</a>') : '');

//head
$qt->setv('headcopy_is_empty', trim($headcopy) === '');
if (! $qt->getv('headcopy_is_empty')) {
	$qt->setv('head_copy_tag', '<div id="headcopy" class="qhm-head-copy">
<h1>' . $headcopy . '</h1>
</div><!-- END: id:headcopy -->
');
}

$_go_url = $script . '?go=' . get_tiny_code($_page);
$qt->setv('go_url', $_go_url);
$_qhm_rawurl = $script . '?' . rawurlencode($_page);

//---- Prohibit direct access
if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');

$link  = &$_LINK;
$image = &$_IMAGE['skin'];
$readOnly    = PKWK_READONLY; // 閲覧専用ではない

$qt->setv_once('rss_link', $link['rss']);

//---- define global values for some plugin.
global $accesstag_moved; //ganatracker.inc.php setting (GoogleAnalytics)
global $shiftjis; //Shift-JIS converter
global $eucjp; //EUC-JP converter
global $is_update; // for update link


if ($shiftjis) {
	define('TEMPLATE_ENCODE', 'Shift_JIS');
} else if ($eucjp) {
	define('TEMPLATE_ENCODE', 'EUC-JP');
} else {
	define('TEMPLATE_ENCODE', 'UTF-8');
}

$qhm_dir = (preg_match('/.*\.php/', $script)) ? dirname($script) : dirname($script . 'index.php');
$qt->setv('qhm_dir', $qhm_dir);

$qt->setv('clickpad_js', '');

// Set toolbar-specific images
$_IMAGE['skin']['edit']     = 'edit.png';
$_IMAGE['skin']['diff']     = 'diff.png';
$_IMAGE['skin']['upload']   = 'file.png';
$_IMAGE['skin']['list']     = 'list.png';
$_IMAGE['skin']['search']   = 'search.png';
$_IMAGE['skin']['recent']   = 'recentchanges.png';
$_IMAGE['skin']['backup']   = 'backup.png';
$_IMAGE['skin']['help']     = 'help.png';
$_IMAGE['skin']['rss']      = 'rss.png';
$_IMAGE['skin']['rss10']    = &$_IMAGE['skin']['rss'];
$_IMAGE['skin']['rss20']    = 'rss20.png';
$_IMAGE['skin']['rdf']      = 'rdf.png';
$_IMAGE['skin']['rename']   = 'rename.png';
$_IMAGE['skin']['menuadmin']   = 'menuadmin.png';


// Editable mode preparation
$qt->setv('editable', check_editable($_page, FALSE, FALSE));

$has_swfu = file_exists('swfu/config.php') ? 'window.qhm_has_swfu = 1;' . "\n" : '';
$has_fwd3 = file_exists('fwd3/sys/config.php') ? 'window.qhm_has_fwd3 = 1;' . "\n" : '';

// other_plugin button
$op_func = <<<EOD
var op = $("div.other_plugin");
var optitle = $("div.other_plugin_box_title");
if (!op.is(':visible')) {
	op.show();
	if (!optitle.is(".expand")) {
		optitle.click();
	}
	document.cookie = "otherplugin=show";
}
else {
	op.hide();
	if (optitle.is(".expand")) {
		optitle.click();
	}
	document.cookie = "otherplugin=hide";
}
EOD;

$qt->setv('toolkit_upper', '');
$qt->setv('toolkit_bottom', '');

// if (($qt->getv('editable') || ss_admin_check()) && !$is_setting) {
if (($qt->getv('editable') || ss_admin_check())) {
	$qt->setv('jquery_include', true);

	$link_haik_parts = '//open-qhm.github.io/haik-parts/';

	$unload_confirm = isset($unload_confirm) ? $unload_confirm : 1;
	$enable_unload_confirm = 'window.qhm_enable_unload_confirm = ' . ($unload_confirm ? 'true' : 'false') . ';' . "\n";

	$btnset_name = is_qblog() ? 'qblog' : 'qhm';
	if (is_bootstrap_skin()) {
		$btnset_name = is_qblog() ? 'qhmHaikQBlog' : 'qhmHaik';
	}
	if (exist_plugin('icon')) {
		plugin_icon_set_google_material_icons("outlined");
	}

	$refleshjs = '?' . QHM_VERSION;


	$clickpad_js = <<<EOD
		<!--[if IE 6]><script type="text/javascript" src="js/fixed.js"></script><![endif]-->
		<script type="text/javascript" src="js/thickbox.js"></script>
		<script type="text/javascript" src="js/jquery.clickpad.js{$refleshjs}"></script>
		<script type="text/javascript" src="js/clickpad2.js{$refleshjs}"></script>
		<link rel="stylesheet" href="js/clickpad2/clickpad2.css{$refleshjs}">
		<script type="text/javascript" src="js/jquery.exnote.js"></script>
		<script type="text/javascript" src="js/jquery.shortkeys.js"></script>
		<script type="text/javascript" src="js/jquery.edit.js"></script>
		<script type="text/javascript">
		{$has_swfu}{$has_fwd3}{$enable_unload_confirm}
		$(function(){
			// clickpad
			if($("#msg").length) {
				$("#msg").data("original", $("#msg").val());

				otherplugin = function(){
		{$op_func}
				};
				showHaikParts = function(){
					tb_show('', '{$link_haik_parts}#{$style_name}?KeepThis=true&TB_iframe=true');
				}
				var ck = document.cookie.split(";");
				for (var i = 0; i < ck.length; i++) {
					if (ck[i].split("=")[0].replace(/^\s|\s$/, '').match(/otherplugin/)
						&& ck[i].split("=")[1].replace(/^\s|\s$/, '').match(/show/)) {
						otherplugin();
					}
				}

				if (window.qhm_enable_unload_confirm) {
					//form からのsubmit では遷移確認を出さない
					$("#edit_form_main, #edit_form_cancel").submit(function(e){
						window.onbeforeunload = null;
					});
					$("input:submit[name=write], input:submit[name=preview]").click(function(){
						window.onbeforeunload = null;
					});
				}

						// keyboard shortcut in textarea#msg
						var isWin = (navigator.platform.indexOf('win') != -1);
						$("#msg").keydown(function(e){
							//Save [Ctrl + S] [Command + S]
							if (((isWin && e.ctrlKey) || (! isWin && e.metaKey)) && e.keyCode == 83) {
								e.preventDefault();
								$("input:submit[name=write]").click();
							}
							//Preview [Ctrl + P] [Command + P]
							else if (((isWin && e.ctrlKey) || (! isWin && e.metaKey)) && e.keyCode == 80) {
								e.preventDefault();
								$("input:submit[name=preview]").click();
							}
						});
				}

		});

		if (window.qhm_enable_unload_confirm) {
			window.onbeforeunload = function(e) {
				if ($("#msg").val() != $("#msg").data("original")) {
					return '{$qm->m['qhm_init']['unload_confirm']}';
				}
			}
		}
		</script>

		<link rel="stylesheet" href="skin/hokukenstyle/qhm.css">
		<link rel="stylesheet" media="screen" href="js/thickbox.css">
	EOD;

	$qt->setv('clickpad_js', $clickpad_js);

	$link_help = QHM_HOME;
	$link_map = $script . '?cmd=map&amp;refer=' . rawurlencode($_page);
	$link_password = $script . '?plugin=qhmsetting&amp;phase=user2&mode=form';
	$link_qhm_update = $script . '?plugin=qhmupdate';
	$link_haik_skin_customizer = $script . '?cmd=qhmsetting&amp;phase=design&amp;mode=form&amp;preview=1&amp;enable_wp_theme=0&amp;design=' . $style_name . '&amp;customizer=1';

	// 「添付」の表示条件分岐
	$ref_link = '';
	$reflink_class = '';
	$reflink_visible = true;
	if (!file_exists('swfu/index.php')) {
		$reflink_visible = false;
	} else {
		$ref_link = 'swfu/index_child.php?page=' . rawurlencode($vars['page']) . '&amp;KeepThis=true&amp;TB_iframe=true';
		$reflink_class = 'swfu';
	}

	$layout_class = "thickbox";
	if (is_bootstrap_skin()) {
		$layout_class = "";
	}

	// `array<Tool>` 型を明示
	$tools = new ToolCollection([
		ToolName::CONFIG_LINK->value => new Tool(
			$qm->m['qhm_init']['configlink_name'],
			$link_qhm_setting,
			visible: true,
		),
		// 'editboxlink' => new Tool(
		// 	name: $qm->m['qhm_init']['editboxlink_name'],
		// 	link: '#msg',
		// 	class: 'go_editbox',
		// ),
		ToolName::EDIT_LINK->value  => new Tool(
			name: $qm->m['qhm_init']['editlink_name'],
			link: $link_edit,
			style: 'margin-top:1.1em;',
		),
		// 'reflink'     => new Tool(
		// 	name: $qm->m['qhm_init']['reflink_name'],
		// 	link: $ref_link,
		// 	class: $reflink_class,
		// 	visible: $reflink_visible
		// ),
		ToolName::PAGE_LINK->value => new Tool(
			name: $qm->m['qhm_init']['pagelink_name'],
			link: '',
			class: 'this_page_tools',
			visible: true,
			sub: [
				ToolName::DIFF_LINK->value => new Tool(
					name: $qm->m['qhm_init']['difflink_name'],
					link: $link_diff,
				),
				ToolName::BACKUP_LINK->value => new Tool(
					name: $qm->m['qhm_init']['backuplink_name'],
					link: $link_backup,
				),
				ToolName::RENAME_LINK->value => new Tool(
					name: $qm->m['qhm_init']['renamelink_name'],
					link: $link_rename,
				),
				ToolName::DEL_LINK->value => new Tool(
					name: '削除',
					link: $link_delete,
				),
				ToolName::MAP_LINK->value => new Tool(
					name: $qm->m['qhm_init']['maplink_name'],
					link: $link_map,
				),
				ToolName::COPY_LINK->value => new Tool(
					name: $qm->m['qhm_init']['copylink_name'],
					link: $link_copy,
				),
				ToolName::SHARE_LINK->value => new Tool(
					name: '共有',
					link: '#',
				),
			],
		),
		// 'qblognewlink' => new Tool(
		// 	name: '記事の追加',
		// 	link: $script . '?cmd=qblog&mode=addpost',
		// ),
		// 'passwordlink'   => new Tool(
		// 	name: $qm->m['qhm_init']['passwordlink_name'],
		// 	link: $link_password,
		// ),
		// 'updatelink' => new Tool(
		// 	name: $qm->m['qhm_init']['updatelink_name'],
		// 	link: $link_qhm_update,
		// 	style: 'margin-top:1.1em;',
		// ),
	]);

	$prevdiv = '';
	if (isset($_SESSION['temp_design'])) {
		unset(
			$tools['editboxlink'],
			$tools['editlink'],
			$tools['reflink'],
			$tools['pagelink'],
			$tools['sitelink'],
			$tools['toollink'],
			$tools['configlink'],
			$tools['helplink'],
			$tools['qbloglink']
		);

		$btn_class = (! isset($_SESSION['temp_skin']) or strlen($_SESSION['temp_skin']) === 0) ? 'local' : '';

		$custom_btn = '';
		$redirect = 0;
		// Set Skin Customizer
		if (exist_plugin('skin_customizer')) {
			if (isset($_SESSION['temp_design_customizer']) && $_SESSION['temp_design_customizer']) {
				$redirect = $_qhm_rawurl;
			}
			$custom_btn = plugin_skin_customizer_set_form();
		}
		$prevdiv = '
			<div id="preview_bar_overlay"></div>
			<div id="preview_bar">
			' . $custom_btn . '
			デザイン ' . h($_SESSION['temp_design']) . ' プレビュー中&nbsp;&nbsp;
			<form action="' . h($script) . '" method="post">
			<input type="hidden" name="cmd" value="qhmsetting" />
			<input type="hidden" name="mode" value="form" />
			<input type="hidden" name="phase" value="design" />
			<input type="hidden" name="preview" value="0" />
			<input type="hidden" name="redirect" value="' . h($redirect) . '" />
			<input type="submit" name="preview_cancel" value="プレビューを解除する" class="qhm-btn-default"/>
			</form>
			<form action="' . h($script) . '" method="post">
			<input type="hidden" name="cmd" value="qhmsetting" />
			<input type="hidden" name="mode" value="msg" />
			<input type="hidden" name="phase" value="design" />
			<input type="hidden" name="from" value="design_form" />
			<input type="hidden" name="qhmsetting[style_name]" value="' . h($_SESSION['temp_design']) . '" />
			<input type="hidden" name="qhmsetting[style_type]" value="none" />
			<input type="hidden" name="qhmsetting[enable_wp_theme]" value="' . h($_SESSION['temp_enable_wp']) . '" />
			<input type="submit" name="preview_set" value="このデザインを適用する" class="' . h($btn_class) . ' qhm-btn-primary" />
			</form>
			</div>
		';
	}

	//unset menu2 for 2-column style
	if (
		strpos($style_name, '3_') !== 0 &&
		!(file_exists("skin/hokukenstyle/$style_name/pukiwiki.skin.php") &&
			strpos(file_get_contents("skin/hokukenstyle/$style_name/pukiwiki.skin.php"), '#{$menubar2_tag}') !== FALSE)
	) {
		// unset($tools['sitelink']['sub']['menu2link']);
	}

	// ページではない＝プラグインで生成されたページ・メッセージ
	if (!$is_page) {
		$tools->removeTool(ToolName::EDITBOX_LINK);
		$tools->removeTool(ToolName::EDIT_LINK);
		$tools->removeTool(ToolName::REF_LINK);
		$tools->removeTool(ToolName::PAGE_LINK);

		// $tools['editboxlink']->visible = false;
		// $tools['editlink']->visible = false;
		// $tools['reflink']->visible = false;
		// $tools['pagelink']->visible = false;
	}

	// `cmd=edit` が含まれるかチェック
	// TODO: そもそも、編集ボックスへのリンクは不要かも。
	$editboxlink = $tools->getTool(ToolName::EDITBOX_LINK);
	if ($editboxlink !== null) {
		$editboxlink->visible = $vars['cmd'] === 'edit' && $vars['page'] !== null && $vars['page'] !== '';
	}

	if ($readOnly) {
		$tools['editlink']['visible'] = false;
		$tools['reflink']['visible'] = false;
	}
	if (!(bool)ini_get('file_uploads')) {
		$tools['reflink']['visible'] = false;
	}
	if (!file_exists('fwd3/sys/fwd3.txt')) {
		// unset($tools['toollink']['sub']['fwd3link']);
	}
	if (!file_exists('qdsgn/index.php')) {
		// if (isset($tools['toollink']['sub']['qdsgnlink'])) unset($tools['toollink']['sub']['qdsgnlink']);
	}
	if (strpos($style_name, 'haik_') !== 0) {

		// if (isset($tools['haikskincustomizer'])) unset($tools['haikskincustomizer']);
		// if (isset($tools['haikpreviewlink'])) unset($tools['haikpreviewlink']);
	} else {
		$addjs = '
			<script type="text/javascript" src="js/haik_theme_utility.js"></script>
			<script type="text/javascript" src="' . PLUGIN_DIR . 'skin_customizer/color_picker.js"></script>
		';
		$qt->appendv('beforescript', $addjs);

		// Determine custom skin
		$style_config = read_skin_config($style_name);
		$skin_custom_vars = get_skin_custom_vars($style_name);
		if (! isset($style_config['custom_options']['header']) || ! $skin_custom_vars['header']) {
			// unset($tools['sitelink']['sub']['headerlink']);
		}
	}
	if (! ss_admin_check()) {
		if (isset($tools['reflink'])) unset($tools['reflink']);
		if (isset($tools['pagelink'])) unset($tools['pagelink']);
		if (isset($tools['sitelink'])) unset($tools['sitelink']);
		if (isset($tools['toollink'])) unset($tools['toollink']);
		if (isset($tools['configlink'])) unset($tools['configlink']);
		if (isset($tools['helplink'])) unset($tools['helplink']);
		if (isset($tools['haikskincustomizer'])) unset($tools['haikskincustomizer']);
		if (isset($tools['haikpreviewlink'])) unset($tools['haikpreviewlink']);
	} else {
		// if (isset($tools['passwordlink'])) unset($tools['passwordlink']);
		$tools->removeTool(ToolName::PASSWORD_LINK);
	}

	if ($_page === $defaultpage) {
		$tools->removeTool(ToolName::DEL_LINK);
		// $tools['pagelink']->sub['dellink']->visible = false;
	}

	if (! isset($_COOKIE['QHM_VERSION']) || $_COOKIE['QHM_VERSION'] <= QHM_VERSION || get_qhm_option('update') !== 'vendor') {
		$tools->removeTool(ToolName::UPDATE_LINK);
		// unset($tools['updatelink']);
	}

	if (is_qblog()) {
		$tools['pagelink']->sub['renamelink']->visible = false;
	}
	if (! is_page($qblog_defaultpage)) {
		if (isset($tools['qbloglink'])) unset($tools['qbloglink']);
	}


	// レイアウトページの時の管理ウィンドウの制御
	if ($no_toolmenu) {
		if (! is_bootstrap_skin()) {
			$tools = array('editlink' => $tools['editlink'], 'reflink' => $tools['reflink'], 'pagelink' => $tools['pagelink']);
		}

		unset($tools['pagelink']['sub']['sharelink']);
		unset($tools['pagelink']['sub']['renamelink']);
		unset($tools['pagelink']['sub']['dellink']);
		unset($tools['pagelink']['sub']['copylink']);
		unset($tools['pagelink']['sub']['maplink']);
		unset($tools['pagelink']['sub']['tinyurllink']);
		if (arg_check('backup') or arg_check('diff')) {
			$tools['reflink']['visible'] = FALSE;
		}
	}

	$tools_str = '<ul class="toolbar_menu">';
	foreach ($tools->getAllTools() as $lv1key => $lv1) {
		// visible
		if ($lv1->visible) {
			// link
			if ($lv1->visible) {
				$tools_str .= '<li style="background-image:none;' . $style . '"' . $lv1->$class . '><a href="' . $lv1->link . '"' . $target . ' id="' . $lv1key . '">' . $lv1->name . '</a>';
			} else {
				$class = isset($lv1['class']) ? ' class="' . $lv1['class'] . '"' : '';
				$style = ($style != '') ? ' style="position:relative;' . $style . '"' : ' style="position:relative;"';
				$tools_str .= '<li' . $style . $class . '>' . $lv1['name'];
			}
		}
		// invisible
		else {
			$tools_str .= '<li style="display: none;">' . $lv1->name;
		}

		// sub menu
		if ($lv1->visible && $lv1->sub != null && $lv1->sub != []) {
			$tools_str .= '<ul class="toolbar_submenu">';
			foreach ($lv1->sub as $lv2key => $lv2) {
				if ($lv2->visible) {
					$class = $lv2->class;
					$style = $lv2->style;
					$tools_str .= '<li class="' . $class . '" style="' . $style . '">';
					if ($lv2->link != '') {
						$tools_str .= '<a href="' . $lv2->link . '" id="' . $lv2key . '">' . $lv2->name . '</a>';
					} else {
						$tools_str .= $lv2->name;
					}
					$tools_str .= '</li>';
				}
			}
			$tools_str .= '</ul>'; // sub menu end
		}
		$tools_str .= '</li>'; // lv1 の li 閉じタグ
	}
	$tools_str .= '</ul>'; // main manu end

	$qm = get_qm();

	//最小化型のtoolbar upper
	// 直下の行は不要なので削除予定
	// $tools_str = preg_replace('/\sid="([a-z_]+?)"/', ' id="$1_min"', $tools_str);
	$tools_str = str_replace('toolbar_menu', 'toolbar_menu', $tools_str);
	$tools_str = str_replace('margin-top:1.1em;', '', $tools_str);

	$logout_label = $qm->m['qhm_init']['logoutlink_name'];

	$tk_append .= '
	<!-- Toolbar upper -->
	<div id="toolbar" class="toolbar">
		' . $tools_str . '
		<div><a href="' . $link_qhm_logout . '" class="btn_logout">' . $logout_label . '</a></div>
	</div>';
	$qt->appendv('toolkit_upper', $tk_append);

	//php setting check warning
	if (defined('WARNING_OF_ENCODING')) {
		$tk_enc_msg = '<p style="font-size:16px;color:white;width:600px;margin:5px auto;background-color:#e00">' . $qm->m['qhm_init']['err_encoding'] . '</p>';
		$qt->prependv('toolkit_upper', $tk_enc_msg);
	}

	//shortcut 一覧
	$is_osx = preg_match('/Mac OS X/', UA_FULL);
	$keybind_for_save = $is_osx ? '⌘+S' : 'Ctrl+S';
	$tk_append = <<<HTML
<div class="modal fade hidden-print" role="dialog" id="keybind_list">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">ショートカット一覧</h4>
			</div>
			<div class="modal-body">
				<table class="table table-hover">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th>ページ</th>
							<th>&nbsp;</th>
							<th>{$qm->m['qhm_init']['sc_col_move']}</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<th><code>E</code></th>
							<td>{$qm->m['qhm_init']['sc_jump_edit']}</td>
							<th><code>T</code></th>
							<td>{$qm->m['qhm_init']['sc_scroll_top']}</td>
						</tr>
						<tr>
							<th><code>P</code></th>
							<td>{$qm->m['qhm_init']['sc_preview']}</td>
							<th><code>H</code></th>
							<td>{$qm->m['qhm_init']['sc_jump_home']}</td>
						</tr>
						<tr>
							<th><code>Z</code></th>
							<td>編集をキャンセル</td>
							<th><code>Q</code></th>
							<td>{$qm->m['qhm_init']['sc_search']}</td>
						</tr>
						<tr>
							<th><code>{$keybind_for_save}</code></th>
							<td>{$qm->m['qhm_init']['sc_save']}</td>
							<th><code>N</code></th>
							<td>{$qm->m['qhm_init']['sc_newpage']}</td>
						</tr>
						<tr>
							<th><code>A</code></th>
							<td>{$qm->m['qhm_init']['sc_attach']}</td>
							<th><code>L</code></th>
							<td>{$qm->m['qhm_init']['sc_filelist']}</td>
						</tr>
						<tr>
							<th><code>I</code></th>
							<td>SWFUを開く</td>
							<th><code>C</code></th>
							<td>{$qm->m['qhm_init']['sc_jump_settings']}</td>
						</tr>
						<tr>
							<th><code>/</code></th>
							<td>編集ボックスへフォーカス</td>
							<th><code>M</code></th>
							<td>{$qm->m['qhm_init']['sc_open_help']}</td>
						</tr>
						<tr>
							<th><code>ESC</code></th>
							<td>編集ボックスのフォーカスを外す</td>
							<th>&nbsp;</th>
							<td>&nbsp;</td>
						</tr>
						<tr>
							<th><code>U</code></th>
							<td>共有・URL表示</td>
							<th>&nbsp;</th>
							<td>&nbsp;</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
HTML;

	//ページ共有
	$tweettext = '%TITLE% - ' . $_go_url;
	$tweeturl_fmt = 'http://twitter.com/intent/tweet?text=$text';
	$tweeturl = str_replace(array('$text', '$url'), array(rawurlencode($tweettext), rawurlencode($_go_url)), $tweeturl_fmt);
	$tk_append .= '
<div class="modal fade" id="shareQHMPage">
  <div class="modal-dialog">
  <div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h4 class="modal-title">このページの共有</h4>
  </div>
  <div class="modal-body clearfix form-horizontal">
    <div class="form-group">
      <label class="col-sm-3 control-label">' . $qm->m['qhm_init']['su_shorten'] . '</label>
      <div class="col-sm-9">
        <input type="text" value="' . $_go_url . '" readonly="readonly" size="36" class="form-control" />
        <a href="' . $script . '?cmd=update_tinycode&page=' . h($_page) . '" class="help-block pull-right">' . $qm->m['qhm_init']['su_update'] . '</a>
      </div>
    </div>

    <div class="form-group">
      <label class="col-sm-3 control-label">' . $qm->m['qhm_init']['su_url'] . '</label>
      <div class="col-sm-9">
        <input type="text" value="' . $_qhm_rawurl . '" readonly="readonly" size="36" class="form-control" />
      </div>
    </div>

    <div class="form-group">
      <label class="col-sm-3 control-label">Twitter</label>
      <div class="col-sm-9">
        <textarea cols="90" rows="3" class="form-control">' . h($tweettext) . '</textarea>
		<ol class="help-block">
			<li><span class="small">内容を編集して投稿できます。<br /><b>%URL%</b> と書くとURLに自動変換されます。</span></li>
			<li><a href="' . $tweeturl . '" class="shareTwitter btn qhm-btn-primary qhm-btn-sm" data-format="' . h($tweeturl_fmt) . '" data-url="' . h($_go_url) . '" target="_blank" rel="noopener">クリックしてTwitterへ投稿</a></li>
		</ol>
      </div>
    </div>

    <div class="form-group">
      <label class="col-sm-3 control-label">Facebook</label>
      <div class="col-sm-9">

        <ol class="help-block">
          <li>短縮URLをコピーする。</li>
          <li><a href="http://www.facebook.com/" class="btn qhm-btn-primary qhm-btn-sm" target="_blank" rel="noopener">ここをクリックして、Facebook を開いて投稿</a></li>
        </ol>
      </div>
    </div>
  </div>
  </div>
  </div>
</div>


';


	// other plugin
	$op_html = '';
	if (! is_bootstrap_skin()) {
		$op_cat = [];
		$op_html  = '<div class="other_plugin">';
		$op_html .= '<div class="other_plugin_box_title expand"><span>' . $qm->m['qhm_init']['ot_label'] . '</span>&nbsp;&nbsp;<span class="mark">ー</span></div>';
		$op_html .= '<div class="other_plugin_box">';
		$op_html_box = "";
		foreach ($other_plugins as $opkey => $op) {
			$insert_cmd = $op['insert'];
			$insert_cmd = str_replace("\n", "##LF##", $insert_cmd);
			$op_html_box = '<li class="' . $op['help'] . '"><span class="opname">' . $op['name'] . '</span><span class="insert_cmd">' . $insert_cmd . '</span></li>';
			$op_cat[$op['category']][] = $op_html_box;
		}
		$op_html .= '<ul class="other_plugin_menu">';
		foreach ($other_plugin_categories as $catkey => $catname) {
			$op_html_menu  = '<li>' . $catname;
			$op_html_menu .= '<ul class="other_plugin_sub">';
			$op_html_menu .= implode('', $op_cat[$catkey]);
			$op_html_menu .= '</ul></li>';
			$op_html .= $op_html_menu;
		}
		$op_html .= '</ul>';

		$op_html .= "</div>";
		$op_html .= "</div>\n";
	}
	$tk_append .= $op_html;
	$tk_append .= $prevdiv;

	$qt->appendv('toolkit_upper', $tk_append);
} else if ($qhm_adminmenu == 0) {
	$tk_bottom = $is_page ? '<div id="toolbar"><a href="' . $link_edit . '" >Edit this page</a></div>' : '<div id="toolbar">' . $qm->m['qhm_init']['err_cannot_edit'] . '</div>';
	$qt->setv('toolkit_bottom', $tk_bottom);
}

//set page title (title tag of HTML)
if ($is_read) {
	$qt->setv_once('this_page_title', $title . " - " . $page_title);
	$qt->setv_once('this_right_title', $title);
} else { //編集時は、必ずシステム情報でタイトルを作る
	$qt->setv('this_page_title', $title . " - " . $page_title);
	$qt->setv('this_right_title', $title);
}

if ($title == $defaultpage) { //トップ用
	$qt->setv('this_page_title', $page_title);
}

if (preg_match("/$non_list/", $vars['page'])) {
	$noindex = TRUE;
}

//seach engine spider control
$qt->setv('noindex', '');
if ($noindex || $nofollow || ! $is_read) {
	$noindexstr = '
<meta name="robots" content="NOINDEX,NOFOLLOW" />
<meta name="googlebot" content="noindex,nofollow" />
';
	$qt->setv('noindex', $noindexstr);
}
//set canonical url
else {
	if ($qt->getv('canonical_url')) {
		$canonical_url = $qt->getv('canonical_url');
	} else {
		if ($vars['page'] === $defaultpage) {
			$canonical_url = dirname($script . 'dummy');
		} else {
			$canonical_url = $script . '?' . rawurlencode($vars['page']);
		}
	}
	$canonical_tag = <<< EOD
<link rel="canonical" href="{$canonical_url}">
EOD;
	$qt->prependv('beforescript', $canonical_tag);
}

//license
$qhm_admin_tag = ($qhm_adminmenu < 2) ? ' <a href="' . $link_qhm_adminmenu . '">QHM</a> ' : '';
$qt->setv('licence_tag', "<p>" . S_COPYRIGHT . $qhm_admin_tag . "</p>");
if ($no_qhm_licence) {
	$qt->setv('licence_tag', '');
}
$qt->setv('qhm_login_link', $link_qhm_adminmenu);

//rss
// $rss_label = $qm->m['qhm_init']['rss_label'];
$rss_label = 'rss';
$rss_tag = '<a href="' . $script . '?cmd=rss&amp;ver=1.0"><img src="image/rss.png" width="36" height="14" alt="' . $rss_label . '" title="' . $rss_label . '" /></a>';
$qt->setv('rss_tag', $rss_tag);

//access tag
$qt->setv('accesstag_tag', '');
if ($qt->getv('editable') === FALSE) {
	if ($is_read && !$accesstag_moved) {
		$qt->setv('accesstag_tag', $accesstag);
	}
	//UniversalAnalytics
	if ($is_read && $ga_tracking_id) {
		$tracking_code = <<< EOD
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '{$ga_tracking_id}', 'auto');
  ga('send', 'pageview');

</script>

EOD;
		$qt->setv('ga_universal_analytics', $tracking_code);
	}
}

$tmp_date = getdate();
$qt->setv('today_year', $tmp_date['year']);


//misc info setting
$summaryflag_start = '';
$summaryflag_end = '';
if (($notes != '') || ($trackback != '') || ($referer != '') || ($related != '') || ($attaches != '')) {
	$summaryflag_start = '<div id="summary"><!-- ■BEGIN id:summary -->';
	$summaryflag_end = '</div><!-- □ END id:summary -->';
}

$attach_tag = '';
if ($attaches != '') {
	$attach_tag = <<<EOD
 <!-- ■ BEGIN id:attach -->
 <div id="attach">
 $hr
 $attaches
 </div><!-- □ END id:attach -->
EOD;
}

$notes_tag = '';
if ($notes != '') {
	$notes_tag = <<<EOD
 <!-- ■BEGIN id:note -->
 <div id="note">
   $notes
 </div>
 <!-- □END id:note -->
EOD;
}

$trackback_tag = '';
if ($trackback) {
	$tb_id = tb_get_id($_page);
	$tb_cnt = tb_count($_page);
	$tb_label = $qm->replace("qhm_init.tb_label", $tb_cnt);
	$trackback_tag = <<<EOD
<div id="trackback"><!-- ■BEGIN id:trackback -->
<a href="{$script}?plugin=tb&amp;__mode=view&amp;tb_id={$tb_id}" onClick="OpenTrackback(this.href); return false">$tb_label</a> |
EOD;
}

$referer_tag = '';
if ($referer) {
	$ref_label = $qm->m['qhm_init']['ref_label'];
	$referer_tag = <<<EOD
<a href="{$script}?plugin=referer&amp;page=$r_page">$ref_label</a>
</div><!-- □ END id:trackback -->
EOD;
}

$related_tag = '';
if ($related != '') {
	$related_tag = <<<EOD
<!-- ■ BEGIN id:related -->
<div id="related">
Link: {$related}
</div>
<!-- □ END id:related -->
EOD;
}

$summarystr = <<<EOD
<!-- summary start -->
{$summaryflag_start}
$notes_tag
$trackback_tag
$referer_tag
$related_tag
$attach_tag
$summaryflag_end
<!-- summary end -->
EOD;
$qt->setv('summary', $summarystr);

//-------------------------------------------------
//
// ログインをチェックし、ログアウトしてれば再ログインをさせるjavascriptの読み込み
//-------------------------------------------------
if (exist_plugin('check_login')) {
	do_plugin_convert('check_login');
}

if (is_qblog()) {
	if ($qblog_defaultpage === $title) {
		$qt->setv('this_page_title', $qblog_title . ' - ' . $page_title);
		if (! $qt->getv('editable')) {
			$qt->setv('this_right_title', $qblog_title);
		} else {
			$qt->appendv('this_right_title', $qblog_title);
		}
	} else {
		$qt->setv('this_page_title', $qt->getv('this_right_title') . ' - ' . $qblog_title . ' - ' . $page_title);
	}
}

/* End of file qhm_init.php */
/* Location: ./lib/qhm_init.php */
