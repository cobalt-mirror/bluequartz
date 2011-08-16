<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: license.php 1136 2008-06-05 01:48:04Z mstauber $

include_once("ServerScriptHelper.php");
include_once("base/swupdate/updateLib.php");
include_once('Error.php');

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$action = "/base/swupdate/downloadHandler.php";

// if cce is suspended, don't bother going any further
if ($cceClient->suspended() !== false)
{
	$msg = $cceClient->suspended() ? $cceClient->suspended() : '[[base-cce.suspended]]';
	print $serverScriptHelper->toHandlerHtml($backUrl, array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}

$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", 
							$action);
$i18n = $factory->getI18n();

// get objects
$package = $cceClient->get($packageOID);
$license = $package["licenseDesc"];
$splash = strstr($package["splashPages"], 'pre-install');

// redirect if we don't have license info. 
$location = "/base/swupdate/downloadHandler.php?packageOID=$packageOID&backUrl=$backUrl";
if (!($license || $splash)) {
	header("location: $location\n\n");
	exit;
}

// we got a splash page. 
if ($splash) {
	$splashdir = updates_splashdir();
	$stage = 'pre-install';
	$name = updates_splashname($package["vendor"], $package["name"],
				   $package["version"], $stage);
	if (file_exists("$splashdir/$name") && $dhandle = opendir("$splashdir/$name")) {
		while ($file = readdir($dhandle)) {
	    	if (strstr($file, 'index.')) {
				$submit = urlencode($location);
				header("location: /$name/?submitURL=$submit&cancelURL=$backUrl\n\n");
				// echo " <ul><li>$name <li>$submit <li> $backUrl </ul>\n";
				exit;
	  		}
		}
		closedir($dhandle);
	}

	if (!$license) {
		header("location: $location\n\n");
		exit;
	}
}

$page = $factory->getPage();

// otherwise, we generate a standard license page

$block0 = $factory->getPagedBlock("licenseField");
$block = $factory->getPagedBlock('');
$block->addFormField($factory->getTextField("nameField", $nameField, ""));
$block->addFormField($factory->getTextField("backUrl", $backUrl, ""));
$block->addFormField($factory->getTextField("packageOID", $packageOID, ""));
$block->addButton($factory->getButton($page->getSubmitAction(), "accept"));
$block->addButton($factory->getButton($backUrl, "decline"));

$stage = 'pre-install';
updates_prependsrc($license, $package['vendor'], $package['name'], 
	$package['version'], $stage);

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
<SCRIPT LANGUAGE="javascript">
top.code.info_show('');
</SCRIPT>
<?php print($block0->toHtml()); ?>
<TABLE WIDTH="550" CELLPADDING=0 CELLSPACING=4 BORDER=0>
<TR VALIGN="TOP" ALIGN=LEFT><TD>
<?php print($i18n->interpolate($license)); ?>
</TD></TR>
</TABLE>
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
