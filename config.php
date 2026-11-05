<?php

	/*
		Change here the IMAP-Server Details and the Folder to move
		audited Mails.
	*/



    $imapconfig["accounts"]["default"]["host"] = "imap.strato.de";
    $imapconfig["accounts"]["default"]["port"] = 993;
    $imapconfig["accounts"]["default"]["protocol"] = "imap";
    $imapconfig["accounts"]["default"]["encryption"] = "ssl";
    $imapconfig["accounts"]["default"]["validate_cert"] = true;
    $imapconfig["accounts"]["default"]["username"] = "";
    $imapconfig["accounts"]["default"]["password"] = "";
    $imapconfig["accounts"]["default"]["authentication"] = null;
    $imapconfig["accounts"]["default"]["rfc"] = "RFC822";
    $imapconfig["accounts"]["default"]["proxy"]["socket"] = null;
    $imapconfig["accounts"]["default"]["proxy"]["request_fulluri"] = false;
    $imapconfig["accounts"]["default"]["proxy"]["username"] = null;
    $imapconfig["accounts"]["default"]["proxy"]["password"] = null;
    $imapconfig["accounts"]["default"]["timeout"] = 30;
    $imapconfig["accounts"]["default"]["extensions"] = [];

    return[
        "foldername" => "SiteGuard",
        "from" => "siteguard@strato.de"
    ];
?>