<?php
require_once("config.php");
require_once("cheetan/cheetan.php");

function action(&$c)
{
	set_menu($c);

	$c->set('_page_title', 'ページ名一覧');

	$imgs = $c->image->find();

	$pages = [];
	foreach ($imgs as $k => $v) {
		$pname = $v['page_name'];
		if ($pname != '') {
			$matches = [];
			$head = (preg_match('/^([A-Za-z])/', $pname, $matches)) ? $matches[1] : (preg_match('/^([ -~])/', $pname, $matches) ? '__symbol__' : '__other__');
		} else {
			$head = '__nolabel__';
		}


		if (! isset($pages[$head]))
			$pages[$head] = [];

		$pages[$head][$pname][] = $v;
	}

	ksort($pages);
	$c->set('pages', $pages);
}
