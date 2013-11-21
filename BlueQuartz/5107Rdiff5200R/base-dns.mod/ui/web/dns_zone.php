<?php
// $Id: dns_soa.php 1371 2010-01-18 11:27:32Z shibuya $
//
// ui for adding/modifying many DNS record types
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
  $Ui->Redirect( $ret_url );
  exit();
} elseif ($HTTP_GET_VARS{'netauth'} != '') {
  $ret_url = $parent.'?netauth='.urlencode($HTTP_GET_VARS{'netauth'});
} else {
  $ret_url = $parent;
}

include_once("CobaltUI.php");
$Ui = new CobaltUI($sessionId, "base-dns"); 

// mapping: lists a form field name to an object attribute.
$mapping = array (
  "zone_format" => "zone_format",
  "zone_user_format" => "zone_user_format" );

// handler:
if (!$_TARGET) { $_TARGET = $HTTP_GET_VARS['_LOAD']; }
$done = handle($Ui, $_TARGET, $mapping, $HTTP_POST_VARS, $HTTP_GET_VARS);

# prevent PHP from sneakily adding new hidden fields:
if (is_array($HTTP_POST_VARS)) {
  $keys = array_keys($HTTP_POST_VARS);
  $index = array_keys($keys, "_LOAD"); array_splice($HTTP_POST_VARS, $index[0], 1);
  $index = array_keys($keys, "_save"); array_splice($HTTP_POST_VARS, $index[0], 1);
}

error_log("Starting page...");
error_log("Target $_TARGET");
$Ui->StartPage("SET", $_TARGET ? $_TARGET : "DnsSOA", "");

error_log("Starting block...");
error_log(intval($_TARGET));
$Ui->StartBlock((intval($_TARGET) > 0) ? "modify_zone" : "create_zone");

// Return to the records list on successful save
if ($done) {
  $Ui->Redirect( $ret_url );
  exit();
}

error_log("Filling in fields...");
$Ui->Data['network_soa'] = $Ui->Data['ipaddr'].'/'.$Ui->Data['netmask'];
$Ui->TextField( 'network_soa', array( 'access' => 'r' ) );
$Ui->Alters( "zone_format", array('SERVER','RFC2317','DION','OCN-JT','USER'));
$Ui->TextField( "zone_user_format", array( "Optional" => 'loud' ) );


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
  $Ui->Data['ipaddr'] = $rec['ipaddr'];
  $Ui->Data['netmask'] = $rec['netmask'];
  $Ui->Data['zone_format'] = $rec['zone_format'];
  $Ui->Data['zone_user_format'] = $rec['zone_user_format'];
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
?>
