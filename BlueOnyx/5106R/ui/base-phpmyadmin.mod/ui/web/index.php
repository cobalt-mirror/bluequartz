<?php

// phpMyAdmin Redirector
// (C) Project BlueOnyx 2008 - All rights reserved.

// Get FQDN of the server:
$dn = $_SERVER['SERVER_NAME'];

if ($_SERVER['HTTPS']) {
    // User logged in by HTTPS - redirect to HTTPS URL:
    header ("Location: https://$dn:81/phpMyAdmin/");
}
else {
    // User logged in by HTTP - redirect to HTTP URL:
    header ("Location: http://$dn:444/phpMyAdmin/");
}

?>
