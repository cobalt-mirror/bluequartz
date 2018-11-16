<?php
// NuOnce Addon - Auto DNS Built into GUI.  Add additional Hostnames & MX Record
include_once("CobaltUI.php");

$Ui = new CobaltUI( $sessionId, "base-dns" );

$ConfDir = "/etc";
$ZonePrefix = $ConfDir."/cobaltdns";
$ZoneConf = $ZonePrefix.".conf";

$Ui->StartPage("SET","System","DNS");

$Ui->Handle( $HTTP_POST_VARS );
for ($i = 0; $i < count($Ui->Errors); $i++) {
	if (method_exists($Ui->Errors[$i], 'getKey') &&
	    ($key = $Ui->Errors[$i]->getKey())) {
		$fieldName = "[[base-dns.$key]]";
		$Ui->Errors[$i]->setVar("key", $fieldName);
	}
}

if ($HTTP_GET_VARS['commit']) {
	// Apply changes from records.php
	$Ui->Cce->setObject('System', 
		array("commit" => $HTTP_GET_VARS['commit']), 'DNS');
	$Ui->Cce->commit();
}

$Ui->StartBlock("modifyDNS");
$Ui->SetBlockView( "basic" );

$Ui->Data['commit'] = time();
$Ui->Hidden( "commit" );

$Ui->Boolean( "enabled" );
// $Ui->Boolean( "auto_config" );

$Ui->SetBlockView( "advanced" );


$Ui->Divider( "soa_defaults" );
$Ui->EmailAddress( "admin_email", array( "Optional" => 'loud' )  );
$Ui->Integer( "default_refresh", 1, 4096000);
$Ui->Integer( "default_retry", 1, 4096000);
$Ui->Integer( "default_expire", 1, 4096000);
$Ui->Integer( "default_ttl", 1, 4096000);

$Ui->Divider( "global_settings" );

$Ui->Boolean( "query" );
$Ui->Boolean( "query_all_allowed" );
$Ui->InetAddressList( "query_inetaddr", array( "Optional" => 'laud') );

$Ui->Boolean( "caching" );
$Ui->Boolean( "caching_all_allowed" );
$Ui->InetAddressList( "recursion_inetaddr", array( "Optional" => 'laud') );


$Ui->IpAddressList( "forwarders", array( "Optional" => 'loud' ) );
$Ui->IpAddressList( "zone_xfer_ipaddr", array( "Optional" => 'loud') );

$Ui->Divider( "rate_limits" );
$Ui->Boolean( "rate_limits_enabled" );
$Ui->Integer( "responses_per_second", 1, 1000);
$Ui->Integer( "window", 1, 128);

$Ui->Divider( "dns_logging" );
$Ui->Boolean( "enable_dns_logging" );

//$Ui->Divider( "dns_sec" );
//$Ui->Boolean( "enable_dns_sec" );


// Zone File Format tab
$Ui->SetBlockView( "zone_format_tab" );
$Ui->Divider( "zone_format_settings_divider" );
//$Ui->Alters( "zone_format", array('RFC2317','DION','OCN-JT','USER'));
$Ui->Alters( "zone_format", array('RFC2317'));
//$Ui->Divider( "zone_format_user_defined_divider" );
//$Ui->TextField( "zone_format_24", array( "Optional" => 'loud' ) );
//$Ui->TextField( "zone_format_16", array( "Optional" => 'loud' ) );
//$Ui->TextField( "zone_format_8", array( "Optional" => 'loud' ) );
//$Ui->TextField( "zone_format_0", array( "Optional" => 'loud' ) );

$Ui->SetBlockView("auto_dns");
$Ui->ListField( "auto_a", array( "Optional" => 'loud' ) );
$Ui->TextField( "auto_mx", array( "Optional" => 'loud' ) );

$Ui->AddSaveButton();

$Ui->EndBlock();


include_once("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$factory = $serverScriptHelper->getHtmlComponentFactory("base-dns");
$primaryButton = $factory->getButton('/base/dns/records.php', 'primary_service_button');
$secondaryButton = $factory->getButton('/base/dns/dns_sec_list.php', 'secondary_service_button');
$serverScriptHelper->destructor();

$Ui->AppendAfterHeaders("<TABLE><TR><TD>".$primaryButton->toHtml()."</TD><TD>".$secondaryButton->toHtml()."</TD></TR></TABLE><BR>");

$Ui->EndPage();

/*
function get_zonetypes() {
	$zones = array();
	$selfflags = array();
	//$oldcontents[] = file($ZoneConf);
	//$oldcontent = join ('', $oldcontents);
	$ConfDir = "/etc";
	if ( $handle = opendir($ConfDir)) {
		while( $file = readdir($handle)) {
			if( ! preg_match( "/^cobaltdns.(.*)$/", $file, $format))
				continue;
			if ( $format[1] == "conf")
				 continue;
			$zones[] = $format[1];
			//$content = join ('', file ($ConfDir."/".$File));
			//if ( $content == $oldcontents)
			//$selfflags[$format] = "selected";
		}
	}
	return $zones;
}
*/
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
