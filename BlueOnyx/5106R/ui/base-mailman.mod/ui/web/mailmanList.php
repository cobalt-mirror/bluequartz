<?php
// $Id: mailmanList.php, v 1.0.0-1 Sun 24 Apr 2011 07:03:22 PM CEST
// Copyright 2011 Team BlueOnyx. All rights reserved.
 
include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper() or die ("no SSH");
$cce = $serverScriptHelper->getCceClient() or die ("no CCE");

// Update subscriber counts
if(!$nosync) {
	$time = time();
	$sysoid = $cce->find('System');
	$ret = $cce->set($sysoid, 'MailList', 
		array('site'=>$group, 'commit'=>$time));
}

$factory = $serverScriptHelper->getHtmlComponentFactory(
	"base-mailman");
	// "base-mailman", "/base/mailman/mailmanList.php?group=$group");
$i18n = $serverScriptHelper->getI18n("base-mailman") or die ("no i18n");

$page = $factory->getPage();

$product = new Product($cce);

if($group == '') {
	header("location: /error/forbidden.html");
	return;
}

// deal with remove actions
if ($_REMOVE) {
  $cce->destroy($_REMOVE);
  $errors = $cce->errors();
}

// build scroll list of mailing lists
$scrollList = $factory->getScrollList("mailmanList", array("mailmanNameHeader", "recipientsHeader", "mailmanDescHeader" ,"mailmanActionHeader"), array(0));
//if ($product->isWhitebox()) {
  list($vsite) = $cce->find('Vsite', array('name' => $group));
  $vsiteObj = $cce->get($vsite);
  $groupName = $vsiteObj['fqdn'];
//} else {
  // find the workgroup name
//  $groupName = "something";
//}

$scrollList->setLabel($factory->getLabel('mailmanList', false, array('group' => $groupName)));
$scrollList->setAlignments(array("left", "left", "left", "center"));
$scrollList->setColumnWidths(array("1%", "", "", "1%"));

$scrollList->addButton($factory->getAddButton("/base/mailman/mailmanMod.php?group=$group"));

// disable sorting
$scrollList->setSortEnabled(false);
// $scrollList->setArrowVisible(true);

// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

$oids = $cce->findSorted("MailList", "name", array('site' => $group));

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
  $oids = array_reverse($oids);

// set total number of entries in list
$scrollList->setEntryNum(count($oids));

for($i = $start; $i < count($oids) && $i < $start+$pageLength; $i++) {
  $oid = $oids[$i];
  $ml = $cce->get($oid, "");
  
  $members = array();
  // magic variables! if subscriber list is empty, then 'nobody' is the sole recipient
  // parse it out so that we don't show it to the user
  if ($ml['local_recips'] != '&nobody&') {
    $members = stringToArray($ml['local_recips']);
  }
  $members = array_merge($members, stringToArray($ml['remote_recips']));
  $members = array_merge($members, stringToArray($ml['remote_recips_digest']));
  if ($ml['group']) {
    $groupText = $i18n->get("groupSubscriber", "", 
      array("group"=>$ml['group']));
    $members = array_merge($members, (array)"". $groupText . (array)"");
  }

  $desc = $factory->getTextField("", $i18n->interpolate($ml["description"]),
	"r");
  $desc->setMaxLength(80);

  $msg = $i18n->get("confirm_removal_of_list", "",
    array('list' => $ml["name"]));

  $w = $factory->getRemoveButton(
	"javascript: confirmRemove('$msg', '$oid')");
  if ($ml['group']) { $w->setDisabled(true); }
  $scrollList->addEntry( array(
    $factory->getTextField("", $ml["name"], "r"),
    $factory->getTextField("", 
	   $i18n->interpolate("[[base-mailman.numSubs]]", array('num' => count($members), 'plural' => (count($members) == 1 ? '':'s'))),
      "r"),
    $desc,
    $factory->getCompositeFormField(array(
      $factory->getModifyButton(
        "/base/mailman/mailmanMod.php?group=$group&_TARGET=$oid&_LOAD=1" ),
      $w
    ))
  ), "", false, $i);
}

print $page->toHeaderHtml();

?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(msg, oid) {
  if(confirm(msg))
    location = "/base/mailman/mailmanList.php?group=<?php print $group; ?>&_REMOVE=" + oid;
}
</SCRIPT>

<?php

print $scrollList->toHtml();

if ($group) {
  $groupfield = $factory->getTextField('group', $group, '');
  print $groupfield->toHtml();
}

if (count($errors))
{
	print "<SCRIPT LANGUAGE=\"javascript\">\n";
	print $serverScriptHelper->toErrorJavascript($errors);
	print "</SCRIPT>\n";
}

print $page->toFooterHtml();

?>
