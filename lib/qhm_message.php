<?php

/**
 *   QHM Message Class
 *   -------------------------------------------
 *   qhm_massage.php
 *   
 *   Copyright (c) 2010 hokuken
 *   http://hokuken.com/
 *   
 *   created  : 2010-09-15
 *   modified :
 *   
 *   save system messages for localization
 *   
 *   Usage :
 *   
 */

class QHM_Message
{
	// Singleton Instance
	private static ?QHM_Message $instance = null;

	// Public properties
	public $m;
	public $file;
	public $file_ja;
	public $cache;
	public $locales;

	private function __construct()
	{
		$this->m = [];
		$this->file = 'lng.' . LANG . '.txt';
		$this->file_ja = 'lng.ja.txt';
		$this->cache = CACHE_DIR . '/lng.' . LANG . '.qmc';
		$this->readCache();
	}

	public static function get_instance(): QHM_Message
	{
		if (self::$instance === null) {
			return self::$instance = new self();
		} else {
			return self::$instance;
		}
	}

	function replace()
	{
		$args = func_get_args();
		$name = array_shift($args);
		if (strpos($name, '.')) {
			list($section, $name) = explode('.', $name);
			$str = $this->m[$section][$name];
		} else {
			$str = $this->m[$name];
		}

		$srcs = array('$1', '$2', '$3', '$4', '$5');
		$args = array_pad($args, 5, '');

		return str_replace($srcs, $args, $str);
	}

	function readCache()
	{
		if ($this->checkCache()) {
			$data = file_get_contents($this->cache);
			$this->m = unserialize($data);

			// var_dump($this->m); // 🔍 確認
			// $qm = get_qm();
			// $qm->m['example']['key'];
			// エラーでページが表示できなくなった場合、
			// /cache/lng.ja.qmc を削除すると解消される場合があります。
		}
	}

	function checkCache()
	{
		//cache OK
		if (file_exists($this->cache) && (filemtime($this->cache) > filemtime($this->file))) {
			return true;
		}
		//make cache
		else {
			$this->buildCache();
			return false;
		}
	}

	function buildCache()
	{
		$ini = parse_ini_file($this->file, true);
		if (LANG != 'ja') {
			$ini_ja = parse_ini_file($this->file_ja, true);
			foreach ($ini_ja as $key => $value) {
				if (is_array($value)) {
					$ini[$key] = array_merge($value, $ini[$key]);
				} else {
					$ini[$key] = isset($ini[$key]) ? $ini[$key] : $value;
				}
			}
		}

		//&quot; を" へ変換する
		//##LF## を\n へ変換する
		$src = array('&quot;', '##LF##');
		$rpl = array('"', "\n");
		$ini = str_replace_deep($src, $rpl, $ini);

		$this->m = $ini;

		//save cache
		$str = serialize($this->m);
		file_put_contents($this->cache, $str);
	}
}

function get_qm()
{
	return QHM_Message::get_instance();
}
