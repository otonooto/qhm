<?php
require(dirname(__FILE__) . '/UploadHandler.php');

class SkinCustomizer_UploadHandler extends UploadHandler
{

    function __construct($options = null, $initialize = true)
    {
        parent::__construct($options, $initialize);
    }

    protected function get_full_url()
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $user = !empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] . '@' : '';
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] === 443 || $_SERVER['SERVER_PORT'] === 80 ? '' : ':' . $_SERVER['SERVER_PORT']));
        $scriptPath = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));

        return $https . $user . $host . $scriptPath;
    }

    public function get_file_name($name, $type, $index, $content_range)
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION)); // MIMEタイプではなくファイル名を処理
        $timestamp = date('Ymd_His'); // `time()` の代わりに `date()` を使用し、人間が判読しやすくする

        return $this->upload_dir . DIRECTORY_SEPARATOR . "custom_skin_{$this->options['skin_name']}_{$this->options['param_name']}_{$timestamp}.{$extension}";
    }
}
