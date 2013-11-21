<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: ethernet.php,v 1.1.1.2.2.3 Mon 10 Aug 2009 11:02:43 AM CEST mstauber Exp $

include_once('ServerScriptHelper.php');
include_once('Product.php');

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

// Protect certain form fields read-only inside VPS's:
if (is_file("/proc/user_beancounters")) {
    $fieldprot = "r";
}
else {
    $fieldprot = "rw";
}

// Are we running on AWS?
if (is_file("/etc/is_aws")) {
    $is_aws = "1";
}
else {
    $is_aws = "0";
}

$cceClient = $serverScriptHelper->getCceClient();
$product = new Product( $cceClient );
$factory = $serverScriptHelper->getHtmlComponentFactory("base-network", "/base/network/ethernetHandler.php");
$i18n = $serverScriptHelper->getI18n("base-network");

// get settings
$system = $cceClient->getObject("System");

$default_page = 'primarySettings';
if (($fieldprot == "rw") && ($is_aws == "0")) {
    // Show "Interface Aliasses" if not inside a VPS:
    $pages = array($default_page, 'aliasSettings');
}
else {
    // Hide "Interface Aliasses" inside a VPS:
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
			   $factory->getDomainName("hostNameField", $system["hostname"], $fieldprot),
			   $factory->getLabel("hostNameField")));
$domainfield = $factory->getVerticalCompositeFormField(array(
				 $factory->getDomainName("domainNameField", $system["domainname"], $fieldprot),
				 $factory->getLabel("domainNameField")));


$fqdn = $factory->getCompositeFormField(array($hostfield, $domainfield), '&nbsp;.&nbsp;');

$block->addFormField(
	$fqdn,
	$factory->getLabel("enterFqdn"), 
	$default_page
);

$dns = $factory->getIpAddressList("dnsAddressesField", $system["dns"], $fieldprot);
$dns->setOptional(true);
$block->addFormField(
  $dns,
  $factory->getLabel("dnsAddressesField"),
  $default_page
);

if (($product->isRaq()) && ($is_aws == "0")) {
	$gw = $factory->getIpAddress("gatewayField", $system["gateway"], $fieldprot);
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
for ($i = 0; $i < count($interfaces); $i++) {

	$is_admin_if = false;
	$iface = $cceClient->get($interfaces[$i]);
	$device = $iface['device'];
	
	// save the devices and strings for javascript fun
	$deviceList[] = $device;
	$devices[] = "'$device'";    
	$devnames[] = "'" . $i18n->getJs("[[base-network.interface$device]]") . "'";

        // Devices:
        $dev[$device] = array (
                        'ipaddr' => $iface["ipaddr"],
                        'netmask' => $iface["netmask"],
                        'mac' => $iface["mac"],
                        'device' => $device,
                        'bootproto' => $iface["bootproto"],
                        'enabled' => $iface["enabled"]
                        );

}

if ($dev['eth0']) {
    $ipaddr = $dev['eth0']['ipaddr'];
    $netmask = $dev['eth0']['netmask'];
    $device = $dev['eth0']['device'];
    $mac = $dev['eth0']['mac'];
    $enabled = $dev['eth0']['enabled'];
    $bootproto = $dev['eth0']['bootproto'];
    
    $ip_label = 'ipAddressField1';
    $nm_label = 'netMaskField1';

    $block->addDivider(
            $factory->getLabel("interface$device", false),
            $default_page);

    if ($is_aws == "0") {
	$devprot = "rw";
    }
    else {
	$devprot = "r";
    }

    $ip_field0 = $factory->getIpAddress("ipAddressField$device", $ipaddr, $devprot);
    $ip_field0->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));

    $block->addFormField(
            $ip_field0,
            $factory->getLabel($ip_label, true,
                        array(), array('name' => "[[base-network.help$device]]")),
            $default_page
        );

    $netmask_field0 = $factory->getIpAddress("netMaskField$device", $netmask, $devprot);
    $netmask_field0->setInvalidMessage($i18n->getJs('netMaskField_invalid'));

    // Netmask is not optional for the admin iface and for eth0
    $netmask_field0->setOptional(false);
    
    $block->addFormField(
            $netmask_field0,
            $factory->getLabel($nm_label, true,
                        array(), array('name' => "[[base-network.help$device]]")),
            $default_page
        );

    $block->addFormField(
            $factory->getMacAddress("macAddressField$device", $mac, "r"),
            $factory->getLabel("macAddressField"),
            $default_page
        );

    // retain orginal information
    $block->addFormField(
            $factory->getBoolean("hasAliases$device", 0, ''));

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
            $factory->getTextField("bootProtoField$device", $bootproto, ""),
            "",
            $default_page
            );
    $block->addFormField(
            $factory->getBoolean("enabled$device", $enabled, ""),
            "",
            $default_page
            );

}
if ($dev['eth1']) {
    $ipaddr = $dev['eth1']['ipaddr'];
    $netmask = $dev['eth1']['netmask'];
    $device = $dev['eth1']['device'];
    $mac = $dev['eth1']['mac'];
    $enabled = $dev['eth1']['enabled'];
    $bootproto = $dev['eth1']['bootproto'];

    if ($enabled == "0") {
	$ipaddr = "";
	$netmask = "";
    }
    
    $ip_label = 'ipAddressField1';
    $nm_label = 'netMaskField1';

    $block->addDivider(
            $factory->getLabel("interface$device", false),
            $default_page);

    $ip_field1 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
    $ip_field1->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));

    $ip_field1->setOptional(true);

    $block->addFormField(
            $ip_field1,
            $factory->getLabel($ip_label, true,
                        array(), array('name' => "[[base-network.help$device]]")),
            $default_page
        );

    $netmask_field1 = $factory->getIpAddress("netMaskField$device", $netmask);
    $netmask_field1->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
    $netmask_field1->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));

    $netmask_field1->setOptional(true);
    
    $block->addFormField(
            $netmask_field1,
            $factory->getLabel($nm_label, true,
                        array(), array('name' => "[[base-network.help$device]]")),
            $default_page
        );

    $block->addFormField(
            $factory->getMacAddress("macAddressField$device", $mac, "r"),
            $factory->getLabel("macAddressField"),
            $default_page
        );

    // retain orginal information
    $block->addFormField(
            $factory->getBoolean("hasAliases$device", 0, ''));
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
            $factory->getTextField("bootProtoField$device", $bootproto, ""),
            "",
            $default_page
            );
    $block->addFormField(
            $factory->getBoolean("enabled$device", $enabled, ""),
            "",
            $default_page
            );

}
if ($dev['eth2']) {
    $ipaddr = $dev['eth2']['ipaddr'];
    $netmask = $dev['eth2']['netmask'];
    $device = $dev['eth2']['device'];
    $mac = $dev['eth2']['mac'];
    $enabled = $dev['eth2']['enabled'];
    $bootproto = $dev['eth2']['bootproto'];
    
    if ($enabled == "0") {
	$ipaddr = "";
	$netmask = "";
    }

    $ip_label = 'ipAddressField';
    $nm_label = 'netMaskField';

    $block->addDivider(
            $factory->getLabel("interface$device", false),
            $default_page);

    $ip_field2 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
    $ip_field2->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));

    $ip_field2->setOptional(true);

    $block->addFormField(
            $ip_field2,
            $factory->getLabel($ip_label, true,
                        array(), array('name' => "[[base-network.help$device]]")),
            $default_page
        );

    $netmask_field2 = $factory->getIpAddress("netMaskField$device", $netmask);
    $netmask_field2->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
    $netmask_field2->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));

    $netmask_field2->setOptional(true);
    
    $block->addFormField(
            $netmask_field2,
            $factory->getLabel($nm_label, true,
                        array(), array('name' => "[[base-network.help$device]]")),
            $default_page
        );

    $block->addFormField(
            $factory->getMacAddress("macAddressField$device", $mac, "r"),
            $factory->getLabel("macAddressField"),
            $default_page
        );

    // retain orginal information
    $block->addFormField(
            $factory->getBoolean("hasAliases$device", 0, ''));
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
            $factory->getTextField("bootProtoField$device", $bootproto, ""),
            "",
            $default_page
            );
    $block->addFormField(
            $factory->getBoolean("enabled$device", $enabled, ""),
            "",
            $default_page
            );

}
if ($dev['eth3']) {
    $ipaddr = $dev['eth3']['ipaddr'];
    $netmask = $dev['eth3']['netmask'];
    $device = $dev['eth3']['device'];
    $mac = $dev['eth3']['mac'];
    $enabled = $dev['eth3']['enabled'];
    $bootproto = $dev['eth3']['bootproto'];
    
    if ($enabled == "0") {
	$ipaddr = "";
	$netmask = "";
    }

    $ip_label = 'ipAddressField';
    $nm_label = 'netMaskField';

    $block->addDivider(
            $factory->getLabel("interface$device", false),
            $default_page);

    $ip_field3 = $factory->getIpAddress("ipAddressField$device", $ipaddr);
    $ip_field3->setInvalidMessage($i18n->getJs('ipAddressField_invalid'));

    $ip_field3->setOptional(true);

    $block->addFormField(
            $ip_field3,
            $factory->getLabel($ip_label, true,
                        array(), array('name' => "[[base-network.help$device]]")),
            $default_page
        );

    $netmask_field3 = $factory->getIpAddress("netMaskField$device", $netmask);
    $netmask_field3->setInvalidMessage($i18n->getJs('netMaskField_invalid'));
    $netmask_field3->setEmptyMessage($i18n->getJs('netMaskField_empty', 'base-network', array('interface' => "[[base-network.interface$device]]")));

    $netmask_field3->setOptional(true);
    
    $block->addFormField(
            $netmask_field3,
            $factory->getLabel($nm_label, true,
                        array(), array('name' => "[[base-network.help$device]]")),
            $default_page
        );

    $block->addFormField(
            $factory->getMacAddress("macAddressField$device", $mac, "r"),
            $factory->getLabel("macAddressField"),
            $default_page
        );

    // retain orginal information
    $block->addFormField(
            $factory->getBoolean("hasAliases$device", 0, ''));
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
            $factory->getTextField("bootProtoField$device", $bootproto, ""),
            "",
            $default_page
            );
    $block->addFormField(
            $factory->getBoolean("enabled$device", $enabled, ""),
            "",
            $default_page
            );
}

// add a hidden field indicating which interface is the admin interface
$block->addFormField($factory->getTextField('adminIf', 'eth0', ''));
$block->addFormField(
	    $factory->getTextField('deviceList', 
	                    $cceClient->array_to_scalar($deviceList), ''));
// only do this when the user wants to view it since it could take a while

if ($block->getSelectedId() == 'aliasSettings')
  {
    // add scrollist of aliases
    $alias_list = $factory->getScrollList(
					  ' ',
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
	$device_info = preg_split('/:/', $alias['device']);
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

// only add the save button if looking at primary settings AND we're not inside a VPS:
if ((!isset($alias_list) && ($fieldprot == "rw"))) {
	$block->addButton($factory->getSaveButton($page->getSubmitAction()));
}

$routeButton = $factory->getButton("/base/network/routes.php", "routes");

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($routeButton->toHtml()); ?>
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
