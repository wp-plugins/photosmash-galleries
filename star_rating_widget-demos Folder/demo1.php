<?php

header('Cache-Control: no-cache'); // Prevent IE from caching XMLHttpRequest response

$buff[] = 'Response from server: ';
$buff[] = $_POST['rate'] ? "Rate recived: {$_POST['rate']}" : '$_POST[\'rate\'] is empty';
$buff[] = $_SERVER['HTTP_X_REQUESTED_WITH'] ? 'This is AJAX request' : 'This is POST request<br><a href="javascript:history.back();">&laquo; Back</a>';

echo implode('<br>', $buff);
