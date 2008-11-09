<?php
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: addressbookCommon.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("uifc/Button.php");
include("ArrayPacker.php");
include("uifc/ImageButton.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
// get the hostname
$system = $cceClient->getObject("System");
$hostname = $system["hostname"] . "." . $system["domainname"];
$factory = $serverScriptHelper->getHtmlComponentFactory("base-addressbook", "/base/addressbook/addressbookCommon.php");
$i18n = $serverScriptHelper->getI18n("base-addressbook");

$page = $factory->getPage();

$scrollList = $factory->getScrollList("addressbookCommon", array("fullname","username", "userdesc", "listAction"),array(1));
$scrollList->setAlignments(array("left", "left", "left", "center"));
$scrollList->setColumnWidths(array("30%", "20%", "35%", "15%"));

// copy speed up from Kevin's changes in userList.php
// disable sorting
$scrollList->setSortEnabled(false);

// find sort key
$sortKeyMap = array(1 => "name");
$sortKey = $sortKeyMap[$scrollList->getSortedIndex()];

// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

$oids = $cceClient->findSorted("User", $sortKey);

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
  $oids = array_reverse($oids);

// set num entries to number of users
$scrollList->setEntryNum(count($oids));

for($i = $start; $i < count($oids) && $i < $start+$pageLength; $i++) {
  $addressbookUser = $cceClient->get($oids[$i]);
  $addressbookEntry = $cceClient->get($oids[$i], "AddressbookEntry");

  $emailPart = $addressbookUser["name"] . "@" . $hostname;
  $fullname = $factory->getTextField("", $addressbookUser["fullName"],"r");
  $username = $factory->getTextField("", $addressbookUser["name"],"r");

  // this sequence needs to get pushed to a common place
  $desc = $factory->getTextField("", 
	$i18n->interpolate($addressbookUser["description"]), "r");
  $desc->setMaxLength(1000);

  $actions = $factory->getCompositeFormField();
  $actions->addFormField(new ImageButton($page, "/base/addressbook/addressbookCommonView.php?oid=$oids[$i]","/libImage/detail.gif", "view", "view_help"));
  $actions->addFormField(new ImageButton($page, "/base/webmail/compose.php?toAddy=".rawurlencode($addressbookUser["fullName"] . " <" . $emailPart . ">"),"/libImage/composeEmail.gif", "mail", "mail_help"));
  $userHomepage = "http://$hostname/~" . $addressbookUser["name"] . "/";
  $userHomepageWindowname = $addressbookUser["name"] . "homepage";
  $homePageButton = new ImageButton($page, "$userHomepage", "/libImage/visitWebsite.gif", "web", "web_help");
  $homePageButton->setTarget($userHomepageWindowname);
  $actions->addFormField($homePageButton);
  $scrollList->addEntry(array($fullname,$username, $desc, $actions), "", false, $i);
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
<?php print($scrollList->toHtml()); ?>
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

