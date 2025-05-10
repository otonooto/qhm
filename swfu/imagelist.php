<?php
require_once("config.php");
require_once("cheetan/cheetan.php");

function action(&$c)
{
	set_menu($c);
	$c->set('_page_title', '画像名一覧');

	$imgs = $c->image->find();

	$images = [];
	foreach ($imgs as $k => $v) {
		$fname = $v['name'];
		$head = $fname[0];

		if (! isset($images[$head]))
			$images[$head] = [];

		$images[$head][] = $v;
	}

	ksort($images);
	$c->set('images', $images);
}
