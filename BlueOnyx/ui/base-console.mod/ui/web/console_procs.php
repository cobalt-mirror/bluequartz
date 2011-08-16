<?php

// Author: 
//		Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Thu 02 Jul 2009 12:48:08 AM CEST
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

        $helper = new ServerScriptHelper($sessionId);
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
$factory = $serverScriptHelper->getHtmlComponentFactory("base-console");
$transMethodOn="off";

// get Process list information out of CCE:
$procdata = $cceClient->get($masterOID[0]);

// Prepare Page generation:
$page = $factory->getPage();

print($page->toHeaderHtml());

// USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND

$node_beans = $factory->getScrollList("vserver_processlist",
        array("PID", "USER", "CPU", "MEM", "VSZ", "RSS", "TTY", "STAT", "START", "TIME", "COMMAND", "KILL"), array(0,1,2,3,4,5,6,7,8,9,10,$action));
$node_beans->setDefaultSortedIndex(0);

// Populate table rows with the data:

// USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND

// Explode entire strings into separate lines:
$pieces = explode("#DELI#", $procdata['sol_processes']);

// Entire lines are now stored in $pieces[$num]
// We need to split it further down to cells:

// How many entries are in $pieces?
$ps_lines = 0;
$ps_lines = count($pieces);
$ps_a = "0";
$ps_b = "1";


foreach ($pieces as $line) {
    if ($ps_a > 0) {

	// Break $output up after 63 chars:
	$output = str_split_php4($line);

	// Replace all whitespaces from $output[0]:
	$pattern = '/\s+/i';
	$replacement = ';';
	$proc_out = preg_replace($pattern, $replacement, $output[0]);

	// Break $output[0] up into:
	// USER PID %CPU %MEM VSZ RSS TTY STAT START TIME COMMAND

	list($USER, $PID[$ps_b], $CPU[$ps_b], $MEM[$ps_b], $VSZ[$ps_b], $RSS[$ps_b], $TTY[$ps_b], $STAT[$ps_b], $START[$ps_b], $TIME[$ps_b]) = explode(";", $proc_out);

	$action = $factory->getCompositeFormField();

	// Put $output[1] to $output[xxxx] together again:
	$output_chunks = count($output);

	if ($output_chunks == "2") {
	    $the_proc = $output[1];
	}
	else {
	    $the_proc = "";
	    $pnum = 0;
	    foreach ($output as $zeile) {
		if ($pnum != 0) {
		    $the_proc = $the_proc . $zeile;
		}
		$pnum++;		
	    }
	}

	if (($PID[$ps_b] != "PID") && ($PID[$ps_b] != "") && ($PID[$ps_b] != $last_PID)) {

    	    if ($serverScriptHelper->getAllowed('adminUser')) {
        	$removeButton = $factory->getRemoveButton("/base/console/proc_kill.php?pid=$PID[$ps_b]");
        	$action->addFormField($removeButton);
    	    }

	    if ((preg_match("/handlers\/base\/console\/generate_process_list.pl/", $the_proc)) || 
		(preg_match("/\/bin\/ps auxwf > \/tmp\/console.process-list/", $the_proc)) || 
		(preg_match("/\_ \/bin\/ps auxwf/", $the_proc))) {
		
		// Ignore
		
	    }
	    else {	    
		// Show
    		$last_PID = $PID[$ps_b];
	        $GLOBALS["_FormField_width"] = 55;
    	        $node_beans->addEntry(array(
        	    $factory->getTextField("", $PID[$ps_b], "r"),
    	            $factory->getTextField("", $USER, "r"),
        	    $factory->getTextField("", $CPU[$ps_b], "r"),
            	    $factory->getTextField("", $MEM[$ps_b], "r"),
            	    $factory->getTextField("", $VSZ[$ps_b], "r"),
            	    $factory->getTextField("", $RSS[$ps_b], "r"),
        	    $factory->getTextField("", $TTY[$ps_b], "r"),
            	    $factory->getTextField("", $STAT[$ps_b], "r"),
            	    $factory->getTextField("", $START[$ps_b], "r"),
            	    $factory->getTextField("", $TIME[$ps_b], "r"),
            	    $factory->getTextField("", $the_proc, "r"),
		    $action
        	));
    		$ps_b++;
    	    }
	}
    }
    $ps_a++;
}

// Results of 'w':
// Slight - but imaginative - misuse of the SimpleBlock function:

$cmd = "/usr/bin/w";
exec("$cmd 2>&1", $output);
$block = $factory->getSimpleBlock(" ");
$block->addHtmlComponent($factory->getTextField("nix", $i18n->interpolate($output[2]), "rw"));
print("<br>");
print $block->toHtml();

print($node_beans->toHtml());

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

