<?php
// $Id: vsite_dns_add.php 1013 2007-06-25 15:25:22Z shibuya $
//
// ui for adding/modifying many DNS record types
$iam = '/base/dns/vsite_dns_add.php';
$parent = '/base/dns/vsite_records.php';

include_once("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();

// Only dnsAdmin should be here
if (!$serverScriptHelper->getAllowed('dnsAdmin')) {
  header("location: /error/forbidden.html");
  return;
}

if ( ! $serverScriptHelper->getAllowed('adminUser') ) {
	$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");
	$user = $cceClient->getObject("User", array("name" => $loginName));
	$group = $user["site"];
	$vsite = $cceClient->getObject("Vsite", array("name" => $group));

	$vsite_dns = $cceClient->getObject('Vsite', array('name' => $group), "DNS");
	$allAliases = $cceClient->scalar_to_array($vsite_dns["domains"]);

	$key = array_search($HTTP_GET_VARS['domauth'], $allAliases);

	if ( ($key === FALSE) ) {
		header("location: /base/dns/vsite_records.php");
		return;
	}
}

include_once("CobaltUI.php");
$Ui = new CobaltUI($sessionId, "base-dns"); 

// return the base ip address of a network
// as defined by dot-quad member ip and netmask
function get_network($ip = "127.0.0.1", $nm = "255.255.255.255") {
	$ip = split('[.]',$ip);
	$nm = split('[.]',$nm);
	for ($i=0; $i<4; $i++):
		$ip[$i] = (int) $ip[$i]; $nm[$i] = (int) $nm[$i];
		$nu[$i] .= $ip[$i] & $nm[$i];
	endfor;

	return join('.',$nu);
}

// mapping: lists a form field name to an object attribute.
//          note the many:1 mapping, this causes a case-map in update_cce()
$mapping = array (

	"type" => "type",
	"network" => "network",

	"a_host_name" => "hostname",
	"a_domain_name" => "domainname",
	"a_ip_address" => "ipaddr",

	"cname_host_name" => "hostname",
	"cname_domain_name" => "domainname",
	"cname_host_target" => "alias_hostname",
	"cname_domain_target" => "alias_domainname",

	"mx_host_name" => "hostname",
	"mx_domain_name" => "domainname",
	"mx_target_server" => "mail_server_name",
	"mx_priority" => "mail_server_priority",

	"txt_host_name" => "hostname",
	"txt_domain_name" => "domainname",
	"txt_strings" => "strings",

	"subdom_host_name" => "hostname",
	"subdom_domain_name" => "domainname",
	"subdom_nameservers" => "delegate_dns_servers",

	"subnet_parent_ip_address" => "ipaddr",
	"subnet_parent_mask" => "netmask",
	"subnet_nameservers" => "delegate_dns_servers",
	"subnet_nameservers" => "delegate_dns_servers",
	"subnet_network" => "network_delegate",

	"unused" => "delegate_sec_dns",
	"unused" => "delegate_sec_dns" );


$nm_to_dec = array(
	"0.0.0.0"   => "0",
	"128.0.0.0" => "1",	"255.128.0.0" => "9",	"255.255.128.0" => "17",	"255.255.255.128" => "25",
	"192.0.0.0" => "2", 	"255.192.0.0" => "10",	"255.255.192.0" => "18",	"255.255.255.192" => "26",
	"224.0.0.0" => "3",	"255.224.0.0" => "11",	"255.255.224.0" => "19",	"255.255.255.224" => "27",
	"240.0.0.0" => "4",	"255.240.0.0" => "12",	"255.255.240.0" => "20",	"255.255.255.240" => "28",
	"248.0.0.0" => "5",	"255.248.0.0" => "13",	"255.255.248.0" => "21",	"255.255.255.248" => "29",
	"252.0.0.0" => "6",	"255.252.0.0" => "14",	"255.255.252.0" => "22",	"255.255.255.252" => "30",
	"254.0.0.0" => "7",	"255.254.0.0" => "15",	"255.255.248.0" => "23",	"255.255.255.254" => "31",
	"255.0.0.0" => "8",	"255.255.0.0" => "16",	"255.255.255.0" => "24",	"255.255.255.255" => "32" );
$dec_to_nm = array_flip($nm_to_dec);

// handler:
if (!$_TARGET) { $_TARGET = 'A'; }
$done = handle($Ui, $_TARGET, $mapping, $HTTP_POST_VARS, $HTTP_GET_VARS, $nm_to_dec, $dec_to_nm);

# prevent PHP from sneakily adding new hidden fields:
if (is_array($HTTP_POST_VARS)) {
	$keys = array_keys($HTTP_POST_VARS);
	$index = array_keys($keys, "_LOAD"); array_splice($HTTP_POST_VARS, $index[0], 1);
	$index = array_keys($keys, "_save"); array_splice($HTTP_POST_VARS, $index[0], 1);
}

if ($HTTP_GET_VARS{'domauth'} != '') {
	$ret_url = $parent.'?group=' . $group . '&domauth='.$HTTP_GET_VARS{'domauth'};
} elseif ($HTTP_GET_VARS{'netauth'} != '') {
	$ret_url = $parent.'?group=' . $group . '&netauth='.urlencode($HTTP_GET_VARS{'netauth'});
} else {
	$ret_url = $parent.'?group=' . $group;
}

$Ui->StartPage("AAS", $_TARGET ? $_TARGET : "DnsRecord", "");
$Ui->StartBlock((intval($_TARGET) > 0) ? "modify_dns_rec".$HTTP_GET_VARS{'TYPE'} : "create_dns_rec".$HTTP_GET_VARS{'TYPE'});

// Bail if we've saved successfully
if ($done) {
	$Ui->Redirect( $ret_url );
	exit();
}

// prep default values
if($HTTP_GET_VARS['netauth'] != '') {
	$net_defaults = split('/', urldecode($HTTP_GET_VARS['netauth']));
}
$dom_default = $HTTP_GET_VARS['domauth'];

if ($HTTP_GET_VARS{'TYPE'} == 'PTR') {


} elseif ($HTTP_GET_VARS{'TYPE'} == 'CNAME') {

	if ($Ui->Data['cname_domain_name'] == '') { 
		$Ui->Data['cname_domain_name'] = $dom_default;
	}
	if ($Ui->Data['cname_domain_target'] == '') { 
		$Ui->Data['cname_domain_target'] = $dom_default; 
	}
	$Ui->DomainName( "cname_host_name" );  // optional alias hostname
	$Ui->DomainName( "cname_domain_name", array( "Access" => "r") ); 
	// target hostname
	$Ui->DomainName( "cname_host_target", array( "Optional" => 'loud' ) );  
	$Ui->DomainName( "cname_domain_target" );  // target domain

} elseif ($HTTP_GET_VARS{'TYPE'} == 'MX') {

	if ($Ui->Data['mx_domain_name'] == '') { 
		$Ui->Data['mx_domain_name'] = $dom_default; 
	}
	$Ui->DomainName( "mx_host_name", array( "Optional" => 'loud' )); 
	$Ui->DomainName( "mx_domain_name", array( "Access" => "r") ); 
	$Ui->DomainName( "mx_target_server" );
	// $Ui->DomainName( "mx_target_server", array( "Optional" => "silent" ) );
	$Ui->Alters( "mx_priority", array('very_high', 'high', 'low', 'very_low') ); 
} elseif ($HTTP_GET_VARS{'TYPE'} == 'SUBDOM') {

	if ( ! $Ui->Data['subdom_domain_name'] ) { 
		$Ui->Data['subdom_domain_name'] = $dom_default; 
	}
	if ( $Ui->Data['subdom_domain_name'] ) { 
		$Ui->DomainName( "subdom_domain_name", array('Access' => 'r') );
	} else {
		$Ui->DomainName( "subdom_domain_name", array( "Access" => "r") );
	}
	$Ui->DomainName( "subdom_host_name" );
 
	$Ui->DomainNameList( "subdom_nameservers" );

} elseif ($HTTP_GET_VARS{'TYPE'} == 'SUBNET') {

	// Preserve authority ties
	$Ui->Data['subnet_parent_ip_address'] = $net_defaults[0];
	$Ui->Data['subnet_parent_mask'] = $dec_to_nm[$net_defaults[1]];
	$Ui->Hidden( 'subnet_parent_ip_address' );
	$Ui->Hidden( 'subnet_parent_mask' );

	$Ui->Data['parent_network'] = $net_defaults[0] .'/'.
		$dec_to_nm[$net_defaults[1]];
	$Ui->TextField( "parent_network", array('Access' => 'r') );

	if (!$Ui->Data['subnet_mask']) { 
		$Ui->Data['subnet_mask'] = $dec_to_nm[$net_defaults[1]+1]; 
	}
	if (!$Ui->Data['subnet_ip_address']) { 
		$Ui->Data['subnet_ip_address'] = $net_defaults[0]; 
	}

	$Ui->IpAddress( "subnet_ip_address" );
	$Ui->IpAddress( "subnet_mask" );
	$Ui->DomainNameList( "subnet_nameservers" );

} elseif ($HTTP_GET_VARS{'TYPE'} == 'TXT') {
	if ($Ui->Data['txt_domain_name'] == '') {
		$Ui->Data['txt_domain_name'] = $dom_default;
	}
	$Ui->DomainName( "txt_host_name", array( "Optional" => "loud" ) );
	$Ui->DomainName( "txt_domain_name", array( "Access" => "r") );
	$Ui->TextField( "txt_strings" );

} else { // ($HTTP_GET_VARS{'TYPE'} == 'A')

	if ($Ui->Data['a_domain_name'] == '') { 
		$Ui->Data['a_domain_name'] = $dom_default; 
	}
	$Ui->DomainName( "a_host_name", array( "Optional" => "loud") );
	$Ui->DomainName( "a_domain_name", array( "Access" => "r") );
	$Ui->IpAddress( "a_ip_address" );

}

$Ui->AddButtons($ret_url);

$Ui->EndBlock();
$Ui->EndPage();

function handle(&$Ui, $target, &$mapping, &$post_vars, &$get_vars, &$nm_to_dec, &$dec_to_nm)
{
	// echo "<b>handle $target</b><br>";
	
	// Set Defaults that can't be grabbed from CCE....
	$Ui->Data["moderator"]="admin";

	$http_vars = array();
	if (is_array($post_vars)) { 
		$http_vars = array_merge($http_vars, $post_vars); 
	}
	if (is_array($get_vars)) {
		$http_vars = array_merge($http_vars, $get_vars);
	}

	if ($http_vars["_LOAD"]) {
		if (intval($target) > 0) {
			handle_load($Ui, intval($target), $dec_to_nm); 
		}
	} else {
		handle_post($Ui, $target, $mapping, $http_vars);
	}

	
	if ($post_vars["_save"]==1) {
		return update_cce($Ui, $target, $mapping, $http_vars, $nm_to_dec);
	}

	return 0;
}
	
function handle_load(&$Ui, $oid, &$dec_to_nm)
{
	// load object attributes
	$rec = $Ui->Cce->get($oid);
 
	// override the http get type
	if($rec['type'] == 'A') {
		$Ui->Data['a_host_name'] = $rec['hostname'];
		$Ui->Data['a_domain_name'] = $rec['domainname'];
		$Ui->Data['a_ip_address'] = $rec['ipaddr'];
		$HTTP_GET_VARS{'TYPE'} = 'A';
		$HTTP_GET_VARS{'domauth'} = $rec['domainname']; 

	} elseif($rec['type'] == 'PTR') {

	} elseif($rec['type'] == 'CNAME') {
		$Ui->Data['cname_host_name'] = $rec['hostname'];
		$Ui->Data['cname_domain_name'] = $rec['domainname'];
		$Ui->Data['cname_host_target'] = $rec['alias_hostname'];
		$Ui->Data['cname_domain_target'] = $rec['alias_domainname'];
		$HTTP_GET_VARS{'TYPE'} = 'CNAME';
		$HTTP_GET_VARS{'domauth'} = $rec['domainname']; 

	} elseif($rec['type'] == 'MX') {
		$Ui->Data['mx_host_name'] = $rec['hostname'];
		$Ui->Data['mx_domain_name'] = $rec['domainname'];
		$Ui->Data['mx_target_server'] = $rec['mail_server_name'];
		$Ui->Data['mx_priority'] = $rec['mail_server_priority'];
		$HTTP_GET_VARS{'TYPE'} = 'MX';
		$HTTP_GET_VARS{'domauth'} = $rec['domainname']; 

	} elseif($rec['type'] == 'TXT') {
		$Ui->Data['txt_host_name'] = $rec['hostname'];
		$Ui->Data['txt_domain_name'] = $rec['domainname'];
		$Ui->Data['txt_strings'] = $rec['strings'];
		$HTTP_GET_VARS{'TYPE'} = 'TXT';
		$HTTP_GET_VARS{'domauth'} = $rec['domainname'];

	} elseif($rec['type'] == 'SN') {

		if ($rec['hostname']) {
			$HTTP_GET_VARS{'TYPE'} = 'SUBDOM';
			$HTTP_GET_VARS{'domauth'} = $rec['domainname']; 
			$Ui->Data['subdom_host_name'] = $rec['hostname'];
			$Ui->Data['subdom_domain_name'] = $rec['domainname'];
			$Ui->Data['subdom_nameservers'] = $rec['delegate_dns_servers'];
		} else { 

			$HTTP_GET_VARS{'TYPE'} = 'SUBNET';
			$HTTP_GET_VARS{'netauth'} = $rec['network']; 
			$Ui->Data['subnet_parent_ip_address'] = $rec['ipaddr'];
			$Ui->Data['subnet_parent_mask'] = $rec['netmask'];
			$smallnet = split('/', $rec['network_delegate']);
			$Ui->Data['subnet_ip_address']  = $smallnet[0];
			$Ui->Data['subnet_mask'] = $dec_to_nm[ $smallnet[1] ];
			$Ui->Data['subnet_nameservers'] = $rec['delegate_dns_servers'];
		}
	}
}

function handle_post(&$ui, $target, &$mapping, &$post_vars)
{
	while (list($key,$val) = each($mapping))
	{
		$ui->Data[$key] = $post_vars[$key];
	}
}

# translate post variables into an object hash based on $mapping.
# $mapping maps "Form Field Name" => "Object Attribute Name"
function map_vars($mapping, $post_vars)
{
	$obj = array();
	while (list($key,$val) = each($mapping))
	{
		if($post_vars[$key] != "") {
		 $obj[$val] = $post_vars[$key];
		} elseif ( $val == "hostname" && 
			! in_array ("hostname", array_keys ($obj))) {
		 $obj[$val] = "";
		}
	}
	return $obj;
}

function update_cce(&$Ui, $target, $mapping, $http_vars, $nm_to_dec)
{
	$oid = 0;
	// create record; first determine type
	if($http_vars['a_domain_name'] != '') {
		$http_vars['type'] = 'A';
	} elseif($http_vars['ptr_ip_address'] != '') {
	} elseif($http_vars['cname_domain_name'] != '') {
		$http_vars['type'] = 'CNAME';
	} elseif($http_vars['mx_domain_name'] != '') {
		$http_vars['type'] = 'MX';
	} elseif($http_vars['txt_domain_name'] != '') {
		$http_vars['type'] = 'TXT';
	} elseif($http_vars['subdom_host_name'] != '') {
		$http_vars['type'] = 'SN';
	} elseif($http_vars['subnet_ip_address'] != '') {
		$http_vars['type'] = 'SN';
		$http_vars['network'] = 
			get_network($http_vars['subnet_parent_ip_address'], 
			$http_vars['subnet_mask']).'/'.
			$nm_to_dec[ $http_vars['subnet_parent_mask'] ];
		$http_vars['subnet_network'] = 
			get_network($http_vars['subnet_ip_address'],
			$http_vars['subnet_mask']).'/'.
			$nm_to_dec[ $http_vars['subnet_mask'] ];
	}
	
	if (intval($target) > 0) {
		// modify record, its type is fixed
		$oid = intval($target);
		$Ui->Cce->set ($oid, "", map_vars($mapping, $http_vars));
		// $Ui->Cce->set ($oid, "Archive", map_vars($mapping, $http_vars));

	} else {

		$class = $target;
		$oid = $Ui->Cce->create( 'DnsRecord', map_vars($mapping, $http_vars));
	
	}
	

	$flip_map = array_flip($mapping); // maps attributes -> form field names (1:many)

	// hack around 1:many reverse mapping
	if ($http_vars['type'] == 'PTR') {
	} elseif ($http_vars['type'] == 'A') {
		$flip_map['hostname'] = 'a_host_name';
		$flip_map['domainname'] = 'a_domain_name';
		$flip_map['ipaddr'] = 'a_ip_address';
	} elseif ($http_vars['type'] == 'CNAME') {
		$flip_map['hostname'] = 'hostname';
		$flip_map['domainname'] = 'domainname';
	} elseif ($http_vars['type'] == 'MX') {
		$flip_map['hostname'] = 'mx_host_name';
		$flip_map['domainname'] = 'mx_domain_name';
	} elseif ($http_vars['type'] == 'TXT') {
		$flip_map['hostname'] = 'txt_host_name';
		$flip_map['domainname'] = 'txt_domain_name';
		$flip_map['strings'] = 'txt_strings';
	} elseif ($http_vars['type'] == 'SUBDOM') {
		$flip_map['hostname'] = 'subdom_host_name';
		$flip_map['domainname'] = 'subdom_domain_name';
		$flip_map['delegate_dns_servers'] = 'subdom_nameservers';
	} elseif ($http_vars['type'] == 'SUBNET') {
		$flip_map['netmask'] = 'subnet_parent_mask';
		$flip_map['ipaddr'] = 'subnet_parent_ip_address';
		$flip_map['delegate_dns_servers'] = 'subnet_nameservers';
	} 
	
	$Ui->report_errors($flip_map);

	return (count($errors) == 0);
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
