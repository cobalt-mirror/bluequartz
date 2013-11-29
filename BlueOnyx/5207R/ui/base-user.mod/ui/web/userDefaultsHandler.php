<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userDefaultsHandler.php 1136 2008-06-05 01:48:04Z mstauber $

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$serverScriptHelper->getAllowed('adminUser') &&
    !($serverScriptHelper->getAllowed('siteAdmin') &&
      $group == $serverScriptHelper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$autoFeatures = new AutoFeatures($serverScriptHelper);
$cceClient = $serverScriptHelper->getCceClient();

$attribs = array();

$maxDiskSpaceField = ( $maxDiskSpaceField ) ? $maxDiskSpaceField : -1;
$attribs = array_merge($attribs, array("quota" => $maxDiskSpaceField,
				       "emailDisabled" => $emailDisabled));

if ( $userNameGenMode ) $attribs = array_merge($attribs, array("userNameGenMode" => $userNameGenMode));

if ( isset( $group ) && $group != "" ) {
  $cceClient->setObject("Vsite", $attribs, "UserDefaults", array("name" => $group));
  $site = $group;
}
else
  $cceClient->setObjectForce("System", $attribs, "UserDefaults");

$errors = $cceClient->errors();

// set autofeatures defaults
list($userservices) = $cceClient->find("UserServices", array("site" => $site));
$af_errors = $autoFeatures->handle("defaults.User", array("CCE_SERVICES_OID" => $userservices));

$errors = array_merge($errors, $af_errors);

if (count($errors) > 0)
    print $serverScriptHelper->toHandlerHtml(
        "/base/user/userDefaults.php?group=$group", $errors);
else
    print($serverScriptHelper->toHandlerHtml(
            "/base/user/userList.php?group=$group", $errors, false));

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