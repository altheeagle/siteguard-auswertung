<?php
namespace netzgrenze;

class router {
    public static function GET(string $path){

        if ($path == ''){
            if (isset($_SESSION['username'])){
                return \netzgrenze\content::makeStartpage();
            } else {
                return \netzgrenze\content::makeLogin();
            }
        }

        if ($path == 'logout'){
            $imap = new \netzgrenze\imap();
            $imap->logout();
            header("Location: /");
            exit();
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

            $imap = new \netzgrenze\imap($post['username'], $post['password']);

            if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                http_response_code(405); // Oder 403 Forbidden
                die('Ungültiger CSRF-Token!');
            }

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
                    'redirectUrl' => 'history.back()',
                ]);
            }


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