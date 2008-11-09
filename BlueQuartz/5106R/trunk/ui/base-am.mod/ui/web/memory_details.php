<?php

// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: memory_details.php 1156 2008-06-10 14:52:07Z mstauber $
//
// Updates for 2.6 kernel
// Copyright 2005 Osser Brosoft AB, Rickard Osser <ricky@osser.se>
//

include_once("ServerScriptHelper.php");
include_once("base/am/am_detail.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
$page = $factory->getPage();
$i18n = $serverScriptHelper->getI18n();

print($page->toHeaderHtml());

am_detail_block($factory, $cce, "Memory", "[[base-am.amMemDetails]]");

$kernelver = `uname -r`;
if (ereg('^2.4', $kernelver)) {
    $kernel = 2.4;
} elseif (ereg('^2.6', $kernelver)) {
    $kernel = 2.6;
}
$mttl = `cat /proc/meminfo | grep MemTotal`;
$mfree = `cat /proc/meminfo | grep MemFree`;
$sttl = `cat /proc/meminfo | grep SwapTotal`;
$sfree = `cat /proc/meminfo | grep SwapFree`;
$uptime = `cat /proc/uptime`;

switch($kernel) {
case 2.4:
    $mempages = `cat /proc/stat | grep page`;
    $memswaps = `cat /proc/stat | grep swap`;
    break;
case 2.6:
    $mempages = `cat /proc/vmstat | grep pgpgin` . `cat /proc/vmstat | grep pgpgout`;
    $memswaps = `cat /proc/swaps | grep partition`;
    break;
}

if (!strlen($mttl) || !strlen($mfree) || !strlen($sttl) || 
    !strlen($sfree) || !strlen($mempages) || 
    !strlen($memswaps) || !strlen($uptime)) {

	$block = $factory->getSimpleBlock("[[base-am.amMemStats]]");
	$block->addHtmlComponent($factory->getTextField("noinfo", $i18n->interpolate("[[base-am.no_mem_stats]]"), "r"));

	print("<br>");

	// PHP5:
    	$block->addFormField(
        	$factory->getTextField("debug_1", "", 'r'),
        	$factory->getLabel("debug_1"),
        	"Hidden"
    	);

	print $block->toHtml();

	am_back($factory);
	print($page->toFooterHtml());
	$serverScriptHelper->destructor();
	exit();
}

// get the sub block 
$subblock = $factory->getPagedBlock(
	$i18n->interpolate("[[base-am.amMemStats]]"));

// total mem
$mttl = eregi_replace("[^0-9]*", "", $mttl);
$subblock->addFormField(
	$factory->getTextField("mttlField", 
		$i18n->interpolate("[[base-am.MemKB,val=\"$mttl\"]]"), "r"),
	$factory->getLabel("amMemTotal")
);

// free mem
$mfree = eregi_replace("[^0-9]*", "", $mfree);
$subblock->addFormField(
	$factory->getTextField("mfreeField",
		$i18n->interpolate("[[base-am.MemKB,val=\"$mfree\"]]"), "r"),
	$factory->getLabel("amMemFree")
);

// used mem
$mused = $mttl - $mfree;
$mpcnt = round(($mused*100)/$mttl);
$subblock->addFormField(
	$factory->getTextField("musedField", 
		$i18n->interpolate(
		    "[[base-am.amMemPcnt,used=\"$mused\",pcnt=\"$mpcnt\"]]"),
		"r"),
	$factory->getLabel("amMemUsed")
);
	
$sttl = eregi_replace("[^0-9]*", "", $sttl);
$subblock->addFormField(
	$factory->getTextField("sttlField", 
		$i18n->interpolate("[[base-am.MemKB,val=\"$sttl\"]]"), "r"),
	$factory->getLabel("amSwapTotal")
);

$sfree = eregi_replace("[^0-9]*", "", $sfree);
$subblock->addFormField(
	$factory->getTextField("sfreeField",
		$i18n->interpolate("[[base-am.MemKB,val=\"$sfree\"]]"), "r"),
	$factory->getLabel("amSwapFree")
);

switch($kernel){
case 2.4:
    list($tmp, $pagesin, $pagesout) = split("[[:space:]]+", $mempages);
    list($tmp, $swapin, $swapout) = split("[[:space:]]+", $memswaps);
    break;
case 2.6:
    list($tmp, $pagesin, $tmp, $pagesout) = split("[[:space:]]+", $mempages);
    list($tmp, $tmp, $size, $swaps) = split("[[:space:]]+", $memswaps);
    break;
}

list($uptime, $tmp) = split("[[:space:]]+", $uptime);

$pages = round(($pagesin + $pagesout) / $uptime);
$string = $i18n->interpolate("[[base-am.amPagesSec,pages=\"$pages\"]]");
$subblock->addFormField($factory->getTextField("pagessec", $string, "r"),
	$factory->getLabel("pagesPerSec"));
switch($kernel){
case 2.4:
    $swaps  = round(($swapin + $swapout) / $uptime);
    break;
case 2.6:
    $swaps  = round($swaps / $uptime);
    break;
}

$string = $i18n->interpolate("[[base-am.amSwapsSec,swaps=\"$swaps\"]]");
$subblock->addFormField($factory->getTextField("swapssec", $string, "r"),
	$factory->getLabel("swapsPerSec"));

$sused = $sttl - $sfree;
if ($sttl > 0) {
    $spcnt = round(($sused*100)/$sttl);
} else {
    $spcnt = 0;
}
$subblock->addFormField(
	$factory->getTextField("susedField", 
		$i18n->interpolate(
		    "[[base-am.amMemPcnt,used=\"$sused\",pcnt=\"$spcnt\"]]"),
		"r"),
	$factory->getLabel("amSwapUsed")
);
	
// print it 
print("<br>");
// PHP5:
$subblock->addFormField(
    $factory->getTextField("debug_1", "", 'r'),
    $factory->getLabel("debug_1"),
    "Hidden"
);

print($subblock->toHtml());

am_back($factory);

print($page->toFooterHtml());

$serverScriptHelper->destructor();
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
