<?php
// Author: Will DeHaan, will@cobalt.com, Eric Braswell, ebraswell@cobalt.com
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: records.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");

$iam = '/base/dns/records.php';
$addmod = '/base/dns/dns_add.php';
$soamod = '/base/dns/dns_soa.php';

$serverScriptHelper = new ServerScriptHelper() or die ("no server-script-helper");
$i18n = $serverScriptHelper->getI18n("base-dns");
$confirm_removal=$i18n->get('confirm_removal');
$confirm_delall=$i18n->get('confirm_delall');
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-dns", $iam);
$records_title_separator = '   -   ';

$nm_to_dec = array(
  "0.0.0.0"   => "0",
  "128.0.0.0" => "1", "255.128.0.0" => "9",  "255.255.128.0" => "17", "255.255.255.128" => "25",
  "192.0.0.0" => "2", "255.192.0.0" => "10", "255.255.192.0" => "18", "255.255.255.192" => "26",
  "224.0.0.0" => "3", "255.224.0.0" => "11", "255.255.224.0" => "19", "255.255.255.224" => "27",
  "240.0.0.0" => "4", "255.240.0.0" => "12", "255.255.240.0" => "20", "255.255.255.240" => "28",
  "248.0.0.0" => "5", "255.248.0.0" => "13", "255.255.248.0" => "21", "255.255.255.248" => "29",
  "252.0.0.0" => "6", "255.252.0.0" => "14", "255.255.252.0" => "22", "255.255.255.252" => "30",
  "254.0.0.0" => "7", "255.254.0.0" => "15", "255.255.254.0" => "23", "255.255.255.254" => "31",
  "255.0.0.0" => "8", "255.255.0.0" => "16", "255.255.255.0" => "24", "255.255.255.255" => "32" );
$dec_to_nm = array_flip($nm_to_dec);

// start our scrolling list
$page = $factory->getPage();

// deal with remove actions
if ($_REMOVE) {
  $cceClient->destroy($_REMOVE);
}
if ($_DELMANY) {
  $death_row = split('x', $_DELMANY);

  rsort($death_row);
  for ($i = 0; $i < $death_row[0]; $i++) {
    if($death_row[$i] != '') {
      $cceClient->destroy($death_row[$i]);
    }
  }
}

// Grab system-DNS data
$sys_oid = $cceClient->find('System');
$sys_dns = $cceClient->get($sys_oid, 'DNS');

// Abstract our authorities list
// build a pull-down menu, select a default authority
$oids = $cceClient->find("DnsSOA");
$rec_oids = array();
$auth_dom_oids = array();
$auth_net_oids = array();

rsort($oids);
if(count($oids)) { // Any current records?
	for ($i = 0; $i <= $oids[0]; $i++) {
		if($oids[$i] != '') {
			$rec = $cceClient->get($oids[$i], "");

			if ($rec["domainname"] != "") {
				$authorities_dom[$rec["domainname"]] = "$iam?domauth=".urlencode($rec["domainname"]);
				$authorities_dom_label[$rec["domainname"]] = "$iam?domauth=".urlencode($rec["domainname"]);
				$auth_oids[$rec['domainname']] = $oids[$i];
				if($default_domauth == '') { $default_domauth = $rec['domainname']; }
			}

			if ($rec["ipaddr"] != "") {
				$network_label = $rec["ipaddr"].'/'.$rec["netmask"];
				$network = $rec["ipaddr"].'/'.$nm_to_dec[$rec["netmask"]];
				$authorities_net[$network] = "$iam?netauth=".urlencode($network);
				$authorities_net_label[$network_label] = "$iam?netauth=".urlencode($network);
				$auth_oids[$network] = $oids[$i];
				if($default_netauth == '') { $default_netauth = urlencode($network); }
			}

		}
	}
}

// Actually default
$title_authority = $HTTP_GET_VARS['domauth'];
if($title_authority == '') { $title_authority = urldecode($HTTP_GET_VARS['netauth']); }
if(($HTTP_GET_VARS['domauth'] == '') && ($HTTP_GET_VARS['netauth'] == '')) { 
  $HTTP_GET_VARS['domauth'] = $default_domauth;
  if($title_authority == '') { $title_authority = $default_domauth; }
  $HTTP_GET_VARS['netauth'] = $default_netauth; 
  if($title_authority == '') { $title_authority = urldecode($default_netauth); }
}
if($title_authority != '') { 
  // $title_authority = $records_title_separator.$title_authority; 
  $title_members = split('/', $title_authority);
  $title_authority = $records_title_separator.$title_members[0];
  if ($title_members[1] != '') { $title_authority .= '/'.$dec_to_nm[$title_members[1]]; }
}

// start the table
$block = $factory->getScrollList($i18n->get('dnsSetting')."   $title_authority", array("source", "direction", "resolution", "listAction"));
$block->setAlignments(array("left", "left", "left", "center"));
$block->setColumnWidths(array("", "", "", "1%"));

// Preserve the selected authority between menus by appending the $auth_link to hyperlinks
if($HTTP_GET_VARS['domauth'] != '') {
	$domauth=urldecode($HTTP_GET_VARS['domauth']);
	$rec_oids = $cceClient->find("DnsRecord", array('domainname' => $domauth));
	$auth_link = '&domauth='.$HTTP_GET_VARS['domauth'];
	$block->addButton($factory->getButton("$soamod?_LOAD=".$auth_oids[$domauth].$auth_link,"edit_soa"));
        $many_oids = join('x', $rec_oids);
	$block->addButton($factory->getButton("javascript: confirmDelAll('$confirm_delall', '_DELMANY=$many_oids');", 'del_records'));
	// $block->addButton($factory->getButton("javascript: confirmDelAll('$confirm_delall', '_DELMANY=".$HTTP_GET_VARS['domauth']."');", 'del_records'));
} elseif($HTTP_GET_VARS['netauth'] != '') {
	$netauth=urldecode($HTTP_GET_VARS['netauth']);
	$rec_oids = $cceClient->find("DnsRecord", array('network' => $netauth));
	$auth_link = '&netauth='.urlencode($HTTP_GET_VARS['netauth']);
	$block->addButton($factory->getButton("$soamod?_LOAD=".$auth_oids[$netauth].$auth_link,"edit_soa"));
        $many_oids = join('x', $rec_oids);
	$block->addButton($factory->getButton("javascript: confirmDelAll('$confirm_delall', '_DELMANY=$many_oids');", 'del_records'));
}

if(count($rec_oids) == 0) {
	$rec_oids = $cceClient->find("DnsRecord");
}


//  Array of labels => actions for "add a record" menu
$addRecordsList = array(	"a_record" => "dns_add.php?TYPE=A".$auth_link,
				"ptr_record" => "dns_add.php?TYPE=PTR".$auth_link,
				"mx_record" => "dns_add.php?TYPE=MX".$auth_link,
				"cname_record" => "dns_add.php?TYPE=CNAME".$auth_link);
//				"subdom" => "dns_add.php?TYPE=SUBDOM".$auth_link,
//				"subnet" => "dns_add.php?TYPE=SUBNET".$auth_link);

$addButton = $factory->getMultiButton("add_record", array_values($addRecordsList), array_keys($addRecordsList));

// display records
rsort($rec_oids);
if(count($rec_oids)) { 
	$block->setLength(count($rec_oids));
	for ($i = 0; $i < $rec_oids[0]; $i++) {
		if($rec_oids[$i] != '') {
			$oid = $rec_oids[$i];
			$rec = $cceClient->get($oid, "");

			// we could add a recordtype if structure to build the scrollist entries aesthetically
			// all records define { $source, $direction, $resolution, $label }
			$direction = $rec['type'];
			$resolution = ''; $source = '';
		
			if($rec['type'] == 'A') {
				if($rec['hostname'] != '') { $source = $rec['hostname'].' . '; }
				$source .= $rec['domainname'];
				$resolution = $rec['ipaddr'];
				$label = $rec['hostname'].'.'.$rec['domainname'];
			} elseif($rec['type'] == 'PTR') {
				$source = $rec['ipaddr'].'/'.$rec['netmask'];
				if($rec['hostname'] != '') { $resolution = $rec['hostname'].' . '; }
				$resolution .= $rec['domainname'];
				$label = $rec['ipaddr'].'/'.$rec['netmask'];
			} elseif($rec['type'] == 'CNAME') {
				if($rec['hostname'] != '') { $source = $rec['hostname'].' . '; } 
				$source .= $rec['domainname'];
				// $direction = $i18n->get('cname_dir');
				if($rec['alias_hostname'] != '') { $resolution = $rec['alias_hostname'].' . '; }
				$resolution .= $rec['alias_domainname'];
				$label = $rec['hostname'].'.'.$rec['domainname'];
			} elseif($rec['type'] == 'MX') {
				if($rec['hostname'] != '') { $source = $rec['hostname'].' . '; }
				$source .= $rec['domainname'];
				$resolution = $rec['mail_server_name'];
				// $direction = $i18n->get('mx_dir');
				$label = $rec['hostname'].'.'.$rec['domainname'];
			} elseif($rec['type'] == 'SN') {
				if($rec['ipaddr'] != '') {
					$source = $rec['ipaddr'].'/'.$rec['netmask'];
					$resolution = $rec['delegate_pri_dns'];
					$label = $rec['ipaddr'].' / '.$rec["netmask"];
					$direction = $i18n->get('subnet');
				} else {
					$source = $rec['hostname'].' . '.$rec['domainname'];
					$resolution = $rec['delegate_pri_dns'];
					$label = $rec['hostname'].'.'.$rec['domainname'];
					$direction = $i18n->get('subdom');
				}
			} else {
				next;
				echo "unkown type: ".$rec['type']."\n";
			}
	
			$block->addEntry(array(
				$factory->getTextField("", $source, "r"),
				$factory->getTextField("", $direction, "r"),
				$factory->getTextField("", $resolution, "r"),
				$factory->getCompositeFormField(array(
					$factory->getModifyButton( "$addmod?_PagedBlock_selectedId_blockid0=_".$rec['type']."&_TARGET=$oid&_LOAD=1&TYPE=".$rec['type'].$auth_link ),
					$factory->getRemoveButton( "javascript: confirmRemove('$confirm_removal', '$oid', '$label')" )
	
				))
			));
		}
	}
}

$serverScriptHelper->destructor();

print($page->toHeaderHtml()); 

if(count($authorities_dom_label) > 0) {
  // select-an-authority button
  ksort($authorities_dom_label);
  $authorityDomButton = $factory->getMultiButton("select_dom", array_values($authorities_dom_label), array_keys($authorities_dom_label));
  print($authorityDomButton->toHtml());
  print("&nbsp;");
}
if(count($authorities_net_label) > 0) {
  // select-an-authority button
  ksort($authorities_net_label);
  $authorityNetButton = $factory->getMultiButton("select_net", array_values($authorities_net_label), array_keys($authorities_net_label));
  print($authorityNetButton->toHtml());
  // print("&nbsp;");
  print("&nbsp;");
}

print($addButton->toHtml());
print("<P>");


print($block->toHtml()); 

// Add commit and back buttons -- hack around uifc single-button formatting limitations
// Gray-out the commit button if there are no uncommitted changes
//print "Sys oid: $sys_oid, sys_dirty: ".$sys_dns['dirty'];
$commit_time = time();
$commitButton = $factory->getButton("/base/dns/dns.php?commit=$commit_time", "apply_changes");
if($sys_dns['dirty'] == 0) {
  $commitButton->setDisabled(true);
}

$backButton = $factory->getBackButton("/base/dns/dns.php");
?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid, label) {
  // var msg = "<?php print($i18n->get("removeUserConfirm"))?>";
  msg = top.code.string_substitute(msg, "[[VAR.rec]]", label);
	 
  if(confirm(msg))
    location = "/base/dns/records.php?_REMOVE=" + oid;
}

function confirmDelAll(msg, url) {
  if(confirm(msg))
    location = "/base/dns/records.php?" + url;
}
</SCRIPT>

<BR>

<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
<TR>
    <TD NOWRAP>
    <?php print($commitButton->toHtml()); ?>
    </TD>
    <TD NOWRAP>
    <?php print($backButton->toHtml()); ?>
    </TD>
</TR>
</TABLE>
  
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

