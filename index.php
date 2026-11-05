<?php
    session_start();

    require_once "vendor/autoload.php";


    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(35));
    }

    $_SESSION["msg"] = [];
    $_SESSION["msg"][] = '';

    $request = $_SERVER['REQUEST_URI'];
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $path = preg_replace('#^' . preg_quote($scriptName) . '#', '', $request);
    $path = parse_url($path, PHP_URL_PATH);
    $path = trim($path, '/');
    $path = urldecode($path);


    if ($_POST) {
        $response = netzgrenze\router::POST($path, $_POST);
    } else {
        $response = netzgrenze\router::GET($path, $_GET);
    }

    echo $response;


?>

