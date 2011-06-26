<?php
/*
 * $Id: mailmanMod.php, v 1.0.0-2 Sun 01 May 2011 02:22:45 AM CEST
 * Copyright 2011 Team BlueOnyx. All rights reserved.
 */

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");

include_once("uifc/Option.php");
include_once("uifc/FormFieldBuilder.php");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-mailman", "/base/mailman/mailmanMod.php");
$i18n = $serverScriptHelper->getI18n("base-mailman");

$page = $factory->getPage();

$form = $page->getForm();
$formId = $form->getId();

$builder = new FormFieldBuilder();

session_start();

// fix the local user select problem,
// session was constantly killed...
if($_POST['mode'] != "") {
        $mode = $_POST['mode'];
}

if (!isset($mode)) {
	session_unset();
	session_destroy();
	session_start();
}

// get the Vsite or System info since it is needed below a few times
if (isset($group)) {
	list($vsite) = $cce->find('Vsite', array('name' => $group));
	$vsiteObj = $cce->get($vsite);
	$display_fqdn = $vsiteObj['fqdn'];
} else {
	list($sys) = $cce->find('System');
	$sys_obj = $cce->get($sys);
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

//session_register("localSubs");
$_SESSION["localSubs"] = $localSubs;
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
  //if (ereg("^name_(.*)$", $key, $regs)) {
  if (preg_match('/^name_(.*)$/', $key, $regs)) {
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
// ************************************
// ** FIXED (herps@rackstar.net):
// ** This unset caused it not to save when changing pages
// **
// ************************************
//
//unset($localSubs);

$localSubs = array();
//special case, "All", copy all into local subscriber temp list
if ($selectAll) {
  $users = $cce->findx("User", array('site' => $group), array(), "ascii", "name");
  foreach ($users as $oid) {
    $user = $cce->get($oid);
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
  if ($remote_recips_digest) {
    $a = stringToArray($remote_recips_digest);
    //uniqify
    $b = array_flip($a);
    $a = array_keys($b);
        
    $remote_recips_digest = arrayToString($a);
  }

  $vals = array('name' => $listName,
		'apassword' => $apassword,
		'local_recips' => $local_recips,
		'remote_recips' => $remote_recips,
		'remote_recips_digest' => $remote_recips_digest,
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
    $cce->set($oid, '', $vals);
  } else {
    //creating new mailing list
    $cce->create('MailList', $vals);
  }
  $errors = $cce->errors();
  if (!$errors) {
    header("location: /base/mailman/mailmanList.php?group=$group");
    exit;
  }
} else if (isset($_TARGET) && intval($_TARGET) > 0) {
  // we're editing an existing mailing list
  // but not saving just yet
  $obj = $cce->get($_TARGET);
  $listName = $obj['name'];
  $apassword = $obj['apassword'];
  $internal_name = $obj['internal_name'];
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
  $remote_recips_digest = $obj['remote_recips_digest'];
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
    $list = $cce->get($_TARGET);
    $block->setLabel($factory->getLabel('modifyMailList', false, array('listName' => $list['name'])));
  } else {
    $createMailList = "1";
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

  // Show List Email Address:
  if ($internal_name) {
    $listAddress = $internal_name . '@' . $display_fqdn;
    $block->addFormField(
		       $factory->getMailListname("listAddress", $listAddress, 'r'), 
		       $factory->getLabel("listAddress"),
		       $simpleId);
  }
  
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
				$i18n->interpolate("[[base-mailman.numLocals]]", 
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

  $remote_digest = $factory->getEmailAddressList("remote_recips_digest", $remote_recips_digest);
  $remote_digest->setOptional("silent");
  $block->addFormField($remote_digest,
                       $factory->getLabel("remoteSubscribersDigest"),
                       $subsId);

  //advanced page
  if (!$moderator) {
    $moderator = "admin";
  }
  $block->addFormField($factory->getEmailAddress("moderator", $moderator),
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
    $maxlength = 50;
  }
  $length = $factory->getMultiChoice("maxlength");
  foreach (array(5, 50, 500, 10000, 100000) as $a_length) {
    $length->addOption($factory->getOption($a_length, $maxlength == $a_length));
  }
  $block->addFormField($length,
		       $factory->getLabel("maxlength"),
		       $advancedId);

  if (!isset($replyToList)) {
    $replyToList = 1;
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
  $block->addButton($factory->getCancelButton("/base/mailman/mailmanList.php?group=$group"));

  // MailMan redirect buttons (only visible if we're modifying an existing list:
  if ( $createMailList != "1" ) {
    $list_admin_button = "http://" . $display_fqdn . "/mailman/admin/$internal_name";
    $list_archive_button = "http://" . $display_fqdn . "/pipermail/$internal_name/";
    $block->addButton($factory->getButton("$list_admin_button", "vsiteMailMan_Admin"));
    $block->addButton($factory->getButton("$list_archive_button", "MailMan_Archive"));
  }

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
		'remote_recips_digest' => $remote_recips_digest,
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
    $users = $cce->findx("User", array('site' => $group), array('name' => $searchtext), "ascii", "name");
    $matchstring =  $i18n->interpolate("[[base-mailman.matching]]", array("criteria" => $searchtext));
    $locals->setLabel($factory->getLabel("localSubs_list", false, array("criteria" => $matchstring, 'fqdn' => $vsiteObj['fqdn'])));
  } else if ($show == 'selected') {
    //showing only already selected items
    //no need to get the data, we already have it in $localSubs
    $users = $localSubs;
    sort($users);
    $matchstring =  $i18n->interpolate("[[base-mailman.selected]]");
    $locals->setLabel($factory->getLabel("localSubs_list", false, array("criteria" => $matchstring, 'fqdn' => $vsiteObj['fqdn'])));
  } else {
    //show all items
    $users = $cce->findx("User", array('site' => $group), array(), "ascii", "name");
    $matchstring =  $i18n->interpolate("[[base-mailman.all]]");
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
      $user = $cce->get($users[$i]);
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

?>
