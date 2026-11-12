<?php
namespace netzgrenze;

class router {
    public static function GET(string $path){

        if ($path == ''){
            if (isset($_SESSION['username'])){
                return \netzgrenze\content::makeMainpage();
            } else {
                return \netzgrenze\content::makeLogin();
            }
        }



        if (preg_match('#^api/getMail(?:/(\d+))?/?$#', $path, $matches)){
            $imap = new \netzgrenze\imap();
            header('Content-Type: application/json');

            $mail = $imap->getMail((int)$matches[1]);

            $actions = \netzgrenze\content::body2Array($mail["body"]);

            return json_encode($actions);
        }

    }

    public static function POST(string $path, array $post){

        if ($path == 'login'){
            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                http_response_code(405); // Oder 403 Forbidden
                die('Ungültiger CSRF-Token!');
            }

            $config = require "config.php";

            \netzgrenze\utils::clog("$post: " . \json_encode($post));

            // E-Mail Server Daten ermitteln
            if (empty($_SESSION["host"]) && empty($config["host"]) && empty($post["host"])) {
                \netzgrenze\utils::clog("Autoconfig wird gesucht");
                $emailserver = \netzgrenze\imap::getAutoconfigData($post['username']);
                \netzgrenze\utils::clog(\json_encode($emailserver));
                if (!$emailserver){
                    $_SESSION["showServerConfigOptions"] = true;
                    return \netzgrenze\content::showError([
                            'errorTitle' => 'Login fehlgeschlagen',
                            'errorMessage' => 'Für diese E-Mailadresse konnten keine Autoconfig Einträge gefunden werden. Sie haben nun zwei Optionen:
                                                <ul>
                                                    <li>Mit <a href="/">Zurück</a> kommen Sie auf die Login Seite. Dort haben Sie jetzt die Möglichkeit die Serverinformationen eintzutragen.</li>
                                                    <li>Alternativ können Sie die Serverinformationen dauerhaft in der Datei <code>/config.php</code> eintragen.</li>
                                                </ul>
                                                Hier finden Sie <a href="https://github.com/altheeagle/siteguard-auswertung/">weitere Informationen</a> zu dieser Meldung.',
                            'websitetitle' => 'Fehler beim Login',
                        ],[
                            'statusCode' => 401,
                            'redirectUrl' => '/',
                            'autoRedirect' => false,
                        ]);
                }
                $_SESSION["showServerConfigOptions"] = false;
                $_SESSION["host"] = $emailserver[0]["hostname"];
                $_SESSION["port"] = $emailserver[0]["port"];
                $_SESSION["encryption"] = $emailserver[0]["socketType"];
            }

            if (isset($_POST["host"])){
                $_SESSION["host"] = $_POST["host"];
            }

            if (isset($_POST["port"])){
                $_SESSION["port"] = $_POST["port"];
            }

            if (isset($_POST["encryption"])){
                $_SESSION["encryption"] = (bool)$_POST["encryption"];
            }



            $imap = new \netzgrenze\imap($post['username'], $post['password']);


            $loginresult = $imap->login();

            if ($loginresult){


                $fields = [
                    'websitetitle' => 'Login erfolgreich',
                    'successTitle' => 'Login erfolgreich',
                    'successMessage' => 'Lade die E-Mails',
                ];
                $content = file_get_contents('html/success.html');
                $options = [
                    'autoRedirect' => true,
                    'redirectTime' => 3,
                    'redirectUrl' => '/',
                    'redirectHidden' => true,
                ];
                return \netzgrenze\content::composePage($content, $fields,$options);
            } else {
                return \netzgrenze\content::showError([
                    'errorTitle' => 'Login fehlgeschlagen',
                    'errorMessage' => "Die Anmeldung ist fehlgeschlagen. Bitte überprüfen Sie Ihre Zugangsdaten und versuchen Sie es erneut.",
                    'websitetitle' => 'Fehler beim Login',
                ],[
                    'statusCode' => 401,
                    'redirectUrl' => '/',
                ]);
            }


        }

        if ($path == 'logout'){

            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                http_response_code(405); // Oder 403 Forbidden
                die('Ungültiger CSRF-Token!');
            }

            $imap = new \netzgrenze\imap();
            $imap->logout();
            header("Location: /");
            \session_destroy();
            exit();
        }

        if ($path == "api/moveMail"){
            $headers = getallheaders();
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $headers['X-CSRF-TOKEN'] ?? '';

            if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'CSRF-Token ungültig oder fehlt.']);
                exit;
            }

            $imap = new \netzgrenze\imap();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(35));

            try {
                $id = (int)json_decode($post["id"]);
            } catch (\Throwable $th) {
                header('Content-Type: application/json');
                http_response_code(400);
                return json_encode([
                    'msg' => 'Fehlerhafte Datenübermittlung:\n' . $th->getMessage(),
                    'nextToken' => $_SESSION['csrf_token'],
                    ]);
            }

            if($imap->moveMail($id)){
                header('Content-Type: application/json');
                return json_encode([
                    "msg" => "Die Mail " . $id . " wurde erfolgreich verschoben.",
                    'nextToken' => $_SESSION['csrf_token'],
                    ]);
            }
            http_response_code(500);
            return json_encode([
                    "msg" => $_SESSION["msg"],
                    'nextToken' => $_SESSION['csrf_token'],
                    ]);
        }

    }
}
?>