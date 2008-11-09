<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: logoutHandler.php 1050 2008-01-23 11:45:43Z mstauber $

include_once("CceClient.php");

// connect to CCE
$cceClient = new CceClient();
if(!$cceClient->connect()) {
  error_log("logoutHandler.php: CCE is down");
  exit;
}

// end key
if ($HTTP_COOKIE_VARS["loginName"] && $HTTP_COOKIE_VARS["sessionId"]) {
	$cceClient->authkey( $HTTP_COOKIE_VARS["loginName"],
		$HTTP_COOKIE_VARS["sessionId"] );
	$cceClient->endkey();
}

// !!! this kills the script, but why???
// $cceClient->bye();

// clean up cookie
setcookie("loginName");
setcookie("sessionId");

header("cache-control: no-cache");
?>

<HTML>
<HEAD>
<META HTTP-EQUIV="expires" CONTENT="-1">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
</HEAD>
<BODY onLoad="init()">
<SCRIPT LANGUAGE="javascript">
function init() {
  var loginWindow = (window.opener != null) ? window.opener : window;
  loginWindow.top.location = "/login.php?bye=true";
  loginWindow.focus();

  // close window if login window is some other window
  if(window.opener != null)
    close();
}
</SCRIPT>
</BODY>
<HEAD>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
</HEAD>
</HTML>
<?php
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
