<?php
// Copyright 2001, Sun Microsystems, Inc.
// $Id: warAddHandler.php,v 1.9.2.1 2002/02/08 23:41:02 naroori Exp $

include_once("ServerScriptHelper.php");
include_once("Error.php");

// declare some constants
$prepare_cmd = "/usr/sausalito/sbin/java_load_war.pl";
$packageDir = "/home/tmp";
$pageUrl = "/base/java/warAdd.php?group=$group&backUrl=$backUrl";

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

// check if cce is suspended, can't do anything if it is
if ($cceClient->suspended() !== false)
{
	$msg = $cceClient->suspended() ? $cceClient->suspended() : '[[base-cce.suspended]]';
	print $serverScriptHelper->toHandlerHtml(
		"/base/java/warList.php?group=$group", array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}

// The name of the servlet archive 
$nameField = "";

// Track the user & group names, necessary for war extraction seuid/gid 
$user = $serverScriptHelper->getLoginName();
    
// Filter bash manipulative characters
$targetField = preg_replace('/[\n\r\;\"]/', "", $targetField);

// reset progress status/messages
if ($_TARGET) { // Modify

  $servlet = $cceClient->get($_TARGET);
  $cceClient->set($_TARGET, "", array('description' => $descField));
  $errors = $cceClient->errors();
  if (count($errors)) {
    print($serverScriptHelper->toHandlerHtml(
      "/base/java/warAdd.php?group=$group&user=$user&_TARGET=$_TARGET", $errors));
  } else {
    print($serverScriptHelper->toHandlerHtml("/base/java/warList.php?group=$group&user=$user", 
      $errors));
  }

} else { // Add 
 
  $oids = $cceClient->find('Vsite', array('name' => $group));
  if ($oids[0] == '') { 
    exit();
  }
  $jsite = $cceClient->get($oids[0], 'Java');
  $cceClient->set($oids[0], 'Java', 
    array("uiCMD" => "", "message" => "[[base-java.initializing]]", "progress" => 0));
  
  // install
  if($locationField == "url") {
  
    // check if URL is secure
    if(substr($urlField, 0, 8) != "https://" && substr($urlField, 0, 7) != "http://" && substr($urlField, 0, 6) != "ftp://") {
      print($serverScriptHelper->toHandlerHtml($pageUrl, 
        array(new Error("[[base-java.invalidUrl]]"))
        ));
      $serverScriptHelper->destructor();
      exit();
    }

    // package name is the last piece of the URL
    $names = explode("/", $urlField);
    $nameField = $names[count($names)-1];
  
    $urlField = preg_replace('/[\n\r\;\"]/', "", $urlField);
    $serverScriptHelper->fork("$prepare_cmd -n $user -g $group -u \"$urlField\" -t \"$targetField\" -c", $user);
  
    // print "$prepare_cmd -n $user -g $group -u \"$urlField\"";
    // exit();
  
  } else if($locationField == "upload") {
  
    //targetField can not start with tilda
    if(ord($targetField) == 0x7e) {
          print($serverScriptHelper->toHandlerHtml($pageUrl, 
                 array(new Error("[[base-java.notToUserHome]]"))
               ));
    }

    // package name is the last piece of the supplied file name
    // if windows
    if(!strpos($fileField_name, "\\") === false)
      $names = explode("\\", $fileField_name);
    else
      $names = explode("/", $fileField_name);
    $nameField = $names[count($names)-1];
  
    $file = '/home/tmp/wardownload.';
    $id = posix_getpid(); 
    while (file_exists($file . $id) && (unlink($file . $id) == 0))
  	$id++;
    $file .= $id;
    
    if(file_exists($fileField)) {
      rename($fileField, $file);

      chmod($file, 0666); // httpd->$user hand-off
  
      // install
      $file = preg_replace('/[\n\r\;\"]/', "", $file);

      $serverScriptHelper->fork("$prepare_cmd -n $user -g $group -f \"$file\" -t \"$targetField\" -c", $user);

    } else {

      // Bail with an error on the failed file upload
      print($serverScriptHelper->toHandlerHtml($pageUrl, 
        array( new Error("[[base-java.invalidUpload]]") )
        ));
      $serverScriptHelper->destructor();
      exit();
    }

  } else if($locationField == "loaded") {

    $nameField = $loadedField;

    $loadedField = preg_replace('/[\n\r\;\"]/', "", $loadedField);
    $serverScriptHelper->fork("$prepare_cmd -n $user -g $group -f \"$packageDir/$loadedField\" -t \"$targetField\" -c", $user);
  }

  $nameField = trim($nameField);
  $backUrl = trim($backUrl);

  print($serverScriptHelper->toHandlerHtml("/base/java/status.php?group=$group&nameField=".$nameField."&backUrl=/base/java/warAdd.php?group=$group"));
}

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
