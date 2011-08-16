<?php

// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: ecc_logs.php 1050 2008-01-23 11:45:43Z mstauber $

include_once("ServerScriptHelper.php");
include_once("base/am/am_detail.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
$page = $factory->getPage();
$i18n = $serverScriptHelper->getI18n();

$stylist = $serverScriptHelper->getStylist();
$style = $stylist->getStyle("Page");
$fontTag = "<FONT STYLE=\"" . $style->toTextStyle() . "\">%s</FONT>";

$log = fopen("/var/log/kernel", "r");
$output = "";

while (!feof($log)) {
  $line = fgets($log, 4096);
  // strpos may return 0 if NMI is at first character
  // but the logs always have the date header, so no
  // need to check for that
  // print only lines that have NMI data or
  // that related to kernel logging and reboots

  if (strpos($line, "NMI:") || strpos($line, "klogd") || 
      preg_match("/Kernel.*log/", $line)) {
    $output .= $line;
  }
}

$back = $factory->getBackButton("/base/am/ecc_details.php");

$list = $factory->getScrollList("eccLogs", array("entries"));

$ablock = $factory->getTextBlock("entries", $output, "r");
$ablock->setWrap(true);
$list->addEntry(array($ablock));
$list->setWidth(700);
$list->setEntryCountHidden(true);
$list->setHeaderRowHidden(true);

print ($page->toHeaderHtml());
print ($list->toHtml());

print ("<BR>");
print ($back->toHtml());

print ($page->toFooterHtml());
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
