<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: email.php 259 2004-01-03 06:28:40Z shibuya $

include("ServerScriptHelper.php");
include("Product.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-email", "/base/email/emailHandler.php");
$product = new Product($cceClient);

// get object
$email = $cceClient->getObject("System", array(), "Email");

$page = $factory->getPage();

$block = $factory->getPagedBlock("emailSettings", array("basic", "advanced"));

// think about what we've done wrong
$errors = $serverScriptHelper->getErrors(); 
$block->processErrors($errors); 

// basic page
$block->addFormField(
  $factory->getBoolean("enableServersField", $email["enableSMTP"]),
  $factory->getLabel("enableServersField"),
  "basic"
);

$block->addFormField(
  $factory->getBoolean("enableImapField", $email["enableImap"]),
  $factory->getLabel("enableImapField"),
  "basic"
);

$imaprate = $factory->getInteger("imapConnectRateField", $email["imapConnectRate"], 1, 4096);
$imaprate->showBounds(1);
$imaprate->setWidth(5);
$block->addFormField(
  $imaprate,
  $factory->getLabel("imapConnectRateField"),
  "basic"
);

$block->addFormField(
  $factory->getBoolean("enablePopField", $email["enablePop"]),
  $factory->getLabel("enablePopField"),
  "basic"
);

$poprate = $factory->getInteger("popConnectRateField", $email["popConnectRate"], 1, 1024);
$poprate->showBounds(1);
$poprate->setWidth(5);
$block->addFormField(
  $poprate,
  $factory->getLabel("popConnectRateField"),
  "basic"
);

// advanced page
$queueTimeMap = array("immediate" => "queue0", "quarter-hourly" => "queue15", "half-hourly" => "queue30", "hourly" => "queue60", "quarter-daily" => "queue360", "daily" => "queue1440");
$queueSelectedMap = array("immediate" => 0, "quarter-hourly" => 1, "half-hourly" => 2, "hourly" => 3, "quarter-daily" => 4, "daily" => 5);
  
$queue_select = $factory->getMultiChoice("queueTimeField", array_values($queueTimeMap));
$queue_select->setSelected($queueSelectedMap[$email['queueTime']], true);
$block->addFormField($queue_select, $factory->getLabel("queueTimeField"), 'advanced');

// convert from KB to MB
$max = $email["maxMessageSize"]/1024;

// No maximum size limit if it is 0
$max = $max == 0 ? "" : $max;

$maxEmailSize = $factory->getInteger("maxEmailSizeField", $max, 1);
$maxEmailSize->setOptional(true);
$block->addFormField(
  $maxEmailSize,
  $factory->getLabel("maxEmailSizeField"),
  "advanced"
);

$masqAddress = $factory->getNetAddress("masqAddressField", $email["masqAddress"]);
$masqAddress->setOptional(true);
$block->addFormField(
  $masqAddress,
  $factory->getLabel("masqAddressField"),
  "advanced"
);

$smartRelay = $factory->getDomainName("smartRelayField", $email["smartRelay"]);
$smartRelay->setOptional(true);
$block->addFormField(
  $smartRelay, 
  $factory->getLabel("smartRelayField"),
  "advanced"
);

$block->addFormField(
  $factory->getBoolean("popRelayField", $email["popRelay"]),
  $factory->getLabel("popRelayField"),
  "advanced"
);

$relay = $factory->getNetAddressList("relayField", $email["relayFor"]);
$relay->setOptional(true);
$block->addFormField(
  $relay,
  $factory->getLabel("relayField"),
  "advanced"
);

if ( ! $product->isRaq() ) {
  $receive = $factory->getDomainNameList("receiveField", $email["acceptFor"]);
  $receive->setOptional(true);
  $block->addFormField(
    $receive,
    $factory->getLabel("receiveField"),
    "advanced"
  );
}

$blockHost = $factory->getDomainNameList("blockHostField", $email["deniedHosts"]);
$blockHost->setOptional(true);
$block->addFormField(
  $blockHost,
  $factory->getLabel("blockHostField"),
  "advanced"
);

$blockUser = $factory->getEmailAddressList("blockUserField", $email["deniedUsers"]);
$blockUser->setOptional(true);
$block->addFormField(
  $blockUser,
  $factory->getLabel("blockUserField"),
  "advanced"
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
