<?php
require_once('lib/WkHtml.php');

$input = file_get_contents("php://input");

switch ($_SERVER['REQUEST_URI']) {
    default:
        require_once('usage.html');
        exit;
    break;

    case '/gif':
        $format = WkHtml::TO_GIF;
        header('Content-Type: image/gif');
    break;
    case '/jpg':
        $format = WkHtml::TO_JPG;
        header('Content-Type: image/jpeg');
    break;
    case '/png':
        $format = WkHtml::TO_PNG;
        header('Content-Type: image/png');
    break;
    case '/pdf':
        $format = WkHtml::TO_PDF;
        header('Content-Type: application/pdf');
    break;
    break;
}

$wk = new WkHtml($input, $format, $_GET);
$wk->generate();
