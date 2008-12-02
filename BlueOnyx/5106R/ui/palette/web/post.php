<HTML>
<BODY>

<?php

// Check that we are logged in
include_once("CceClient.php");
$cceClient = new CceClient();
$cceClient->connect();
if (!$HTTP_COOKIE_VARS["loginName"]
	|| !$HTTP_COOKIE_VARS["sessionId"]
	|| !$cceClient->authkey($HTTP_COOKIE_VARS["loginName"], $HTTP_COOKIE_VARS["sessionId"])) {
	print "<script language=\"javascript\">\n
		top.location=\"/\";\n
		</script>";
} else {

function printVars($hash) {
  $keys = array_keys($hash);
  for($i = 0; $i < count($keys); $i++) {
    $var = $hash[$keys[$i]];
    if(is_array($var))
      for($j = 0; $j < count($var); $j++)
        print($keys[$i]."[$j] = ".$var[$j]."<BR>");
    else
      print($keys[$i]." = ".$hash[$keys[$i]]."<BR>");
  }
}
?>

<?php
print("GET vars<BR>");
printVars($HTTP_GET_VARS);
?>

<BR>

<?php
print("POST vars<BR>");
printVars($HTTP_POST_VARS);
?>

<BR>

<?php
print("COOKIE vars<BR>");
printVars($HTTP_COOKIE_VARS);
?>

<?php
phpinfo();

} /* is logged in */
?>


</BODY>
</HTML>
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
