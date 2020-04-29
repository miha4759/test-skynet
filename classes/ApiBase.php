<?php

class ApiBase {

	public static function processHttpRequest() {
		$response = [];
		$path = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
		$path = self::parsePathToApiFunction($path);
		$requestMethod = '_' . strtolower($_SERVER['REQUEST_METHOD']);
		$requestBody = self::getRequestBody($_SERVER['REQUEST_METHOD']);
		$response['result'] = 'ok';

		if (!$path)
			showError(createError("Empty query", 400, null, null, []));

		$prepareRelations = [];

		foreach ($path as $i => $class) {
			$object = self::prepareClassName($class[0]);

			if (!class_exists($object)) {
				$last = array_pop($prepareRelations);
				$funcName = $requestMethod.ucfirst($class[0]);
				if ($last && !method_exists($last, $funcName)) {
					showError(createError("Unknown path \"{$class[0]}\"", 404, null, null, []));
				} else {
					$response[$class[0]] = $last->$funcName($prepareRelations, $requestBody);
					continue;
				}
			}
			$object = new $object;

			if ($class[1])
				$object->ID = $class[1];

			if (sizeof(array_keys($path)) === $i+1) {
				;
				if (!($response[$class[0]] = $object->$requestMethod($prepareRelations, $requestBody))) {
					$response['result'] = 'error';
				}
				continue;
			}

			$prepareRelations[$class[0]] = $object;
		}

		return $response;
	}

	public static function getRequestBody($requestMethod) {
		$body = [];
		$strs = [];

		switch ($requestMethod) {
			case "DELETE":
			case "PUT":
				parse_str(file_get_contents("php://input"),$strs);
			break;
			case "POST":
				$body = $_POST;
				break;
			default :
				$body = $_GET;
				break;
		}

		if ($strs) {
			foreach (array_keys($strs) as $json) {
				$body = array_merge($body, json_decode($json, true));
			}
		}

		return $body;
	}

	public static function parsePathToApiFunction($path) {
		$path = trim($path, '/');
		$path = explode('/', $path);

		$classes = [];

		foreach ($path as $i => $v) {
			if ((int) $v || empty($v) || $v === "index.php")
				continue;
			$classes[] = [$v, (isset($path[$i+1])) ? (int) $path[$i + 1] : null];
		}

		return $classes;
	}

	public static function prepareClassName($name) {
		return 'models\\' .ucfirst($name);
	}
}