<?php
/*
 * $Id: desktopcontrolHandler.php,v 1.1.2.2 2002/01/12 02:38:59 pbaltz Exp $
 * Copyright 2001, 2002 Sun Microsystems, Inc.  All rights reserved.
 */

include_once("ServerScriptHelper.php");
include_once("Error.php");

$helper = new ServerScriptHelper();
$cce = $helper->getCceClient();
$errors = array();
$lock_script = '/usr/sausalito/sbin/cce_lock.pl';

if ($lock_desktop) {
	/*
	 * We are attempting to lock the desktop.  First, get the current
	 * state for rollback
	 */
	$sysobj = $cce->getObject('System', array(), 'DesktopControl');

	# Set the lock state in CCE
	$cce->setObject('System', array('lock' => $lock_desktop),
			'DesktopControl');

	/*
	 * If the desktop is already locked, the above will fail.  Let it.
	 * It is probably a race with the command line too and there is nothing
	 * we should do about it here or we may interfere with a backup or 
	 * some such.
	 */
	$errors = $cce->errors();
	if (count($errors) == 0) {
		/*
		 * We successfully set the lock bit.  Now actually lock the
		 * desktop by running a cce wrapped script as root.
		 */
		$lock_cmd = "$lock_script --lock --reason=[[base-backupcontrol.locked]]";
		$ret = $helper->shell($lock_cmd, $output, 'root');

		if ($ret != 0) {
			# Suspending failed.  Rollback the lock bit.
			$cce->setObject('System', array('lock' => $sysobj['lock']),
			    'DesktopControl');
			array_merge($errors, $cce->errors()); 
		}				
	}
} else {
	// We are attempting to unlock the desktop.  Unlock cce first.
	$ret = $helper->shell("$lock_script --unlock", $output, 'root');

	if ($ret == 0) {
		// Try to unset the lock bit.
		$cce->setObject('System', array('lock' => $lock_desktop),
				'DesktopControl');
		array_merge($errors, $cce->errors()); 
	}
}

// Return to the calling page and process errors.
print($helper->toHandlerHtml('/base/backupcontrol/desktopcontrol.php', $errors,
			     false));

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
