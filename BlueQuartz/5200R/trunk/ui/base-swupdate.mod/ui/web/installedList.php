<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: installedList.php 1008 2007-06-25 15:23:03Z shibuya $

include_once("ServerScriptHelper.php");
include_once("base/swupdate/updateLib.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate");
$i18n = $serverScriptHelper->getI18n("base-swupdate");

$page = $factory->getPage();

// build the scroll list
$scrollList = $factory->getScrollList("installedList", array("nameField", "versionField", "vendorField", "descriptionField", "uninstall"), array(0, 1, 2));
$scrollList->setAlignments(array("left", "left", "left", "left", "center"));
$scrollList->setWidth(600);
$scrollList->setColumnWidths(array("15%", "20%", "10%", "60%", ""));

$scrollList->processErrors($serverScriptHelper->getErrors());

// let cce do all of the sorting for us
// $scrollList->setSortEnabled(false);

// sort key
$sortKeyMap = array(0 => "name", 1 => "version", 2 => "vendor");
$key = $sortKeyMap[$scrollList->getSortedIndex()];

$pageLength = $scrollList->getLength();
$start = $scrollList->getPageIndex()*$pageLength;
$search = array('installState' => 'Installed');

if ($key == 'version')
	$oids = $cceClient->findNSorted("Package", 'version', $search);
else 
	$oids = $cceClient->findSorted("Package", $key, $search);

if ($scrollList->getSortOrder() == "descending") 
	$oids = array_reverse($oids);
$scrollList->setEntryNum(count($oids));

for($i = 0; $i < count($oids); $i++) {
  $package = $cceClient->get($oids[$i]);

  $packageName = $package["nameTag"] ? $i18n->interpolate($package["nameTag"]) : $package["name"];
  $version = $package["versionTag"] ? $i18n->interpolate($package["versionTag"]) : substr($package["version"], 1);
  $vendorName = $package["vendorTag"] ? $i18n->interpolate($package["vendorTag"]) : $package["vendor"];
  $description = $i18n->interpolate($package["shortDesc"]);
  $uninstallable = strstr($package['options'], 'uninstallable');
  $oid = &$oids[$i];

  // disable button if not installable
  //escape for javascript. ugly, yes, but it works.
  $escName=$i18n->interpolateJs("[[VAR.foo,foo=\"$packageName\"]]");
  $button = $factory->getUninstallButton("javascript: uninstall('$oid', '$escName')");
  if (!$uninstallable)
    $button->setDisabled("true");

  $scrollList->addEntry(array(
    $factory->getTextField("", "$packageName", "r"),
    $factory->getTextField("", $version, "r"),
    $factory->getTextField("", $vendorName, "r"),
    $factory->getTextField("", $description, "r"),
    $button
  ));
}

print($page->toHeaderHtml());

$hasUpdates = updates_check($cceClient);	
if ($hasUpdates == "true")
    print(updates_getJS($hasUpdates));

$serverScriptHelper->destructor();
?>
<SCRIPT LANGUAGE="javascript">
function uninstall(oid, packageName) {
  var message = "<?php print($i18n->get("uninstallConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.packageName]]", packageName);

  if(confirm(message))
    location="/base/swupdate/uninstallHandler.php?nameField="+escape(packageName)+"&packageOID="+oid;
}
</SCRIPT>

<?php print($scrollList->toHtml()); ?>
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
