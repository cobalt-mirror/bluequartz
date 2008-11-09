<?php
// $Id: dns_sec.php 3 2003-07-17 15:19:15Z will $
//
// ui for adding/modifying many DNS record types
$iam = '/base/dns/dns_sec.php';
$parent = '/base/dns/dns_sec_list.php';

include("CobaltUI.php");
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
$mapping = array (
  "slave_domain" => "domain",
  "slave_netmask" => "netmask",
  "slave_ipaddr" => "ipaddr",
  "slave_dom_masters" => "masters",
  "slave_net_masters" => "masters" );

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

// handler:
if (!$_TARGET) { $_TARGET = $HTTP_GET_VARS['_LOAD']; }
$done = handle($Ui, $_TARGET, $mapping, $HTTP_POST_VARS, $HTTP_GET_VARS, $nm_to_dec);

# prevent PHP from sneakily adding new hidden fields:
if (is_array($HTTP_POST_VARS)) {
  $keys = array_keys($HTTP_POST_VARS);
  $index = array_keys($keys, "_LOAD"); array_splice($HTTP_POST_VARS, $index[0], 1);
  $index = array_keys($keys, "_save"); array_splice($HTTP_POST_VARS, $index[0], 1);
}

$Ui->StartPage("AAS", $_TARGET ? $_TARGET : "DnsSlaveZone", "");
$Ui->StartBlock((intval($_TARGET) > 0) ? "modify_slave_rec" : "create_slave_rec");

// Bail if we've saved successfully
if ($done) {
  $Ui->Redirect( $parent );
  exit();
}

if ($HTTP_GET_VARS{'TYPE'} == 'NETWORK') {

  // secondary network auth
  if ($Ui->Data['slave_netmask'] == '') 
  {  $Ui->Data['slave_netmask'] = '255.255.255.0';  }
  $Ui->SetBlockView( "slave_network_but" );
  $Ui->IpAddress( "slave_ipaddr" );
  $Ui->IpAddress( "slave_netmask" );
  $Ui->IpAddress( "slave_net_masters" );

} else {

  // secondary domain auth
  $Ui->SetBlockView( "slave_domain_but" );
  $Ui->DomainName( "slave_domain" );
  $Ui->IpAddress( "slave_dom_masters" );

}

$Ui->AddButtons($parent);

$Ui->EndBlock();
$Ui->EndPage();



function handle(&$Ui, $target, &$mapping, &$post_vars, &$get_vars, &$nm_to_dec)
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
      handle_load($Ui, intval($target)); 
    }
  } else {
    handle_post($Ui, $target, $mapping, $http_vars);
  }
  
  if ($post_vars["_save"]==1) {
    return update_cce($Ui, $target, $mapping, $http_vars, $nm_to_dec);
  }
  
  return 0;
}
  
function handle_load(&$Ui, $oid)
{
  // load object attributes
  $rec = $Ui->Cce->get($oid);
  
  $Ui->Data['slave_domain'] = $rec['domain'];
  $Ui->Data['slave_ipaddr'] = $rec['ipaddr'];
  $Ui->Data['slave_netmask'] = $rec['netmask'];

  if($rec['ipaddr'] == '') {
    $Ui->Data['slave_dom_masters'] = $rec['masters'];
  } else {
    $Ui->Data['slave_net_masters'] = $rec['masters'];
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
    }
  }
  return $obj;
}

function update_cce(&$Ui, $target, $mapping, $http_vars, $nm_to_dec)
{
  $oid = 0;

  if (intval($target) > 0) {

    // modify record, its type is fixed
    $oid = intval($target);
    $Ui->Cce->set ($oid, "", map_vars($mapping, $http_vars));
    // $Ui->Cce->set ($oid, "Archive", map_vars($mapping, $http_vars));

  } else {

    $class = $target;
    $oid = $Ui->Cce->create( 'DnsSlaveZone', map_vars($mapping, $http_vars));
  
  }

  $flip_map = array_flip($mapping); // maps attributes -> form field names (1:many)

  // hack around 1:many reverse mapping
  if ($http_vars['slave_dom_masters'] != '') {
    $flip_map['masters'] = 'slave_dom_masters';
  } else {
    $flip_map['masters'] = 'slave_net_masters';
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

