<?php
/*
 * $Id: maillistMod.php 576 2005-09-05 10:26:24Z shibuya $
 * Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
 */

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");
include_once("uifc/Option.php");
include_once("uifc/FormFieldBuilder.php");

$helper = new ServerScriptHelper();
$cceClient = $helper->getCceClient();
$factory = $helper->getHtmlComponentFactory("base-maillist",
		    "/base/maillist/maillistMod.php");
$i18n = $helper->getI18n("base-maillist");

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$builder = new FormFieldBuilder();

session_start();
if (!isset($mode)) {
	session_unset();
	session_destroy();
	session_start();
}

// get the Vsite or System info since it is needed below a few times
if (isset($group)) {
	list($vsite) = $cceClient->find('Vsite', array('name' => $group));
	$vsiteObj = $cceClient->get($vsite);
	$display_fqdn = $vsiteObj['fqdn'];
} else {
	list($sys) = $cceClient->find('System');
	$sys_obj = $cceClient->get($sys);
	$display_fqdn= $sys_obj['hostname'] . '.' . $sys_obj['domainname'];
}

//pre and post processing for local subscriber list

//$localSubs is the "temp" list. It's used for storing values to and
//from the local subscriber choice page.

//$local_recips is the "true" subscriber list. If you click "use current
//selection" when leaving the local subscriber choice page, then
//$localSubs is copied to $local_recips. If you click "cancel", it is
//NOT copied. that means that you can add one, click save, then add 3
//and click cancel, and the original one would be added, but not 3. of
//course, no changes are applied until you click "save" for the
//mailing list.

session_register("localSubs");
if (!isset($localSubs)) {
  $localSubs = array();
}

//we need to add and delete some items to local subscriber temp list
//all items are have vars called "name_$id" where $id is username of the user
//we only want to add the ones for which "locals_id_$id" is present and true
//if it's not present and true, we take it out of the local subscriber temp list

//we have to do it this way because ScrollList only returns variables for "true" selections,
//and false ones, it leaves out completely

//copy local subscriber temp list
$temp_array = array();
foreach ($localSubs as $temp) {
  $temp_array[$temp] = 1;
}

//find all items
$postids = array();
while (list($key, $val) = each($HTTP_POST_VARS)) {
  if (ereg("^name_(.*)$", $key, $regs)) {
    $postids[$regs[1]] = $val;
  }
}

//find which items are selected
while (list ($key, $val) = each($postids)) {

  $varname = "locals_id_" . $key;
  if (isset($$varname) && $$varname == "true") {
    $temp_array[$val] = 1;
  } else {
    $temp_array[$val] = 0;
  }
}

//put new selection back into local subscriber temp list
unset($localSubs);
$localSubs = array();
//special case, "All", copy all into local subscriber temp list
if ($selectAll) {
  $users = $cceClient->findx("User", array('site' => $group), array(), "ascii", "name");
  foreach ($users as $oid) {
    $user = $cceClient->get($oid);
    $localSubs[] = $user['name'];
  }
  $selectAll = 0;
  $HTTP_POST_VARS['selectAll'] = 0;
} else {
  while (list($key, $val) = each($temp_array)) {
    if ($val == 1) {
      $localSubs[] = $key;
    }
  }
}

// if we're in "save local subscriber list" mode
// then we copy the temp list onto the "true" list
if (isset($mode) && ($mode == 'locals_save')) {
  $local_recips = arrayToString($localSubs);
} else if (isset($mode) && ($mode == 'locals_new') ) {
  // we've just entered the local subscriber page
  // so we take whatever the most recent "truth" is as our starting point
  $localSubs = stringToArray($local_recips);
}

reset($HTTP_POST_VARS);    
//save
if (isset($mode) && $mode == 'save') {
  if ($remote_recips) {
    $a = stringToArray($remote_recips);
    //uniqify
    $b = array_flip($a);
    $a = array_keys($b);
    
    $remote_recips = arrayToString($a);
  }
  if ($moderator) {
    $a = stringToArray($moderator);
    //uniqify
    $b = array_flip($a);
    $a = array_keys($b);
    
    $moderator = arrayToString($a);
  }
  $remote_recips = str_replace("%40", "@", $remote_recips);
  $moderator = str_replace("%40", "@", $moderator);
  $vals = array('name' => $listName,
		'apassword' => $apassword,
		'local_recips' => $local_recips,
		'remote_recips' => $remote_recips,
		'postPolicy' => $postPolicy,
		'subPolicy' => $subPolicy,
		'moderator' => $moderator,
		'maxlength' => $maxlength,
		'replyToList' => $replyToList,
		'description' => $description,
		'site' => $group);
  if (isset($_TARGET) && intval($_TARGET) > 0) {
    //saving new settings for existing mailing list
    $oid = $_TARGET;
    $cceClient->set($oid, '', $vals);
  } else {
    //creating new mailing list
    $cceClient->create('MailList', $vals);
  }
  $errors = $cceClient->errors();
  if (!$errors) {
    header("location: /base/maillist/maillistList.php?group=$group");
    exit;
  }
} else if (isset($_TARGET) && intval($_TARGET) > 0) {
  // we're editing an existing mailing list
  // but not saving just yet
  $obj = $cceClient->get($_TARGET);
  $listName = $obj['name'];
  $apassword = $obj['apassword'];
  // we're at the top of a post to a non-locals mode
  // if we're in locals_save mode, we've already copied localsubs into local_recips (see above)
  // if we're not in a mode, we're at first load, so get from obj
  // if we're in some mode, then don't touch anything

  if (isset($mode) && ($mode == 'locals_save')) {
    // preserve $local_recips across page loads
    $localSubs = stringToArray($local_recips);
  } else if (!isset($mode)){
    // if no mode variable, then we're on first load
    // so we get it from $obj
    $local_recips = $obj['local_recips'];
    // magic variables. user list is '&nobody&' if there are no subscribers present
    // some handler sets it, so we have to handle it
    if ($local_recips == '&nobody&') {
      $local_recips = '';
    }
    $localSubs = stringToArray($local_recips);
  } else {
    // mode is set and it's not save
    // that means we're across page loads
    // don't do anything
  }

  $remote_recips = $obj['remote_recips'];
  $postPolicy = $obj['postPolicy'];
  $subPolicy = $obj['subPolicy'];
  $moderator = $obj['moderator'];
  $maxlength = $obj['maxlength'];
  $replyToList = $obj['replyToList'];
  $description = $obj['description'];
  $group = $obj['site'];
}

$ids = array();
$ids[] = $simpleId = "basic";
$ids[] = $subsId = "subscribers";
$ids[] = $advancedId = "advanced";

if ($mode != 'locals' && $mode != 'locals_new') {
  //displaying normal mailing list settings
  //we use modifyMailList as the block id in BOTH cases below, but something else as the label
  //we do this so that we can do nasty hacks 
  //involving the internal variables involved with PagedBlock paging.
  if (isset($_TARGET) && intval($_TARGET) > 0) {
    //different title for modify list
    $block = $factory->getPagedBlock("modifyMailList", $ids);
    $list = $cceClient->get($_TARGET);
    $block->setLabel($factory->getLabel('modifyMailList', false, array('listName' => $list['name'])));
  } else {
    $block = $factory->getPagedBlock("modifyMailList", $ids);
    $block->setLabel($factory->getLabel('createMailList', false, array('fqdn' => $vsiteObj['fqdn'])));
  }
  $block->processErrors($errors);

  //hidden fields
  $hidden = $builder->makeHiddenField("group", $group);
  if (isset($_TARGET)) {
    $target = $builder->makeHiddenField("_TARGET", $_TARGET);
  }
  $mode = $builder->makeHiddenField("mode", '');
  // this hidden variable is used to store the true local subscriber list
  // see comments at the top of the file for more details
  $hidden_locals = $builder->makeHiddenField("local_recips", $local_recips);

  //simple page
  $block->addFormField(
		       $factory->getMailListname("listName", $listName), 
		       $factory->getLabel("listName"),
		       $simpleId);

  $desc = $factory->getTextBlock("description", $description);
  $desc->setOptional("silent");
  $desc->setWidth(40);
  $block->addFormField($desc,
		       $factory->getLabel("description"),
		       $simpleId);

  //subscribers page
  $button = $factory->getCompositeFormField();
  // $local_recips is considered "truth", remember?
  // use $local_recips to say what we think the current subscriber list is
  $num = $factory->getTextField("number", 
				$i18n->interpolate("[[base-maillist.numLocals]]", 
  						   array('num' => count(stringToArray($local_recips)), 
  							 'plural' => (count(stringToArray($local_recips)) == 1 ? '' : 's'))),
				"r");
  $num->setPreserveData(false);
  $button->addFormField($num);
  $button->addFormField($factory->getButton("javascript: if (document.$formId.onsubmit()) { document.$formId.mode.value='locals_new'; document.$formId.submit(); }", "edit_locals"));
  $block->addFormField($button,
		       $factory->getLabel("localSubscribers"),
		       $subsId);

  $remote = $factory->getEmailAddressList("remote_recips", $remote_recips);
  $remote->setOptional("silent");
  $block->addFormField($remote,
		       $factory->getLabel("remoteSubscribers"),
		       $subsId);

  //advanced page
  if (!$moderator) {
    $moderators = $factory->getEmailAddress("moderator", "admin");
  } else {
    $moderator = str_replace("%40", "@", $moderator);
    $moderators = $factory->getEmailAddressList("moderator", $moderator);
  }
  $moderators->setOptional("silent");
  $block->addFormField($moderators,
		       $factory->getLabel("moderator"),
		       $advancedId);

  $pass = $factory->getPassword("apassword", $apassword, false);
  $pass->setOptional("silent");
  $block->addFormField($pass,
		       $factory->getLabel("apassword"),
		       $advancedId);

  $block->addDivider($factory->getLabel("policies", false), $advancedId);

  if (!$postPolicy) {
    $postPolicy = "members";
  }
  $posting = $factory->getMultiChoice("postPolicy");
  $posting->addOption($factory->getOption("members"));
  $posting->addOption($factory->getOption("any"));
  $posting->addOption($factory->getOption("moderated"));
  $posting->addOption($factory->getOption("admin"));
  $posting->addOption($factory->getOption("domain"));
  $posting->setSelected($postPolicy, true);
  $block->addFormField($posting,
		       $factory->getLabel("postingPolicy"),
		       $advancedId);

  if (!$subPolicy) {
    $subPolicy = "open";
  }

  $subscription = $factory->getMultiChoice("subPolicy");
  $subscription->addOption($factory->getOption("open"));
  $subscription->addOption($factory->getOption("confirm"));
  $subscription->addOption($factory->getOption("closed"));
  $subscription->setSelected($subPolicy, true);
  $block->addFormField($subscription,
		       $factory->getLabel("subscriptionPolicy", true, array(),
		       			  array('fqdn' => $display_fqdn)),
		       $advancedId);

  if (!$maxlength) {
    $maxlength = 51200;
  }
  $length = $factory->getMultiChoice("maxlength");
  foreach (array(5120, 51200, 512000, 10485760, 104857600) as $a_length) {
    $length->addOption($factory->getOption($a_length, $maxlength == $a_length));
  }
  $block->addFormField($length,
		       $factory->getLabel("maxlength"),
		       $advancedId);

  if (!isset($replyToList)) {
    $replyToList = 0;
  }
  $reply = $factory->getMultiChoice("replyToList");
  $sender = new Option($factory->getLabel("replySender", false), 0, $replyToList == 0);
  $reply->addOption($sender);
  $list = new Option($factory->getLabel("replyList", false), 1, $replyToList == 1);
  $reply->addOption($list);
  $block->addFormField($reply,
		       $factory->getLabel("replyToList"),
		       $advancedId);
 
  // common buttons
  $block->addButton($factory->getSaveButton("javascript: if (document.$formId.onsubmit()) { document.$formId.mode.value='save'; document.$formId.submit(); }"));
  $block->addButton($factory->getCancelButton("/base/maillist/maillistList.php?group=$group"));


  // print page
  print $page->toHeaderHtml();
  print $hidden;
  print $mode;
  print $target;
  print $hidden_locals;
  print $block->toHtml();
  print $page->toFooterHtml();
} else {
  // MODIFYING LOCAL SUBSCRIBER LIST
  print $page->toHeaderHtml();

  // search 
  // generate search fields
  $searchBlock = $factory->getPagedBlock("localsSearch");
  $searchBlock->setColumnWidths(array("8%", "92%"));
  
  $searchTextField = $factory->getTextField('searchtext');
  $searchTextField->setOptional('silent');

  $searchButton = $factory->getButton("javascript: if (document.$formId.onsubmit()) { document.$formId.show.value='';  document.$formId.submit(); }", "search");
  $searchField = $factory->getCompositeFormField(array($searchTextField, $searchButton));
  $searchBlock->addFormField($searchField, $factory->getLabel("search"));
  
  // buttons for filtering the display
  // show all
  $showAll = $factory->getButton("javascript: if (document.$formId.onsubmit()) { " . 
				 " document.$formId.show.value='all';" . 
				 " document.$formId.searchtext.value=''; document.$formId.submit() }", "showall");
  // show all currently selected
  $showSelected = $factory->getButton("javascript: if (document.$formId.onsubmit()) { " . 
				      "document.$formId.show.value='selected'; " . 
				      "document.$formId.searchtext.value='';  document.$formId.submit() }", "showselected");
  
  $filter = $factory->getCompositeFormField(array($showAll, $showSelected), "&nbsp;&nbsp;&nbsp;");

  //go back
  $back = $factory->getButton("javascript: if (document.$formId.onsubmit()) { document.$formId.mode.value='locals_save'; document.$formId.submit() }", "use_these");
  $cancel = $factory->getButton("javascript: if (document.$formId.onsubmit()) { document.$formId.mode.value=''; document.$formId.submit() }", "cancel");
  $actions = $factory->getCompositeFormField(array($back, $cancel), "&nbsp;&nbsp;&nbsp;");

  // need to preserve settings from basic and advanced pages
  $vals = array('listName' => $listName,
		'apassword' => $apassword,
		'local_recips' => $local_recips,
		'remote_recips' => $remote_recips,
		'postPolicy' => $postPolicy,
		'subPolicy' => $subPolicy,
		'moderator' => $moderator,
		'maxlength' => $maxlength,
		'replyToList' => $replyToList,
		'description' => $description,
		'group' => $group);

  reset ($vals);
  while (list ($key, $val) = each ($vals)) {
    print $builder->makeHiddenField($key, $val);
  }
  print $builder->makeHiddenField("_TARGET", $_TARGET);

  // some special variables
  // so we post back to the local subscriber list page
  print $builder->makeHiddenField("mode", "locals"); 
  //so when we go back, we return to the subscriber tab
  print $builder->makeHiddenField("_PagedBlock_selectedId_modifyMailList", $subsId); 
  // show all? show selected?
  print $builder->makeHiddenField("show", $show); 
  //do we select all?
  print $builder->makeHiddenField("selectAll", $selectAll); 
  
  // scroll list
  // setup
  $locals = $factory->getScrollList("localSubs_list", array("name"), array(0));
  $pagelength = 100;
  $locals->setLength($pagelength);
  //we do our own, hacked selectAll
  //$locals->setSelectAll(true);
  $startIndex = $pagelength * $locals->getPageIndex();
  $locals->setSortEnabled(false);

  //search mode is defined as presence of $searchtext
  if ($searchtext) {
    $show = 'search';
  }
  if ($show == 'search') {
    //showing search results
    $users = $cceClient->findx("User", array('site' => $group), array('name' => $searchtext), "ascii", "name");
    $matchstring =  $i18n->interpolate("[[base-maillist.matching]]", array("criteria" => $searchtext));
    $locals->setLabel($factory->getLabel("localSubs_list", false, array("criteria" => $matchstring, 'fqdn' => $vsiteObj['fqdn'])));
  } else if ($show == 'selected') {
    //showing only already selected items
    //no need to get the data, we already have it in $localSubs
    $users = $localSubs;
    sort($users);
    $matchstring =  $i18n->interpolate("[[base-maillist.selected]]");
    $locals->setLabel($factory->getLabel("localSubs_list", false, array("criteria" => $matchstring, 'fqdn' => $vsiteObj['fqdn'])));
  } else {
    //show all items
    $users = $cceClient->findx("User", array('site' => $group), array(), "ascii", "name");
    $matchstring =  $i18n->interpolate("[[base-maillist.all]]");
    $locals->setLabel($factory->getLabel("localSubs_list", false, array("criteria" => $matchstring, 'fqdn' => $vsiteObj['fqdn'])));
  }
  
  $locals->setEntryNum(count($users));

  // if we have less users that the current index says we do
  // then we throw to the last available page
  // indices are 0 based, so we add a 1
  if (count($users) < ($startIndex + 1)) {
    //how many pages will we need for this many users
    // so  75 users, 25 per page, we want 3 pages
    // but 76 users, 25 per page, we want 4 pages
    $numPages = ceil(count($users) /$pagelength);

    // go to the last page
    $pageIndex = $numPages - 1;
    // make sure we don't go below 0
    $pageIndex = $pageIndex >= 0 ? $pageIndex : 0;
    $locals->setPageIndex($pageIndex);

    // get index for that page
    $startIndex = $pageIndex * $pagelength;
  }

  if ($locals->getSortOrder() == "descending")
    $users = array_reverse($users);

  for ($i = $startIndex; $i < ($startIndex + $pagelength) && $i < count($users); $i++) {
    switch ($show) {
    case 'selected':
      $name = $users[$i];
      break;
    case 'search':
    case 'all':
    default:
      $user = $cceClient->get($users[$i]);
      $name = $user['name'];
      $fullName = $user['fullName'];
      break;
    }
    $entry = $factory->getTextField("name_$i", $name, "r");
    $entry->setPreserveData(false);  

    $locals->addEntry(array($entry),
		      "locals_id_$i",
		      in_array($name, $localSubs),
		      $i);
  }
  //make my own javascript because uifc's can't do it
  $script = <<<HERE
<SCRIPT LANGUAGE="javascript">
function ScrollList_selectAllSwitch(element) {
  var form = element.form;
  var entryIdsString = form.elements._entryIds.value;
  var entryIds = entryIdsString.split(',');
  for (var i = 0; i<entryIds.length; i++) {
    if (form[entryIds[i]] != null) 
      form[entryIds[i]].checked = 1;
  }
}
</SCRIPT>
HERE;


  //select all
  $get_all = $factory->getButton("javascript: if (document.$formId.onsubmit()) { " . 
		 "document.$formId.selectAll.value='true'; document.$formId.submit() }", "selectAll");
  $locals->addButton($get_all);

  // select page and select all buttons
  $get_page = $factory->getButton("javascript: ScrollList_selectAllSwitch(document);", "selectPage");
  $locals->addButton($get_page);
  //this is bad, but i sneaked in private variables
  print $builder->makeHiddenField("_entryIds", implode(",", $locals->entryIds));

  //print them out
  print $searchBlock->toHtml();
  print "<BR>";
  print $filter->toHtml();
  print "<BR>";
  print $script;
  print $locals->toHtml();
  print "<BR>";
  print $actions->toHtml();
  print "<BR>";
  print $page->toFooterHtml();
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
