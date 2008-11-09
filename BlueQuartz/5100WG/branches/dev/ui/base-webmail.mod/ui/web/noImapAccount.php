<?
include("./imapconnect.inc");
global $IMAP_username;
$message = $i18n->get("[[base-webmail.noImapAccount]]");
header("cache-control: no-cache");
?>
<!DOCTYPE html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Untitled</TITLE>
<HTML>
  <HEAD>
    <META HTTP-EQUIV="expires" CONTENT="-1">
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  </HEAD>
    <BODY leftmargin="0" topmargin="0" bottommargin="0" marginheight="0" marginwidth="0" rightmargin="0" bgcolor="White">
    <CENTER>
    <BR><BR><BR><BR>
    <TABLE WIDTH="60%"><TR>
      <TD><FONT COLOR="#990000"><? print $message ?></FONT></TD>
    </TR></TABLE>
    </CENTER>
  </BODY>
  <HEAD>
    <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  </HEAD>
</HTML>
<?
exit;

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

