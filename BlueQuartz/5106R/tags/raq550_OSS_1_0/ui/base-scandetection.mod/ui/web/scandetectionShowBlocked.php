<?php
// Author: Jesse Throwe
// Copyright 2001 Sun Microsystems, Inc. All rights reserved.
// $Id: scandetectionShowBlocked.php,v 1.2.2.1 2002/02/20 01:30:36 pbaltz Exp $

// -------------------------------------
// Includes

include("ServerScriptHelper.php");

// -------------------------------------
// Variables

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-scandetection");
$i18n = $serverScriptHelper->getI18n("base-scandetection");
$page = $factory->getPage();


// -------------------------------------
// Construct buttons

$goback = $factory->getBackButton("/base/scandetection/scandetection.php"); 

$clearblockedButton = $factory->getButton("javascript: confirmClear()", "clearBlocked");



// -------------------------------------
// Build the scroll list
$iplisting = $factory->getScrollList("scandetectionShowBlocked", array (""),array(0,1));
$iplisting->setDefaultSortedIndex(0);
$iplisting->setColumnWidths(100);
$iplisting->setAlignments(array("Center"));

// run the helper script
$serverScriptHelper->shell("/usr/sausalito/handlers/base/scandetection/detectportscans.pl", $output, 'root');

// parse the output
$iparray = explode("\n", $output);

// add each line to the listing
foreach ($iparray as $anip) {
if ($anip == "") { continue; }
$htmlip = $factory->getTextField("ip", $anip, "r");
$iplisting->addEntry(array($htmlip));
}

// cleanup our mess, print out the results, and quit
$serverScriptHelper->destructor();

print $page->toHeaderHtml();
?>
<SCRIPT LANGUAGE="javascript">
function confirmClear() {
  var message = "<?php print($i18n->get("confirmClearBlocked"))?>";

  if(confirm(message))
    location = "/base/scandetection/scandetectionBlockClear.php?reallydo=yes";
}
</SCRIPT>

<table><tr>
<td><? print $clearblockedButton->toHtml(); ?></td></tr></table><br>
<?
print $iplisting->toHtml();
?> <br> <?
print $goback->toHtml();
print $page->toFooterHtml();
/*
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
