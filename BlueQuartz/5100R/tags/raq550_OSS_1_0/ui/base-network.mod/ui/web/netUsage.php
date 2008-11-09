<?

// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.

include("ServerScriptHelper.php");
include("uifc/ScrollList.php");
include("uifc/Label.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-network");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());

$devlist = $factory->getScrollList("amNetUsage", array("amIface", "amSentB", "amRcvdB", "amErrors", "amColls"), array(0, 1, 2, 3, 4));
$devlist->setColumnWidths(array(0, 75, 75, 75, 75));

$ifFile = fopen("/proc/net/dev", "r");
$line = fgets($ifFile, 256);  // waste headers
$line = fgets($ifFile, 256);  // more headers
$line = fgets($ifFile, 256);
while (!feof($ifFile))
{
	$fields = split('[: ]+', $line);
	$if = $fields[1];
	if ($if == "lo")
	{
	  $line = fgets($ifFile, 256);
          continue;
	}
	$recvb = $fields[2];
	$sentb = $fields[10];
	$errs = $fields[4] + $fields[12];
	$colls = $fields[15];

	$devlist->addEntry(array(
		$factory->getLabel($if, false),
		$factory->getTextField("sentb", $sentb, "r"),
		$factory->getTextField("recvb", $recvb, "r"),
		$factory->getTextField("errs", $errs, "r"),
		$factory->getTextField("colls", $colls, "r")
	));
	$line = fgets($ifFile, 256);
}
fclose($ifFile);

print($devlist->toHtml());

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
