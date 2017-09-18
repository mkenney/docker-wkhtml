<?php
require_once('lib/WkHtml.php');

$input = file_get_contents("php://input");
$uri = explode('?', $_SERVER['REQUEST_URI'])[0];
switch ($uri) {
    default:
        require_once('usage.html');
        exit;
    break;

    case '/gif':
        $format = WkHtml::TO_GIF;
    break;
    case '/jpg':
        $format = WkHtml::TO_JPG;
    break;
    case '/png':
        $format = WkHtml::TO_PNG;
    break;
    case '/pdf':
        $format = WkHtml::TO_PDF;
    break;
}

$wk = new WkHtml($input, $_GET, $format);
$wk->generate();
