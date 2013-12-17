<?php
// Author: Kevin K.M. Chiu
// $Id: email.php 
  
include_once("ServerScriptHelper.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper();

// Only 'serverEmail' should be here:
if (!$serverScriptHelper->getAllowed('serverEmail')) {
  header("location: /error/forbidden.html");
  return;
}

if (isset($view)) {
  $_PagedBlock_selectedId_emailSettings = "$view";
}

$cceClient = $serverScriptHelper->getCceClient();
if($_PagedBlock_selectedId_emailSettings == "mx") {
  $factory = $serverScriptHelper->getHtmlComponentFactory("base-email", "/base/email/email.php");
 } else {
  $factory = $serverScriptHelper->getHtmlComponentFactory("base-email", "/base/email/emailHandler.php");
 }
$i18n = $serverScriptHelper->getI18n("base-email");
$product = new Product($cceClient);


// get object
$email = $cceClient->getObject("System", array(), "Email");

$page = $factory->getPage();

$block = $factory->getPagedBlock("emailSettings", array("basic", "advanced", "mx", "blacklist"));
$block->processErrors($serverScriptHelper->getErrors());

if (isset($view)) {
  $block->setSelectedId($view);
  $_PagedBlock_selectedId_emailSettings = "$view";
}

// think about what we've done wrong
$errors = $serverScriptHelper->getErrors(); 
$block->processErrors($errors); 

// basic page
// smtp
$block->addDivider($factory->getLabel('SMTP', false), 'basic');
$block->addFormField(
  $factory->getBoolean("enableServersField", $email["enableSMTP"]),
  $factory->getLabel("enableServersField"),
  "basic"
);

$block->addFormField(
  $factory->getBoolean("enableSMTPSField", $email["enableSMTPS"]),
  $factory->getLabel("enableSMTPSField"),
  "basic"
);

$block->addFormField(
  $factory->getBoolean("enableSMTPAuthField", $email["enableSMTPAuth"]),
  $factory->getLabel("enableSMTPAuthField"),
  "basic"
);

$block->addFormField(
  $factory->getBoolean("enableSubmissionPortField", $email["enableSubmissionPort"]),
  $factory->getLabel("enableSubmissionPortField"),
  "basic"
);

// imap
$block->addDivider($factory->getLabel('IMAP', false), 'basic');
$block->addFormField(
  $factory->getBoolean("enableImapField", $email["enableImap"]),
  $factory->getLabel("enableImapField"),
  "basic"
);

$block->addFormField(
  $factory->getBoolean("enableImapsField", $email["enableImaps"]),
  $factory->getLabel("enableImapsField"),
  "basic"
);

// pop
$block->addDivider($factory->getLabel('POP', false), 'basic');
$block->addFormField(
  $factory->getBoolean("enablePopField", $email["enablePop"]),
  $factory->getLabel("enablePopField"),
  "basic"
);

$block->addFormField(
  $factory->getBoolean("enablePopsField", $email["enablePops"]),
  $factory->getLabel("enablePopsField"),
  "basic"
);

// Z-Push
$block->addDivider($factory->getLabel('Z-Push ActiveSync', false), 'basic');
$block->addFormField(
  $factory->getBoolean("enableZpushField", $email["enableZpush"]),
  $factory->getLabel("enableZpushField"),
  "basic"
);


// advanced page
$queueTimeMap = array("immediate" => "queue0", "quarter-hourly" => "queue15", "half-hourly" => "queue30", "hourly" => "queue60", "quarter-daily" => "queue360", "daily" => "queue1440");
$queueSelectedMap = array("immediate" => 0, "quarter-hourly" => 1, "half-hourly" => 2, "hourly" => 3, "quarter-daily" => 4, "daily" => 5);

$maxRecipientsPerMessageMap = 
    array(
	"0" => "unlimited", 
        "5" => "5", 
        "10" => "10", 
        "15" => "15", 
        "20" => "20", 
        "25" => "25", 
	"50" => "50", 
        "75" => "75", 
        "100" => "100", 
        "125" => "125", 
        "150" => "150", 
        "175" => "175", 
        "200" => "200" 
    );
  
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

// maxRecipientsPerMessage
$maxRecipientsPerMessage_select = $factory->getMultiChoice("maxRecipientsPerMessageField", array_values($maxRecipientsPerMessageMap));
$maxRecipientsPerMessage_select->setSelected($maxRecipientsPerMessageMap[$email['maxRecipientsPerMessage']], true);
$block->addFormField($maxRecipientsPerMessage_select, $factory->getLabel("maxRecipientsPerMessageField"), 'advanced');

// Enable delay_checks
$block->addFormField(
  $factory->getBoolean("delayChecksField", $email["delayChecks"]),
  $factory->getLabel("delayChecksField"),
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

if($_PagedBlock_selectedId_emailSettings == "mx" || $view == "mx") {
  $addmod = '/base/email/mx2_add.php';
  $confirm_removal = $i18n->get("mxRemoveEntry");
 
  $oids = $cceClient->findx("mx2",array(),array(), 'ascii', 'domain');
  $oidsNum = count($oids);


  $mxList = "";
  $mxList = $factory->getScrollList("mx2List", array("secondaryDomain", " "), array(0));
  $mxList->setDefaultSortedIndex(0);
  $mxList->setAlignments(array("left", "center"));
  $mxList->addButton($factory->getAddButton("javascript: location='$addmod';"
					    . " top.code.flow_showNavigation(false)", "addmx"));
  $mxList->setLength(25);
  $maxLength = $oidsNum;
  $currentPage = $mxList->getPageIndex();
  $startIndex = 25 * $currentPage;
  $mxList->processErrors($serverScriptHelper->getErrors());
  $mxList->setEntryNum($oidsNum);
  
  if ($mxList->getSortOrder() == "descending") {
    $oids = array_reverse($oids);
  }

  for($i = $startIndex; 
      $i < count($oids) || $i < $startIndex + $maxLength;
      $i++) {
    $oid = $oids[$i];
    $domains = $cceClient->get($oid);
    $domain = $domains['domain'];
    $mapto = $domains['mapto'];
    
    $mxList->addEntry(array(
			    $factory->getTextField("", $domain, "r"),
			    $factory->getCompositeFormField(array(
								  $factory->getModifyButton( "$addmod?_TARGET=$oid"),
								  $factory->getRemoveButton( "javascript: confirmRemove(strConfirmRemoval, '$oid', '$domain', 'mx');" )))));
  }
 }

if($_PagedBlock_selectedId_emailSettings == "blacklist" || $view == "blacklist") {
  $addmod = '/base/email/blacklist_add.php';
  $confirm_removal = $i18n->get("blacklistRemoveEntry");
  
  $oids = $cceClient->findx("dnsbl",array(),array(), 'ascii', 'blacklistHost');
  $oidsNum = count($oids);
  
  
  $blackList = "";
  $blackList = $factory->getScrollList("blackList", array("blackList", "activated", " "), array(0));
  $blackList->setDefaultSortedIndex(0);
  $blackList->setAlignments(array("left", "left", "center"));
  $blackList->addButton($factory->getAddButton("javascript: location='$addmod';"
					    . " top.code.flow_showNavigation(false)", "addBlacklist"));
  $blackList->setLength(25);
  $maxLength = $oidsNum;
  $currentPage = $blackList->getPageIndex();
  $startIndex = 25 * $currentPage;
  $blackList->processErrors($serverScriptHelper->getErrors());
  $blackList->setEntryNum($oidsNum);
  
  if ($blackList->getSortOrder() == "descending") {
    $oids = array_reverse($oids);
  }

  for($i = $startIndex; 
      $i < count($oids) || $i < $startIndex + $maxLength; 
      $i++) {
    $oid = $oids[$i];
    $hosts = $cceClient->get($oid);
    $host = $hosts['blacklistHost'];
    $active = $hosts['active'];
    if( $active) {
      $activeStatus = $factory->getImageLabel("blacklist", "/libImage/greenCheck.gif");
    } else {
      $activeStatus = $factory->getImageLabel("blacklist", "/libImage/redX.gif");
    }
    
    $blackList->addEntry(array(
			    $factory->getTextField("", $host, "r"),
			    $activeStatus,
			    $factory->getCompositeFormField(array(
								  $factory->getModifyButton( "$addmod?_TARGET=$oid"),
								  $factory->getRemoveButton( "javascript: confirmRemove(strConfirmRemoval, '$oid', '$host', 'blacklist');" )))));
  }
 }

if( $_PagedBlock_selectedId_emailSettings != "mx" && $_PagedBlock_selectedId_emailSettings != "blacklist") {
  $block->addButton($factory->getSaveButton($page->getSubmitAction()));
  //$factory->getSaveButton($page->getSubmitAction("/base/email/emailHandler.php")));
 }
$serverScriptHelper->destructor();


print($page->toHeaderHtml());
?>
<SCRIPT LANGUAGE="javascript">
// these need to be defined seperately or Japanese gets corrupted
var strConfirmRemoval = '<?php print $confirm_removal; ?>';
</SCRIPT>
<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid, label, view, netauth) {
  msg = top.code.string_substitute(msg, "[[VAR.mxHost]]", label);
  msg = top.code.string_substitute(msg, "[[VAR.blacklistHost]]", label);
  
  if(confirm(msg))
    location = "/base/email/oid_deleteHandler.php?_REMOVE=" + oid + "&_VIEW=" + view;
}
</SCRIPT>


<?php 

print($block->toHtml());

if($_PagedBlock_selectedId_emailSettings == "mx" || $view == "mx") {
  print($mxList->toHtml()); 
 }

if($_PagedBlock_selectedId_emailSettings == "blacklist" || $view == "blacklist") {
  print($blackList->toHtml()); 
 }

print($page->toFooterHtml());

# 
# Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without modification, 
# are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation and/or 
# other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
# 

?>