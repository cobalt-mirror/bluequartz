<?php

$Domain = "base-firewall";
if ($HTTP_GET_VARS['oid'] || $HTTP_POST_VARS['OID']) {
  $Title = "EditFirewallRule";
} else {
  $Title = "CreateFirewallRule";
}

// standard uifc header:
include_once("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();

// Only modifySystemFirewall should be here
if (!$serverScriptHelper->getAllowed('modifySystemFirewall')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory(
  $Domain, $PHP_SELF);
$i18n = $serverScriptHelper->getI18n("base-network");
$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();
$block = $factory->getPagedBlock($Title);

$Chain = $HTTP_GET_VARS['chain'];
if (!$Chain) {
	$Chain = $HTTP_POST_VARS['chain'];
}

// save handler:
if ($HTTP_POST_VARS['_save']) {
  save_rule();
}

$rule_obj = false;
// load data from CCE, if necessary and possible.
$oid = $HTTP_GET_VARS['oid'];
if (!$oid) $oid = $HTTP_POST_VARS['OID'];
if ($oid) {
  // echo "<li> get $oid";
  $rule_obj = $cceClient->get($oid);
  if (!($rule_obj['CLASS'] === 'FirewallRule')) {
    $rule_obj = false;
  }
  $rule_obj['OID'] = $oid;
  $rule_obj['chain'] = '';
}

// load data from POST request, if available
if ($HTTP_POST_VARS['OID'] || $HTTP_POST_VARS['chain'])
{
  $rule_obj = $HTTP_POST_VARS;
}

// otherwise, initialize from defaults:
if (!$rule_obj) {
  $ch = $HTTP_GET_VARS['chain'];
  if (!$ch) $ch = $HTTP_POST_VARS['chain'];
  $rule_obj = array(
    "OID" => '',
    "chain" => $ch,
    "policy" => 'ACCEPT',
    "source_ip_start" => '',
    "source_ip_stop" =>  '',
    "source_ports" => '',
    "dest_ip_start" => '',
    "dest_ip_stop" => '',
    "dest_ports" => '',
    "protocol" => 'all',
    "interface" => 'any',
    "redir_target" => '',
    "jump_target" => '',
    "owner" => 'user-created',
    "description" => ''
  );
}

// clean up a bit:
if ( ($rule_obj['source_ip_start'] === '0.0.0.0')
  && ($rule_obj['source_ip_stop'] === '255.255.255.255')) 
{
  $rule_obj['source_ip_start'] = '';
  $rule_obj['source_ip_stop'] = '';
}

if ( ($rule_obj['dest_ip_start'] === '0.0.0.0')
  && ($rule_obj['dest_ip_stop'] === '255.255.255.255')) 
{
  $rule_obj['dest_ip_start'] = '';
  $rule_obj['dest_ip_stop'] = '';
}

if (! $rule_obj['redir_target'] ) { $rule_obj['redir_target'] = ""; }
if (! $rule_obj['interface'] ) { $rule_obj['interface'] = 'any'; }
if (! $rule_obj['protocol'] ) { $rule_obj['protocol'] = 'tcp'; }

// hidden fields:
$w6 = $factory->getTextField("OID", $rule_obj['OID']);
$w6->setAccess("");
$block->addFormField($w6);
$w7 = $factory->getTextField("chain", $rule_obj['chain']);
$w7->setAccess("");
$block->addFormField($w7);

// form content:
$block->addDivider($factory->getLabel("SourceCriteriaSection"));
$w = $factory->getIpAddress("source_ip_start", $rule_obj['source_ip_start']);
$w->setOptional("silent");
$block->addFormField( $w,
  $factory->getLabel("source_ip_start_field"));
$w0=$factory->getIpAddress("source_ip_stop", $rule_obj['source_ip_stop']);
$w0->setOptional("silent");
$block->addFormField( $w0,
  $factory->getLabel("source_ip_stop_field"));
$w1=$factory->getIntRange("source_ports", $rule_obj['source_ports']);
$w1->setOptional("silent");
$block->addFormField( $w1,
  $factory->getLabel("source_ports_field"));

$block->addDivider($factory->getLabel("DestCriteriaSection"));
$w2=$factory->getIpAddress("dest_ip_start", $rule_obj['dest_ip_start']);
$w2->setOptional("silent");
$block->addFormField( $w2,
  $factory->getLabel("dest_ip_start_field"));
$w3=$factory->getIpAddress("dest_ip_stop", $rule_obj['dest_ip_stop']);
$w3->setOptional("silent");
$block->addFormField( $w3,
  $factory->getLabel("dest_ip_stop_field"));
$w4=$factory->getIntRange("dest_ports", $rule_obj['dest_ports']);
$w4->setOptional("silent");
$block->addFormField( $w4,
  $factory->getLabel("dest_ports_field"));

$block->addDivider($factory->getLabel("GeneralCriteriaSection"));
$mc = $factory->getMultiChoice("protocol",
    array("all", "tcp", "udp", "icmp", "ipip", "encap", "gre", "esp", "ah"));
$mc->setSelected($rule_obj['protocol'], 1);
$block->addFormField( $mc,
  $factory->getLabel("protocol_field"));
$mc0 = $factory->getMultiChoice("finterface",
    array("any","lo","eth0","eth1"));
$mc0->setSelected($rule_obj['interface'], 1);
$block->addFormField( $mc0,
  $factory->getLabel("interface_field"));

$block->addDivider($factory->getLabel("DescribePolicy"));
  $mc1 = $factory->getMultiChoice("policy",
    array("ACCEPT","DENY","REJECT"));
$mc1->setSelected($rule_obj['policy']);
$block->addFormField( $mc1,
  $factory->getLabel("policy_field"));

/*** we need to rethink this part:
 * $block->addDivider($factory->getLabel("AnnotationSection"));
 * $block->addFormField(
 *   $factory->getTextField("description", $rule_obj['description']),
 *   $factory->getLabel("description_field"));
 * $block->addFormField(
 *   $factory->getTextField("owner", $rule_obj['owner']),
 *   $factory->getLabel("owner_field"));
 ***/
 
$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/firewall/rules.php?chain=$Chain"));

// print page
print $page->toHeaderHtml();
print $block->toHtml();

// this is an incredibly ugly hack, but this is the way base-user does it,
// so ...
print "
<script language=\"javascript\">  

var old_source_handler = document.form.source_ip_start.onchange;
function source_ip_change()
{
  if (old_source_handler != null) {
     var ret = old_source_handler();  if (!ret) return ret;
  }
  var form = document.form;
  if (form.source_ip_stop.value == \"\") {
    form.source_ip_stop.value = form.source_ip_start.value;
  }
}
document.form.source_ip_start.onchange = source_ip_change;

var old_dest_handler = document.form.dest_ip_start.onchange;
function dest_ip_change()
{
  if (old_dest_handler != null) {
     var ret = old_dest_handler();  if (!ret) return ret;
  }
  var form = document.form;
  if (form.dest_ip_stop.value == \"\") {
    form.dest_ip_stop.value = form.dest_ip_start.value;
  }
}
document.form.dest_ip_start.onchange = dest_ip_change;

</script>
";

print $page->toFooterHtml();

function save_rule()
{
  global $HTTP_POST_VARS;
  global $cceClient;
  global $block;

  $failed = 0;
  
  $oid = $HTTP_POST_VARS['OID'];
  $chain = $HTTP_POST_VARS['chain'];
  if (!$oid && !chain) { 
    phpinfo();
    exit (1);
  }
  
  $rule_obj = array(
    "policy" => $HTTP_POST_VARS['policy'],
    "source_ip_start" => $HTTP_POST_VARS['source_ip_start'],
    "source_ip_stop" => $HTTP_POST_VARS['source_ip_stop'],
    "source_ports" => $HTTP_POST_VARS['source_ports'],
    "dest_ip_start" => $HTTP_POST_VARS['dest_ip_start'],
    "dest_ip_stop" => $HTTP_POST_VARS['dest_ip_stop'],
    "dest_ports" => $HTTP_POST_VARS['dest_ports'],
    "protocol" => $HTTP_POST_VARS['protocol'],
    "interface" => $HTTP_POST_VARS['finterface'],
    "redir_target" => $HTTP_POST_VARS['redir_target'],
    "jump_target" => $HTTP_POST_VARS['jump_target'],
    "owner" => $HTTP_POST_VARS['owner'],
    "description" => $HTTP_POST_VARS['description'],
  );
  if (!$rule_obj['source_ip_start']) 
    $rule_obj['source_ip_start'] = '0.0.0.0'; 
  if (!$rule_obj['dest_ip_start']) 
    $rule_obj['dest_ip_start'] = '0.0.0.0'; 
  if (!$rule_obj['source_ip_stop']) 
    $rule_obj['source_ip_stop'] = '255.255.255.255'; 
  if (!$rule_obj['dest_ip_stop']) 
    $rule_obj['dest_ip_stop'] = '255.255.255.255'; 
  if ($rule_obj['interface'] === 'any') {
    $rule_obj['interface'] = '';
  }
  
  if ($oid) {
    // set
    $ok = $cceClient->set($oid, "", $rule_obj);
    if (!$ok) $failed = 1;
  } else {
    // create
    $oid = $cceClient->create("FirewallRule", $rule_obj);
    if (!$oid) $failed = 1;
    // echo "<li> created $oid";
    $HTTP_POST_VARS['OID'] = $oid;
  }
  // handle errors.
  $block->process_errors($cceClient->errors());
  
  if ($chain && !$failed) {
    // append to chain
    // echo "<li> append to chain $chain";
    $oids = $cceClient->find("FirewallChain", array('name'=>$chain));
    if ($oids[0]) {
      $obj = $cceClient->get($oids[0], "");
      $rules = stringToArray($obj['rules']);
      array_push($rules, $oid);
      $ok = $cceClient->set($oids[0], "", array('rules' => arrayToString($rules)));
      if (!$ok) $failed = 1;
      // handle errors.
      $block->process_errors($cceClient->errors());
    } else {
      // FIXME: report "bad chain" error.
    }
  }
  
  // FIXME: if success, redir to rules.php
  if (!$failed) {
    global $Chain;
    $url = "/base/firewall/rules.php?chain=$Chain";
    print "<html><body onLoad=\"location = '$url';\">\n";
    print $block->reportErrors();
    print "</body></html>";
    exit();
  }
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

