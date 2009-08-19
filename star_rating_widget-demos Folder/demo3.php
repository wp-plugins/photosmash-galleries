<?php

$buff[] = 'Response from server: ';
if(count($_POST))
{
	$buff[] = 'POST data recived: ';
	$buff[] = '<pre style="text-align:left">'.print_r($_POST, true).'</pre>';
}
else
{
	$buff[] = 'No POST data';
}
$buff[] = $_SERVER['HTTP_X_REQUESTED_WITH'] ? 'This is AJAX request' : 'This is POST request<br><a href="javascript:history.back();">&laquo; Back</a>';

echo implode('<br>', $buff);
