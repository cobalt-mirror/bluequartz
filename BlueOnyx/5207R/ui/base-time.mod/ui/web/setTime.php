<?php
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: setTime.php
set_time_limit(0);
include_once("ServerScriptHelper.php");
//include_once("tzoffset.php"); <- No longer needed.
$serverScriptHelper = new ServerScriptHelper();

// Only 'serverTime' should be here:
if (!$serverScriptHelper->getAllowed('serverTime')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-time", "/base/time/setTimeHandler.php");

// get defaults
$defaults = $cceClient->getObject("System", array(), "Time");
$page = $factory->getPage();
$block = $factory->getPagedBlock("timeSetting");
$block->processErrors($serverScriptHelper->getErrors());

// Get current time from time():
$t = time();

$systemDisplayedDate = $factory->getTimeStamp("systemDate", $t, "datetime");
$block->addFormField($factory->getTimeStamp("oldTime", $t, "time", ""));
$block->addFormField($systemDisplayedDate, $factory->getLabel("systemDisplayedDate"));

$systemDisplayedTimeZone = $factory->getTimeZone("systemTimeZone", $defaults["timeZone"]);
$oldTimeZone = $factory->getTimeZone("oldTimeZone", $defaults["timeZone"], "");

$block->addFormField($systemDisplayedTimeZone, $factory->getLabel("systemDisplayedTimeZone"));
$block->addFormField($oldTimeZone);

// NTP server may only be set on stand alone servers, not in a VPS:
if (! is_file("/proc/user_beancounters")) {
    $ntpAddress = $factory->getNetAddress("ntpAddress",$defaults["ntpAddress"]);
    $ntpAddress->setOptional(true);
    $block->addFormField($ntpAddress, $factory->getLabel("ntpAddress"));
}

// PHP5 related fix:
$block->addFormField(
    $factory->getTextField("debug_1", "", 'r'),
    $factory->getLabel("debug_1"),
    Hidden
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$serverScriptHelper->destructor();

?>
<?php print($page->toHeaderHtml()); ?>
<?php print($block->toHtml()); ?>
<?php print($page->toFooterHtml());
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
