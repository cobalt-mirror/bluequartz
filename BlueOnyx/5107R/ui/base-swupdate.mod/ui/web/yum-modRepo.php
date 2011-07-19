<?php
// Author: Rickard Osser
// Copyright 2011, Bluapp AB.  All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-yum", "/base/swupdate/yum-modRepoHandler.php");

$page = $factory->getPage();
if( $oid) {
  $repoObj = $cceClient->get($oid);
  if($repoObj["systemRepo"]) {
    $block = $factory->getPagedBlock("viewRepo");
  } else {
    $block = $factory->getPagedBlock("modRepo");
  }
 } else {
  $block = $factory->getPagedBlock("addRepo");
 }

if($repoObj["systemRepo"]) {
  $writeable = "r";
 } else {
  $writeable = "rw";
 }

$block->processErrors($serverScriptHelper->getErrors());

$block->addFormField(
		     $factory->getTextField("oid", $oid, ""),
		     $factory->getLabel("oid")
		     );

$block->addFormField(
		     $factory->getBoolean("enabled", $repoObj["enabled"], $writeable),
		     $factory->getLabel("enabledField")
);

$repoNameField = $factory->getTextField("repoName", $repoObj["repoName"], $writeable);
$repoNameField->setSize(50);
$block->addFormField(
		    $repoNameField,
		     $factory->getLabel("repoNameField")
		     );
$descriptionField = $factory->getTextField("description", $repoObj["name"], $writeable);
$descriptionField->setSize(50);
$block->addFormField(
		     $descriptionField,
		     $factory->getLabel("descriptionField")
		     );

if(!$writeable) { 
  $baseurlField = $factory->getUrl("baseurl", $repoObj["baseurl"]);
 } else {
  $baseurlField = $factory->getTextField("baseurl", $repoObj["baseurl"], $writeable);
 }
$baseurlField->setOptional(true);
$baseurlField->setSize(50);
$block->addFormField(
		     $baseurlField,
		     $factory->getLabel("baseurlField")
		     );

if(!$writeable) {
  $mirrorlistField = $factory->getUrl("mirrorlist", $repoObj["mirrorlist"]);
 } else {
  $mirrorlistField = $factory->getTextField("mirrorlist", $repoObj["mirrorlist"], $writeable);
 }
$mirrorlistField->setOptional(true);
$mirrorlistField->setSize(50);
$block->addFormField(
		     $mirrorlistField,
		     $factory->getLabel("mirrorlistField")
		     );

if(!$writeable) {
  $gpgkeyField = $factory->getUrl("gpgkey", $repoObj["gpgkey"]);
 } else {
  $gpgkeyField = $factory->getTextField("gpgkey", $repoObj["gpgkey"], $writeable);
 }
$gpgkeyField->setOptional("true");
$gpgkeyField->setSize(50);
$block->addFormField(
		     $gpgkeyField,
		     $factory->getLabel("gpgkeyField")
		     );

$block->addFormField(
		     $factory->getBoolean("gpgcheck", $repoObj["gpgcheck"], $writeable),
		     $factory->getLabel("gpgcheckField")
);


$excludeField = $factory->getTextField("exclude", $repoObj["exclude"], $writeable);
$excludeField->setOptional(true);
$excludeField->setSize(50);
$block->addFormField(
		     $excludeField,
		     $factory->getLabel("excludeField")
		     );

$includepkgsField = $factory->getTextField("includepkgs", $repoObj["includepkgs"], $writeable);
$includepkgsField->setOptional(true);
$includepkgsField->setSize(50);
$block->addFormField(
		     $includepkgsField,
		     $factory->getLabel("includepkgsField")
		     );

if($oid && $writeable == "rw") {
  $block->addButton($factory->getSaveButton($page->getSubmitAction()));
 }

if(!$oid && $writeable == "rw") {
  $block->addButton($factory->getAddButton($page->getSubmitAction()));
 }

if($repoObj["systemRepo"]) {
  $block->addButton($factory->getBackButton("/base/swupdate/yum.php?_PagedBlock_selectedId_yumgui_head=repos"));
 } else {
  $block->addButton($factory->getCancelButton("/base/swupdate/yum.php?_PagedBlock_selectedId_yumgui_head=repos"));
 }

$serverScriptHelper->destructor();

print($page->toHeaderHtml());

print($block->toHtml());
print($page->toFooterHtml()); 

?>