<?php
require_once('lib/WkHtml.php');

$input = file_get_contents("php://input");
$uri = explode('?', $_SERVER['REQUEST_URI'])[0];
switch ($uri) {
    case '/':
        require_once('usage.html');
        exit;
    break;

    case '/healthcheck':
        header('HTTP/1.1 200 OK');
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

    default:
        header("HTTP/1.1 404 Not Found");
        exit;
    break;
}

(new WkHtml($input, $format, $_GET))->generate()->send();
