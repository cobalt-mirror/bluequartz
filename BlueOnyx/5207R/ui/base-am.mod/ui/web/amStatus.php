<?php
// Author: Phil Ploquin, Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.
// This is the Active Monitor status page

include_once("ServerScriptHelper.php");

$servhelp = new ServerScriptHelper();

// Only users with 'serverShowActiveMonitor' should be here:
if (!$servhelp->getAllowed('serverShowActiveMonitor')) {
  header("location: /error/forbidden.html");
  return;
}

$cce = $servhelp->getCceClient();
$factory = $servhelp->getHtmlComponentFactory("base-am");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());
?>

<SCRIPT LANGUAGE="javascript">
	top.code.monitorLight_checkAlert();
</SCRIPT>
<SCRIPT LANGUAGE="javascript">
var updateConfirm = '<?php print $i18n->getJs("amUpdateNowConfirm");?>';

function confirmUpdate()
{
	if(confirm(updateConfirm)) {
		location = 'am_update.php';
	}
}
</SCRIPT>
 
<?php
$amobj = $cce->getObject("ActiveMonitor");
if ($amobj == null) {
	$msg = $factory->getTextBlock("", 
		$i18n->interpolate("[[base-am.amObjNotFound]]"), "r");
	print($msg->toHtml());
} else {
    $amenabled = $amobj["enabled"];

    $swatchbutton = $factory->getButton("javascript: confirmUpdate();",
					"amUpdateNow");
    if (!$amenabled) {
        $swatchbutton->setDisabled(true);
    }
    print($swatchbutton->toHtml());
    print("<br>");

    // standardize column attributes
    $colwidths = array(25, 475, 50);
    $alignments = array("center", "left", "center");

    $syslist = $factory->getScrollList(
        "amSysClients",     // Header label (i18n tag)
        array(         // The column headers
            " ",
            "amClientName",
	    "action"
        ),
        array(0, 1)
    );
    $syslist->setDefaultSortedIndex(1);
    $syslist->setColumnWidths($colwidths);
    $syslist->setAlignments($alignments);

    $servlist = $factory->getScrollList(
        "amServClients",     // Header label (i18n tag)
        array(         // The column headers
            " ",
            "amClientName",
	    "action"
        ),
        array(0, 1)
    );
    $servlist->setDefaultSortedIndex(1);
    $servlist->setColumnWidths($colwidths);
    $servlist->setAlignments($alignments);

    $am_names = $cce->names("ActiveMonitor");

    $stmap = array(
        "N" => "none", 
        "G" => "normal", 
        "Y" => "problem", 
        "R" => "severeProblem");
    
    for ($i=0; $i < count($am_names); ++$i) {
        $nspace = $cce->get($amobj["OID"], $am_names[$i]);
        if (!$nspace["hideUI"]) {
            $iname = $i18n->interpolate($nspace["nameTag"]);

	    if (!$amenabled) {
		$icon = $factory->getStatusSignal("noMonitor", $nspace["URL"]);
	    } else if (!$nspace["enabled"]) {
		$icon = $factory->getStatusSignal("disabled", $nspace["URL"]);
	    } else if (!$nspace["monitor"]) {
		$icon = $factory->getStatusSignal("noMonitor", $nspace["URL"]);
	    } else {
                $icon = $factory->getStatusSignal(
			$stmap[$nspace["currentState"]], $nspace["URL"]);
            }

	    $namefield = $factory->getTextField("name$i", $iname, "r");

	    $details = $factory->getDetailButton($nspace["URL"]);
            if ($nspace["URL"] == "") {
		$details->setDisabled(true);
	    }

            if ($nspace["UIGroup"] == "system") {
                $syslist->addEntry(array($icon, $namefield, $details));
            } else if ($nspace["UIGroup"] == "service") {
                $servlist->addEntry(array($icon, $namefield, $details));
            } else {
		if (!$otherlist) { 
                        $otherlist = $factory->getScrollList(
                            "amOtherClients",
                            array(
                                " ",
                                "amClientName",
				"action"
                            ),
                            array(0, 1)
                        );
                        $otherlist->setDefaultSortedIndex(1);
                        $otherlist->setColumnWidths($colwidths);
			$otherlist->setAlignments($alignments);
		}
		$otherlist->addEntry(array($icon, $namefield, $details));
	    }
        }
    }

    print($syslist->toHtml());
    print("<br>");
    print($servlist->toHtml());
    if ($otherlist) {
	print("<br>");
	print($otherlist->toHtml());
    }

    $key = $factory->getLabel("amKey", false);
    $noneSignal = $factory->getStatusSignal("none");
    $noneLabel = $factory->getLabel("amKeyGrey", false);
    $normalSignal = $factory->getStatusSignal("normal");
    $normalLabel = $factory->getLabel("amKeyGreen", false);
    $problemSignal = $factory->getStatusSignal("problem");
    $problemLabel = $factory->getLabel("amKeyYellow", false);
    $severeProblemSignal = $factory->getStatusSignal("severeProblem");
    $severeProblemLabel = $factory->getLabel("amKeyRed", false);

    print("<BR><TABLE>");
    print("<TR><TD ROWSPAN=\"4\" VALIGN=\"TOP\" WIDTH=\"60\">"
		. $key->toHtml() . "</TD>"
		. "<TD>" . $noneSignal->toHtml() . "</TD>"
		. "<TD>" . $noneLabel->toHtml() . "</TD></TR>");
    print("<TR><TD>" . $normalSignal->toHtml() . "</TD>"
		. "<TD>" . $normalLabel->toHtml() . "</TD></TR>");
    print("<TR><TD>" . $problemSignal->toHtml() . "</TD>"
		. "<TD>" . $problemLabel->toHtml() . "</TD></TR>");
    print("<TR><TD>" . $severeProblemSignal->toHtml() . "</TD>"
		. "<TD>".$severeProblemLabel->toHtml()."</TD></TR>");
    print("</TABLE>");
}

$refresh = 900 * 1000; // 15 minutes, in milliseconds
print("<SCRIPT LANGUAGE=\"javascript\"> 
	setTimeout(\"location.reload(true)\", $refresh); </SCRIPT>");

print($page->toFooterHtml());

$servhelp->destructor();

/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>