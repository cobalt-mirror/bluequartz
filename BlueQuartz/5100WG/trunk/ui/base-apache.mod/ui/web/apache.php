<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: apache.php 3 2003-07-17 15:19:15Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-apache", "/base/apache/apacheHandler.php");

// get web
$web = $cceClient->getObject("System", array(), "Web");

// get frontpage
$frontpage = $cceClient->getObject("System", array(), "Frontpage");

// get all user names
$users = $cceClient->getObjects("User");
$userNames = array();
for($i = 0; $i < count($users); $i++)
  $userNames[] = $users[$i]["name"];
$userNamesString = arrayToString($userNames);

$page = $factory->getPage();

$block = $factory->getPagedBlock("apacheSettings");

// make frontpage field
// password only needed to enable frontpage
if(!$frontpage["enabled"]) {
  $enable = $factory->getOption("frontpageEnabled", $frontpage["enabled"]);
  $enable->addFormField(
    $factory->getPassword("passwordWebmasterField"),
    $factory->getLabel("passwordWebmasterField")
  );
  $frontpageEnabled = $factory->getMultiChoice("frontpageField");
  $frontpageEnabled->addOption($enable);
}
else
  $frontpageEnabled = $factory->getBoolean("frontpageField", $frontpage["enabled"]);
$block->addFormField(
  $frontpageEnabled,
  $factory->getLabel("frontpageField")
);

// make cgi field
$access = $web["cgiAccess"];
$users = $web["cgiUsers"];
$cgiSubset = $factory->getOption("cgiSubset", $access == "subset" && $users != "");
$cgiSubset->addFormField(
  $factory->getSetSelector("cgiUsersField", $users, $userNamesString, "cgiUsersAllowed", "cgiUsersDisallowed"),
  $factory->getLabel("cgiUsersField")
);
$cgiAccess = $factory->getMultiChoice("cgiAccessField");
$cgiAccess->addOption($factory->getOption("cgiNone", $access == "subset" && $users == ""));
$cgiAccess->addOption($factory->getOption("cgiAll", $access == "all"));
$cgiAccess->addOption($cgiSubset);
$block->addFormField(
  $cgiAccess,
  $factory->getLabel("cgiAccessField")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

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

