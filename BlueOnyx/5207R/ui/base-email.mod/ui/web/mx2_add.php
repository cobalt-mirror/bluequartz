<?php

// Author: Rickard Osser <rickard.osser@bluapp.com>
// $Id: mx2_add.php

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only 'serverEmail' should be here:
if (!$serverScriptHelper->getAllowed('serverEmail')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-email", "/base/email/mx2_addHandler.php");
$i18n = $serverScriptHelper->getI18n("base-email");

$page = $factory->getPage();

if(isset($_TARGET)) {
  $oid = $cceClient->get($_TARGET);
  $domain = $oid["domain"];
  $mapto = $oid["mapto"];
 }


$block = $factory->getPagedBlock("secondarySettings");
$block->processErrors($serverScriptHelper->getErrors());

$block->addFormField(
		     $factory->getDomainName("domainField", $domain),
		     $factory->getLabel("domainField"),
		     ""
		     );

$mapto_field = $factory->getTextField("maptoField", $mapto);

$block->addFormField(
		     $mapto_field,
		     $factory->getLabel("maptoField"),
		     ""
		     );

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton(
	$factory->getCancelButton('/base/email/email.php?view=mx'));

$serverScriptHelper->destructor();

print($page->toHeaderHtml());

print($block->toHtml());
if (isset($_TARGET))
{
	$target = $factory->getTextField('_TARGET', $_TARGET, '');
	print $target->toHtml();
}
print($page->toFooterHtml());

# 
# Copyright (c) 2009, Bluapp AB.  All rights reserved.
# Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
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