<?php

include("ServerScriptHelper.php");
include("base/wizard/WizardSupport.php");

global $WizError;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

<html>

	<head>
		<?php print WizGetCharSetHTML(WizDetermineLocale())."\n"; ?>
		<META HTTP-EQUIV="expires" CONTENT="-1">
		<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
		<link rel=stylesheet type="text/css" href="/base/wizard/wizard.css">
		<title>Setup: License</title>
	</head>

	<body bgcolor="#ffffff">
		<div class='Fine-Print'><?php print($i18n->get("license")); ?></div>
	  <br>
		<center>
			<form name="LicenseForm">
		  <input type="Submit" name="decline" value="<?php echo $i18n->getHtml('decline'); ?>" onClick="window.top.document.WizardForm.WizLicenseDecision.value = 'Decline'; window.top.document.WizardForm.submit();">
		  <input type="Submit" name="accept" value="<?php echo $i18n->getHtml('accept'); ?>" onClick="window.top.document.WizardForm.WizLicenseDecision.value = 'Accept'; window.top.document.WizardForm.submit();">
			</form>
		</center>

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

