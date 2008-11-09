<?
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: addressbookCommonView.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-addressbook", "/base/addressbook/addressbookPrivateModifyHandler.php");

// get the hostname for emails..
$system = $cceClient->getObject("System");
$hostname = ($system["hostname"] ? $system["hostname"] . "." : "") . $system["domainname"];

// get defaults
$page = $factory->getPage();
$block = $factory->getPagedBlock("addressbookCommonView");
$defaults = $cceClient->get($oid, "AddressbookEntry");
$defaultsUser = $cceClient->get($oid);

$fullname = $factory->getFullname("fullname",$defaultsUser["fullName"],"r");
$block->addFormField($fullname, $factory->getLabel("fullname"));

$email = $factory->getTextField("email", $defaultsUser["name"] . "@" . $hostname,"r");
$block->addFormField($email, $factory->getLabel("email"));

$phone = $factory->getTextField("phone", $defaults["phone"],"r");
$block->addFormField($phone, $factory->getLabel("phone"));

$fax = $factory->getTextField("fax", $defaults["fax"],"r");
$block->addFormField($fax, $factory->getLabel("fax"));

if ($defaults["homeUrl"]=="") {
	$defaults["homeUrl"] = "http://$hostname/~" . $defaultsUser["name"];
}

// prepend http:// or browser gets confused
if(!eregi("^http://", $defaults["homeUrl"]))
	$homepage = "http://" . $defaults["homeUrl"];
else
	$homepage = $defaults["homeUrl"];

$homeUrl = $factory->getUrl("homeUrl", ($homepage?$homepage:"javascript: void(0)"), $defaults["homeUrl"], "_window","r");
$block->addFormField($homeUrl, $factory->getLabel("homeUrl"));

$address = $factory->getTextBlock("address", $defaults["address"],"r");
$block->addFormField($address, $factory->getLabel("address"));

$remarks = $factory->getTextBlock("remarks", $defaultsUser["description"],"r");
$block->addFormField($remarks, $factory->getLabel("userdesc"));

$oid = $factory->getTextField("oid",$oid,"");

$block->addButton($factory->getBackButton("/base/addressbook/addressbookCommon.php"));
$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
<?php print($block->toHtml()); ?>
<?php print($oid->toHtml()); ?>
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

