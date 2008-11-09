<?php
// $Id: maillistAdd.php 3 2003-07-17 15:19:15Z will $
//
// ui for adding/modifying a mailing list.

include("CobaltUI.php");
include("ArrayPacker.php");

// phpinfo();

$ui = new CobaltUI($sessionId, "base-maillist"); /* maillist is the domain */

// some initialization -- this isn't going to scale very well. :-(
$usernames = array();
$useroids = $ui->Cce->find("User");
for ($i = 0; $i < count($useroids); $i++) {
  $user = $ui->Cce->get($useroids[$i]);
  array_push($usernames, $user["name"]);
}
sort($usernames);

// mapping: lists a form field name to an object attribute.
$mapping = array (
  "listName" => "name",
  "password" => "apassword",
  "localSubscribers" => "local_recips",
  "remoteSubscribers" => "remote_recips",
  "postingPolicy" => "postPolicy",
  "subscriptionPolicy" => "subPolicy",
  "keepForDays" => "keep_for_days",
  "archiveEnabled" => "enabled",
  "moderator" => "moderator",
  "group_field" => "group",
  "maxlength" => "maxlength",
  "replyToList" => "replyToList",
  "description" => "description"	
);

$NsMapping = array (
  "keep_for_days" => "Archive",
  "enabled" => "Archive" );

// handler:
if (!$_TARGET) { $_TARGET = "MailList"; }
$done = handle($ui, $_TARGET, $mapping, $HTTP_POST_VARS, $HTTP_GET_VARS, $NsMapping);

// handle mail lists bound to Workgroups specially:
$groupmembers = array();
if ($ui->Data["group_field"]) {
  $oids = $ui->Cce->find("Workgroup", array( 'name' => $ui->Data["group_field"] ) );
  if ($oids[0]) {
    $groupobj = $ui->Cce->get($oids[0]);
    $ui->Data['workgroupMembers'] = $groupobj['members'];
    $groupmembersStr = $groupobj['members'];
    $groupmembers = stringToArray($groupobj['members']);
    $usernames = array_diff($usernames, $groupmembers);
    $usernames = array_values($usernames); // pack array
  }
}

/**
print "<hr><pre>\n";
print "groupmembers = "; var_dump($groupmembers);
print "\n";
print "usernames = "; var_dump($usernames);
print "\n</pre><hr>\n";
**/

if (!$ui->Data["maxlength"]) {
  $ui->Data["maxlength"] = 51200;
}
if (!$ui->Data["replyToList"]) {
  $ui->Data["replyToList"] = "0";
}
if (!isset($ui->Data["keepForDays"])) {
  $ui->Data["keepForDays"] = 30;
}

# prevent PHP from sneakily adding new hidden fields:
if (is_array($HTTP_POST_VARS)) {
  $keys = array_keys($HTTP_POST_VARS);
  $index = array_keys($keys, "_LOAD"); array_splice($HTTP_POST_VARS, $index[0], 1);
  $index = array_keys($keys, "_save"); array_splice($HTTP_POST_VARS, $index[0], 1);
}

if ($done) {
  $ui->Redirect( "/base/maillist/maillistList.php" );
  exit();
}

$ui->StartPage("AAS", $_TARGET ? $_TARGET : "MailList", "");

$ui->StartBlock((intval($_TARGET) > 0) ? "modifyMailList" : "createMailList");

///////////////////////////////////////////////////////////////
// basic view
///////////////////////////////////////////////////////////////
$ui->SetBlockView("basic");
if ($ui->Data['group_field']) {
	$ui->MailListName("group_field", array("Access" => "r"));
	$ui->MailListName("listName", array("Access" => ""));
	$ui->EmailListField("workgroupMembers", 
	  array("Optional" => "silent", "Access" => "r") );
} else {
	$ui->MailListName("listName");
}
$ui->SetSelectField("localSubscribers", 
  "subscribed", "unsubscribed",
  $usernames, array ("Optional" => "silent" ));
//$ui->ListField("remoteSubscribers",array ("Optional" => "silent" ));
$ui->EmailListField("remoteSubscribers", array("Optional" => "silent"));
$ui->TextBlock("description", array("Optional" => "silent", "Width" => 40));

///////////////////////////////////////////////////////////////
// advanced view
///////////////////////////////////////////////////////////////
$ui->SetBlockView("advanced");

$ui->EmailAddress("moderator");

$ui->TextField("password", array( "Optional" => "silent") );

$ui->Divider("policies");

$ui->SelectField("postingPolicy", 
  array( "members", "any", "moderated") );

$ui->SelectField("subscriptionPolicy", 
  array( "open", "confirm", "closed" ));

$ui->SelectField("maxlength",
  array( 5120, 51200, 512000, 1048576, 104857600 ));

$ui->SelectField("replyToList",
  array( "true", "0" ));

$ui->Divider("archiveSettings");

if(!isset($ui->Data["archiveEnabled"]))
	$ui->Data["archiveEnabled"] = 0;

$ui->Boolean("archiveEnabled");

$ui->Integer("keepForDays", 0, 10000 );

$ui->AddButtons("/base/maillist/maillistList.php");

$ui->EndBlock();
$ui->EndPage();

function handle(&$ui, $target, &$mapping, &$post_vars, &$get_vars, &$NsMapping)
{
  // echo "<b>handle $target</b><br>";
  global $HTTP_POST_VARS;
  
  $http_vars = array();
  if (is_array($post_vars)) { 
    $http_vars = array_merge($http_vars, $post_vars); 
  }
  if (is_array($get_vars)) {
    $http_vars = array_merge($http_vars, $get_vars);
  }
  
  if ($http_vars["remoteSubscribers"]) {
    // get subscribers
    $a = stringToArray($http_vars["remoteSubscribers"]);
    // uniquify:
    $b = array_flip($a); // to hash 
    $a = array_keys($b); // back to array
    // echo "<li> a = ";var_dump($a);
    // echo "<li> b = ";var_dump($b);
    // set subscribers:
    $http_vars["remoteSubscribers"] = arrayToString($a);
  }

  if ($http_vars["_LOAD"]) {
    if (intval($target) > 0) {
      handle_load($ui, intval($target), $mapping, $NsMapping);
    }
  } else {
    handle_post($ui, $target, $mapping, $http_vars);
  }

  if (!$ui->Data["moderator"])
	$ui->Data["moderator"] = "admin";
  if ($ui->Data["remoteSubscribers"]) {
    // get subscribers
    $a = stringToArray($ui->Data["remoteSubscribers"]);
    // uniquify:
    $b = array_flip($a); // to hash 
    $a = array_keys($b); // back to array
    // set subscribers:
    $ui->Data["remoteSubscribers"] = arrayToString($a);
  }

  
  if ($post_vars["_save"]==1) {
    // view the page with errors on it...
    global $HTTP_POST_VARS;
    $HTTP_POST_VARS['_PagedBlock_selectedId_blockid0'] = 'errors';
    // attempt to update cce.
    return update_cce($ui, $target, $mapping, $http_vars, $NsMapping);
  }

  return 0;
}
  
function handle_load(&$ui, $oid, &$mapping, $NsMapping)
{
  // refresh
  $ui->Cce->set($oid, "", array("update" => time()));

  // load object attributes
  $list = $ui->Cce->get($oid);
  while (list($key,$val) = each($mapping))
  {
	if ($NsMapping[$val] == "") {
	     $ui->Data[$key] = $list[$val];
     	     //echo "<li> $key <- $val ($list[$val])";
	}
  }
  // do again for archive namespace
  $list = $ui->Cce->get($oid, "Archive");
  reset ($mapping);
  while (list($key,$val) = each($mapping))
  {
	if ($NsMapping[$val] == "Archive") {
    		$ui->Data[$key] = $list[$val];
//		echo "<li> $key <- $val ($list[$val])";
	}
  } 
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
function map_vars($mapping, $post_vars, &$NsMapping, $ns)
{
  $obj = array();
  while (list($key,$val) = each($mapping))
  {
    if ($NsMapping[$val]==$ns) 
      $obj[$val] = $post_vars[$key];
  }
  return $obj;
}

function stuff_errors(&$ui, &$mapping)
{
  $myerr = 0;
  $myerr = $ui->report_errors(array_flip($mapping));
  return($myerr);
}

function update_cce(&$ui, $target, $mapping, $http_vars, $NsMapping)
{
  $errs = 0;
  $oid = 0;

  $ui->BadData = array();
  $ui->Errors = array();
  if (intval($target) > 0) {
    $oid = intval($target);
    $ui->Cce->set ($oid, "",
    		map_vars($mapping, $http_vars, $NsMapping, ""));
  } else {
    $class = $target;
    $oid = $ui->Cce->create($class,
    		map_vars($mapping, $http_vars, $NsMapping, ""));
  }
  $errs = stuff_errors($ui, $mapping);

  $ui->Cce->set($oid, "Archive",
		map_vars($mapping, $http_vars, $NsMapping, "Archive"));
  $errs += stuff_errors($ui, $mapping);

  // debug:
  /*** echo "<hr>errors:<br>";
  reset($errors);
  for ($i = 0; $i < count($errors) ; $i++) {
    echo "Error $i:<ul>";
    while (list($key,$val) = each($errors[$i])) {
      echo "<li> $key = $val";
    }
    echo "</ul>";
  }
  echo "<hr>";
  ***/

  //echo ($errs == 0) ? "<hr> success!" : "<hr> failure!";
  return ($errs == 0);
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

