<?php
include("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-webmail");
?>
<!DOCTYPE html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Untitled</TITLE>
</HEAD>
<BODY background="bgimage_login2.jpg" leftmargin="0" topmargin="0" bottommargin="0" marginheight="0" marginwidth="0" rightmargin="0" bgcolor="White">
<TABLE width="600" border="0" cellspacing="0" cellpadding="0" align="left">
	<TR>
		<TD colspan="4" height="10" width=600>&nbsp;</TD>
	</TR>
	<TR>
		<TD height="108" width="15">&nbsp;</TD>
		<TD width="269" height"108">&nbsp;</TD>
		<TD width="10" height="108">&nbsp;</TD>
		<TD align="left" valign="top" height="108" width="306"><FONT face="Arial,'MS Sans Serif',sans-serif"><? print $i18n->get("welcomeTo") ?></FONT>
		</TD>
	</TR>
	<TR>
		<TD width="15" height="74">&nbsp;</TD>
		<TD height="74" align="left" valign="top" nowrap width="269"><FONT face="Arial,'MS Sans Serif',sans-serif" size="5"><B><? print $i18n->get("webmailLogin") ?></B></FONT>
		</TD>
		<TD height="74" width="10">&nbsp;
		</TD>
		<TD height="74" width="306">
<?
	if ($authFailed) {
		print "<font color='red'>" . $i18n->interpolate("[[base-webmail.authenticationFailed]]") . "</font>";
	} else {
		print "&nbsp;";
	}
?>
		</TD>
	</TR>
	<TR>
		<TD width="15" height="74">&nbsp;
		</TD>
		<TD width="269" height="74">&nbsp;
		</TD>
		<TD width="10" height="74">&nbsp;
		</TD>
		<TD width="306" height="74">
			<FORM action="loginHandler.php" method="post"><FONT face="Arial,'MS Sans Serif',sans-serif">
	<? print $i18n->get("login") ?><BR>
				<INPUT type="text" name="username"><BR><? print $i18n->get("password") ?><BR>
				<INPUT type="password" name="password"></FONT>
				<INPUT type="submit" name="submit" value="<? print $i18n->get("submit") ?>">
			</FORM>
		</TD>
	</TR>
</TABLE>
</BODY>
</HTML>
<?$serverScriptHelper->destructor();
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

