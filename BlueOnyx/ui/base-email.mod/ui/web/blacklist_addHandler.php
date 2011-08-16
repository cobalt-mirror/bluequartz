<?php
// Author: Rickard Osser
// Copyright 2009, Bluapp AB.  All rights reserved.
// $Id: mx2_add.php,v 1.0 2009/03/17 <rickard.osser@bluapp.com>

include_once("ServerScriptHelper.php");

$errors = array();

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$sysOid = $cceClient->getObject("System", array(), "Email");
$oids = $cceClient->findx("dnsbl", array('blacklistHost' => "$blacklistHostField"), array(),"","");
if($_TARGET) {
  $old = $cceClient->get($_TARGET);
  $oldHost = $old["blacklistHost"];
 }

if(!$activeField) {
  $activeField = 0;
 }
if(!$errors) {
  if((count($oids) < 1) || (count($oids) == 1 && $_TARGET == $oids[0] )) {
    if($_TARGET) {
      $oid = $_TARGET;
      $vals = array(
		    "blacklistHost" => $blacklistHostField, 
		    "deferTemporary" => $deferField,
		    "active" => $activeField);
      
      $cceClient->set($oid, "", $vals);
    } else {
      $cceClient->create("dnsbl", 
			 array(
			       "blacklistHost" => $blacklistHostField, 
			       "deferTemporary" => $deferField,
			       "active" => $activeField));
    }
  } else {
    $msg = "[[base-email.blacklistHostExists_error]]";
    $errors = array_merge($errors, array((new Error($msg))));
  }
 }

if($cceClient->errors()) {
  $errors = array_merge($errors, $cceClient->errors());
 }

if($errors) {
  print($serverScriptHelper->toHandlerHtml("/base/email/blacklist_add.php?_TARGET=$_TARGET", $errors, "base-email"));
 } else {
  print($serverScriptHelper->toHandlerHtml("/base/email/email.php?view=blacklist", $errors, "base-email"));
 }

# disable activeMonitor for these items
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
