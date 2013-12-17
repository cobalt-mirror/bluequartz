<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: manualHandler.php 1136 2008-06-05 01:48:04Z mstauber $

include_once("ServerScriptHelper.php");
include_once("Error.php");

// Adds settings to avoid changing php.ini
ini_set('memory_limit', '128M');
ini_set('post_max_size ', '100M');
ini_set('upload_max_filesize', '100M');
ini_set('max_execution_time', '0');
ini_set('max_input_time', '0');

// declare some constants
$prepare_cmd = "/usr/sausalito/sbin/pkg_prepare.pl";
$packageDir = "/home/packages";
$pageUrl = "/base/swupdate/manual.php?backUrl=$backUrl";

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();

// check if cce is suspended before proceeding
if ($cceClient->suspended() !== false)
{
	$msg = $cceClient->suspended() ? $cceClient->suspended() : '[[base-cce.suspended]]';
	print $serverScriptHelper->toHandlerHtml($backUrl, array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}

// get any options
$opts = strstr($prepareOpts, 'install') ? '-i' : '';

// find name of the package
$nameField = "";

// reset
$cceClient->setObject("System", array("uiCMD" => "", "message" => "[[base-swupdate.initializing]]", "progress" => 0), "SWUpdate");

// install
if($locationField == "url") {

  // check if URL is secure
  if(substr($urlField, 0, 8) != "https://" && substr($urlField, 0, 7) != "http://" && substr($urlField, 0, 6) != "ftp://") 
  {
    print($serverScriptHelper->toHandlerHtml($pageUrl, 
      	array(new Error("[[base-swupdate.invalidUrl]]"))));

    $serverScriptHelper->destructor();
    exit();
  }

  // package name is the last piece of the URL
  $names = explode("/", $urlField);
  $nameField = $names[count($names)-1];

  // install
  $serverScriptHelper->fork("$prepare_cmd $opts -u \"$urlField\"", 'root');

} else if($locationField == "upload") {

  // package name is the last piece of the supplied file name
  // if windows
  if(!strpos($fileField_name, "\\") === false)
    $names = explode("\\", $fileField_name);
  else
    $names = explode("/", $fileField_name);
  $nameField = $names[count($names)-1];

  $file = '/tmp/pkgdownload.';
  $id = posix_getpid(); 
  while (file_exists($file . $id) && (unlink($file . $id) == 0))
	$id++;
  $file .= $id;
  
  if(file_exists($fileField)) {
    move_uploaded_file($fileField, $file);

    // install
    $serverScriptHelper->fork("$prepare_cmd $opts -f $file", 'root');

  } else {

    // Bail with an error on the failed file upload
    print($serverScriptHelper->toHandlerHtml($pageUrl, 
      array( new Error("[[base-swupdate.invalidUpload]]") )));
    $serverScriptHelper->destructor();
    exit();
  }

} else if($locationField == "loaded") {

  $nameField = $loadedField;

  $serverScriptHelper->fork("$prepare_cmd $opts -f \"$packageDir/$loadedField\"", 'root');
}

$nameField = trim($nameField);
$backUrl = trim($backUrl);

print($serverScriptHelper->toHandlerHtml("/base/swupdate/status.php?nameField="
	.rawurlencode($nameField)."&backUrl=/base/swupdate/manual.php&backbackUrl=$backUrl"));

$serverScriptHelper->destructor();
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
