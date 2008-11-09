<?
include("Error.php");

$ID = 0;
$FULLNAME = 1;
$EMAIL = 2;
$PHONE = 3;
$FAX = 4;
$HOMEPAGE = 5;
$ADDRESSES = 6;
$REMARK = 7;

function addressBookGetall($serverScriptHelper) {
	$ret = $serverScriptHelper->shell("/usr/sausalito/sbin/addressbook.pl getall", $output);
	if (!$ret) {
		throwError($serverScriptHelper, "[[base-addressbook.errorPrivateGetAll]]");
	}
	$addyStrings = explode ( "\n", $output);
	$addys = array();
	// get rid of the last \n
	array_pop($addyStrings);
	while (list($key, $val) = each($addyStrings)) {
		$addys[] = unescapeArray(explode( "\t", $val));
		
	}
	return $addys;
}

function addressBookGet($serverScriptHelper, $oid) {
	global $ID, $FULLNAME, $EMAIL, $PHONE, $FAX, $HOMEPAGE, $ADDRESSES, $REMARK;
	$addys = addressBookGetall($serverScriptHelper);
	for ($i=0;$i<count($addys);$i++) 
		if ($addys[$i][$ID] == $oid) 
			return $addys[$i];
	return array();
}

function addressBookModify( $serverScriptHelper, $ar) {
	global $ID, $FULLNAME, $EMAIL, $PHONE, $FAX, $HOMEPAGE, $ADDRESSES, $REMARK;
	escapeArray($ar);
	$ret = $serverScriptHelper->shell("/usr/sausalito/sbin/addressbook.pl modify '$ar[$ID]' '$ar[$FULLNAME]' '$ar[$EMAIL]' '$ar[$PHONE]' '$ar[$FAX]' '$ar[$HOMEPAGE]' '$ar[$ADDRESSES]' '$ar[$REMARK]'", $output);
	if (!$ret) {
		// an error has occured while modifying
		throwError($serverScriptHelper, "[[base-addressbook.errorPrivateModify]]");
	}
			

}
	
function addressBookAdd( $serverScriptHelper, $ar) {
	global $ID, $FULLNAME, $EMAIL, $PHONE, $FAX, $HOMEPAGE, $ADDRESSES, $REMARK;
	escapeArray($ar);
	$ret = $serverScriptHelper->shell("/usr/sausalito/sbin/addressbook.pl add '$ar[$FULLNAME]' '$ar[$EMAIL]' '$ar[$PHONE]' '$ar[$FAX]' '$ar[$HOMEPAGE]' '$ar[$ADDRESSES]' '$ar[$REMARK]'", $output);
	if (!$ret) {
		throwError($serverScriptHelper, "[[base-addressbook.errorPrivateAdd]]");
	}

}

function addressBookDrop( $serverScriptHelper, $oid)  {
	$ret = $serverScriptHelper->shell("/usr/sausalito/sbin/addressbook.pl drop '$oid'", $output);
	if (!$ret) {
		throwError($serverScriptHelper, "[[base-addressbook.errorPrivateDrop]]");
	}
}


function escapeArray(&$ar) {
	while (list($key, $val) = each($ar)) {
		//$tmp = str_replace("\\","\\\\ ",$val);	
		//$tmp = str_replace("\n","\\n",$tmp);
		//$ar[$key] = str_replace("\t","\\t",$tmp);
		$ar[$key] = rawurlencode($val);
	}
	return $ar;
}

function unescapeArray(&$ar) {
	while (list($key, $val) = each($ar)) {
		//$tmp = str_replace("\\t", "\t", $val);
		//$tmp = str_replace("\\n", "\n", $tmp);
		//$ar[$key] = str_replace("\\\\ ", "\\", $tmp);
		$ar[$key] = rawurldecode($val);
	}
	return $ar;
}

function throwError(&$serverScriptHelper, $error, $page = "/base/addressbook/AddressBookPrivate.php") {
	$i18n = $serverScriptHelper->getI18n("base-addressbook");
	$errorObj = new Error($i18n->get($error));
	print($serverScriptHelper->toHandlerHtml($page, array($errorObj)));	
	$serverScriptHelper->destructor();
	exit;
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

