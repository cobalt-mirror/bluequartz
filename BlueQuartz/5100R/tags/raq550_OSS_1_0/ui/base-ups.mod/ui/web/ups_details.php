<?
// Author: Joshua Uziel
// Copyright 2001, Sun Microsystems, Inc.  All rights reserved.
// $Id: ups_details.php,v 1.7 2001/11/16 01:27:38 uzi Exp $

include("ServerScriptHelper.php");
include("base/am/am_detail.inc");

$helper = new ServerScriptHelper();
$cce = $helper->getCceClient();
$factory = $helper->getHtmlComponentFactory("base-ups");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());

am_detail_block($factory, $cce, "UPS", "[[base-ups.amUPSDetails]]");

// Get the sub block
$subblock = $factory->getPagedBlock(
	$i18n->interpolate("[[base-ups.amUPSStats]]"));

// Battery level
$HOST = `grep HOST= /etc/sysconfig/ups | sed -e 's/.*=//'`;
$HOST = chop($HOST);
$pct = `/usr/bin/upsc $HOST | grep BATTPCT | sed -e 's/.*: //' -e 's/\..*//' -e 's/^0*//'`;
$battpct = "[[base-ups.amUPSBattPct,val=$pct]]";
$subblock->addFormField(
	$factory->getTextField("battlevel", $i18n->interpolate($battpct), "r"),
	$factory->getLabel("amUPSBattLevel", true)
);

// Status
$status = `/usr/bin/upsc $HOST | grep STATUS | sed -e 's/.*: //'`;
$status = chop($status);

// Get into separate tokens of values, since there can be more than
// one at a time.
$mystatus = "";
$tok = strtok($status," ");
while ($tok) {
	$mystatus .= "[[base-ups.amUPSStatus$tok]] ";
	$tok = strtok (" ");
}

$subblock->addFormField(
	$factory->getTextField("status", $i18n->interpolate($mystatus), "r"),
	$factory->getLabel("amUPSStatus", true)
);

// print it if we were able to read the status
if ($status) {
	print("<br>");
	print($subblock->toHtml());
	// reload page every 60 seconds, so we have up to date battery stats
    print("<SCRIPT LANGUAGE=\"javascript\">	
       setTimeout(\"location.reload(true)\", 60000); </SCRIPT>");
}

am_back($factory);

print($page->toFooterHtml());

$helper->destructor();
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
