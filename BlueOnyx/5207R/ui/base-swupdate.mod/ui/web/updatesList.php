<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: updatesList.php 1136 2008-06-05 01:48:04Z mstauber $

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

$manualInstallButton = $factory->getButton("/base/swupdate/manual.php?backUrl=/base/swupdate/updatesList.php", "manualInstallUpdate");
$checkNowButton = $factory->getButton("/base/swupdate/checkHandler.php?backUrl=/base/swupdate/updatesList.php", "checkNowUpdate");

// build the scroll list
$scrollList = $factory->getScrollList("availableListUpdates", array("statusField", "nameField", "versionField", "vendorField", "descriptionField", "installField"), array(0, 1, 2, 3));
$scrollList->setAlignments(array("center", "left", "left", "left", "left", "center"));
$scrollList->setColumnWidths(array("", "25%", "25%", "25%", "45%", "5%"));

$scrollList->processErrors($serverScriptHelper->getErrors());

// let cce do all of the sorting for us
// $scrollList->setSortEnabled(false);

// sort key
$sortKeyMap = array(0 => "new", 1 => "name", 2 => "version", 3 => "vendor");
$key = $sortKeyMap[$scrollList->getSortedIndex()];

$pageLength = $scrollList->getLength();
$start = $scrollList->getPageIndex()*$pageLength;
$search = array('installState' => 'Available', 'packageType' => 'update', 'isVisible' => 1);

if ($key == 'version') 
	$oids = $cceClient->findNSorted("Package", 'version', $search);
else 
	$oids = $cceClient->findSorted("Package", $key, $search);

$order = $scrollList->getSortOrder();
if ((($order == "descending") && ($key != 'new')) ||
    (($order == "ascending") && ($key == 'new')))
	$oids = array_reverse($oids);

for($i = 0; $i < count($oids); $i++) {
  $package = $cceClient->get($oids[$i]);
  //cobalt packages get displayed in the special cobalt tab.  see sunUpdatesList.php
  if(preg_match("/(?:cobalt|sun)/i",$package["vendor"])){
	continue;
  }
  $new = $package["new"] ? "new" : "old";
  $packageName = $package["nameTag"] ? $i18n->interpolate($package["nameTag"]) : $package["name"];
  $version = $package["versionTag"] ? $i18n->interpolate($package["versionTag"]) : substr($package["version"], 1);
  $vendorName = $package["vendorTag"] ? $i18n->interpolate($package["vendorTag"]) : $package["vendor"];
  $description = $i18n->interpolate($package["shortDesc"]);
  $url = $package["url"];
  $options = updates_geturloptions($cceClient, $package["urloptions"]);

  $packageType = $i18n->get($package["packageType"]);
  $oid = &$oids[$i];
  $system = $cceClient->getObject("System", array(), "");
  $sn = &$system["serialNumber"];

  $detailUrl = ($url != "") ? "javascript: window.open('$url$options', 'softwareDetails'); void 0;" : "javascript: location='/base/swupdate/download.php?backUrl=/base/swupdate/updatesList.php&packageOID=$oid'; void 0;";

  $removeButton = preg_match("/^file:/", $package["location"]) ? "javascript: confirmRemove('$oid', '$packageName');" : '';

  $composite = $removeButton ? array($factory->getDetailButton($detailUrl),
        $factory->getRemoveButton($removeButton)) : array($factory->getDetailButton($detailUrl));

  $scrollList->addEntry(array(
    $factory->getStatusSignal($new),
    $factory->getTextField("", $packageName, "r"),
    $factory->getTextField("", $version, "r"),
    $factory->getTextField("", $vendorName, "r"),
    $factory->getTextField("", $description, "r"),
    $factory->getCompositeFormField($composite)
  ));

  # after the packages are seen, they are not new anymore
  $cceClient->set($oids[$i], "", array("new" => 0));
}

system("/bin/touch /tmp/.guipkginstall");

print($page->toHeaderHtml());

$hasUpdates = updates_check($cceClient);	
if ($hasUpdates == "false") 
   print(updates_getJS($hasUpdates));

$serverScriptHelper->destructor();
?>
<SCRIPT LANGUAGE="javascript">
function confirmRemove(oid, name) {
  var message = "<?php print($i18n->get("removePackage"))?>";
  message = top.code.string_substitute(message, "[[VAR.packageName]]", name);
  if(confirm(message))
    location = "/base/swupdate/removeHandler.php?backUrl=/base/swupdate/updatesList.php&packageOID=" + oid;
}
</SCRIPT>

<br>
<TABLE>
  <TR>
    <TD>
<?php print($checkNowButton->toHtml()); ?>
    </TD>
    <TD>
<?php print($manualInstallButton->toHtml()); ?>
    </TD>
  </TR>
</TABLE>
<BR>

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
