<html>
<head>
<title>Test Internationalization Features</title>
</head>
<body BGCOLOR="#EEEEFF">

<center>
<h1>Test I18n Features</h1>

<form action="test_i18n.php" method="POST">
<textarea name="magicstr" rows="6" cols="60"></textarea>
<br>
<input type="text" name="lang" width="20" value="en">
<br>
<input type="submit" value="Translate">
</form>
<br>
<hr>
<br>

<?php include_once("I18n.php"); 
if ($lang && $magicstr) { ?>
<table width="90%" border="1" cellspacing="0" cellpadding="0">
<tr><td align="center">
	<b>LanguagePreferences: </b><?php print $lang; ?>
</td></tr>
<tr><td align="center">
  <b>Text: </b><br>
<?php 
	$i18n = new I18n("test",$lang);
  print $i18n->interpolateHtml($magicstr);
?>
</td></tr>
</table>
<?php
}
?>

<br>
<hr>

</body>
</html>
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
