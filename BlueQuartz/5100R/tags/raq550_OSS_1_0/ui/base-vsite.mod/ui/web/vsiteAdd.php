<?php
// Copyright Sun Microsystems, Inc. 2001
// $Id: vsiteAdd.php,v 1.38.2.1 2002/01/12 05:00:00 pbaltz Exp $
// vsiteAdd.php
// display the form for creating a new virtual site

include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");
include_once("ArrayPacker.php");

$helper = new ServerScriptHelper($sessionId);
$factory =& $helper->getHtmlComponentFactory("base-vsite", 
                "/base/vsite/vsiteAddSave.php");
$cce =& $helper->getCceClient();

/*
 *  DATA PRESERVATION
 *  All display scripts which use PagedBlock containers must get 
 *  the array of Error objects from ServerScriptHelper.  This can serve 
 *  a few different purposes.  In order to be able to mark individual 
 *  fields, PagedBlock has a function that takes the
 *  array of Error objects as it's argument (see below) and sorts it out into
 *  field specific errors and general errors.
 */
$errors = $helper->getErrors();

/*
 *  DATA PRESERVATION
 *  One other possible use of the Errors array is to determine whether 
 *  any information needs to be read from CCE.  In the case of Vsite 
 *  addition, if there are errors present then there is no need to 
 *  get information from CCE for most things, because the data that was 
 *  in the fields, when the user clicked save, are available as
 *  global variables.  This should give a slight performance gain, but it isn't
 *  necessary for things to work correctly.
 */

list($sysoid) = $cce->find("System");
if (count($errors) == 0)
{    
    $vsiteDefaults = $cce->get($sysoid, "VsiteDefaults");
}


$defaultPage = "basicSettingsTab";

$settings =& $factory->getPagedBlock("newVsiteSettings", 
                array($defaultPage));

/*
 *  DATA PRESERVATION
 *  In the case of PagedBlock containers, it is necessary to call
 *  process_errors on the Errors array to seperate the field specific errors
 *  from the general errors that may have occurred.  For non-PagedBlock
 *  containers, it would be necessary to do something like the following
 *  after doing the "print $page->toHeaderHtml();" at the end of the script
 *  if the page needs to use data preservation:
 *  
 *  print "<SCRIPT LANGUAGE=\"javascript\">";
 *  print $helper->toErrorJavaScript($helper->getErrors());
 *  print "</SCRIPT>";
 *
 *  Hopefully, any new container classes will take care of this themselves.
 */
$settings->processErrors($errors);

// IP address
/*
 *  DATA PRESERVATION
 *  Since data preservation is done on an individual basis by each FormField
 *  object, there is no need to worry about passing the right value to the
 *  get<FormField> methods (or the FormField constructor if you use UIFC without
 *  the aid of HtmlComponentFactory).  
 *
 *  Data preservation is the default, but
 *  it can be turned off for individual FormField objects by calling the setPreserveData
 *  method.  In the example below for an IpAddress field, calling
 *  "$ip_address->setPreserveData(false);" would turn off data preservation
 *  for the ipAddr field.
 *  A subsequent call to "$ip_address->getValue();" would then return the 
 *  value given when the field was created, or the value given to it
 *  by calling "$ip_address->setValue($new_value);".  Remember to read data from CCE, if
 *  necessary, for fields where data preservation is not desired.
 *
 *  To determine if data preservation is on for a FormField object, 
 *  call "$formField->isDataPreserved();".  This will return true, if user entered
 *  data will be given priority, and it will return false if data preservation
 *  is turned off.
 */

// With IP Pooling enabled, display the IP field with a 
// range of possible choices
$net_opts = $cce->get($sysoid, "Network");
if ($net_opts["pooling"]) {
	$range_strings = array();
	$oids = $cce->findx('IPPoolingRange', array(), array(), 'old_numeric', 'creation_time');
	foreach ($oids as $oid) {
		$range = $cce->get($oid);
		$range_strings[] = $range['min'] . ' - ' . $range['max'];
	}
	$string = arrayToString($range_strings);
	$ip = $factory->getIpAddress("ipAddr", $vsiteDefaults["ipaddr"]);
	$mylabel = $factory->getLabel("[[base-network.valid_ranges]]");
	$mylabel->setDescription($factory->i18n->get('[[base-network.valid_ranges_help]]'));
	$range_list = $factory->getCompositeFormField(
						      array($mylabel,
							    $factory->getTextList("valid_ranges", $string, "r")
							    ),
						      "&nbsp;"
						      );
	$range_list->setAlignment("TOP");
	$ip_address = $factory->getVerticalCompositeFormField(array($ip, $range_list));
	$ip_address->setId("ipAddr");
	$ip_address->setAlignment("LEFT");
} else {
	// IP Address, without ranges
	$ip_address = $factory->getIpAddress("ipAddr", $vsiteDefaults["ipaddr"]);
}
// IP Address
$settings->addFormField(
        $ip_address,
        $factory->getLabel("ipAddr"),
        $defaultPage
        );

// host and domain names
$hostfield = $factory->getVerticalCompositeFormField(array(
			     $factory->getDomainName("hostname", $vsiteDefaults["hostname"]),
			     $factory->getLabel("hostName")));
$domainfield = $factory->getVerticalCompositeFormField(array(
			     $factory->getDomainName("domain", $vsiteDefaults["domain"]),
			     $factory->getLabel("domain")));
$fqdn =& $factory->getCompositeFormField(array($hostfield, $domainfield), '&nbsp.&nbsp');

$settings->addFormField(
        $fqdn,
        $factory->getLabel("enterFqdn"),
        $defaultPage
        );

// web server aliases
$webAliasesField = $factory->getDomainNameList("webAliases", $vsiteDefaults["webAliases"]);
$webAliasesField->setOptional(true);

$settings->addFormField(
        $webAliasesField,
        $factory->getLabel("webAliases"),
        $defaultPage
        );

// mail server aliases
$mailAliasesField = $factory->getDomainNameList("mailAliases", $vsiteDefaults["mailAliases"]);
$mailAliasesField->setOptional(true);
$settings->addFormField(
        $mailAliasesField,
        $factory->getLabel("mailAliases"),
        $defaultPage
        );

// site email catch-all
$mailCatchAllField = $factory->getEmailAddress(
	"mailCatchAll", 
	$vsiteDefaults["mailCatchAll"], 
	1);
$mailCatchAllField->setOptional(true);
$settings->addFormField(
	$mailCatchAllField,
	$factory->getLabel("mailCatchAll"),
	$defaultPage
	);

// site quota
// Determine maximum site storage
$diskoids = $cce->find("Disk", array('isHomePartition' => 1, 'mounted' => 1));

$home_oid = 0;
$page =& $factory->getPage();
if (count($diskoids) > 1)
{
    $form =& $page->getForm();
    $form_id = $form->getId();
    $partitionField = $factory->getMultiButton('');
    $partitionField->setId('stupidButton');
    for($i = 0; $i < count($diskoids); $i++)
    {
        // DATA PRESERVATION
        // options don't have ids to track values unless they are radio or checkbox
        // so need to read from CCE here every time
        $disk = $cce->get($diskoids[$i]);

        // add the action to select when selecting this disk
        $action = "javascript: selectPartition($diskoids[$i], '$disk[mountPoint]', 1);";

        if ($disk['mountPoint'] == '/home')
        {
            $home_oid = $diskoids[$i];
            $disk['label'] = $factory->i18n->get('[[base-vsite.defaultHome]]');
            if (!isset($selected_oid))
            {
                $partitionField->setSelectedIndex($i);
                $volume = $disk['mountPoint'];
                $action = "javascript: selectPartition($diskoids[$i], '$volume', 0);";
            }
        }
        
        if (isset($selected_oid) && $selected_oid == $diskoids[$i])
        {
            $partitionField->setSelectedIndex($i);
            $volume = $disk['mountPoint'];
            $action = "javascript: selectPartition($diskoids[$i], '$volume', 0);";
        }
        
        // add this partition to the button
        $partitionField->addAction($action, $disk['label']);
    }

    $soid_field = $factory->getTextField('selected_oid', 
                        (isset($selected_oid) ? $selected_oid : $home_oid),
                        '');
    $soid_field->setPreserveData(false);
    $settings->addFormField($soid_field);
    $volume_field = $factory->getTextField('volume', $volume, '');
    $volume_field->setPreserveData(false);
    $settings->addFormField($volume_field);
    $settings->addFormField($partitionField, 
                    $factory->getLabel("homePartition"), $defaultPage);
}
else  // only one disk so don't show this
{
    $home_oid = $diskoids[0];
    $settings->addFormField($factory->getTextField('volume', '/home', ''));
}

// update usage info for selected partition, because if the raid level changed
// for a raid array it could be wrong
$cce->set((!isset($selected_oid) ? $home_oid : $selected_oid), 
            '', array('refresh' => time()));

$partition = $cce->get((!isset($selected_oid) ? $home_oid : $selected_oid));
$partitionMax = floor( $partition["total"] / 1024 );
if($partitionMax) {
    $quotaField = $factory->getInteger("quota", $vsiteDefaults["quota"], 1, $partitionMax);
    $quotaField->showBounds(1);
} else {
    $quotaField = $factory->getInteger("quota", $vsiteDefaults["quota"], 0);
}

$settings->addFormField(
        $quotaField,
        $factory->getLabel("quota"),
        $defaultPage
        );

// max number of users site can have
$settings->addFormField(
        $factory->getInteger("maxusers", $vsiteDefaults["maxusers"], 1),
        $factory->getLabel("maxUsers"),
        $defaultPage
        );

// auto dns option
$settings->addFormField(
        $factory->getBoolean("dns_auto", $vsiteDefaults["dns_auto"]),
        $factory->getLabel("dns_auto"),
        $defaultPage
        );

// add all the other service options
$settings->addDivider($factory->getLabel('otherServices', false), $defaultPage);
// figure out which services are available
list($vsiteServices) = $cce->find('VsiteServices');
$autoFeatures = new AutoFeatures($helper);

// add all generic enabled/disabled type services detected above
$autoFeatures->display($settings, 'create.Vsite', 
        array(
            'CCE_SERVICES_OID' => $vsiteServices,
            'PAGED_BLOCK_DEFAULT_PAGE' => $defaultPage,
            'CAN_ADD_PAGE' => false
            ));

// add the buttons
$settings->addButton($factory->getSaveButton($page->getSubmitAction()));
$settings->addButton($factory->getCancelButton("/base/vsite/vsiteList.php"));

print $page->toHeaderHtml();

// add this function if more than one disk
if (count($diskoids) > 1)
{
?>
<SCRIPT LANGUAGE="javascript">
function selectPartition(oid, volume, reload)
{
	document.<?php print($form_id); ?>.volume.value = volume;
	document.<? print($form_id); ?>.selected_oid.value = oid;
	// run onsubmit or values for some widgets get lost
	if ((reload == 1) && document.<? print($form_id); ?>.onsubmit()) {
		document.<? print($form_id); ?>.action = '/base/vsite/vsiteAdd.php';
		document.<? print($form_id); ?>.submit();
	}
}
</SCRIPT>
<?
} // end if count($diskoids) > 1

print $settings->toHtml();
print $page->toFooterHtml();

// nice people say goodbye
$helper->destructor();
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
