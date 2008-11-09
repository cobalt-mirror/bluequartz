<?php
// Copyright 2001, Sun Microsystems, Inc.
// $Id: warAdd.php,v 1.8.2.2 2002/04/04 21:27:43 naroori Exp $

include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-java", "/base/java/warAddHandler.php?group=$group&_TARGET=$_TARGET");
$i18n = $factory->i18n;

// don't bother if cce is suspended
if ($cceClient->suspended() !== false)
{
	$msg = $cceClient->suspended() ? $cceClient->suspended() : '[[base-cce.suspended]]';
	print $serverScriptHelper->toHandlerHtml(
		"/base/java/warList.php?group=$group", array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}

// dont allow the war deployment if java is not enabled
list($myvsite) = $cceClient->find('Vsite', array('name' => $group));
$myVsiteJavaObj = $cceClient->get($myvsite, "Java");
if ($myVsiteJavaObj["enabled"] == "0")
{
	$msg =  '[[base-java.javaNotEnabled]]';
	print $serverScriptHelper->toHandlerHtml(
		"/base/java/warList.php?group=$group", array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}

$errors = $serverScriptHelper->getErrors();

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();



if($_TARGET) 
{
	$add = 0;
	$title = 'modWar';
	$war = $cceClient->get($_TARGET);
	$item = $war['name'];
} 
else
{
	$add = 1;
	$title = 'addWar';
	list($vsite) = $cceClient->find('Vsite', array('name' => $group));
	$vsiteObj = $cceClient->get($vsite);
	$item = $vsiteObj['fqdn'];
}


// Locate .war archives
// FIXME: don't display loaded/running apps
if($group)
{
        $dirName = "$vsiteObj[basedir]/web";
}

$block = $factory->getPagedBlock($title);
$block->setLabel($factory->getLabel($title, false, array('item' => $item)));

if(count($errors) > 0)
{
	$block->process_errors($errors);
}

// Lookup the current site for the fqdn, used in the form/help text
$vsite = $cceClient->getObject('Vsite', array('name' => $group));

if($add)
{
	$location = $factory->getMultiChoice("locationField");
	$url = $factory->getOption("url", true);
	$url->addFormField($factory->getUrl("urlField"));
	$location->addOption($url);
	$upload = $factory->getOption("upload");
	$upload->addFormField($factory->getFileUpload("fileField"));
	$location->addOption($upload);

	// add .war's as an option if they exist
	$magic_cmd = "/usr/bin/file";
	$wars = array();
	if(is_dir($dirName)) {
	        $dir = opendir($dirName);
	        while($file = readdir($dir)) {
	                if ($file[0] == '.')
	                continue;

	                // $serverScriptHelper->shell("$magic_cmd $dirName/$file", $output);
			$output = `$magic_cmd $dirName/$file 2>&1`;
			// $output = system("$magic_cmd $dirName/$file");
	                if (ereg("Zip archive data", $output)) {
	                        $wars[] = $file;
	                }
	        }
	        closedir($dir);
	}
	if(count($wars) > 0) {
	  $loaded = $factory->getOption("loaded");
	  $loaded->addFormField($factory->getMultiChoice("loadedField", $wars));
	  $location->addOption($loaded);
	}

	$block->addFormField(
	  $location,
	  $factory->getLabel("locationFieldEnter")
	);

	$target = $factory->getCompositeFormField(array(
		$factory->getTextField('siteurl', 'http://'.$vsite['fqdn'].'/', 'r'),
		$factory->getTextField('targetField', $war['name'], 'rw')
		));
	$block->addFormField(
		$target,
		$factory->getLabel("targetName")
		);
}
else
{
	$block->addFormField(
		$factory->getTextField("name", $war["name"], 'r'),
		$factory->getLabel("warName")
		);
	$block->addFormField(
		$factory->getTextField("target", $war['name'], 'r'),
		$factory->getLabel("targetName")
		);
}

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/java/warList.php?group=$group&user=$user"));

$serverScriptHelper->destructor();

print($page->toHeaderHtml());
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
