<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        require_once('../wkhtml.php');
    break;

    default:
        if ('/healthcheck' == explode('?', $_SERVER['REQUEST_URI'])[0]) {
            header('HTTP/1.1 200 OK');
            exit;
        }
        require_once('../usage.html');
    break;
}