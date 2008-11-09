<?

// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.

include("ServerScriptHelper.php");
include("base/am/am_detail.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());

am_detail_block($factory, $cce, "Memory", "[[base-am.amMemDetails]]");

// get the sub block 
$subblock = $factory->getPagedBlock(
	$i18n->interpolate("[[base-am.amMemStats]]"));

// total mem
$mttl = `cat /proc/meminfo | grep MemTotal`;
$mttl = eregi_replace("[^0-9]*", "", $mttl);
$subblock->addFormField(
	$factory->getTextField("mttl", 
		$i18n->interpolate("[[base-am.MemKB,val=\"$mttl\"]]"), "r"),
	$factory->getLabel("amMemTotal")
);

// free mem
$mfree = `cat /proc/meminfo | grep MemFree`;
$mfree = eregi_replace("[^0-9]*", "", $mfree);
$subblock->addFormField(
	$factory->getTextField("mfree",
		$i18n->interpolate("[[base-am.MemKB,val=\"$mfree\"]]"), "r"),
	$factory->getLabel("amMemFree")
);

// used mem
$mused = $mttl - $mfree;
$mpcnt = round(($mused*100)/$mttl);
$subblock->addFormField(
	$factory->getTextField("mused", 
		$i18n->interpolate(
		    "[[base-am.amMemPcnt,used=\"$mused\",pcnt=\"$mpcnt\"]]"),
		"r"),
	$factory->getLabel("amMemUsed")
);
	
$sttl = `cat /proc/meminfo | grep SwapTotal`;
$sttl = eregi_replace("[^0-9]*", "", $sttl);
$subblock->addFormField(
	$factory->getTextField("sttl", 
		$i18n->interpolate("[[base-am.MemKB,val=\"$sttl\"]]"), "r"),
	$factory->getLabel("amSwapTotal")
);

$sfree = `cat /proc/meminfo | grep SwapFree`;
$sfree = eregi_replace("[^0-9]*", "", $sfree);
$subblock->addFormField(
	$factory->getTextField("sfree",
		$i18n->interpolate("[[base-am.MemKB,val=\"$sfree\"]]"), "r"),
	$factory->getLabel("amSwapFree")
);

/* FIXME: show Page operations/sec, too */

$sused = $sttl - $sfree;
$spcnt = round(($sused*100)/$sttl);
$subblock->addFormField(
	$factory->getTextField("sused", 
		$i18n->interpolate(
		    "[[base-am.amMemPcnt,used=\"$sused\",pcnt=\"$spcnt\"]]"),
		"r"),
	$factory->getLabel("amSwapUsed")
);
	
// print it 
print("<br>");
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

