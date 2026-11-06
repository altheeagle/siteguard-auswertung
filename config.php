<?php
    return[
        // Der Ordner in dem die bearbeiteten SiteGuard Mails
        // abgelegt werden. Falls der Ordner nicht vorhanden ist
        // wird er angelegt.

        // <delimiter> ersetzt das serverspezifische Trenn-
        // zeichen und muss genau so eingesetzt werden
        // Beispiele:
        //
        //  "foldername" => "SiteGuard",
        //      Der Ordner "SiteGuard" wird als Ordner auf
        //      der Ebene des Posteingang angelegt
        //
        //  "foldername" => "INBOX<delimiter>SiteGuard",
        //      Der Ordner "SiteGuard" wird als
        //      Unterordner des Posteingangs gesetzt
        //  "foldername" => "INBOX<delimiter>Strato<delimiter>SiteGuard",
        //      Der Ordner "SiteGuard" wird als Posteingang/Webhosting/SiteGuard
        //      angelegt
        //


        "foldername" => "Strato2<delimiter>SiteGuard",

        // Sollen die abgelegten Mails als gelesen markiert werden
        "marAsRead" => true,

        // Hier können die Serverdaten eingetragen werden.
        // Falls die Werte nicht gefüllt sind, wird nach
        // Autoconfig Werten gesucht
        "host" => "",
        "port" => "",
        "encryption" => "",

        // Einstellungen für den Proxyzugriff auf den IMAP Server
        "proxy" => [
            "socket" => null,
            "request_fulluri" => false,
            "username" => null,
            "password" => null,

        ],

        // Zertifikatsprüfung mit false deaktivieren
        "validate_cert" => true, // true (default) oder false

        // ----------------------------------------------------
        //  Diese Einstellungen müssen in
        //  der Regel nicht geändert werden
        // ----------------------------------------------------

        "rfc" => "RFC822",
        "timeout" => 30,

        // Absender der Sitegurad Mails
        "from" => "siteguard@strato.de",
    ];
?>