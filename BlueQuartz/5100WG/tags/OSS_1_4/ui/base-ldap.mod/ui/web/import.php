<?php
include("CobaltUI.php");
unset($HTTP_GET_VARS["valset"]);
unset($HTTP_POST_VARS["valset"]);
$HTTP_POST_VARS["now"] = time();
$Ui = new CobaltUI( $sessionId, "base-ldap" );

$Ui->SetAction( array(
	'Action' => 'SET',
	'Target' => 'System',
	'Namespace' => 'LdapImport'
	));
$Ui->Handle();

/* This is an ugly way of grabbing data, but... */
$errors = $Ui->Errors;
for($i=0;$i<count($errors);$i++) {
	$error = $errors[$i];
	if (ereg("\[\[base-ldap\.logFilename,filename=(.*)\]\]", $error->getMessage(), $regs)) {
		/* found the filename! */
		$filename = $regs[1];
		$doStatus = true;
		array_splice($Ui->Errors, $i, 1);
	}
}

//if (!$HTTP_POST_VARS["_save"] && $Ui->CceClient->errors)
/*if (!$HTTP_POST_VARS["_save"]) {
  $Ui->Data = array();
} */

include("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-ldap");
$pageChanger = $factory->getMultiButton("[[base-import.importChanger]]",
        array("/base/import/import.php?valset=1",
        "/base/ldap/import.php?valset=1"),
        array("[[base-import.importChangerFile]]",
        "[[base-ldap.importChangerLdap]]"));
$serverScriptHelper->destructor();

if ($valset) {
	$pageChanger->setSelectedIndex(1);
}

if (!$doStatus) {
	$Ui->StartPage();
	//$Ui->SetAction(array(Action => "importStatus.php"));
	$Ui->StartBlock("ldapImport");
		$Ui->NetAddress("server");
		$Ui->TextField("base");
		$Ui->TextField("bindDn");
		$Ui->Password("passwordAuth");
		$Ui->TextField("userFilter", array("Optional" => true));
		$Ui->TextField("groupFilter", array("Optional" => true));
		$Ui->AddGenericButton($Ui->Page->getSubmitAction(), "import");
	//	$Ui->AddButtons();
		$Ui->Hidden("now"); // something to *touch*
		$Ui->AppendAfterHeaders($pageChanger->toHtml()."<BR><BR>");
	$Ui->EndBlock();
	$Ui->EndPage();
} else {
	/* throw the user to the status page */
	$Ui->Redirect( "importStatus.php?status=".rawurlencode($filename));
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

