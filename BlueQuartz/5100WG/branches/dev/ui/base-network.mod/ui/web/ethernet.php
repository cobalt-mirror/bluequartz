<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: ethernet.php 201 2003-07-18 19:11:07Z will $

include_once('ServerScriptHelper.php');
include_once('Product.php');

$serverScriptHelper =& new ServerScriptHelper();
$cceClient =& $serverScriptHelper->getCceClient();
$product =& new Product( $cceClient );
$factory =& $serverScriptHelper->getHtmlComponentFactory("base-network", "/base/network/ethernetHandler.php");
$i18n =& $serverScriptHelper->getI18n("base-network");

// get settings
$system = $cceClient->getObject("System");

$default_page = 'primarySettings';
if($product->isRaq()) {
	$pages = array($default_page, 'aliasSettings');
} else {
	$pages = array($default_page);
}

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$block = $factory->getPagedBlock("tcpIpSettings", $pages);
$block->processErrors($serverScriptHelper->getErrors());

if (isset($view))
	$block->setSelectedId($view);

// host and domain names
$hostfield = $factory->getVerticalCompositeFormField(array(
			   $factory->getLabel("hostNameField"),
			   $factory->getDomainName("hostNameField", $system["hostname"])));
$domainfield = $factory->getVerticalCompositeFormField(array(
				 $factory->getLabel("domainNameField"),
				 $factory->getDomainName("domainNameField", $system["domainname"])));


$fqdn =& $factory->getCompositeFormField(array($hostfield, $domainfield), '&nbsp.&nbsp');

$block->addFormField(
	$fqdn,
	$factory->getLabel("enterFqdn"),
	$default_page
);

$dns = $factory->getIpAddressList("dnsAddressesField", $system["dns"]);
$dns->setOptional(true);
$block->addFormField(
  $dns,
  $factory->getLabel("dnsAddressesField"),
  $default_page
);

if ($product->isRaq()) {
	$gw = $factory->getIpAddress("gatewayField", $system["gateway"]);
	$gw->setOptional(true);
	$block->addFormField($gw, $factory->getLabel("gatewayField"), $default_page);
}

// real interfaces
// ascii sorted, this may be a problem if there are more than 10 interfaces
$interfaces = $cceClient->findx('Network', array('real' => 1), array(),
				'ascii', 'device');
$devices = array();
$deviceList = array();
$devnames = array();
$i18n = $factory->getI18n();
$admin_if = '';
for ($i = 0; $i < count($interfaces); $i++)
{
	$is_admin_if = false;
	$iface = $cceClient->get($interfaces[$i]);
	$device = $iface['device'];
	
	// save the devices and strings for javascript fun
	$deviceList[] = $device;
	$devices[] = "'$device'";    
	$devnames[] = "'" . $i18n->getJs("[[base-network.interface$device]]") . "'";

	if ($iface["enabled"]) 
	{
		$ipaddr = $iface["ipaddr"];
		$netmask = $iface["netmask"];
	} 
	else 
	{
		$ipaddr = "";
		$netmask = "";
	}

	if ($SERVER_ADDR == $ipaddr)
	{
	    $admin_if = $device;
	    $is_admin_if = true;
	}

	$block->addDivider(
	        $factory->getLabel("interface$device", false), 
	        $default_page);

	// With IP Pooling enabled, display the IP field with a 
	// range of possible choices
	list($sysoid) = $cceClient->find("System");
	$net_opts = $cceClient->get($sysoid, "Network");
	if ($net_opts["pooling"]) {
		$range_strings = array();
		$oids = $cceClient->findx('IPPoolingRange', array(), array(), 'old_numeric', 'creation_time');
		foreach ($oids as $oid) {
			$range = $cceClient->get($oid);
			$range_strings[] = $range['min'] . ' - ' . $range['max'];
		}
		$string = arrayToString($range_strings);
		$ip = $factory->getIpAddress("ipAddressField$device", $ipaddr);
		$ip->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));

		if ($device != 'eth0') {
			$ip->setEmptyMessage($i18n->getJs('ipAddressField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
		}

		// IP is optional, if it isn't the admin iface or eth0
		if (!$is_admin_if && ($device != 'eth0'))
			$ip->setOptional(true);

		$mylabel = $factory->getLabel("[[base-network.valid_ranges]]");
		$mylabel->setDescription($factory->i18n->get('[[base-network.valid_ranges_help]]'));
		$range_list = $factory->getCompositeFormField(
			  array($mylabel,
				$factory->getTextList("valid_ranges", $string, "r")
				),
			  "&nbsp;"
			  );
		$range_list->setAlignment("TOP");
		$ip_field =& $factory->getVerticalCompositeFormField(array($ip, $range_list));
		$ip_field->setId("ipAddr");
		$ip_field->setAlignment("LEFT");

	} else {
	  
		$ip_field =& $factory->getIpAddress("ipAddressField$device", $ipaddr);
		$ip_field->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));
		if ($device != 'eth0') {
			$ip_field->setEmptyMessage($i18n->getJs('ipAddressField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
		}

		// IP not optional for the admin interface and for eth0
		if (!$is_admin_if && ($device != 'eth0'))
			$ip_field->setOptional(true);
	}

	// use special help text for eth0 and eth1 to keep the qube happy
	$ip_label = 'ipAddressField';
	$nm_label = 'netMaskField';
	if ($device == 'eth0')
	{
	    $ip_label = 'ipAddressField1';
	    $nm_label = 'netMaskField1';
	}
	else if ($device == 'eth1')
	{
	    $ip_label = 'ipAddressField2';
	    $nm_label = 'netMaskField2';
	}
	
	$block->addFormField(
	    $ip_field,
	    $factory->getLabel($ip_label, true, 
	                array(), array('name' => "[[base-network.help$device]]")),
	    $default_page
	);

	$netmask_field =& $factory->getIpAddress("netMaskField$device", $netmask);
	$netmask_field->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
	if ($device != 'eth0') {
		$netmask_field->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));
	}

	// Netmask is not optional for the admin iface and for eth0
	if (!$is_admin_if && ($device != 'eth0'))
	    $netmask_field->setOptional(true);

	$block->addFormField(
	    $netmask_field,
	    $factory->getLabel($nm_label, true,
	                array(), array('name' => "[[base-network.help$device]]")),
	    $default_page
	);

	$block->addFormField(
	    $factory->getMacAddress("macAddressField$device", $iface["mac"], "r"),
	    $factory->getLabel("macAddressField"),
	    $default_page
	);

	// check for aliases, so a warning can be issued when disabling an
	// interface with aliases on it
	$aliases = $cceClient->findx('Network', array('real' => 0),
					array('device' => "^$device:"));
	if (count($aliases) > 0)
	{
		$block->addFormField(
			$factory->getBoolean("hasAliases$device", 1, ''));
	}
	else
	{
		$block->addFormField(
			$factory->getBoolean("hasAliases$device", 0, ''));
	}

	// retain orginal information
	$block->addFormField(
	    $factory->getIpAddress("ipAddressOrig$device", $ipaddr, ""), 
	    '',
	    $default_page
	    );
	$block->addFormField(
	    $factory->getIpAddress("netMaskOrig$device", $netmask, ""), 
	    "",
	    $default_page
	    );
	$block->addFormField(
	    $factory->getTextField("bootProtoField$device", $iface["bootproto"], ""),
	    "",
	    $default_page
	    );
	$block->addFormField(
	    $factory->getBoolean("enabled$device", $iface["enabled"], ""),
	    "",
	    $default_page
	    );
}

// add a hidden field indicating which interface is the admin interface
$block->addFormField($factory->getTextField('adminIf', $admin_if, ''));
$block->addFormField(
	    $factory->getTextField('deviceList', 
	                    $cceClient->array_to_scalar($deviceList), ''));

// only do this when the user wants to view it since it could take a while
if ($block->getSelectedId() == 'aliasSettings')
{
	// add scrollist of aliases
	$alias_list = $factory->getScrollList(
	                    '',
	                    array(
	                        'aliasName',
	                        'aliasIpaddr',
	                        'aliasNetmask',
	                        'aliasActions'
	                        ),
	                    array(0, 1, 2)
	                    );

	$sort_map = array('device', 'ipaddr', 'netmask');
	$networks = $cceClient->findx(
	                'Network', array('real' => 0), array(),
	                'ascii', $sort_map[$alias_list->getSortedIndex()]);

	if ($alias_list->getSortOrder() == 'descending')
	    $networks = array_reverse($networks);

	$alias_list->setSortEnabled(false);
	$alias_list->setAlignments(array('left', 'right', 'right', 'center'));
	$alias_list->setColumnWidths(array('', '', '', '20'));
	$num_entries = count($networks);
	$alias_list->setEntryNum($num_entries);
	
	$page_length = 15;
	$alias_list->setLength($page_length);
	$start = $alias_list->getPageIndex() * $page_length;
	for ($i = $start, $j = $start; 
	        $j < $num_entries && $j < ($start + $page_length); $i++, $j++)
	{
	    // must be an alias
	    $alias = $cceClient->get($networks[$i]);
	    $device_info = split(':', $alias['device']);
	    $alias_name = $i18n->interpolateHtml('[[base-network.alias' .
	                        $device_info[0] . ']]',
	                        array('num' => $device_info[1]));

	    $device = $factory->getTextField("dev$i", $alias_name, 'r');
	    $dev_ipaddr = $factory->getTextField("ip$i", $alias['ipaddr'], 'r');
	    $dev_netmask = $factory->getTextField("nm$i", $alias['netmask'], 'r');

	    $device->setPreserveData(false);
	    $dev_ipaddr->setPreserveData(false);
	    $dev_netmask->setPreserveData(false);

	    $alias_list->addEntry(
	        array(
	            $device,
	            $dev_ipaddr,
	            $dev_netmask,
	            $factory->getCompositeFormField(
	                array(
	                    $factory->getModifyButton(
	                        "/base/network/aliasModify.php?_oid=$networks[$i]"
	                        ),
	                    $factory->getRemoveButton(
	                        "/base/network/aliasRemove.php?_oid=$networks[$i]"
	                        )
	                    )
	                )
	            ),
	        '', false, $j);
	}
	
	$alias_list->addButton(
	    $factory->getButton('/base/network/aliasModify.php', 'addAliasButton'));

}	   

// only add the save button if looking at primary settings
if (!isset($alias_list))
	$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$routeButton = $factory->getButton("/base/network/routes.php", "routes");
$portFwdButton = $factory->getButton("/base/portforward/list.php", "portFwd");

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<table>
<tr>
<td><?php print($routeButton->toHtml()); ?></td>
<td><?php print($portFwdButton->toHtml()); ?></td>
</tr>
</table>
<BR>

<?php 
print $block->toHtml();

if (isset($alias_list))
{
	print $alias_list->toHtml();
}
?>
<SCRIPT  LANGUAGE="javascript">
	var devices = new Array(<? print(implode(', ', $devices)); ?>);
	var devnames = new Array(
			<? print(implode(",\n\t\t\t", $devnames) . "\n"); ?>
			);
	var no_dhcp_confirm = '<?php print($i18n->getJs("confirmNoDhcp", ""))?>';
	var migrate_confirm = '<?php print($i18n->getJs('confirmMigrateAliases')) ?>';
</SCRIPT>


<SCRIPT LANGUAGE="javascript">
var oldFormSubmitHandler = document.<?php print($formId)?>.onsubmit;

function formSubmitHandler() 
{
	if(!oldFormSubmitHandler())
	    return false;

	var form = document.<?php print($formId)?>;

	// check to see if a dhcped interface is being changed
	// or if one field is filled in for an iface, but not the other
	for (var i = 0; i < devices.length; i++)
	{
	    var ipaddr = form.elements['ipAddressField' + devices[i]];
	    var netmask = form.elements['netMaskField' + devices[i]];
	    var old_ip = form.elements['ipAddressOrig' + devices[i]];
	    var old_nm = form.elements['netMaskOrig' + devices[i]];
	    var boot_proto = form.elements['bootProtoField' + devices[i]];
	    var enabled = form.elements['enabled' + devices[i]];
	    var aliases = form.elements['hasAliases' + devices[i]];

	    // both fields must be filled in or none
	    if (!netmaskSubmitHandler(ipaddr, netmask))
	        return false;

	    if(enabled.value == '1' && boot_proto.value == "dhcp" && 
	        ((ipaddr.value != old_ip.value) || (netmask.value != old_nm.value)))
	    {
	        // restore settings if user do not want to turn off DHCP
	        var confirm_msg = top.code.string_substitute(no_dhcp_confirm, 
	                            '[[VAR.interface]]', devnames[i],
	                            '[[VAR.interface]]', devnames[i]);

	        if(!confirm(confirm_msg))
	        {
	            ipaddr.value = old_ip.value;
	            netmask.value = old_nm.value;
	        }
	    }
	    else
	        boot_proto.value = "none";

		// ask for confirmation to migrate aliases
		if (enabled.value == '1' && aliases.value == '1'
				&& ipaddr.value == '')
		{
			var migrate_msg = top.code.string_substitute(migrate_confirm,
								'[[VAR.interface]]', devnames[i],
								'[[VAR.interface]]', devnames[i]);
								
			if (!confirm(migrate_msg))
			{
				ipaddr.value = old_ip.value;
				netmask.value = old_nm.value;
			}
		}
	}
<?
if ($admin_if != '')
{
?>
	if(form.ipAddressField<? print($admin_if); ?>.value !=
	    form.ipAddressOrig<? print($admin_if); ?>.value)
	{
	    alert("<?php print($i18n->getJs("ethernetChanged"))?>");
	}
<?
}  // end if ($admin_if != '')
?>
	return true;
}

document.<?php print($formId)?>.onsubmit = formSubmitHandler;

function netmaskSubmitHandler(ip, nm) 
{
	if(ip.value == "" && nm.value != "") 
	{
	    top.code.error_invalidElement(ip, 
	            "<?php print($i18n->getJs("netMaskIpAddressMismatch")) ?>");
	    return false;
	}
	if(ip.value != "" && nm.value == "") 
	{
	    top.code.error_invalidElement(nm, 
	            "<?php print($i18n->getJs("ipAddressNetMaskMismatch")) ?>");
	    return false;
	}

	return true;
}

</SCRIPT>

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

