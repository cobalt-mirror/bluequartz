<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: download.php 1136 2008-06-05 01:48:04Z mstauber $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/base/swupdate/license.php");
$i18n = $factory->getI18n();

// get objects
$package = $cceClient->get($packageOID);

// bring up a javascript pop-up if the reboot option is set
$javascript = '';
if (strstr($package['options'], 'reboot')) {
	$string = $i18n->interpolateJS('[[base-swupdate.rebootpopup]]');
	$javascript = "<SCRIPT LANGUAGE=\"javascript\">
	function trysubmit() {
		var answer;
		if (answer = confirm(\"$string\")) {
			location.replace(\"/base/swupdate/license.php?packageOID=$packageOID&backUrl=$backUrl\");
		}
	}
	</SCRIPT>";
}

$page = $factory->getPage();

$block = $factory->getPagedBlock("downloadSoftware");
$name = $package["nameTag"] ? $i18n->interpolate($package["nameTag"]) : $package["name"];

 $packageName = $package["nameTag"] ? $i18n->interpolate($package["nameTag"]) : $package["name"];
 $vendorName = $package["vendorTag"] ? $i18n->interpolate($package["vendorTag"]) : $package["vendor"];


$block->addFormField(
  $factory->getTextField("nameField", $packageName, "r"),
  $factory->getLabel("nameField")
);

$version = $package["versionTag"] ? $i18n->interpolate($package["versionTag"]) : substr($package["version"], 1);
$block->addFormField(
  $factory->getTextField("versionField", $version, "r"),
  $factory->getLabel("versionField")
);

$name = $package["vendorTag"] ? $i18n->interpolate($package["vendorTag"]) : $package["vendor"];

$block->addFormField(
  $factory->getTextField("vendorField", $name, "r"),
  $factory->getLabel("vendorField")
);


if ($package["copyright"]) {
	$block->addFormField(
  		$factory->getTextField("copyrightField", 
			$i18n->interpolate($package["copyright"]), "r"),
  		$factory->getLabel("copyrightField")
	);
}

$desc = $package['longDesc'] ? $package['longDesc'] : $package['shortDesc'];
$block->addFormField(
  $factory->getTextField("descriptionField", 
	$i18n->interpolate($desc), "r"),
  $factory->getLabel("descriptionField")
);

$location = preg_match('/^file:/', $package['location']) ? $i18n->interpolate('[[base-swupdate.locationLocal]]') : $package['location'];
$block->addFormField(
  $factory->getTextField("locationField", $location, "r"),
  $factory->getLabel("locationField")
);

$size = $package["size"] ? sprintf("%.3f", $package["size"] / (1024*1024)) : 
	$i18n->interpolate('[[base-swupdate.unknownSize]]');
$block->addFormField(
  $factory->getNumber("sizeField", $size, 0, 0, "r"),
  $factory->getLabel("sizeField")
);

if (strstr($package['options'], 'uninstallable')) {
  $uninst = "yes";
}
else {
  $uninst = "no";
}

$block->addFormField(
  $factory->getTextField("uninstallableField", $i18n->get($uninst), "r"),
  $factory->getLabel("uninstallableField")
);

$dependency = stringToArray($package["visibleList"]);
if($dependency) {
	$needed = join(', ', $dependency);
	$needed = str_replace(':', ' ', $needed);
} else {
	$needed = $i18n->get('none');
}

$block->addFormField(
    $factory->getTextField("packagesNeededField", $needed, "r"),
    $factory->getLabel("packagesNeededField")
);

$block->addFormField($factory->getTextField("backUrl", $backUrl, ""));
$block->addFormField($factory->getTextField("packageOID", $packageOID, ""));
$action = $javascript ? "javascript: trysubmit();" : $page->getSubmitAction();
$block->addButton($factory->getButton($action, "install"));
$block->addButton($factory->getCancelButton($backUrl));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
<?php print($javascript); ?>
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
