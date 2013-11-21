<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: status.php,v 1.1 2001/06/06 21:13:36 will Exp $

include_once("ServerScriptHelper.php");
include_once("uifc/PagedBlock.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-java");
$i18n = $serverScriptHelper->getI18n("base-java");

// reset progress status/messages
$oids = $cceClient->find('Vsite', array('name' => $group));
if ($oids[0] == '') {
  exit();
}
$jsite = $cceClient->get($oids[0], 'Java');
$jsite["progress"] = round( $jsite["progress"] );
$cmd = &$jsite["uiCMD"];

// check to see if we need to redirect to the download page
if (preg_match("/success/i", $cmd, $reg)) {
	$oid = &$reg[1];
	// Allow the servlet list to identify the installed war
	header("location: /base/java/warList.php?group=$group&warOID=$oid&backUrl=$backbackUrl");
	$cceClient->set($oids[0], array("uiCMD" => '', 'progress' => '100'), "Java");
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
	$cceClient->set($oids[0], array("uiCMD" => '', 'progress' => '100'), "Java");
	exit;
}


// get message
$message = $i18n->interpolate($jsite["message"]);

$page = $factory->getPage();

$block = new PagedBlock($page, "installStatus", $factory->getLabel("installStatus", 
	false, array("fileName" => $nameField)));

$block->addFormField(
	$factory->getTextField("statusField", $message, "r"),
	$factory->getLabel("statusField")
);

$block->addFormField(
	$factory->getBar("progressField", $jsite["progress"]),
	$factory->getLabel("progressField")
);

// we need to propagate back the URL.
$block->addFormField(
	$factory->getTextField("backbackUrl", $backbackUrl, ""),
	""
);

$block->addButton($factory->getBackButton("$backUrl&backUrl=$backbackUrl"));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
<?php print($block->toHtml()); ?>

<?php
// reload if not completed
if($jsite["progress"] < 100)
  print("<SCRIPT LANGUAGE=\"javascript\">setTimeout(\"location.reload()\", 3000);</SCRIPT>");
?>

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
