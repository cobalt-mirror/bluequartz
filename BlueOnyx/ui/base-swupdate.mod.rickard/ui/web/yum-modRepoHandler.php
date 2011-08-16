<?php
// Author: Rickard Osser <rickard.osser@bluapp.com>
// Copyright 2011 Bluapp AB.  All rights reserved.

include_once("ServerScriptHelper.php");
include_once('Error.php');

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$pageUrl = "/base/swupdate/yum-modRepo.php?oid=$oid";

$cceClient = $serverScriptHelper->getCceClient();

// check if cce is suspended, and don't proceed if it is
if ($cceClient->suspended() !== false)
{
	$msg = $cceClient->suspended() ? $cceClient->suspended() : '[[base-cce.suspended]]';
	print $serverScriptHelper->toHandlerHtml("/base/swupdate/yum.php?_PagedBlock_selectedId_yumgui_head=repos",
			array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}


$notUnique = $cceClient->findx("yumRepo", array('repoName' => $repoName), array(), "", "");
if ($notUnique && !$oid) {
  $msg = "[[base-yum.repoNotUnique]]";
  print $serverScriptHelper->toHandlerHtml("/base/swupdate/yum.php?_PagedBlock_selectedId_yumgui_head=repos",
					   array(new Error($msg)));
  $serverScriptHelper->destructor();
  exit;
  
 }


if(!$enabled) {
  $enabled = 0;
 }

if(!$gpgcheck) {
  $gpgcheck = 0;
 }

if( !$baseurl && !$mirrorlist) {
  $msg = "[[base-swupdate.noURL]]";
  $errors = array((new Error($msg)));
 }

if ($baseurl && $mirrorlist) {
  print($serverScriptHelper->toHandlerHtml($pageUrl, 
					   array(new Error("[[base-yum.chooseBaseOrMirror]]"))));
  $serverScriptHelper->destructor();
  exit();
 }

if($baseurl) {
  if(substr($baseurl, 0, 8) != "https://" && substr($baseurl, 0, 7) != "http://" && substr($baseurl, 0, 6) != "ftp://") {
    print($serverScriptHelper->toHandlerHtml($pageUrl, 
					     array(new Error("[[base-yum.invalidUrlBase]]"))));
    $serverScriptHelper->destructor();
    exit();
  }
 }
if($mirrorlist) {
  if((substr($mirrorlist, 0, 8) != "https://" && substr($mirrorlist, 0, 7) != "http://" && substr($mirrorlist, 0, 6) != "ftp://" && substr($mirrorlist, 0, 7) != "file://")) {	
    print($serverScriptHelper->toHandlerHtml($pageUrl, 
					     array(new Error("[[base-swupdate.invalidUrlMirror]]"))));
    $serverScriptHelper->destructor();
    exit();
  }
 }
if(substr($gpgkey, 0, 8) != "https://" && substr($gpgkey, 0, 7) != "http://" && substr($gpgkey, 0, 6) != "ftp://" && substr($gpgkey, 0, 7) != "file://") {	
  print($serverScriptHelper->toHandlerHtml($pageUrl, 
					   array(new Error("[[base-swupdate.invalidUrlPki]]"))));
  $serverScriptHelper->destructor();
  exit();
 }


$values = array(
		   "enabled" => "$enabled",
		   "repoName" => "$repoName",
		   "name" => "$description",
		   "baseurl" => "$baseurl",
		   "mirrorlist" => "$mirrorlist",
		   "gpgkey" => "$gpgkey",
		   "gpgcheck" => "$gpgcheck",
		   "exclude" => "$exclude",
		   "includepkgs" => "$includepkgs"
		   );

if ($oid) {
  $cceClient->set($oid, "", $values);
 } else {
  $cceClient->create("yumRepo", $values);
 }

print($serverScriptHelper->toHandlerHtml("/base/swupdate/yum.php?_PagedBlock_selectedId_yumgui_head=repos"));

$serverScriptHelper->destructor();
