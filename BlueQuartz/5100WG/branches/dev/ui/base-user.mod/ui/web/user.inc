<?
// $Id: user.inc 201 2003-07-18 19:11:07Z will $
// Copyright 2001 Sun Microsystems, Inc. All rights reserved.
// Helper methods for user manipulation

// reserved user login names that are not allowed to be manipulated
// like regular users, e.g. create/destroy in the UI 
$_reserved_usernames = array('admin'); 

// isReservedUsername: checks if login name is restricted from
//  normal manipulation in the UI
// param: name - login name to check
// returns: true|false
function isReservedUsername($name) {
	global $_reserved_usernames;
	return in_array(strtolower($name), $_reserved_usernames);
}


?>
