<?php

require_once __DIR__ . '/init.php';



ini_set('display_errors', '0');
function createError($message, $code, $file, $line, array $trace) {
	http_response_code(400 <= $code && $code < 500 ? $code : 400);

	$result = array(
		'result' => 'error',
		'message' => $message,
	);

	return $result;
}

function showError(array $error) {
	header('Content-Type: application/json; charset="utf-8"', true);
	die(json_encode($error));
}

try {
	set_error_handler(function ($code, $message, $file = null, $line = null, $context = null) {
		showError(createError($message, $code, $file, $line, []));
	});
	register_shutdown_function(function () {
		if ($error = error_get_last())
			showError(createError($error['message'], $error['type'], $error['file'], $error['line'], []));
	});
	$result = ApiBase::processHttpRequest();
} catch (Exception $e) {
	$result = createError($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace());
}

if ($result === null) {
	header('Content-Type: application/json; charset="utf-8"');
	echo 'null';
} else {
	header('Content-Type: application/json; charset="utf-8"');
	echo json_encode($result);
}