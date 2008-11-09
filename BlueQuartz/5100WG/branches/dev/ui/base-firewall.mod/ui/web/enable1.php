<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: enable1.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");
include("ArrayPacker.php");
include("uifc/PagedBlock.php");
include("uifc/MultiChoice.php");
include("uifc/Option.php");

$iam = '/base/firewall/enable1.php';

// standard uifc header:
$serverScriptHelper = new ServerScriptHelper() or die ("no server-script-helper");
$i18n = $serverScriptHelper->getI18n("base-firewall");
$confirm_removal=$i18n->get('confirm_removal');
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-firewall", $iam);
$page = $factory->getPage();
$page->setOnLoad('attempt_to_confirm()');

print($page->toHeaderHtml()); 

echo '';
echo '<script language="javascript"> ';
echo '';
echo 'function attempt_to_confirm()';
echo '{';
echo '  location = "/base/firewall/rules.php?confirm=1";';
echo '}';
echo '';
echo '</script>';
echo '';

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
$block = new PagedBlock($page, "FirewallEnable1", 
  $factory->getLabel("FirewallEnable1",true));

$block->addDivider($factory->getLabel("explain-firewall-enable1-page"));

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

