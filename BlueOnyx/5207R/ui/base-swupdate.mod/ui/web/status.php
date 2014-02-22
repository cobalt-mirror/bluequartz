<?php
// Author: Kevin K.M. Chiu
// $Id: status.php

include_once("ServerScriptHelper.php");
include_once("uifc/PagedBlock.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate");
$i18n = $serverScriptHelper->getI18n("base-swupdate");

$swupdate = $cceClient->getObject("System", array(), "SWUpdate");
$swupdate["progress"] = round( $swupdate["progress"] );
$cmd = &$swupdate["uiCMD"];

// check to see if we need to redirect to the download page
if (preg_match("/packageOID=([0-9]+)/i", $cmd, $reg)) {
	$oid = &$reg[1];
header("location: /base/swupdate/download.php?packageOID=$oid&backUrl=$backbackUrl");
	$cceClient->setObject("System", array("uiCMD" => '', 'progress' => '100'), "SWUpdate");
	exit;
} else if (strstr($cmd, 'reboot')) {
	$page = $factory->getPage();
	print($page->toHeaderHtml());
	print $i18n->get("rebooting");
	print($page->toFooterHtml());
	$cceClient->setObject("System", array("uiCMD" => '', 'progress' => '100'), "SWUpdate");
	exit;
} else if (strstr($cmd, 'refresh')) {
	$page = $factory->getPage();
	print($page->toHeaderHtml());
	if (strstr($cmd, 'uninstall'))
		print($i18n->get("uninstallrefresh"));
	else if (strstr($cmd, 'install'))
		print($i18n->get("installrefresh"));
	print($page->toFooterHtml());
print('<SCRIPT LANGUAGE="javascript">setTimeout("top.location.reload();", 7000);</SCRIPT>');	
	$cceClient->setObject("System", array("uiCMD" => '', 'progress' => '100'), "SWUpdate");
	exit;
}


// get message
$message = $i18n->interpolate($swupdate["message"]);

$page = $factory->getPage();

$block = new PagedBlock($page, "installStatus", $factory->getLabel("installStatus", false, array("fileName" => $nameField)));

$block->addFormField(
  $factory->getTextField("statusField", $message, "r"),
  $factory->getLabel("statusField")
);

$block->addFormField(
  $factory->getBar("progressField", $swupdate["progress"]),
  $factory->getLabel("progressField")
);

// we need to propagate back the URL.
$block->addFormField(
	$factory->getTextField("backbackUrl", $backbackUrl, ""),
	""
);

$block->addButton($factory->getBackButton("$backUrl?backUrl=$backbackUrl"));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
<?php print($block->toHtml()); ?>

<?php
// reload if not completed
if($swupdate["progress"] < 100)
  print("<SCRIPT LANGUAGE=\"javascript\">setTimeout(\"location.reload()\", 3000);</SCRIPT>");
?>

<?php print($page->toFooterHtml());
/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
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