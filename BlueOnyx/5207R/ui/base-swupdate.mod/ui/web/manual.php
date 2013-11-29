<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: manual.php 1136 2008-06-05 01:48:04Z mstauber $

include_once("ServerScriptHelper.php");
include_once('Error.php');

// Adds settings to avoid changing php.ini
ini_set('memory_limit', '128M');
ini_set('post_max_size ', '100M');
ini_set('upload_max_filesize', '100M');
ini_set('max_execution_time', '0');  
ini_set('max_input_time', '0');  

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-swupdate", "/base/swupdate/manualHandler.php");
$i18n = $serverScriptHelper->getI18n("base-swupdate");

// check to see if cce is suspended, because it doesn't make any sense
// to let them continue when cce is locked
if ($cceClient->suspended() !== false)
{
	$msg = $cceClient->suspended() ? $cceClient->suspended() : '[[base-cce.suspended]]';
	print $serverScriptHelper->toHandlerHtml($backUrl, array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}

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
	$serverScriptHelper->shell("$magic_cmd $dirName/$file", $output, 'root');
	if (preg_match("/(tar|compressed|PGP\s+armored|\sdata$)/", $output)) {
		$packages[] = $file;
	}
  }
  closedir($dir);
}

$page = $factory->getPage();

$block = $factory->getPagedBlock("manualInstall");
$block->processErrors($serverScriptHelper->getErrors());

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
print($page->toHeaderHtml()); 

// 3rd party software warning:
$thirdparty = $factory->getPagedBlock("warning_header", array("Default"));
$thirdparty->processErrors($serverScriptHelper->getErrors());

$warning = $i18n->get("3rdpartypkg_warning");
$thirdparty->addFormField(
    $factory->getTextList("_", $warning, 'r'),
    $factory->getLabel(" "),
    "Default"
    );
print($thirdparty->toHtml());
print($block->toHtml());
print($page->toFooterHtml());
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
