<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: warList.php,v 1.8 2001/12/08 00:58:05 pbaltz Exp $

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper() or die ("no SSH");
$cceClient = $serverScriptHelper->getCceClient() or die ("no CCE");
$factory = $serverScriptHelper->getHtmlComponentFactory(
	"base-java", "/base/java/warList.php");
$i18n = $serverScriptHelper->getI18n("base-java") or die ("no i18n");

$page = $factory->getPage();

$errors = $serverScriptHelper->getErrors();

// deal with remove actions
if ($_REMOVE) {
  // don't bother if cce is suspended, because it will fail
  if ($cceClient->suspended() !== false)
  {
  	$msg = $cceClient->suspended() ? $cceClient->suspended() : '[[base-cce.suspended]]';
	$errors = array(new Error($msg));
  }
  else
  {
  	$war = $cceClient->get($_REMOVE);

  	// Delete the installation directory.  Aggressive, eh?
  	// start by building the path with site volume, group, and war install path
  	$vsite = $cceClient->getObject('Vsite', array('name' => $group));

  	if(($war['name'] != '') && ($vsite['basedir'] != '')) {
    	$path = $vsite['basedir'].'/web/'.$war['name'];
    	$serverScriptHelper->shell("/bin/rm -rf \"$path\"", $output); // bombs away
  	}

  	$cceClient->destroy($_REMOVE);
  }
}

// build scroll list of mailing lists
$scrollList = $factory->getScrollList("warList", array("warNameHeader", "warListPathHeader" ,"warListActionHeader"));
list($vsite) = $cceClient->find('Vsite', array('name' => $group));
$vsiteObj = $cceClient->get($vsite);
$scrollList->setLabel($factory->getLabel('warList', false, array('fqdn' => $vsiteObj['fqdn'])));
$scrollList->setAlignments(array("left", "left", "center"));
$scrollList->setColumnWidths(array("1%", "", "1%"));

$scrollList->addButton($factory->getAddButton("/base/java/warAdd.php?group=$group&user=$user"));

// disable sorting
$scrollList->setSortEnabled(false);
$scrollList->setArrowVisible(true);
// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

if($group != '') {
  $search_cce = array('user' => $user);
} else {
  $search_cce = array('group' => $group);
}
$oids = $cceClient->find("JavaWar", array("group" => "$group"));

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
  $oids = array_reverse($oids);

// set total number of entries in list
$scrollList->setEntryNum(count($oids));

// Lookup the current site for the fqdn, used in the form/help text
$vsite = $cceClient->getObject('Vsite', array('name' => $group));

for($i = $start; $i < count($oids) && $i < $start+$pageLength; $i++) {
  $oid = $oids[$i];
  $war = $cceClient->get($oid, "");

  $path = 'http://'.$vsite['fqdn'].'/'.$war['name'];
  
  $desc = $factory->getTextField("", $path, 
	"r");
  $desc->setMaxLength(80);

  $msg = $i18n->get("confirm_archive_removal", "",
    array('path' => $path));

  $w = $factory->getRemoveButton(
	"javascript: confirmRemove('$msg', '$oid')");
  // if ($war['group']) { $w->setDisabled(true); }

  $archive = ereg_replace("^.+/([^/]+)$", "\\1", $war["war"]);

  $scrollList->addEntry( array(
    $factory->getTextField("", $archive, "r"),
    $desc,
    $factory->getCompositeFormField(array(
      // $factory->getModifyButton(
      //   "/base/java/warAdd.php?_TARGET=$oid&_LOAD=1&group=$group&user=$user" ),
      $w
    ))
  ), "", false, $i);
}

print $page->toHeaderHtml();

?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid) {
  if(confirm(msg))
    location = "/base/java/warList.php?group=<?php print $group; ?>&_REMOVE=" + oid;
}
</SCRIPT>

<?php

print $scrollList->toHtml();

if (count($errors) > 0)
{
	print "<SCRIPT LANGUAGE=\"javascript\">\n";
	print $serverScriptHelper->toErrorJavascript($errors);
	print "</SCRIPT>\n";
}
print $page->toFooterHtml();
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
