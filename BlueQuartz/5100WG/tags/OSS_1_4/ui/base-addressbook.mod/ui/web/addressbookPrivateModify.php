<?
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: addressbookPrivateModify.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("ArrayPacker.php");
include("./addressbookPrivateCommon.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-addressbook", "/base/addressbook/addressbookPrivateModifyHandler.php");

// get addy
$page = $factory->getPage();
$block = $factory->getPagedBlock("addressbookPrivateModify");
$addy = addressBookGet($serverScriptHelper, $oid);

$fullname = $factory->getFullname("fullname",$addy[$FULLNAME]);
$block->addFormField($fullname, $factory->getLabel("enterFullname"));

$email = $factory->getEmailAddress("email", $addy[$EMAIL]);
$email->setOptional(true);
$block->addFormField($email, $factory->getLabel("enterEmail"));

$phone = $factory->getTextField("phone", $addy[$PHONE]);
$phone->setOptional(true);
$block->addFormField($phone, $factory->getLabel("enterPhone"));

$fax = $factory->getTextField("fax", $addy[$FAX]);
$fax->setOptional(true);
$block->addFormField($fax, $factory->getLabel("enterFax"));

$homeUrl = $factory->getUrl("homeUrl", $addy[$HOMEPAGE]);
$homeUrl->setOptional(true);
$block->addFormField($homeUrl, $factory->getLabel("enterHomeUrl"));

$address = $factory->getTextBlock("address", $addy[$ADDRESSES]);
$address->setWidth(2*$address->getWidth());
$address->setWrap(true);
$address->setOptional(true);
$block->addFormField($address, $factory->getLabel("enterAddress"));

$remarks = $factory->getTextBlock("remarks", $addy[$REMARK]);
$remarks->setWidth(2*$remarks->getWidth());
$remarks->setWrap(true);
$remarks->setOptional(true);
$block->addFormField($remarks, $factory->getLabel("enterRemarks"));

$oid = $factory->getTextField("oid",$oid,"");

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/addressbook/addressbookPrivate.php"));
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

