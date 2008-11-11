<?php
// Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.
// $Id: finish.php 1097 2008-02-13 15:12:32Z brian $

include_once("ServerScriptHelper.php");

$helper = new ServerScriptHelper($sessionId);
$helper->shell("/usr/sausalito/sbin/copy.pl -splash", $output, 'root');

// Redirect
header("cache-control: no-cache");
?>

<HTML>
<HEAD>
<META HTTP-EQUIV="expires" CONTENT="-1">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<SCRIPT LANGUAGE="javascript">
function redirect() {
  var url = "/nav/cList.php?root=root";
  if(opener != null && !opener.closed) {
    opener.location = url;
    close();
  }
  else
    location = url;
}
</SCRIPT>
</HEAD>
<BODY onLoad="redirect()"></BODY>
<HEAD>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
</HEAD>
</HTML>

<?php

$cce = $helper->getCceClient();
list($soid) = $cce->find('System');
$time = $cce->getObject('System', array(), 'Time');

$tz = $time["timeZone"];
if($time["deferTimeZone"])
	$tz = $time["deferTimeZone"];

$cce->set($soid, 'Time', array('deferCommit'=>0, 'timeZone'=>$tz, 'deferTimeZone'=>''));
$cce->set($soid, 'convert2passwd', array('convert' => '1'));

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
