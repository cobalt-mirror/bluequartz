<?php
// Author: Patrick Bose
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: User.php 3 2003-07-17 15:19:15Z will $

// This module replicates a subset of the User.pm functionality.

global $isUserDefined;
if($isUserDefined)
  return;
$isUserDefined = true;

include("CceClient.php");
include("Meta.php");

global $loginName;
global $sessionId;

// description: check the access rights of a user
// params:
//   user: - login of the user
//   access: systemAdministrator|siteAdministrator
// returns: 1|0
function user_authok( $user, $access ) {
	$cce = new CceClient();
	$cce->connect();
	$cce->auth($loginName, $sessionId);
	$cceUser = $cce->find("User", array("name" => $user, "uiRights" => $access));
	var_dump($cceUser);

	$cce->bye();
	error_log("{$cceUser[0]} $user $access");
	if ( ! $cceUser[0] ) 
		return 0;
	else
		return 1;
}

// description: tests if a user belongs to a site
// params:
//   user: username
//   group: sites group  
// returns: 0|1
function isUserInSite ( $user, $group ) {
		$userinfo = user_list_site($user);
		if ( $group == $userinfo[1] ) 
			return 1;
		return 0;
}

// description: returns the fqdn and group for a user
// params: 
//   user: username
// returns: fqdn, group of site user is associated with
function user_list_site ( $user ) {
	$obj = new Meta( array( "type" => "users" ) );
	$obj->retrieve($user);
	$group = $obj->get("vsite");

	$vobj = new Meta( array( "type" => "vsite") );
	$vobj->retrieve($group);
	$fqdn = $vobj->get("fqdn");

	return array( $fqdn, $group );
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

