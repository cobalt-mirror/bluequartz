<?

// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.

include("ServerScriptHelper.php");
include("base/am/am_detail.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
$page = $factory->getPage();
$i18n = $serverScriptHelper->getI18n();

print($page->toHeaderHtml());

am_detail_block($factory, $cce, "CPU", "[[base-am.amCPUDetails]]");

// get the sub block 
$subblock = $factory->getPagedBlock(
	$i18n->interpolate("[[base-am.amCPUStats]]"));

// load average
$loadavg1 = `cat /proc/loadavg  | cut -d' ' -f1`;
$loadavg1 = chop($loadavg1);
$subblock->addFormField(
	$factory->getNumber("loadavg1Field", $loadavg1, 0, 0, "r"),
	$factory->getLabel("amCPULoadAvg", true, 
		array("mins" => "1"), 
		array("mins" => "1"))
);

$loadavg15 = `cat /proc/loadavg  | cut -d' ' -f3`;
$loadavg15 = chop($loadavg15);
$subblock->addFormField(
	$factory->getNumber("loadavg15Field", $loadavg15, 0, 0, "r"),
	$factory->getLabel("amCPULoadAvgFifteen", true, 
		array("mins" => "15"), 
		array("mins" => "15"))
);

// uptime
$upsecs = floor(`cat /proc/uptime | cut -d' ' -f1`);
$upmins = floor($upsecs/60);
$uphrs = floor($upmins/60);
$upmins %= 60;
$updays = floor($uphrs/24);
$uphrs %= 24;

$up = "";
if ($updays == 1) {
	$up .= "[[base-am.day,val=$updays]] ";
} else if ($updays > 1) {
	$up .= "[[base-am.days,val=$updays]] ";
}

if ($uphrs == 1) {
	$up .= "[[base-am.hour,val=$uphrs]] ";
} else if ($uphrs > 1) {
	$up .= "[[base-am.hours,val=$uphrs]] ";
}

if ($upmins == 1) {
	$up .= "[[base-am.minute,val=$upmins]]";
} else {
	$up .= "[[base-am.minutes,val=$upmins]]";
}
$subblock->addFormField(
	$factory->getTextField("upField", $i18n->interpolate($up), "r"),
	$factory->getLabel("amCPUUptime", true)
);

// cpu temperature
$temp = `cat /proc/cpuinfo | grep temperature`;
$temp = eregi_replace("[^0-9\.]*", "", $temp);
$itemp = $i18n->interpolate("[[base-am.degrees,val=\"$temp\"]]");
$subblock->addFormField(
	$factory->getTextField("tempField", $itemp, "r"),
	$factory->getLabel("amCPUTemp", true)
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

