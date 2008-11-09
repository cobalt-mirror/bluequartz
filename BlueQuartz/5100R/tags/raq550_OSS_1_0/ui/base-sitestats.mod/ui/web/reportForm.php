<?
// Form to submit to customize report date range
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: reportForm.php,v 1.13 2001/12/07 02:51:33 pbaltz Exp $


include("ServerScriptHelper.php");

$helper =& new ServerScriptHelper($sessionId);
$factory =& $helper->getHtmlComponentFactory('base-sitestats', 
	"/base/sitestats/reportHandler.php?group=$group&type=$type&noback=$noback");

$page = $factory->getPage();
$block = $factory->getPagedBlock("generateSettings");
$block->processErrors($helper->getErrors());

$i18n = $factory->getI18n();
$typestring = $i18n->interpolate("[[base-sitestats." . $type . "usage]]");
$i18nvars['type'] = $typestring;
if (isset($group) && $group != 'server') {
  $cce = $helper->getCceClient();
  list($vsite) = $cce->find('Vsite', array('name' => $group));
  $vsiteObj = $cce->get($vsite);
  $i18nvars['fqdn'] = $vsiteObj['fqdn'];
  $block->setLabel($factory->getLabel('generateSettingsVsite', false, $i18nvars));
} else {
  $block->setLabel($factory->getLabel('generateSettings', false, $i18nvars));
}

$block->addFormField( $factory->getTimeStamp("startDate", time(), "date"),
	$factory->getLabel("startDate") );

$block->addFormField( $factory->getTimeStamp("endDate", time(), "date"),
        $factory->getLabel("endDate") );

$submit = $factory->getButton($page->getSubmitAction(), "generateBut");
$back = $factory->getBackButton("/base/sitestats/summary.php?group=$group&type=$type");

print $page->toHeaderHtml();
print $block->toHtml();
print "<P><TABLE BORDER=0 CELLPADDING=2><TR><TD>";
print $submit->toHtml();
print "</TD><TD>";
if(!$noback)
{
	print $back->toHtml();
}
print "</TD></TR></TABLE>";
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
