<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: gsearch.inc.php,v 0.5 2007/10/12 19:21:08 henoheno Exp $
//
// gserach convert view plugin

// Allow CSS instead of <font> tag
// NOTE: <font> tag become invalid from XHTML 1.1
// ----

function plugin_search_menu_convert()
{
  global $script;
  $qm = get_qm();
  $qt = get_qt();

  $style = '  <style type="text/css">
  #qhm_search_menu_form {padding: 0;}
  .qhm_search_form * {padding: 0;margin: 0;font-weight: normal;}
  input[type=checkbox], input[type=radio] {margin:0;}
  .wrap_search_input {display:flex;width: 100%;max-width: 320px;}
  .search_text_input {
    max-width: 80%;
    border: 1px #ccc solid;
    border-right: 0;
  	border-top-left-radius: 6px;
    border-bottom-left-radius: 6px;
  	padding: 4px;
  }
  .search_text_button {
  	border: 1px #1790d6 solid;
  	border-right: 0;
  	background-color: #1790d6;
  	border-top-right-radius: 6px;
  	border-bottom-right-radius: 6px;
  	width: 20%;
  	color: #ffffff;
  }
  .qhm_search_form{
  	display: flex;
  	flex-direction: column;
  	gap: 6px;
  	margin-top: 12px;
  }
  .wrap_search_select {
  	display: flex;
  	flex-wrap: wrap;
  	gap: 12px;
  	align-items: center;
  }
  .search_select {
    display: flex;
    gap: 4px;
    align-items: center;
  	letter-spacing: 1px;
  }
  </style>';

  $qt->appendv('beforescript', $style);

  return <<<EOF
<form action="{$script}" method="get" class="qhm_search_form" id="qhm_search_menu_form">
	<div class="wrap_search_input">
		<input type="text" class="search_text_input" name="word" value="" size="50" accesskey="k" tabindex="1" />
    <input type="submit" value="{$qm->m['plg_search']['btn']}" tabindex="2" accesskey="s" class="search_text_button" />
  </div>
  <div class="wrap_search_select">
    <label for="_p_search_AND" class="search_select">
      <input type="radio" name="type" id="and_search" value="AND" tabindex="3" accesskey="a" /> {$qm->m['plg_search']['lbl_and']}
    </label>
    <label for="_p_search_OR" class="search_select">
      <input type="radio" name="type" id="or_search"  value="OR" tabindex="3" accesskey="o" /> {$qm->m['plg_search']['lbl_or']}
    </label>
  </div>


	<input type="hidden" name="cmd" value="search" />
	<input type="hidden" name="encode_hint" value="ぷ" />
</form>
EOF;
}
