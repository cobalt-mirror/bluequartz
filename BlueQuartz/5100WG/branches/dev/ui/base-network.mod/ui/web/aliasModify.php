<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: aliasModify.php 201 2003-07-18 19:11:07Z will $
// aliasModify.php
// add or modify an alias for the ethernet interfaces

include_once('ServerScriptHelper.php');
include_once('uifc/Option.php');
include_once('base/network/network_common.php');

$helper =& new ServerScriptHelper();
$cce = $helper->getCceClient();

// handle a save if necessary
if (isset($ipaddr))
{
	$call_to_handler = true;
	if (isset($_oid))
	{
		// check for duplicates, because it will fail on this
		$dups = $cce->find('Network', array('ipaddr' => $ipaddr));
		if (isset($oldIpaddr) && $ipaddr != $oldIpaddr && (count($dups) < 1))
		{
			$call_to_handler = false;
			ip_change_redirect($helper, $ipaddr);
		}

		$settings = array('ipaddr' => $ipaddr, 'netmask' => $netmask);

		if ($device != $old_device)
			$settings['device'] = find_free_device($cce, $device);
			
		$ok = $cce->set($_oid, '', $settings);
	}
	else
	{
		// create new interface, but first find an available one
		$real_device_name = find_free_device($cce, $device);
		
		$ok = $cce->create('Network',
			array(
				'device' => $real_device_name,
				'ipaddr' => $ipaddr,
				'netmask' => $netmask,
				'enabled' => 1
				));
	}

	$errors = $cce->errors();

	if ($ok)
	{
		if ($call_to_handler)
		{
			print $helper->toHandlerHtml(
				'/base/network/ethernet.php?view=aliasSettings', $errors, 0);
		}
		$helper->destructor();
		exit;
	}
	// we do an explicit processErrors down below

}

$factory =& $helper->getHtmlComponentFactory(
				'base-network', '/base/network/aliasModify.php');
$i18n =& $helper->getI18n('base-network');

// here is the display code
if (isset($_oid))
{
	$add = false;
	$current = $cce->get($_oid);
}
else
{
	$add = true;
}
   
$alias =& $factory->getPagedBlock(($add ? 'addAlias' : 'modAlias'));

// display choice of interfaces to associate this alias with
$assoc_if =& $factory->getMultiChoice('device');
$real_ifs = $cce->find('Network', array('enabled' => 1, 'real' => 1));

// get the current real device for this alias, if modifying
if (!$add)
{
	preg_match("/^([^:]+)/", $current['device'], $matches);
	$current_if = $matches[1];
}

foreach ($real_ifs as $oid)
{
	$eth =& $cce->get($oid);
	ereg('([0-9]+)', $eth['device'], $matches);
	$option =& new Option(
					$factory->getLabel($eth['device']),
					$eth['device']
					);
	if (!$add && ($eth['device'] == $current_if))
		$option->setSelected(true);

	$assoc_if->addOption($option);
}

$alias->addFormField($assoc_if, $factory->getLabel('assocIface'));

if (!$add)
{
	$alias->addFormField(
		$factory->getTextField('old_device', $current_if, ''));
}

// now add the stuff common to adding or modifying
$ipfield = $factory->getIpAddress('ipaddr', $current['ipaddr']);
$ipfield->setInvalidMessage($i18n->getJs('aliasModIpaddr_invalid'));
$ipfield->setEmptyMessage($i18n->getJs('aliasModIpaddr_empty'));

$alias->addFormField($ipfield,$factory->getLabel('aliasModIpaddr'));
		
$netmaskfield = $factory->getNetAddress('netmask', $current['netmask']);
$netmaskfield->setInvalidMessage($i18n->getJs('aliasNetmask_invalid'));
$netmaskfield->setEmptyMessage($i18n->getJs('aliasNetmask_empty'));

$alias->addFormField(
                $netmaskfield,
		$factory->getLabel('aliasNetmask')
		);

// store old settings if this is modify to check for the admin if
if ($SERVER_ADDR == $current['ipaddr'])
{
	$alias->addFormField(
		$factory->getTextField('oldIpaddr', $current['ipaddr'], ''));
	$alias->addFormField(
		$factory->getTextField('oldNetmask', $current['netmask'], ''));
}

$page =& $factory->getPage();
$form =& $page->getForm();

$alias->addButton($factory->getSaveButton($page->getSubmitAction()));
$alias->addButton(
	$factory->getCancelButton('/base/network/ethernet.php?view=aliasSettings'));

print $page->toHeaderHtml();

if (isset($_oid))
{
	$oid = $factory->getTextField('_oid', $_oid, '');
	print $oid->toHtml();
}

// explicitly do error reporting
$alias->processErrors($errors);
print $alias->toHtml();

// add check for admin if, if necessary
if ($SERVER_ADDR == $current['ipaddr'])
{
?>
<SCRIPT LANGUAGE="javascript">
var old_onsubmit = document.<? print($form->getId()); ?>.onsubmit;

function admin_warning()
{
	var form = document.<? print($form->getId()); ?>;

	if (!old_onsubmit())
		return false;

	if ((form.ipaddr.value != form.oldIpaddr.value) ||
		(form.netmask.value != form.oldNetmask.value))
	{
		alert("<? print($i18n->getJs('ethernetChanged')); ?>");
	}

	return true;
}

document.<? print($form->getId()); ?>.onsubmit = admin_warning;
</SCRIPT>
<?
} // end if ($SERVER_ADDR == $current['ipaddr'])

print $page->toFooterHtml();

$helper->destructor();

function ip_change_redirect(&$ssh, $new_ip)
{
	global $SERVER_ADDR;

	$factory =& $ssh->getHtmlComponentFactory('base-network');
	$i18n = $factory->getI18n();
	$page = $factory->getPage();

	$page->setOnLoad("top.location = 'http://$new_ip/login/'");
	$reconnect =& $factory->getButton("javascript: top.location = 'http://$new_ip/login/';", 'reconnect');
	$fallback =& $factory->getButton("javascript: top.location = 'http://$SERVER_ADDR/login/';", 'oldIPReconnect');
	
	print $page->toHeaderHtml();
	print $i18n->interpolateHtml('[[base-network.adminRedirect]]');
	print "<p></p>\n";
	print $reconnect->toHtml();
	print "<p></p>\n";
	print $fallback->toHtml();
	print $page->toFooterHtml();
	
	// make sure that part gets sent back before possible disconnect
	// due to network confusion
	// push ie to display junk
	for ($i = 0; $i < 600; $i++) print "<br>\n";
	flush();
	sleep(2);
}

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

