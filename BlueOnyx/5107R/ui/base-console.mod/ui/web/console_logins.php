<?php

// Author: 
//		Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Fri 03 Jul 2009 10:24:46 AM CEST
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
                        'gui_list_lasttrigger' => time()
                )
         );
        $errors = $cceHelper->errors();

//

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-console");
$transMethodOn="off";

// get Process list information out of CCE:
$procdata = $cceClient->get($masterOID[0]);

// Prepare Page generation:
$page = $factory->getPage();

print($page->toHeaderHtml());

// USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND

$node_beans = $factory->getScrollList("vserver_loginlist", array(" ", "LUSER", "CONSOLE", "HOST", "START_DATE", "STIME", "ETIME", "DURATION", "UKILL"), array(0,1,2,3,4,5,6,7,$action));

// Populate table rows with the data:

// USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND

// Explode entire strings into separate lines:
$pieces = explode("#DELI#", $procdata['sol_logins']);

// How many entries are in $pieces?
$ps_lines = 0;
$ps_lines = count($pieces);
$ps_a = "0";
$ps_b = "1";


foreach ($pieces as $line) {
    if ($ps_a > 0) {

	$action = $factory->getCompositeFormField();

	// Split down each line into the bits and pieces we need:
	$my_login = str_split_php4($line, "0", "9");
	$login = rtrim($my_login[0]);

	$my_console = str_split_php4($line, "9", "22");
	$console = rtrim($my_console[0]);

	$my_host = str_split_php4($line, "22", "38");
	$host = rtrim($my_host[0]);

	$my_startdate = str_split_php4($line, "38", "50");
	$startdate = rtrim($my_startdate[0]);

	$my_starttime = str_split_php4($line, "50", "56");
	$starttime = rtrim($my_starttime[0]);

	$my_endtime = str_split_php4($line, "58", "64");
	$endtime = rtrim($my_endtime[0]);

	$my_duration = str_split_php4($line, "64", "75");
	$duration = rtrim($my_duration[0]);

    	if (($serverScriptHelper->getAllowed('adminUser')) && ($endtime == "still")) {
    	    if (eregi("ftpd", $console)) {
    	        $killer = "ftpd";
		$my_ftpd_pid = str_split_php4($console, "4", "75");
		$ftpd_pid = rtrim($my_ftpd_pid[0]);
    	    }
	    else {
    	        $killer = urlencode($console);
    	        $ftpd_pid = "0";
    	    }
    	    $removeButton = $factory->getRemoveButton("/base/console/userkill.php?console=$killer&username=$login&pid=$ftpd_pid");
    	    $action->addFormField($removeButton);
    	}

        if (eregi("wtmp begins", $line)) {
	    $my_header = str_split_php4($line, "0", "42");
	    $header = rtrim($my_header[0]);
	}
	elseif (!$login) {
	}
	else { 
	    // Show
	    $GLOBALS["_FormField_width"] = 30;
    	    $node_beans->addEntry(array(
        	    $factory->getTextField("", $ps_a, "r"),
        	    $factory->getTextField("", $login, "r"),
        	    $factory->getTextField("", $console, "r"),
        	    $factory->getTextField("", $host, "r"),
        	    $factory->getTextField("", $startdate, "r"),
        	    $factory->getTextField("", $starttime, "r"),
        	    $factory->getTextField("", $endtime, "r"),
        	    $factory->getTextField("", $duration, "r"),
		    $action
    		    ));
    	    $ps_b++;
	}
    }
    $ps_a++;
}

$node_beans->setDefaultSortedIndex(0);

// Print out when wtmp begins:
// Slight - but imaginative - misuse of the SimpleBlock function:
$block = $factory->getSimpleBlock(" ");
$block->addHtmlComponent($factory->getTextField("nix", $i18n->interpolate($header), "rw"));
print("<br>");
print $block->toHtml();

print($node_beans->toHtml());

// Print Footer:
print($page->toFooterHtml()); 

// str_split_php4
function str_split_php4( $text, $min, $max ) {
    // place each character of the string into and array
    $array = array();
    for ( $i=0; $i < strlen( $text ); ){
        $key = NULL;
        for ( $j = 0; $j < $max; $j++, $i++ ) {
    	    if ($j >= $min) {
                $key .= $text[$i];
            }
        }
        array_push( $array, $key );
    }
    return $array;
}

?>

