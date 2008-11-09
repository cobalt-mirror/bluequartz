<?php
// $Id: dns_soa.php 1050 2008-01-23 11:45:43Z mstauber $
//
// ui for adding/modifying many DNS record types
$iam = '/base/dns/dns_add.php';
$parent = '/base/dns/records.php';

include_once("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

// preserve the selected authority in the records list
if ($HTTP_GET_VARS{'domauth'} != '') {
  $ret_url = $parent.'?domauth='.$HTTP_GET_VARS{'domauth'};
} elseif ($HTTP_GET_VARS{'netauth'} != '') {
  $ret_url = $parent.'?netauth='.urlencode($HTTP_GET_VARS{'netauth'});
} else {
  $ret_url = $parent;
}

include_once("CobaltUI.php");
$Ui = new CobaltUI($sessionId, "base-dns"); 

// mapping: lists a form field name to an object attribute.
$mapping = array (
  "primary_dns" => "primary_dns",
  "secondary_dns" => "secondary_dns",
  "domain_admin" => "domain_admin",
  "refresh" => "refresh",
  "retry" => "retry",
  "expire" => "expire",
  "ttl" => "ttl" );

// handler:
if (!$_TARGET) { $_TARGET = $HTTP_GET_VARS['_LOAD']; }
$done = handle($Ui, $_TARGET, $mapping, $HTTP_POST_VARS, $HTTP_GET_VARS);

# prevent PHP from sneakily adding new hidden fields:
if (is_array($HTTP_POST_VARS)) {
  $keys = array_keys($HTTP_POST_VARS);
  $index = array_keys($keys, "_LOAD"); array_splice($HTTP_POST_VARS, $index[0], 1);
  $index = array_keys($keys, "_save"); array_splice($HTTP_POST_VARS, $index[0], 1);
}

// render table title
// $table_title = $18n->get( (intval($_TARGET) > 0) ? "modify_soa" : "create_soa" );
// if($Ui->Data['domainname']) {
//   $table_title .= '   -   '.$Ui->Data['domainname'];
// } else {
//   $table_title .= '   -   '.$Ui->Data['ipaddr'].' / '.$Ui->Data['netmask'];
// }

error_log("Starting page...");
error_log("Target $_TARGET");
$Ui->StartPage("SET", $_TARGET ? $_TARGET : "DnsSOA", "");

error_log("Starting block...");
error_log(intval($_TARGET));
$Ui->StartBlock((intval($_TARGET) > 0) ? "modify_soa" : "create_soa");

// Return to the records list on successful save
if ($done) {
  $Ui->Redirect( $ret_url );
  exit();
}

//  $Ui->Data['ipaddr'] = $rec['ipaddr'];
//  $Ui->Data['netmask'] = $rec['netmask'];

error_log("Filling in fields...");
if($Ui->Data['domainname'] != '') {
  $Ui->Data['domain_soa'] = $Ui->Data['domainname'];
  $Ui->TextField( 'domain_soa', array( 'access' => 'r' ) );
} else {
  $Ui->Data['network_soa'] = $Ui->Data['ipaddr'].'/'.$Ui->Data['netmask'];
  $Ui->TextField( 'network_soa', array( 'access' => 'r' ) );
}

$Ui->DomainName( "primary_dns", array( "Optional" => "loud" ) );
$Ui->DomainNameList( "secondary_dns", array( "Optional" => "loud" ) );
$Ui->EmailAddress( "domain_admin", array( "Optional" => "silent" ) );
$Ui->Integer( "refresh", 1, 4096000);
$Ui->Integer( "retry", 1, 4096000);
$Ui->Integer( "expire", 1, 4096000);
$Ui->Integer( "ttl", 1, 4096000);

$Ui->AddButtons($ret_url);

$Ui->EndBlock();
$Ui->EndPage();

function handle(&$Ui, $target, $mapping, &$post_vars, &$get_vars)
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
      handle_load($Ui, $target);
    }
  } else {
    handle_post($Ui, $target, $mapping, $http_vars);
  }

  if ($post_vars["_save"]==1) {
    return update_cce($Ui, $target, $mapping, $http_vars);
  }

  return 0;
}

function handle_load(&$Ui, $oid)
{
  // load object attributes
  $rec = $Ui->Cce->get($oid);
  $Ui->Data['primary_dns'] = $rec['primary_dns'];
  $Ui->Data['secondary_dns'] = $rec['secondary_dns'];
  $Ui->Data['domain_admin'] = $rec['domain_admin'];
  $Ui->Data['refresh'] = $rec['refresh'];
  $Ui->Data['retry'] = $rec['retry'];
  $Ui->Data['expire'] = $rec['expire'];
  $Ui->Data['ttl'] = $rec['ttl'];
  $Ui->Data['domainname'] = $rec['domainname'];
  $Ui->Data['ipaddr'] = $rec['ipaddr'];
  $Ui->Data['netmask'] = $rec['netmask'];
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
    } elseif (preg_match ("(primary_dns|secondary_dns|domain_admin)", $val)) {
      $obj[$val] = "";
    }
  }
  return $obj;
}

function update_cce(&$Ui, $target, $mapping, $http_vars)
{
  $oid = 0;
  if (intval($target) > 0) {

    $oid = intval($target);
    $Ui->Cce->set ($oid, "", map_vars($mapping, $http_vars));

  } else {

    $class = $target;
    $oid = $Ui->Cce->create( 'DnsSOA', map_vars($mapping, $http_vars));

  }
  $Ui->report_errors($mapping);
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
