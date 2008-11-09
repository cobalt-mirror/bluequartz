<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: manual.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/base/swupdate/manualHandler.php");

// get all loaded packages. we'll match anything that's a tar
// file
$dirName = "/home/packages";
$magic_cmd = "/usr/bin/file";
$packages = array();
if(is_dir($dirName)) {
  $dir = opendir($dirName);
  while($file = readdir($dir)) {
	if ($file[0] == '.')
		continue;
	$output = "";
	$serverScriptHelper->shell("$magic_cmd $dirName/$file", $output);
	if (preg_match("(tar|compressed|PGP\s+armored|\sdata$)", $output)) {
		$packages[] = $file;
	}
  }
  closedir($dir);
}

$page = $factory->getPage();

$block = $factory->getPagedBlock("manualInstall");

$location = $factory->getMultiChoice("locationField");
$url = $factory->getOption("url", true);
$url->addFormField($factory->getUrl("urlField"));
$location->addOption($url);
$upload = $factory->getOption("upload");
$upload->addFormField($factory->getFileUpload("fileField"));
$location->addOption($upload);

// add packages as an option if there are packages
if(count($packages) > 0) {
  $loaded = $factory->getOption("loaded");
  $loaded->addFormField($factory->getMultiChoice("loadedField", $packages));
  $location->addOption($loaded);
}

$block->addFormField(
  $location,
  $factory->getLabel("locationFieldEnter")
);

$block->addFormField($factory->getTextField("backUrl", $backUrl, ""));
$block->addButton($factory->getButton($page->getSubmitAction(), "prepare"));
$block->addButton($factory->getCancelButton($backUrl));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
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

