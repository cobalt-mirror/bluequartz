<?php
// Copyright 2001 Sun Microsystems, Inc. All rights reserved.
// $Id: scandetection.php,v 1.13 2001/12/07 03:05:29 pbaltz Exp $


// -------------------------------------
// Includes

include("ServerScriptHelper.php");


// -------------------------------------
// Variables

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient() or die ("CCE was not found, or is not running");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-scandetection", "/base/scandetection/scandetectionHandler.php");
$i18n = $serverScriptHelper->getI18n("base-scandetection");
$page = $factory->getPage();
$block = $factory->getPagedBlock("scandetectionSettings");

// -------------------------------------
// process errors from the handler page
$block->processErrors($serverScriptHelper->getErrors());

// -------------------------------------
// Get values from CCE and parse the values out to their appropriate places

$cce = $cceClient->getObject("System", array(), "Scandetection");


// -------------------------------------
// Construct buttons

$viewlogButton = $factory->getButton("javascript: window.open('/base/scandetection/scandetectionLog.php','scandetectionLog', 'menubar=no,resizable=yes,hscroll=yes'); void 0;", "viewlogButton");

$viewblockedButton= $factory->getButton("/base/scandetection/scandetectionShowBlocked.php", "showBlocked");

// -------------------------------------
// Block Form

$scanDetectionLevel = $factory->getMultiChoice("scandetection_level",
   array("scandetectionLevel0", "scandetectionLevel1", "scandetectionLevel2"));

$scanDetectionLevel->setSelected($cce["paranoiaLevel"], true);

$block->addFormField(
   $scanDetectionLevel, 
   $factory->getLabel("scandetectionLevel")
);

// now for the timeout form
$setTimeout = $factory->getInteger("timeout", $cce["timeout"], 60, 600);
$setTimeout->showBounds(1);

$block->addFormField($setTimeout, $factory->getLabel("timeout"));

// now set the number of scans
$setNumScans = $factory->getInteger("setNumScans", $cce["numScans"], 3, 8);
$setNumScans->showBounds(1);


$block->addFormField($setNumScans, $factory->getLabel("setNumScans"));

// now get the alert boolean button
$alertBox = $factory->getBoolean("alertMe", $cce["alertMe"]);
$block->addFormField($alertBox, $factory->getLabel("alertMe"));

// now set the admin form
$adminEmail = $factory->getEmailAddress("alertEmail", $cce["alertEmail"]);
$adminEmail->setOptional(true);

// Use ActiveMonitor's email contact list if possible
$am_obj = $cceClient->getObject('ActiveMonitor', array(), '');
if( ! $am_obj["alertEmailList"] )
        $block->addFormField($adminEmail, $factory->getLabel("alertEmail"));

// now set the perm blocked form
$permaBlocked = $factory->getIpAddressList("permBlocked", $cce["permBlocked"]);
$permaBlocked->setOptional(true);

$block->addFormField($permaBlocked, $factory->getLabel("permBlocked"));

// now set the never blocked form
$neverBlocked = $factory->getIpAddressList("permUnblocked",$cce["permUnblocked"]);
$neverBlocked->setOptional(true);

$block->addFormField($neverBlocked, $factory->getLabel("permUnblocked"));

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

// Free Helper
$serverScriptHelper->destructor();


// -------------------------------------
// Print out all the data

print $page->toHeaderHtml();
?>
<table>
<tr><td><? print $viewlogButton->toHtml(); ?></td>
<td><? print $viewblockedButton->toHtml(); ?></td>
</table><br>
<?
print $block->toHtml();
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
