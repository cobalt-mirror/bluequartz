<?

/* This page takes care of the Remote Access button for users 
 * that have pptp access enabled */

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-pptp");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-pptp", "/base/pptp/pptpUserHandler.php?noBackButton=$noBackButton");
$page = $factory->getPage();


$block = $factory->getPagedBlock("pptp_user_block");

$thisUserPptp = $cce->getObject("User", array(name=>$serverScriptHelper->getLoginName()), "Pptp");

/* decide which message we are going to display */
if ($thisUserPptp["secret"] == "") {
  $message = "pptp_user_setyoursecret";
} else {
  $message = "pptp_user_yoursecretisset";
}

$block->addFormField(
  $factory->getPassword("pptp_user_secret", $thisUserPptp["secret"]),
  $factory->getLabel("pptp_user_secret")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
if (!$noBackButton && $thisUserPptp["secret"] != "") {
  $block->addButton($factory->getButton("javascript: returnToLogin();", "returnToLogin"));  
}

print $page->toHeaderHtml();
?><SCRIPT language="javascript">
<!--
function returnToLogin () {
  top.location="/logoutHandler.php?timestamp=<? print time() ?>";
}
// -->
</SCRIPT><?
print "<BR><center>" . $i18n->get($message) . "</center><br>";
print $block->toHtml();

print $page->toFooterHtml();
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

