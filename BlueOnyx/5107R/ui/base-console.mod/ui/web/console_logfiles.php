<?php

// Author: 
//		Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Mon 06 Jul 2009 12:14:19 AM CEST
//

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-console");

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

// First of all we need to update the ressource info in CCE.
// For that purpose we write something into CCE and henceforth
// trigger a handler that will update the info in CCE:

        $helper =& new ServerScriptHelper($sessionId);
        $cceHelper =& $helper->getCceClient();
        $masterOID = $cceHelper->find("SOL_Console");

        $cceHelper->set($masterOID[0], "",
                array(
                        'gui_list_proctrigger' => time()
                )
         );
        $errors = $cceHelper->errors();

//

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-console", "/base/console/console_logfilesHandler.php");
$transMethodOn="off";

// get Process list information out of CCE:
$procdata = $cceClient->get($masterOID[0]);

// Prepare Page generation:
$page = $factory->getPage();

print($page->toHeaderHtml());

$block = $factory->getPagedBlock("Logfile Viewer");
$block->processErrors($serverScriptHelper->getErrors());

$logfile_choices = array
            (
                "1" => "/var/log/cron",
                "2" => "/var/log/maillog",
                "3" => "/var/log/messages",
                "4" => "/var/log/secure",
                "5" => "/var/log/httpd/access_log",
                "6" => "/var/log/httpd/error_log",
                "7" => "/var/log/admserv/adm_access",
                "8" => "/var/log/admserv/adm_error"
            );

$logfile_choices_select = $factory->getMultiChoice("sol_view",array_values($logfile_choices));
$logfile_choices_select->setSelected("$settings[sol_view]", true);
$block->addFormField($logfile_choices_select,$factory->getLabel("sol_view"));

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

print($block->toHtml());

// Print Footer:
print($page->toFooterHtml()); 

// str_split_php4
function str_split_php4( $text, $split = 64 ) {
    // place each character of the string into and array
    $array = array();
    for ( $i=0; $i < strlen( $text ); ){
        $key = NULL;
        for ( $j = 0; $j < $split; $j++, $i++ ) {
            $key .= $text[$i];
        }
        array_push( $array, $key );
    }
    return $array;
}

?>

