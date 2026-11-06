<?php


namespace netzgrenze;
use Webklex\PHPIMAP\ClientManager;
    class imap {
        protected $client;
        protected $config;
        protected $username;
        protected $password;

        public function __construct(?string $username = null, ?string $password = null) {
            $this->client = null;
            $imapconfig = require 'vendor/webklex/php-imap/src/config/imap.php';
            $this->config = require "config.php";

            if(isset($_SESSION["username"])){
                // echo "credentials aus session";
                $username = $_SESSION["username"];
                $password = self::getPassword();
                if ($password === null) {
                    // Session ohne gültiges Passwort (Cookie fehlt/abgelaufen)
                    $this->logout();
                    throw new \Exception('Session ungültig, bitte neu anmelden.');
                }
            }

            if ($this->config["host"] === ""){
                $imapconfig["accounts"]["default"]["host"] = (string)$_SESSION["host"];
            }

            if ($this->config["port"] === ""){
                $imapconfig["accounts"]["default"]["port"] = (int)$_SESSION["port"];
            }

            if ($this->config["encryption"] === ""){
                $imapconfig["accounts"]["default"]["encryption"] = \mb_strtolower((string)$_SESSION["encryption"]);
            }

            // Übernahme der Konfigurationsparamater aus der config falls gesetzt
            if ($this->config["validate_cert"] === ""){
                $imapconfig["accounts"]["default"]["validate_cert"] = (bool)$this->config["validate_cert"];
            }

            if ($this->config["rfc"] === ""){
                $imapconfig["accounts"]["default"]["rfc"] = (string)$this->config["rfc"];
            }

            if (is_null($this->config["proxy"]["socket"])){
                $imapconfig["accounts"]["default"]["proxy"]["socket"] = (string)$this->config["proxy"]["socket"];
            }

            if (is_null($this->config["proxy"]["request_fulluri"])){
                $imapconfig["accounts"]["default"]["proxy"]["request_fulluri"] = (bool)$this->config["proxy"]["request_fulluri"];
            }

            if (is_null($this->config["proxy"]["username"])){
                $imapconfig["accounts"]["default"]["proxy"]["username"] = (string)$this->config["proxy"]["username"];
            }

            if (is_null($this->config["proxy"]["password"])){
                $imapconfig["accounts"]["default"]["proxy"]["password"] = (string)$this->config["proxy"]["password"];
            }

            $imapconfig["accounts"]["default"]["username"] = $username;
            $imapconfig["accounts"]["default"]["password"] = $password;

            $cm = new ClientManager($imapconfig);
            $this->client = $cm->account('default');

            $this->username = $username;
            $this->password = $password;
        }

        public function __destruct() {
            if ($this->client) {
                $this->client->disconnect();
            }
        }

        public function login(): bool {
            try {
                $this->client->connect();
            } catch (\Exception $e) {
                return false;
            }

            if (!isset($_SESSION['username'])) {
                self::storeCredentials($this->username, $this->password);
            }
            $this->checkIfFolderExists($this->config["foldername"], true);
            return true;
        }

        public function logout(): void {
            $_SESSION = [];
            setcookie('mk', '', ['expires' => time() - 3600, 'path' => '/']);
            session_destroy();
        }

        public function getFolder(string $folderName) {
            if (!$this->client) {
                throw new \Exception('IMAP client is not connected.');
            }
            return $this->client->getFolder($folderName);
        }


        public function checkIfFolderExists(string $folderName, bool $createIfNotExists = false): bool {
            if (!$this->client) {
                throw new \Exception("IMAP client is not connected.");
            }

            $folderName = $this->resolveFolderName($folderName);
            $folder = $this->client->getFolderByPath($folderName);
            if ($folder !== null) {
                return true;
            }
            if (!$createIfNotExists) {
                return false;
            }

            try {
                $delimiter = $this->getDelimiter();
                $parts = explode($delimiter, $folderName);
                $path = '';

                foreach ($parts as $part) {
                    $path = ($path === '') ? $part : $path . $delimiter . $part;

                    $current = $this->client->getFolderByPath($path);
                    if ($current === null) {
                        $this->client->createFolder($path, false);
                        $current = $this->client->getFolderByPath($path);
                    }
                    if ($current !== null) {
                        $current->subscribe();
                    }
                }

                $_SESSION["msg"][] = "Der IMAP-Ordner '$folderName' wurde erstellt und alle Ebenen abonniert.";
                return true;
            } catch (\Exception $e) {
                $_SESSION["msg"][] = "Fehler: " . $e->getMessage();
                return false;
            }
        }

        private function getDelimiter(): string {
            $inbox = $this->client->getFolderByPath('INBOX');
            return $inbox->delimiter ?? '.';
        }

        private function resolveFolderName(string $folderName): string {
            return str_replace('<delimiter>', $this->getDelimiter(), $folderName);
        }




        public function getAllSiteGaurdMails(){
            if (!$this->client) {
                throw new \Exception("IMAP client is not connected.");
            }
            try {
                $folder = $this->client->getFolder('INBOX');
            } catch (\Exception $e) {
                $_SESSION["msg"][] = "Fehler: " . $e->getMessage();
                return false;
            }

            $query = $folder->query();
            $query->leaveUnread();
            $query->setFetchOrder("desc");

            $messages = $query->from($this->config["from"])->get();

            $subjects = [];
            foreach ($messages as $message){
                $subjects[] = [
                                'id' => (int)$message->getUid(),
                                'subject' => (string)$message->subject,
                                'date' => (string)$message->date,
                            ];
            }
            return $subjects;
        }



        public function getMail(int $id){
            if (!$this->client) {
                throw new \Exception("IMAP client is not connected.");
            }
            try {
                $folder = $this->client->getFolder('INBOX');
                $query = $folder->query();
                $message = $query->getMessage($id);
                return [
                    'id' => (int)$message->getUid(),
                    'subject' => (string)$message->subject,
                    'date' => (string)$message->date,
                    'body' => (string)$message->getTextBody(),
                ];
            } catch (\Exception $e) {
                $_SESSION["msg"][] = "Fehler: " . $e->getMessage();
                return false;
            }
        }


        public function moveMail(int $id){
            file_put_contents( "php://stdout","ID " . $id. "\n" );
            if (!$this->client) {
                throw new \Exception("IMAP client is not connected.");
            }
            try {
                $folder = $this->client->getFolder('INBOX');
                $query = $folder->query();
                $message = $query->getMessageByUid((int)$id);
            } catch (\Exception $e) {
                $_SESSION["msg"][] = "ERROR: " . $e->getMessage();
                return false;
            }

            $folderName = 'INBOX<delimiter>' . $this->config["foldername"];
            $folderName = $this->resolveFolderName($folderName);

            try {
                $message->move($folderName);
                return true;
            } catch (\Exception $e) {
                // file_put_contents( "php://stdout","Line 178 " . $e->getMessage() . "\n" );
                $_SESSION["msg"][] = "Fehler: " . $e->getMessage();
                return false;
            }

        }



        private static function storeCredentials(string $username, string $password): void {
            $key   = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

            session_regenerate_id(true);
            $_SESSION['username'] = $username;
            $_SESSION['password'] = base64_encode(
                $nonce . sodium_crypto_secretbox($password, $nonce, $key)
            );

            setcookie('mk', base64_encode($key), [
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        private static function getPassword(): ?string {
            if (empty($_SESSION['password']) || empty($_COOKIE['mk'])) {
                return null;
            }
            $key = base64_decode($_COOKIE['mk'], true);
            $raw = base64_decode($_SESSION['password'], true);
            if ($key === false || $raw === false
                || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES
                || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
                return null;
            }
            $nonce  = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $pass   = sodium_crypto_secretbox_open($cipher, $nonce, $key);
            return $pass === false ? null : $pass;
        }

    }



?>