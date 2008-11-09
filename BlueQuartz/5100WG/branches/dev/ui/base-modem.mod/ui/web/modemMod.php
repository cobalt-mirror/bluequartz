<?php

include("CobaltUI.php");

$Ui = new CobaltUI( $sessionId, "base-modem" );

$Ui->StartPage("SET","System","Modem");

// get rid of trailing and leading space
$HTTP_POST_VARS["realIp"] = trim($HTTP_POST_VARS["realIp"]);

// set localIp to realIp if realIp contains anything
if($HTTP_POST_VARS["realIp"] == "")
	$HTTP_POST_VARS["localIp"] = "0.0.0.0";
else
	$HTTP_POST_VARS["localIp"] = $HTTP_POST_VARS["realIp"];

// save test in case it is actually wanted
$test = $HTTP_POST_VARS["test"];

// remove these from the post array, they are updated every time
// no need to store them in cce
unset($HTTP_POST_VARS["status"]);
unset($HTTP_POST_VARS["test"]);
unset($HTTP_POST_VARS["realIp"]);

$Ui->Handle( $HTTP_POST_VARS );

if (count($Ui->Errors) != 0)
	$test = 0;

if ($test) 
	$Ui->Redirect("/base/modem/modemTest.php");
else {
	$Ui->StartBlock("modifyModem");

	$Ui->SetBlockView( "basic" );

	$Ui->Alters( "connMode",
		array("on","off","demand") );

	# print out fields

	$Ui->TextField( "phone", array( "Optional" => 'silent' ) );
	
	$Ui->TextField( "userName", array( "Optional" => 'silent' ) );

	$Ui->Password( "password", array( "Optional" => 'silent' ) );

	$locale = split('[, ]+', getenv(HTTP_ACCEPT_LANGUAGE));

	$Ui->Helper->shell("/usr/sausalito/sbin/modem_status.pl $locale[0]", $output);
	
	$Ui->TextField("status", array('Access' => 'r', 'Value' => $output));

	$Ui->Hidden("test");

	$Ui->SetBlockView( "advanced" );

	$Ui->TextField( "initStr", array( "Optional" => 'silent' ) );

	$Ui->Hidden("localIp");

	$Ui->TextField( "realIp", array( "Optional" => 'loud',
		"Value" => 
		$Ui->Data["localIp"] == "0.0.0.0" ? "" : $Ui->Data["localIp"]));


	$Ui->Alters( "speed", array(115200,57600,38400,28800,19200,9600,2400,300),
	"modemSpeed", array( "Optional" => "silent" ) );
	
	// remove these as they may not be necessary and it reduces complexity
	// $Ui->Integer( "mtu", 128, 5000);
	// $Ui->Integer( "mru", 128, 5000);
	
	$Ui->Boolean( "pulse" );
	
	$Ui->AddSaveButton();

	$wait = $Ui->I18n->interpolate("[[palette.wait]]");
	$Ui->AddGenericButton("javascript: 
if(document.form.userName.value == '') {
	top.code.info_show('" . 
	$Ui->I18n->interpolate("[[base-modem.userName_empty]]") . 
	"', 'error');
} else if(document.form.onsubmit()) { 
	top.code.info_show('$wait', 'wait');
	document.form._save.value = 1; 
	document.form.test.value = 1;
	document.form.submit(); }", 'saveTest');

	$Ui->EndBlock();

	$Ui->EndPage();
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

