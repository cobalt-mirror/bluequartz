<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: enable2.php 1413 2010-03-10 14:28:36Z shibuya $

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");
include_once("uifc/PagedBlock.php");
include_once("uifc/MultiChoice.php");
include_once("uifc/Option.php");

$iam = '/base/firewall/enable1.php';

// standard uifc header:
$serverScriptHelper = new ServerScriptHelper() or die ("no server-script-helper");

// Only serverSystemFirewall should be here
if (!$serverScriptHelper->getAllowed('serverSystemFirewall')) {
  header("location: /error/forbidden.html");
  return;
}

$i18n = $serverScriptHelper->getI18n("base-firewall");
$confirm_removal=$i18n->get('confirm_removal');
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-firewall", $iam);
$page = $factory->getPage();
print($page->toHeaderHtml()); 

///////////////////////////////////////////////////////////////////
// identify objects
///////////////////////////////////////////////////////////////////
$sys_oids = $cceClient->find("System");

///////////////////////////////////////////////////////////////////
// commit firewall
///////////////////////////////////////////////////////////////////
$cceClient->set($sys_oids[0], "Firewall", array(
  'enabled' => 1,
  'commit' => time(),
  'watchdog' => time() ) );

// block:
$block = new PagedBlock($page, "FirewallEnable2", 
  $factory->getLabel("FirewallEnable1",true));

$block->addDivider($factory->getLabel("firewall-congrat-page"));

$block->addButton($factory->getButton('/base/firewall/top.php?confirm=1',
  'confirm-firewall-button'));

print $block->toHtml();
print $page->toFooterHtml();

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

