<?php

// Author:
//              Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Mon 06 Jul 2009 12:14:08 AM CEST
//

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-console");

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-console", "/base/console/console_logfiles.php");
$transMethodOn="off";

$errors = array();

$var_sol_view = $_POST['sol_view'];
$sol_view = $var_sol_view;

        if ($sol_view == "/var/log/cron") {
                $my_sol_view = "console_logs_cron.php";
        }
        elseif ($sol_view == "/var/log/maillog") {
                $my_sol_view = "console_logs_maillog.php";
        }
        elseif ($sol_view == "/var/log/messages") {
                $my_sol_view = "console_logs_messages.php";
        }
        elseif ($sol_view == "/var/log/secure") {
                $my_sol_view = "console_logs_secure.php";
        }
        elseif ($sol_view == "/var/log/httpd/access_log") {
                $my_sol_view = "console_logs_pa.php";
        }
        elseif ($sol_view == "/var/log/httpd/error_log") {
                $my_sol_view = "console_logs_pe.php";
        }
        elseif ($sol_view == "/var/log/admserv/adm_access") {
                $my_sol_view = "console_logs_adm_a.php";
        }
        elseif ($sol_view == "/var/log/admserv/adm_error") {
                $my_sol_view = "console_logs_adm_e.php";
        }
        else {
                $my_sol_view = "0";
        }

print($serverScriptHelper->toHandlerHtml("/base/console/$my_sol_view", $errors));

$serverScriptHelper->destructor();
?>

