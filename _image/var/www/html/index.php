<?php
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        require_once('../wkhtml.php');
    break;

    default:
        require_once('../usage.html');
    break;
}