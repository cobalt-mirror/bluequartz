<?php
/*
 * Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.
 * $Id: vsite_records.php 1050 2008-01-23 11:45:43Z mstauber $
 */
include_once("ServerScriptHelper.php");

$iam = '/base/dns/vsite_records.php'; 
$addmod = '/base/dns/vsite_dns_add.php'; 
$soamod = '/base/dns/vsite_dns_soa.php';

$serverScriptHelper = new ServerScriptHelper() or die ("no server-script-helper");

// Only dnsAdmin should be here 
if (!$serverScriptHelper->getAllowed('siteDNS')) {
	header("location: /error/forbidden.html");
	return; 
}

$i18n = $serverScriptHelper->getI18n("base-dns"); 
$confirm_removal = $i18n->get('confirm_removal'); 
$confirm_delall = $i18n->get('confirm_delall'); 
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE"); 
$factory = $serverScriptHelper->getHtmlComponentFactory("base-dns", $iam); 
$records_title_separator = ' - ';

if ( ! $serverScriptHelper->getAllowed('adminUser') ) {
	$user = $cceClient->getObject("User", array("name" => $loginName));
	$group = $user["site"]; 
}

if ($HTTP_GET_VARS['commit']) {
	// Apply changes from records.php
	$cceClient->setObject('System', array("commit" => $HTTP_GET_VARS['commit']), 'DNS');
	$cceClient->commit(); 
}

$nm_to_dec = array(
	"0.0.0.0"  => "0",
	"128.0.0.0" => "1", "255.128.0.0" => "9", "255.255.128.0" => "17", "255.255.255.128" => "25",
	"192.0.0.0" => "2", "255.192.0.0" => "10", "255.255.192.0" => "18", "255.255.255.192" => "26",
	"224.0.0.0" => "3", "255.224.0.0" => "11", "255.255.224.0" => "19", "255.255.255.224" => "27",
	"240.0.0.0" => "4", "255.240.0.0" => "12", "255.255.240.0" => "20", "255.255.255.240" => "28",
	"248.0.0.0" => "5", "255.248.0.0" => "13", "255.255.248.0" => "21", "255.255.255.248" => "29",
	"252.0.0.0" => "6", "255.252.0.0" => "14", "255.255.252.0" => "22", "255.255.255.252" => "30",
	"254.0.0.0" => "7", "255.254.0.0" => "15", "255.255.248.0" => "23", "255.255.255.254" => "31",
	"255.0.0.0" => "8", "255.255.0.0" => "16", "255.255.255.0" => "24", "255.255.255.255" => "32" ); 
	$dec_to_nm = array_flip($nm_to_dec);

// start our scrolling list 
$page = $factory->getPage();

// deal with remove actions 
$errors = array(); 
if ($_REMOVE) {
	$cceClient->destroy($_REMOVE);
	$errors = $cceClient->errors(); 
} 

if ($_DELMANY) {
	$death_row = preg_split('/x/', $_DELMANY);

	rsort($death_row);
	for ($i = 0; $i < $death_row[0]; $i++) {
		if($death_row[$i] != '') {
			$cceClient->destroy($death_row[$i]);
		}
	}
	$errors = $cceClient->errors(); 
}

if ( $serverScriptHelper->getAllowed('adminUser') ) {
	if ( $_save == "1" ) {
		$cfg = array("domains" => $dnsNames);
		$cceClient->setObject("Vsite", $cfg, "DNS", array('name' => $group));
	}
}

$vsite = $cceClient->getObject('Vsite', array('name' => $group)); 
$vsite_dns = $cceClient->getObject('Vsite', array('name' => $group), "DNS");

if ( $serverScriptHelper->getAllowed('adminUser') ) {
	include_once("array.inc");

	// how to get web aliases & email aliases? 
	$_settings = $factory->getPagedBlock("dnsNames_header");

	$webAliases = $cceClient->scalar_to_array($vsite["webAliases"]); 
	$mailAliases = $cceClient->scalar_to_array($vsite["mailAliases"]); 
	$availableAliases = array_merge_alt($webAliases, $mailAliases);

	$key = array_search($vsite["domain"], $availableAliases);
	if ( $key === FALSE ) 
		array_push($availableAliases, $vsite["domain"]);

	$allAliases = $cceClient->scalar_to_array($vsite_dns["domains"]);

	$picklist = $factory->getSetSelector("dnsNames",
		arrayToString($allAliases), arrayToString($availableAliases),
		"selected", "notSelected","rw",arrayToString($selectedVals),
		arrayToString($notSelectedVals));
	$picklist->setOptional(true);

	$_settings->addFormField($picklist,
		$factory->getLabel("dnsNames"));

	$_settings->addFormField(
		$factory->getTextField("group", $group, ""),
		$factory->getLabel("empty"));

	$_settings->addButton($factory->getSaveButton($page->getSubmitAction()));
}

// Grab system-DNS data 
$sys_oid = $cceClient->find('System'); 
$sys_dns = $cceClient->get($sys_oid, 'DNS');

// Abstract our authorities list 
// build a pull-down menu, select a default authority 
$oids = $cceClient->find("DnsSOA"); 
$rec_oids = array(); 
$smallnet = array(); 
$auth_dom_oids = array(); 
$auth_net_oids = array();

$allAliases = $cceClient->scalar_to_array($vsite_dns["domains"]);

foreach ( $allAliases as $mydomain ) {
	$rec["domainname"] = $mydomain;
	$authorities_dom[$rec["domainname"]] = "$iam?group=" . $group . "&domauth=".urlencode($rec["domainname"]); 
	$authorities_dom_label[$rec["domainname"]] = "$iam?group=" . $group . "&domauth=".urlencode($rec["domainname"]); 

	$oids = $cceClient->find("DnsSOA", array("domainname" => $mydomain));
	$auth_oids[$rec['domainname']] = $oids[0];
	$default_domauth = $rec["domainname"];
}

// Actually default 
$title_authority = $domauth; 
if ($title_authority == '') {
	$title_authority = urldecode($netauth); 
} 
if (($domauth == '') && ($netauth == '')) {
	$domauth = $default_domauth;
	if ($title_authority == '') {
		$title_authority = $default_domauth;
	}
	$netauth = $default_netauth;
	if ($title_authority == '') {
		$title_authority = urldecode($default_netauth);
	} 
} 
if ($title_authority != '') {
	$title_members = preg_split('/\//', $title_authority);
	$title_authority = $records_title_separator . $title_members[0];
	if ($title_members[1] != '') {
		$title_authority .= '/' . $dec_to_nm[$title_members[1]];
	} 
}

// start the table 
$block = $factory->getScrollList($i18n->get('dnsSetting') . $title_authority,
				 array("source", "direction", "resolution",
				       "listAction"),
				 array(1,0,2)); 
$block->setAlignments(array("left", "center", "left", "center")); 
$block->setColumnWidths(array("", "", "", "1%")); 
$block->setLength(999999);

/*
 * Preserve the selected authority between menus by appending the
 * $auth_link to hyperlinks
 */ 
if($domauth != '') {
	$domauth = urldecode($domauth);
	$rec_oids = $cceClient->find("DnsRecord",
				      array('domainname' => $domauth));
	$auth_link = '&domauth=' . $domauth;
	if ( $auth_oids[$domauth] ) {
		$block->addButton($factory->getButton("$soamod?group=$group&_LOAD=" . $auth_oids[$domauth] . $auth_link,"edit_soa"));
	}
	$many_oids = join('x', $rec_oids);
	$block->addButton($factory->getButton("javascript: confirmDelAll(strConfirmDelAll, '_DELMANY=$many_oids');", 'del_records'));

} else if ($netauth != '') {

	$netauth = urldecode($netauth);
	$rec_oids = $cceClient->find("DnsRecord", array('network' => $netauth));
	$auth_link = '&netauth=' . urlencode($netauth);
	$block->addButton($factory->getButton("$soamod?_LOAD=" . $auth_oids[$netauth] . $auth_link,"edit_soa"));
	$many_oids = join('x', $rec_oids);
	$block->addButton($factory->getButton("javascript: confirmDelAll(strConfirmDelAll, '_DELMANY=$many_oids');", 'del_records'));

}

if (count($rec_oids) == 0) {
	$rec_oids = $cceClient->find("DnsRecord", array('domainname' => $domauth)); }


// Array of labels => actions for "add a record" menu 
$addRecordsList = array("a_record" => "vsite_dns_add.php?group=" . $group . "&TYPE=A" . $auth_link,
			"mx_record" => "vsite_dns_add.php?group=" . $group . "&TYPE=MX" . $auth_link,
			"cname_record" => "vsite_dns_add.php?group=" . $group . "&TYPE=CNAME" . $auth_link,
			"txt_record" => "vsite_dns_add.php?group=" . $group . "&TYPE=TXT" . $auth_link);

if ($domauth != '') {
	$addRecordsList['subdom'] = "vsite_dns_add.php?group=" . $group . "&TYPE=SUBDOM" . $auth_link; } 
else if ($netauth != '') {
	$addRecordsList['subnet'] = "vsite_dns_add.php?group=" . $group . "&TYPE=SUBNET" . $auth_link; }

$addButton = $factory->getMultiButton("add_record",
				      array_values($addRecordsList),
				      array_keys($addRecordsList));

// display records 
rsort($rec_oids); 
if(count($rec_oids)) {
	for ($i = 0; $i < $rec_oids[0]; $i++) {
		if($rec_oids[$i] != '') {
			$oid = $rec_oids[$i];
			$rec = $cceClient->get($oid, "");

			/*
			 * we could add a recordtype if structure to build the
			 * scrollist entries aesthetically
			 * all records define
			 * { $source, $direction, $resolution, $label }
			 */
			$direction = $rec['type'];
			$resolution = '';
			$source = '';
		
			if ($rec['type'] == 'A') {
				if($rec['hostname']) {
					$source = $rec['hostname'] . ' . ';
				}
				$source .= $rec['domainname'];
				$direction = $i18n->get('a_dir');
				$resolution = $rec['ipaddr'];
				$label = $rec['hostname'] . '.' .
					$rec['domainname'];

			} else if($rec['type'] == 'PTR') {
				$source = $rec['ipaddr'];
				if ($domauth) {
					$source .= '/' . $rec['netmask'];
				}
				if ($rec['hostname'] != '') {
					$resolution = $rec['hostname'] . ' . ';
				}
				$direction = $i18n->get('ptr_dir');
				$resolution .= $rec['domainname'];
				$label = $rec['ipaddr'] . '/' . $rec['netmask'];

			} else if ($rec['type'] == 'CNAME') {
				if($rec['hostname'] != '') {
					$source = $rec['hostname'].' . ';
				}
				$source .= $rec['domainname'];
				$direction = $i18n->get('cname_dir');
				if ($rec['alias_hostname'] != '') {
					$resolution = $rec['alias_hostname'] .
						' . ';
				}
				$resolution .= $rec['alias_domainname'];
				$label = $rec['alias_hostname'] . '.' .
					$rec['domainname'];

			} else if ($rec['type'] == 'MX') {
				if($rec['hostname']) {
					$source = $rec['hostname'] . ' . ';
				}
				$source .= $rec['domainname'];
				$resolution = $rec['mail_server_name'];
				$direction = $i18n->get('mx_dir_' .
					$rec['mail_server_priority']);
				$label = $rec['hostname'] . '.' .
					$rec['domainname'];

			} else if ($rec['type'] == 'TXT') {
				if($rec['hostname']) {
					$source = $rec['hostname'] . ' . ';
				}
				$source .= $rec['domainname'];
				$resolution = $rec['strings'];
				$direction = $i18n->get('txt_dir');
				$label = $rec['hostname'] . '.' .
					$rec['domainname'];

			} else if ($rec['type'] == 'SN') {
				if($rec['ipaddr']) {
					$rec['type'] = 'SUBNET';
					$direction = $i18n->get('subnet_dir');

					$smallnet = preg_split('/\//', $rec['network_delegate']);
					$source = $smallnet[0] . '/' .
						$dec_to_nm[$smallnet[1]];
					$resolution = $rec['delegate_dns_servers'];
					$label = $rec['ipaddr'] . '/' .
						$rec["netmask"];
				} else {
					$rec['type'] = 'SUBDOM';
					$direction = $i18n->get('subdom_dir');

					$source = $rec['hostname'].' . '.$rec['domainname'];
					$resolution = $rec['delegate_dns_servers'];
					$label = $rec['hostname'].'.'.$rec['domainname'];
				}
				$resolution = preg_replace('/^&/', '', $resolution);
				$resolution = preg_replace('/&$/', '', $resolution);
				$resolution = preg_replace('/&/', ' ', $resolution);
			} else {
				next;
				echo "unkown type: ".$rec['type']."\n";
			}
	
			$block->addEntry(array(
				$factory->getTextField("", $source, "r"),
				$factory->getTextField("", $direction, "r"),
				$factory->getTextField("", $resolution, "r"),
				$factory->getCompositeFormField(array(
					$factory->getModifyButton( "$addmod?_PagedBlock_selectedId_blockid0=_".$rec['type']."&_TARGET=$oid&_LOAD=1&TYPE=".$rec['type'].$auth_link . "&group=$group"),
					$factory->getRemoveButton( "javascript: confirmRemove(strConfirmRemoval, '$oid', '$label', '$domauth', '$netauth')" )
	
				))
			));
		}
	} }

$domauthRemember = $factory->getTextField('domauth', $domauth, ''); 
$domauthRemember->setPreserveData(false); 
$netauthRemember = $factory->getTextField('netauth', $netauth, ''); 
$netauthRemember->setPreserveData(false);

$serverScriptHelper->destructor();

print($page->toHeaderHtml()); 
?> 
<SCRIPT LANGUAGE="javascript"> 
  // these need to be defined seperately or Japanese gets corrupted 
  var strConfirmRemoval = '<?php print $confirm_removal; ?>'; 
  var strConfirmDelAll = '<?php print $confirm_delall; ?>'; 
</SCRIPT> 
<? 
if ( $serverScriptHelper->getAllowed('adminUser') ) {
	print $_settings->toHtml(); 
	print "<br>"; 
}

if ( $vsite_dns["domains"] ) {
	print $domauthRemember->toHtml();
	print $netauthRemember->toHtml();


	if(count($authorities_dom_label) > 0) {
        	// select-an-authority button
	        ksort($authorities_dom_label);
        	$authorityDomButton = $factory->getMultiButton("select_dom", array_values($authorities_dom_label), array_keys($authorities_dom_label));
	        print($authorityDomButton->toHtml());
        	print("&nbsp;");
	}

	print($addButton->toHtml());
	print("<P>");

	print($block->toHtml()); 

	// Add commit and back buttons -- hack around uifc single-button formatting limitations
	// Gray-out the commit button if there are no uncommitted changes
	// print "Sys oid: $sys_oid, sys_dirty: ".$sys_dns['dirty'];
	$commit_time = time();
	$commitButton = $factory->getButton("/base/dns/vsite_records.php?group=$group&commit=$commit_time", "apply_changes");
//	if($sys_dns['dirty'] == 0) {
//	        $commitButton->setDisabled(true);
//	}
?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid, label, domauth, netauth) {
        // var msg = "<?php print($i18n->get("removeUserConfirm"))?>";
        msg = top.code.string_substitute(msg, "[[VAR.rec]]", label);
         
        if(confirm(msg))
                location = "/base/dns/vsite_records.php?group=<?=$group;?>&_REMOVE=" + oid +
                        "&domauth=" + domauth + "&netauth=" + netauth;
}

function confirmDelAll(msg, url) {
        if(confirm(msg))
                location = "/base/dns/vsite_records.php?group=<?=$group;?>&" + url;
}
</SCRIPT>

<BR>

<TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2>
<TR>
                <TD NOWRAP>
                <?php print($commitButton->toHtml()); ?>
                </TD>
                <TD NOWRAP>
                </TD>
</TR>
</TABLE>

<?php
	// output any error messages
	if (count($errors) > 0) {
        	print "<SCRIPT LANGUAGE=\"javascript\">\n";
	        print $serverScriptHelper->toErrorJavascript($errors);
        	print "</SCRIPT>\n";
	}
} else {
	if ( ! $serverScriptHelper->getAllowed('adminUser') ) {
		$nodns = $factory->getPagedBlock("nodnsmanage");
		$nodns->setColumnWidths(array('0%',"100%"));

		$dnsempty = $i18n->interpolate("[[dnsempty]]");
		$dnsempty = str_replace("<br>", "\n", $dnsempty);

		$box = $factory->getTextBlock("", $dnsempty, "r");
		$box->setHeight("20");
		$box->setWidth("40");

		$nodns->addFormField($box,'');

		echo $nodns->toHtml();	
	}
}
print($page->toFooterHtml());


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
