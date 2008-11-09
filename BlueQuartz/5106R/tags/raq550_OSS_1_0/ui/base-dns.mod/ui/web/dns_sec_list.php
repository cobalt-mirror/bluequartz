<?php
// List secondary dns authorities
// TODO: promote secondary to primaries
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: dns_sec_list.php 259 2004-01-03 06:28:40Z shibuya $
$iam = '/base/dns/dns_sec_list.php';
$edit = '/base/dns/dns_sec.php';
$parent = '/base/dns/dns.php';

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper() or die ("no SSH");
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");
$factory = $serverScriptHelper->getHtmlComponentFactory(
	"base-dns", $iam);
$i18n = $serverScriptHelper->getI18n("base-dns") or die ("no i18n");

$page = $factory->getPage();

// deal with remove actions
if ($_REMOVE) {
  $cceClient->destroy($_REMOVE);
}

// Grab system-DNS data
$sys_oid = $cceClient->find('System');
$sys_dns = $cceClient->get($sys_oid, 'DNS');

// pull-down add secondary service
$addList = array(	"add_secondary_forward" => "$edit?TYPE=FORWARD",
			"add_secondary_network" => "$edit?TYPE=NETWORK");
$addButton = $factory->getMultiButton("add_secondary", array_values($addList), array_keys($addList));

// build scroll list of mailing lists
$scrollList = $factory->getScrollList("sec_list", array("sec_authority", "sec_primaries", 'listAction'), array(0, 1));
$scrollList->setAlignments(array("left", "left", "center"));
$scrollList->setColumnWidths(array("", "", "1%"));

// $scrollList->addButton($factory->getAddButton($edit));

// populate elements in the scroll list
$oids = $cceClient->find("DnsSlaveZone");

for ($i = 0; $i < count($oids); $i++) {
  if ($oids[$i] != '') {
    $oid = $oids[$i];
    $rec = $cceClient->get($oid, "");

    if($rec['ipaddr'] != '') {
      $label = $rec['ipaddr'].'/'.$rec['netmask'];
      $type = 'NETWORK';
    } else {
      // domain auth
      $label = $rec['domain'];
      $type = 'DOMAIN';
    }

    $msg = $i18n->get("confirm_removal_of_sec");  // .$label.'?';

    $scrollList->addEntry( array(
      $factory->getTextField("", $label, "r"),
      $factory->getTextField("", $rec['masters'], "r"),
      $factory->getCompositeFormField(array(
        $factory->getModifyButton(
          "$edit?_TARGET=$oid&_LOAD=1&TYPE=$type" ),
        $factory->getRemoveButton(
          "javascript: confirmRemove('$msg', '$oid', '$label')")
      ))
    ));
  }
}

print $page->toHeaderHtml();

?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid, label) {
	msg = top.code.string_substitute(msg, "[[VAR.rec]]", label);
  if(confirm(msg))
    location = "<?php print $iam; ?>?_REMOVE=" + oid;
}
</SCRIPT>

<?php

print $addButton->toHtml();
print '<BR><BR>';

print $scrollList->toHtml();
print '<P>';

// Add commit and back buttons -- hack around uifc single-button formatting limitations
$commit_time = time();
$commitButton = $factory->getButton("/base/dns/dns.php?commit=$commit_time", "apply_changes");
if($sys_dns['dirty'] == 0) {
  $commitButton->setDisabled(true);
}

$backButton = $factory->getBackButton($parent);
?>

<P>

<CENTER>
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


<?php print $page->toFooterHtml();
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
