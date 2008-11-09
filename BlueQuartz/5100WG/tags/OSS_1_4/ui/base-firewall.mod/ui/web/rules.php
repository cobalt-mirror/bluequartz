<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id
// $Id: rules.php 3 2003-07-17 15:19:15Z will $
//

include("ServerScriptHelper.php");
include("ArrayPacker.php");
include("uifc/PagedBlock.php");
include("uifc/MultiChoice.php");
include("uifc/Option.php");

$iam = '/base/firewall/rules.php';

$start = gettimeofday();
function profile( $tag )
{
  global $start;
  $now = gettimeofday();
  $delta = 1000*1000*($now['sec'] - $start['sec']) + ($now['usec'] - $start['usec']) ;
  $delta = $delta / 1000000.0;
  echo "<b> $tag time = $delta <p>";
}

$serverScriptHelper = new ServerScriptHelper() or die ("no server-script-helper");
$i18n = $serverScriptHelper->getI18n("base-firewall");
$confirm_removal=$i18n->get('confirm_removal');
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-firewall", $iam);

// start our scrolling list
$page = $factory->getPage();

$ruleArray=array();

// profile('alpha');

///////////////////////////////////////////////////////////////////
// identify objects
///////////////////////////////////////////////////////////////////
$chain = $HTTP_POST_VARS['chain'];
if (!$chain) { $chain = $HTTP_GET_VARS['chain']; }
if ($chain) {
  $chain_oids = $cceClient->find("FirewallChain", array( 'name'=>$chain ) );
  if  (count($chain_oids) < 1) {
	$cceClient->create("FirewallChain", array( 'name' => $chain ));
	$chain_oids = $cceClient->find("FirewallChain",
		array('name' => $chain) );
  }
}
$sys_oids = $cceClient->find("System");


///////////////////////////////////////////////////////////////////
// handle confirm event
///////////////////////////////////////////////////////////////////
if ($HTTP_GET_VARS['confirm']) {
  $cceClient->set($sys_oids[0], "Firewall", array('watchdog' => '0'));
}

///////////////////////////////////////////////////////////////////
// handle disable event
///////////////////////////////////////////////////////////////////
if ($HTTP_GET_VARS['disable']) {
  $cceClient->set($sys_oids[0], "Firewall", array('enabled' => '0'));
}

//////////////////////////////////////////////////////////////////////////
// handle save/reorder event from rules list page
//////////////////////////////////////////////////////////////////////////
if ($HTTP_POST_VARS['_save'] && $chain) 
{
  $errors = array();


  // FIXME: handle error
  $chain_object = $cceClient->get($chain_oids[0]);
  $oid_list = stringToArray($chain_object['rules']);
  $new_order = array();
 
  // initialize array (otherwise an array_shift down the road complains )
  for ( $i=0; $i < count($oid_list); $i++) {
	if($HTTP_POST_VARS["rule".$oid_list[$i]] != $i+1){
		$new_order[$i] = array();

		$newidx=$HTTP_POST_VARS["rule".$oid_list[$i]]-1;
		if($newidx<0){
			$newidx=0;
		}
		for( $j=$i; $j<=$newidx; $j++){
			$new_order[$j]=array();
		}
	}else{
		$new_order[$i] = array();
	}
  }

  // go through our oid list. if it was on the web page, use that rank, 
  // othewise use it's rank in the list
  for ( $i=0; $i < count($oid_list); $i++) {

     if ( $rank = $HTTP_POST_VARS["rule".$oid_list[$i]] ) {
        array_unshift($new_order[$rank-1], $oid_list[$i]);
     } else {
        array_unshift($new_order[$i], $oid_list[$i]);
     }
  }
  //for ( $i =0; $i < count ($new_order); $i++) {
  //    echo "<br> new_order $i";
  //    var_dump($new_order[$i]);
  //}
  // take the array of arrays of oid's and flatten it out to a 
  // single array of oids
  $j = 0;
  for ($i =0; $i < count($new_order); $i++) {
     while ($a = array_shift($new_order[$i])) {
       $final_order[$j] = $a;
       $j++;
     }
  }
//  for ( $i =0; $i < count ($final_order); $i++) {
//        echo "<br> final_order $i";
//        var_dump($final_order[$i]);
//  }

  // write changes to the FirewallChain object:
  $cceClient->set($chain_oids[0], "", 
    array( 
      'default' => $HTTP_POST_VARS['defaultPolicy'],
      'rules' => arrayToString( $final_order )
    ) 
  );

////  // on success, redirect to top.php:  
////  if (count($errors) < 1) {
////    print ($serverScriptHelper->toHandlerHtml("/base/firewall/top.php", 
////      array(), "base-firewall"));
////    exit();
////  }
////  // otherwise, stay right here.
}

/////////////////////////////////////////////////////////////////////////
// deal with remove actions
/////////////////////////////////////////////////////////////////////////
$_REMOVE = $HTTP_GET_VARS['_REMOVE'];
if ($_REMOVE) {
  // echo "<li> removing $_REMOVE";
  $cceClient->destroy($_REMOVE);
}

//////////////////////////////////////////////////////////////////////////
// load useful objects.
//////////////////////////////////////////////////////////////////////////
$sys_obj = $cceClient->get($sys_oids[0], "Firewall");
if ($chain) {
  $chain_obj = $cceClient->get((int)$chain_oids[0], "");
} else {
  $chain_obj = array();
}

function num_cmp ( $a, $b ) {
  // good grief, this is going to be slow:
  if ((int)($a) == (int)($b)) return 0;
  return ((int)($a) > (int)($b) ) ? 1 : -1;
}

function get_list_of_rules(&$cceClient, &$factory, $chain_obj)
{
      	// profile ('glor.1');
	$chain = $chain_obj['name'];
	$rule_oids = stringToArray($chain_obj['rules']);

	$i18n = $factory->getI18n();
	$iam = '/base/firewall/rules.php';
	$chain_label = array();
	$chain_label[$i18n->get('Select_chain')] = $iam;
	$chain_label[$i18n->get('chain_input')] = $iam . "?chain=input";
	$chain_label[$i18n->get('chain_forward')] = $iam . "?chain=forward";
	$chain_label[$i18n->get('chain_output')] = $iam . "?chain=output";

      	// profile ('glor.2');

	$block = $factory->getScrollList("FirewallRules".$chain, 
		array(	"header_order", 
			"header_source",
			"header_dest",
			"header_policy",
			"header_action" ),
		array( 0) );

	$block->setAlignments(array(
		"center",
		"center",
		"center",
		"center",
		"center" ));
	$block->setColumnWidths( array( 1, "30%", "30%" ) );
	$block->setEmptyMessage($i18n->get('no_rules_defined'));

      	// profile ('glor.3');

	// $block->addButton(
	//   $factory->getMultiButton('', 
	//     array_values($chain_label),
	//     array_keys($chain_label)
	//   )
	// );
	

	global $ruleArray;
	$ruleArray=array();

	for ($i = 0; $i < count($rule_oids); $i++) {
        	// profile ("glor.8.$i");
	
		$rule = $cceClient->get($rule_oids[$i], "");
        	// profile ("glor.8.$i.1");
		if (!$rule) {
			break; // or php equivalent
		}

		// set up fields:
		$crit = "";
		$dest = "";

		if (!($rule['source_ip_start'] === '0.0.0.0')
		 || !($rule['source_ip_stop'] === '255.255.255.255'))
		{
		  $crit .= $i18n->get("criteria_ip", "", array(
		    "low" => $rule['source_ip_start'],
		    "high" => $rule['source_ip_stop'])) . "<br> ";
		}

		if ($rule['source_ports']) {
		  $crit .= $i18n->get("criteria_ports", "", array(
		    "ports" => $rule['source_ports'],
		    "proto" => $rule['protocol'] )
		    ) . "<br> ";
		}

		if (!($rule['dest_ip_start'] === '0.0.0.0')
		 || !($rule['dest_ip_stop'] === '255.255.255.255'))
		{
		  $dest .= $i18n->get("criteria_ip", "", array(
		    "low" => $rule['dest_ip_start'],
		    "high" => $rule['dest_ip_stop'])) . "<br> ";
		}

		if ($rule['dest_ports']) {
		  $dest .= $i18n->get("criteria_ports", "", array(
		    "ports" => $rule['dest_ports'],
		    "proto" => $rule['protocol'] )
		    ) . "<br> ";
		}
		
		if ($rule['protocol'] && !($rule['protocol'] === 'all')) {
		  $dest .= $i18n->get("criteria_proto", "", array(
		    "proto" => $rule['protocol'] ) ) . "<br> ";
		}

		if ($rule['interface']) {
		  $crit .= $i18n->get("criteria_interface", "", array(
		    "iface" => $rule['interface'] ) ) . "<br> ";
		}

		$policy = $i18n->get("policy_" . $rule['policy'], 
		  "", array('redir' => $rule['redir_target'],
		    'jump' => $rule['jump_target']));

		if ($crit === '') { 
		  $crit = $i18n->get('Any');
		}
		if ($dest === '') { 
		  $dest = $i18n->get('Any');
		}

        	// profile ("glor.8.aboutToAddEntry");
		$ruleNumber = $factory->getInteger(
			'rule'.$rule['OID'],
			($i+1),
			"", "", "rw");
		array_push($ruleArray, "rule".$rule["OID"]);
		$ruleNumber->setMin(1);
		$ruleNumber->setWidth(3);

		// disable the remove button for some rules:
		$modbut = $factory->getModifyButton(
			"/base/firewall/add.php?oid="
			.$rule_oids[$i] );
		$rembut = $factory->getRemoveButton(
			"/base/firewall/rules.php?_REMOVE="
			.$rule_oids[$i]."&chain=$chain");
		if ($rule["owner"] === "squid") {
			$modbut->setDisabled(true);
			$rembut->setDisabled(true);
		}

		$block->addEntry( array(
			$ruleNumber,
			$factory->getTextField("", $crit, "R"),
			$factory->getTextField("", $dest, "R"),
			$factory->getTextField("", $policy, "R"),
			$factory->getCompositeFormField(array(
			  $modbut,
			  $rembut,
			) ) 
		) );
        	// profile ("glor.8.$i.end");
	} // end of "for each rule" loop
	
      	// profile ('glor.10');
	$block->addButton(
	  $factory->getAddButton(
	    "/base/firewall/add.php?chain=" . $chain
	  )
	);

      	// profile ('glor.end');
	return $block;
}

//////////////////////////////////////////////////////////////////////
// generate HTML
//////////////////////////////////////////////////////////////////////
print($page->toHeaderHtml()); 

echo "";
echo '<!-- firewall navigation widget -->';
echo '<table width="550" border="0" cellpadding="0" cellspacing="2">';
echo '<tr valign="center"><td valign="center" align="left">';
print_multibutton();
echo '</td><td valign="center" align="right">';
print_applybutton();
echo '</td></tr>';
echo '</table><p>';

print "\n";


// profile('c');

if (!$chain) {
  
  // create the "general settings" page ...
  
  $old = $factory->getTextField( 'old_enabled', $sys_obj['enabled'], "" );
  print ( $old->toHtml() );
  
  $block = new PagedBlock ($page,
     "FirewallConfiguration",
     $factory->getLabel("FirewallConfiguration", true,
	     array("chain" => $chain)));
  $block->addFormField(
    $factory->getBoolean("enabled", $sys_obj['enabled']),
    $factory->getLabel("enableFirewall")
  );
  $block->addButton($factory->getSaveButton("javascript: save_firewall()"));
  print $block->toHtml();
  print "<p>\n";
  
  $popupmsg = $i18n->getJs("confirm-enabling-popup");

  echo '<SCRIPT LANGUAGE="javascript">  <!--';
  echo '
function save_firewall() {
  var msg = "' . $popupmsg . '";
  var f = document.form;
  var old = !(f.old_enabled.value == "0" || f.old_enabled.value == "");
  var en = (f.enabled.value == "true") || (f.enabled.value == "1") || (f.enabled.value);
  if (en == "0") { en = false; }
  // alert("old = " + old + "  new = " + en + "\n"
  //	+ "old.value = " + f.old_enabled.value + "\n"
  //	+ "enabled.value = " + f.enabled.value + "\n");

  if ((old) && !(en)) {
    // disable firewall:
    location = "/base/firewall/rules.php?disable=1";
    return true;
  }
  if (!(old) && (en)) {
    // enable firewall:
    if (confirm(msg)) {
      location = "/base/firewall/enable1.php";
      return true;
    }
  }
  location = "/base/firewall/rules.php";
  return false;
}

';
  echo '// --> </SCRIPT>';
  
} else {

  // create the rules list page ...

  // this instead of my nice $block->addHidden API.  fooey.
  $old_order = $factory->getTextField( 'old_order', $chain_obj['rules'], "" );
  print ( $old_order->toHtml() );

  $block = get_list_of_rules($cceClient, $factory, $chain_obj);
  print $block->toHtml();

  $block = new PagedBlock ($page,
	  "defaultPolicy", false);

  include ("uifc/TextField.php");
  $obj = new TextField($page, "chain", $chain, "", "");
  $obj->setAccess("");
  $block->addFormField($obj);
  $block->addDivider();

  // profile('e');

  // why uifc api needs help:
  $widget = new MultiChoice($page, "defaultPolicy");
  $widget->addOption(new Option(new Label($page, $i18n->get('policy_ACCEPT')),'ACCEPT'));
  $widget->addOption(new Option(new Label($page, $i18n->get('policy_DENY')),'DENY'));
  $widget->setSelected(
    ($chain_obj['default'] === 'ACCEPT') ? 0 : 1, 1);
  $widget->setValue(($chain_obj['default'] === 'ACCEPT') ? 'ACCEPT' : 'DENY');

  $block->addFormField( $widget, 
    $factory->getLabel("defaultPolicyField", true, 
      array('chain' => $chain)));

  // $block->addButton(
  //   $factory->getButton(
  //     $page->getSubmitAction(),
  //     'reorder-button'));
  $block->addButton($factory->getSaveButton($page->getSubmitAction()));
  //$block->addButton($factory->getBackButton("/base/firewall/top.php"));

  // profile('f');

  print($block->toHtml());
}

// profile('g');

?>

<SCRIPT LANGUAGE="javascript">
function orderChange(element){
	var errString = '<?php print($i18n->getJs("[[palette.defaultInvalidMessage]]"))?>';
	var name = '<?php print($i18n->getJs("[[base-firewall.header_order]]"))?>';
	if(!top.code.Integer_isIntegerValid(element.value, element.min, element.max)){
		errString=top.code.string_substitute(errString, "[[VAR.invalidValue]]", element.value);
		errString=top.code.string_substitute(errString, "[[VAR.name]]", name);
		errString=top.code.string_substitute(errString, "[[VAR.rule]]", "");
		top.code.error_invalidElement(element, errString);
		return false;
	}
	return true;
}

<?php
foreach($ruleArray as $r){
	print("document.form.$r.changeHandler=orderChange;\n");
}
?>
</SCRIPT>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid) {
  if(confirm(msg))
    location = "/base/firewall/rules.php?_REMOVE=" + oid;
}
</SCRIPT>

<?php 

print($page->toFooterHtml()); 

function print_multibutton()
{
  global $factory;
  $i18n = $factory->getI18n();
  $chain_label = array();
  $rules_url = '/base/firewall/rules.php';

  $chain_label[$i18n->get('general_settings')] = $rules_url;  
  $chain_label[$i18n->get('chain_input')] = $rules_url . "?chain=input";
  $chain_label[$i18n->get('chain_forward')] = $rules_url . "?chain=forward";
  $chain_label[$i18n->get('chain_output')] = $rules_url . "?chain=output";
  $mb = $factory->getMultiButton('Selectchain', 
      array_values($chain_label),
      array_keys($chain_label)
  );
  echo $mb->toHtml();
}

function print_applybutton()
{
  global $factory, $i18n;
  global $sys_obj;
  
  echo '<SCRIPT LANGUAGE="javascript">  <!--';
  echo '

function apply_changes() {
  var msg = "' . $i18n->getJs('apply-changes-popup') . '";
  if (confirm(msg)) {
    location = "/base/firewall/enable1.php";
    return true;
  } else {
    location = "/base/firewall/rules.php";
    return false;
  }
}

';
  echo '// --> </SCRIPT>';

  $button = $factory->getButton('javascript: apply_changes()', 'commit-changes-button');
  if (!$sys_obj['enabled']) {
    $button->setDisabled(true);
    $button->setDisabledDescription($i18n->get('firewall-not-enabled_help'));
  } else if (!$sys_obj['dirty']) {
    $button->setDisabled(true);
    $button->setDisabledDescription($i18n->get('firewall-not-dirty_help'));
  }
  echo $button->toHtml();
}
  

// profile('end');


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

