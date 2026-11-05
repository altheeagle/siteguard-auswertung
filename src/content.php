<?php
namespace netzgrenze;

class content {


    /**
     * Formatiert ein Datum oder einen Zeitstempel in ein deutsches Datumsformat.
     *
     * @param string|int $input Datum als String (z. B. aus MySQL) oder Unix-Timestamp.
     * @param bool $mitUhrzeit Gibt an, ob die Uhrzeit mit ausgegeben werden soll (Standard: true).
     * @return string Formatierter Datumsstring, z. B. "17.05.2025 12:34:56" oder "17.05.2025".
     */
    public static function formatDate($input, bool $mitUhrzeit = true): string {
        // Wenn es ein Timestamp ist (nur Zahlen), in DateTime umwandeln
        if (is_numeric($input)) {
            $date = (new \DateTime())->setTimestamp((int)$input);
        } else {
            // Andernfalls annehmen, dass es ein MySQL-Datum/-Zeitstring ist
            $date = new \DateTime($input);
        }

        // Format wählen
        $format = $mitUhrzeit ? 'd.m.Y H:i:s' : 'd.m.Y';

        return $date->format($format);
    }
	/**
	 * Erzeugt eine Seite mit den übergebenen Inhalten, Platzhaltern und Optionen.
	 *
	 * @param string $content Inhalt der Seite, Platzhalter mit {{}} werden durch die Inhalte von $fields ersetzt.
	 * @param array|null $fields Platzhalter, die ersetzt werden sollen ['platzhaltername' => ['Inhalt']].
	 * @param array|null $options Optionen für die Seite. Verfügbare Optionen:
	 *                            - statusCode: HTTP-Statuscode der Seite.
	 *                            - autoRedirect: true/false, ob die Seite automatisch weiterleiten soll.
	 *                            - redirectTime: Zeit in Sekunden bis zur Weiterleitung.
	 *                            - redirectUrl: URL, zu der weitergeleitet werden soll.
	 * @param string|null $rightRequired Erforderliches Recht für den Zugriff auf die Seite.
	 * @return string Die fertiggestellte Seite.
	 */
	public static function composePage($content, $fields = null, $options = null, $rightRequired = null){

		if (isset($options['statusCode'])){
			http_response_code($options['statusCode']);
		}


		if (isset($options['autoRedirect'])){
			if ($options['autoRedirect'] == true){
				$content = $content . PHP_EOL . content::autoRedirect($options['redirectUrl'], $options['redirectTime'], $options['redirectHidden']);
			} else {
				$content = $content . PHP_EOL . '<a href="' . $options['redirectUrl'] . '">Zurück</a>';
			}
		}


		$content = '<div class="container">' . $content . '</div>' . PHP_EOL;


		if ($fields == null){
			$fields = ['script_head','script_foot','css'];
		}


		// Wenn eines der immer referenzierten Felder nicht gesetzt ist, dann wird es mit einem leeren String ersetzt
		foreach (['script_head','script_foot','css'] as $key ){
			if (!array_key_exists($key ,$fields)){
				$fields[$key] = [""];
			}
		}

		$fields["appname"] = "Strato SiteGuard Auswertung";

		// Defaultwerte setzen
		array_unshift($fields['script_foot'], '/js/script.js');
		array_unshift($fields['script_foot'], '/vendor/twbs/bootstrap/dist/js/bootstrap.bundle.min.js');
		array_unshift($fields['css'], '/css/style.css');
		array_unshift($fields['css'], '/vendor/twbs/bootstrap/dist/css/bootstrap.min.css');

		$fields["csrf_token"] = $_SESSION['csrf_token'];

		$content = content::replace_placeholder(
			file_get_contents('html/header.html')
			. $content
			. file_get_contents('html/footer.html')
			,$fields);


		if (isset($options['container-lg'])){
			$content = str_replace('<div class="container">','<div class="container-lg">',$content);
		}

		return $content;
	}

	/**
	 * Ersetzt Platzhalter im Inhalt durch die übergebenen Werte.
	 *
	 * @param string $content Der Inhalt mit Platzhaltern.
	 * @param array|null $fields Die zu ersetzenden Platzhalter und ihre Werte.
	 * @return string Der Inhalt mit ersetzten Platzhaltern.
	 */
	public static function replace_placeholder($content, $fields = []){
		$return = $content;

		if (!is_array($fields)){
			throw new \Exception("Die Variable $fields wurde nicht als Array übergeben.", 1);
		}

		if ($fields != null){
			foreach ($fields as $key => $value){
				switch ($key) {
					case 'script_head':
					case 'script_foot':
					case 'css':
						$filereplace = '';
						foreach ($value as $file){
							if(!empty($file)){
								if ($key == 'script_foot' || $key == 'script_head'){
									$filereplace .= '<script src="' . $file . '" defer></script>' . PHP_EOL;
								} elseif ($key == 'css'){
									$filereplace .= '<link rel="stylesheet" href="' . $file . '">' . PHP_EOL;
								}
							}
						}
						$return = str_replace('{{'.$key.'}}',$filereplace,$return);
						break;

					default:
						if ($value == null){
							$value = "";
						}
						$return = str_replace('{{'.$key.'}}',$value,$return);
						break;
				}
			}
		}
		$return = str_replace('{{copyright}}',$_SERVER['SERVER_NAME'],$return);

		return $return;
	}


	/**
	 * Generiert einen Auto-Redirect-Skriptblock.
	 *
	 * @param string $target Ziel-URL für die Weiterleitung.
	 * @param int|false $countdown Countdown-Zeit in Sekunden.
	 * @return string HTML- und JavaScript-Code für den Auto-Redirect.
	 */
	public static function autoRedirect($target,$countdown = false, $hidden = false){
		$content = "";
		if($target != 'history.back()'){
			$targetcode = '		    window.location.href="' . $target . '";' . PHP_EOL . '		    return;';
		} else {
			$targetcode = $target . ';';
		}

		if (!$countdown){
			$countdown = 999999999;
		}
		if(true){
			if (!$hidden){
				$content .= '<p>Du wirst in <span id="autoRedirectCounter"></span> Sekunden weitergeleitet.</p>' . PHP_EOL;
			}
			$content .= '<script>' . PHP_EOL;
			$content .= 'var count=' . $countdown . ';' . PHP_EOL;
			if (!$hidden){
				$content .= 'document.getElementById("autoRedirectCounter").innerText = count;' . PHP_EOL;
			}
			$content .= 'var counter=setInterval(timer, 1000);' . PHP_EOL;
			$content .= 'function timer(){' . PHP_EOL;
			$content .= '	count=count-1;' . PHP_EOL;
			$content .= '	if (count <= 0){' . PHP_EOL;
			$content .= '		clearInterval(counter);' . PHP_EOL;
			$content .= $targetcode;
			$content .= '		' . PHP_EOL;
			$content .= '	}' . PHP_EOL;
			if (!$hidden){
				$content .= '	document.getElementById("autoRedirectCounter").innerText =  count;' . PHP_EOL;
			}
			$content .= '}' . PHP_EOL;
			$content .= '</script>' . PHP_EOL;
		}

		if (!$hidden){
			if($target != 'history.back()'){
				$content .= '<p>Klicken Sie <a href="' . $target . '">hier</a> um direkt weitergelietet zu werden.</p>' . PHP_EOL;
			} else {
				$content .= '<p>Klicken Sie <a href="#" onclick="history.back(); return false;">hier</a> um direkt weitergeleitet zu werden</p>';
			}
		}


		return $content;
	}
	/**
	 * Zeigt eine generische Fehlerseite an.
	 *
	 * @param array $fields Platzhalter für die Fehlermeldung (z. B. 'errorTitle', 'errorMessage').
	 * @param array $options Optionen für die Fehlerseite. Verfügbare Optionen:
	 *                       - statusCode: HTTP-Statuscode der Seite.
	 *                       - autoRedirect: true/false, ob die Seite automatisch weiterleiten soll.
	 *                       - redirectTime: Zeit in Sekunden bis zur Weiterleitung.
	 *                       - redirectUrl: URL, zu der weitergeleitet werden soll.
	 * @return string Die Fehlerseite.
	 */
	public static function showError($fields = [],$options = []){
		if (!isset($fields['errorTitle'])){
			$fields['errorTitle'] = 'Fehler';
		}

		if (!isset($fields['errorMessage'])){
			$fields['errorMessage'] = 'Ein Fehler ist aufgetreten.';
		}

		if (!isset($fields['websitetitle'])){
			$fields['websitetitle'] = 'Fehler';
		}

		if (!isset($options['statusCode'])){
			$options['statusCode'] = 500;
		}

		if (!isset($options['autoRedirect'])){
			$options['autoRedirect'] = true;
		}

		if (!isset($options['redirectTime'])){
			$options['redirectTime'] = 10;
		}

		if (!isset($options['redirectUrl'])){
			$options['redirectUrl'] = '/';
		}

		$content = file_get_contents('html/error.html');

		return content::composePage($content,[
			'errorTitle' => $fields['errorTitle'],
			'errorMessage' => $fields['errorMessage'],
			'websitetitle' => $fields['websitetitle'],
		],[
			'statusCode' => $options['statusCode'],
			'autoRedirect' => $options['autoRedirect'],
			'redirectTime' => $options['redirectTime'],
			'redirectUrl' => $options['redirectUrl'],
		]);
	}

	/**
	 * Erzeigt die Startseite nach der Anmeldung
	 *
	 *
	 *
	 */
	public static function makeStartpage(){

        $config = require_once 'config.php';

        $imap = new \netzgrenze\imap();
        $content = file_get_contents('html/hauptseite.html');
        $fields = [
            'websitetitle' => 'Startseite',
            'sessionUsername' => $_SESSION['username'],
            'foldername' => $config["foldername"],
            'sessionMsg' => implode("\n", $_SESSION['msg']),
            'siteguardMails' => self::makeAccordion($imap->getAllSiteGaurdMails()),
        ];
        $_SESSION["msg"] = [];
        return \netzgrenze\content::composePage($content, $fields);
    }

    public static function makeLogin(){
        $content = file_get_contents('html/login.html');
        $fields["csrf_token"] = $_SESSION["csrf_token"];
        return \netzgrenze\content::composePage($content, $fields);
    }


    public static function makeAccordion($mails){

    if (count($mails) == 0){
        return '<div class="alert alert-success" role="alert">
    Keine SiteGuard Mails im Posteingang vorhanden.
</div>';
    }

        $html = '<div class="accordion" id="siteguardmails">';

        foreach ($mails as $index => $mail){
            $html .= '<div class="accordion-item" data-mail-id="' . htmlspecialchars($mail['id']) . '">';
            $html .= '<h2 class="accordion-header" id="heading' . $index . '">';
            $html .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' . $index . '" aria-expanded="false" aria-controls="collapse' . $index . '">';
            $html .= '<span class="mail-date">' . self::formatDate(htmlspecialchars($mail['date'])) . '</span><span class="mail-sep"> - </span><span class="mail-subject">' . htmlspecialchars($mail['subject']) . '</span>';
            $html .= '</button>';
            $html .= '</h2>';
            $html .= '<div id="collapse' . $index . '" class="accordion-collapse collapse" aria-labelledby="heading' . $index . '" data-bs-parent="#siteguardmails" data-mail-id="' . htmlspecialchars($mail['id']) . '">';
            $html .= '<div class="accordion-body">';
            $html .= 'Hier könnten weitere Details zur E-Mail stehen.';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

	public static function body2Array(string $body){

        $body = str_replace(["\r\n", "\r"], "\n", $body);


        $blocks = preg_split('/\n\s*\n/', trim($body));

        $keyMap = [
            'ausfuehrendes script' => 'script',
            'betroffene datei'     => 'datei',
            'vorgang'              => 'vorgang',
        ];

        $entries = [];

        foreach ($blocks as $block) {
            $lines = array_values(array_filter(
                array_map('trim', explode("\n", trim($block))),
                fn($l) => $l !== ''
            ));

            if (!$lines) continue;

            $entry = ['datum' => null, 'script' => null, 'datei' => null, 'vorgang' => null];

            foreach ($lines as $i => $line) {
                if (strpos($line, ':') !== false) {
                    [$key, $value] = array_map('trim', explode(':', $line, 2));
                    $k = mb_strtolower($key);
                    if (isset($keyMap[$k])) {
                        $entry[$keyMap[$k]] = $value;
                        continue;
                    }
                }
                // Erste nicht zuordenbare Zeile = Zeitstempel
                if ($i === 0) {
                    $entry['datum'] = $line;
                }
            }

            // Nur Blöcke übernehmen, die wirklich nach Log-Eintrag aussehen
            if ($entry['script'] !== null || $entry['datei'] !== null || $entry['vorgang'] !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }


}


?>