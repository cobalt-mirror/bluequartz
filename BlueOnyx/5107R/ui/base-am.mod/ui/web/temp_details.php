<?php

// Author: James Cheng
// Copyright 2000, Sun Microsystems, Inc.  All rights reserved.

include_once("ServerScriptHelper.php");
include_once("base/am/am_detail.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
$page = $factory->getPage();
$i18n = $serverScriptHelper->getI18n();

print($page->toHeaderHtml());

am_detail_block($factory, $cce, "Temp", "[[base-am.amTempDetails]]");

$subblock = $factory->getPagedBlock("amTempStats");


$tempinfo = fopen("/proc/cobalt/sensors/thermal", "r");
if ($tempinfo) {
  while (!feof($tempinfo)) {
    $line = fgets($tempinfo, 1024);
    list ($sensorNum, $sensorName, $temperature) = preg_split ("/([[:space:]]|\.|:|\[|\])+/", $line);
    if ($sensorNum === false || !$temperature) {
      //need to do this check because there's an extra newline at end of file
      continue;
    }

    $subblock->addFormField(
			    $factory->getTextField("temp$sensorNum", 
						   $i18n->interpolate("[[base-am.amTemp]]", array( "temp" => $temperature)), "r"),
	  $factory->getLabel($i18n->interpolate("sensorName$sensorName")));
  }
} else {
  $subblock->addFormField($factory->getTextField('cant_get_info', $i18n->get('[[base-am.cant_get_tempinfo]]'), 'r'),
			  $factory->getLabel('amClientStatus'));
}
print("<BR>\n");
print($subblock->toHtml());

am_back($factory);
print ($page->toFooterHtml());


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
