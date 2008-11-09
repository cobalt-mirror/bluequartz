<?
# $Id: arkeia.php,v 1.15.2.1 2002/03/25 22:48:16 bservies Exp $
#
# Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
#
include_once('ServerScriptHelper.php');
include_once('Error.php');

#
# main
#

# A reference to this page to clean up some code
$url = '/knox/arkeia/arkeia.php';

#
# Declare an array to hold errors that will be reported to the UI by displaying
# the page.
#
$errors = array();

# Get connected to CCE
$helper = new ServerScriptHelper();
$cce = $helper->getCceClient();

if (isset($action) && $action == 'save') {
	# We are saving the changes from the post before re-displaying
	$cce->setObject('System', array('enabled' => $enabled,
	    'server' => $server, 'port' => $port), 'Arkeia');
	$errors = $cce->errors();
}

# Acquire the Arkeia configuration object to get the current values
$obj = $cce->getObject('System', array(), 'Arkeia');
$errors = array_merge($errors, $cce->errors());
if ($obj == null) {
	# Create an object with the appropriate default values
	$obj = array(
	    'enabled' => 0,
	    'port' => 617,
	    'server' => ''
	);
}


#
# Display the page.  UIFC takes care of switching between post'ed information
# and the given values, which is handy in the case of post's.
#

# Connect to UIFC and create a factory to generate the page.
$factory = $helper->getHtmlComponentFactory('knox-arkeia',
    $url . '?action=save');
$page = $factory->getPage();
$block = $factory->getPagedBlock('settings');

# Create the enabled, host, and port widgets on the appropriate pages
$block->addFormField($factory->getBoolean('enabled', $obj['enabled']),
    $factory->getLabel('enabled'));
$block->addFormField($factory->getNetAddress('server', $obj['server']),
    $factory->getLabel('server'));

#
# Separate the port settings to indicate they are for advanced use.  They
# would be on a separate page, but with only 3 items, that is a waste...
#
$block->addDivider($factory->getLabel('firewall', false));
$block->addFormField($factory->getInteger('port', $obj['port'], 1),
    $factory->getLabel('port'));

# Add the buttons necessary to store the new data
$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton($url));

# Handle any errors that the page may have.
$block->processErrors($errors);

# Emit the page (cough up the html!)
print($page->toHeaderHtml());
print($block->toHtml());
print($page->toFooterHtml());

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
