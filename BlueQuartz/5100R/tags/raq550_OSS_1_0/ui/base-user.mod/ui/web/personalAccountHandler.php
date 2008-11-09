<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: personalAccountHandler.php 259 2004-01-03 06:28:40Z shibuya $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-user");
$loginName = $serverScriptHelper->getLoginName();

// get old settings
$user = $cceClient->getObject("User", array("name" => $loginName));

$attributes = array("fullName" => $fullNameField, "localePreference" => $languageField);
if($newPasswordField)
  $attributes["password"] = $newPasswordField;
if($styleField)
  $attributes["stylePreference"] = $styleField;

$cceClient->setObject("User", $attributes, "", array("name" => $loginName));
$errors = $cceClient->errors();

for ($i = 0; $i < count($errors); $i++) {
	if ( ($errors[$i]->code == 2) && ($errors[$i]->key === "password"))
	{
		$errors[$i]->message = "[[base-user.error-invalid-password]]";
	}
}


print($serverScriptHelper->toHandlerHtml("/base/user/personalAccount.php", $errors));

// reload browser window if style has changed
if(($cceClient->suspended() === false) && 
	(($styleField && $user["stylePreference"] != $styleField) || 
   	($languageField && $user["localePreference"] != $languageField))) 
{
  $reloadMessage = $i18n->getJs("reloadMessage");

  // need to set the encoding correctly or Japanese strings get murdered
  $encoding = $i18n->getProperty('encoding', 'palette');
  if ($encoding != 'none') {
	$encoding = "; charset=$encoding";
  } else {
	$encoding = "";
  }
?>
<HTML>
<HEAD>
<META HTTP-EQUIV="Content-type" CONTENT="text/html<?php print $encoding; ?>">
</HEAD>
<BODY>
<SCRIPT LANGUAGE="javascript">
var strReloadMessage = '<?php print $reloadMessage; ?>';
alert(strReloadMessage);
setTimeout("top.location.reload();", 2);
</SCRIPT>
</BODY>
</HTML>
<?
}

$serverScriptHelper->destructor();
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
