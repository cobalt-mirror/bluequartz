<?php
include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-ipsec");



function toSubnet($addr,$netmask){
$ip=array();
foreach(explode(".",$addr) as $b){
	array_push($ip,pack('C',$b));
}

$nm=array();
foreach(explode(".",$netmask) as $b){
        array_push($nm,pack('C',$b));
} 

$n1 = unpack('Cchar',($nm[0] & $ip[0]));
$n2 = unpack('Cchar',($nm[1] & $ip[1]));
$n3 = unpack('Cchar',($ip[2] & $nm[2]));
$n4 = unpack('Cchar',($ip[3] & $nm[3]));
$network = $n1["char"].".".$n2["char"].".".$n3["char"].".".$n4["char"];
return $network;

}


// get objects
$system = $cceClient->getObject("System");
$eth0 = $cceClient->getObject("Network", array("device" => "eth0"));
$eth1 = $cceClient->getObject("Network", array("device" => "eth1", "enabled"=>1));

if($eth1 == null){
	$ipsecIp=$eth0["ipaddr"];
}else{
	$ipsecIp=$eth1["ipaddr"];
}

$backButton = $factory->getBackButton("/base/ipsec/tunnelList.php");

$page = $factory->getPage();

$block = $factory->getPagedBlock("ipsecInfo");
$block->setColumnWidths(array("40%","60%"));

$block->addFormField(
  $factory->getIpAddress("foo", $ipsecIp, "r"),
  $factory->getLabel("leftIpAddressField")
);

$block->addFormField(
  $factory->getNetAddress("foo2", toSubnet($eth0["ipaddr"], $eth0["netmask"]), "r"),
  $factory->getLabel("leftSubnetField")
); 

$block->addFormField(
  $factory->getTextField("foo3", $eth0["netmask"], "r"),
  $factory->getLabel("leftNetmaskField")
); 


$block->addFormField(
  $factory->getCompositeFormField(array($factory->getButton("javascript: top.code.Ipsec_LaunchKeyWindow(top)", "openKey"))),
  $factory->getLabel("leftRsaKeyField")
);


$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>
<BR>
<?php print($backButton->toHtml()); ?>

<?php print($page->toFooterHtml()); 
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

