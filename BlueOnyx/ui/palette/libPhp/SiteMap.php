<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: SiteMap.php 259 2004-01-03 06:28:40Z shibuya $

global $isSiteMapDefined;
if($isSiteMapDefined)
    return;
$isSiteMapDefined = true;

include_once("DirTree.php");
include_once("I18n.php");
include_once("System.php");

class SiteMap 
{
    //
    // private variables
    //
    var $items;

    //
    // public functions
    //

    // description: constructor
    function SiteMap() 
    {
        $this->items = _SiteMap_load();
    }

    // description: get the Javascript representation of the site map
    // param: accessRights: an array of access rights in strings
    // param: prefix: an optional prefix string to append to variable names
    // param: localePreference: a comma separated list of preferred locale
    // returns: Javascript code that uses functions in menuItem.js
    //     to build a site map
    function toJavascript($accessRights, $prefix, $localePreference) 
    {
        $result = "";

        // for variable substitution
        global $HTTP_GET_VARS;

        // for i18n
        $i18n = new I18n("", $localePreference);

        $items = $this->items;

        // create the javascript objects
        $itemIds = array_keys($items);
        foreach($itemIds as $itemId) 
        {
            $item = $items[$itemId];
            $description = $i18n->interpolateJs($item["description"]);
            $label = $i18n->interpolateJs($item["label"], $HTTP_GET_VARS);
            $type = $item["type"];
            $url = $i18n->interpolateJs($item["url"], $HTTP_GET_VARS);
	        $window = $i18n->interpolateJs($item["window"]);
	        $imageOff = $item["imageOff"];
	        $imageOn = $item["imageOn"];

            $result .= "$prefix$itemId = new top.code.mItem_Item(\"$itemId\", \"$label\", \"$description\", \"$type\", \"$url\", false, true, \"$window\", \"$imageOff\", \"$imageOn\");\n";

	    // add any script params this item wants
	    foreach ($item["params"] as $varName => $varValue) {
		$varValue = $i18n->interpolateJs($varValue);
		$result .= "$prefix{$itemId}_$varName = '$varValue';\n";
	    }

            // create a list of children for this item
            foreach($item["parents"] as $parentkey => $parentval) 
            {
                if (in_array($parentkey, $itemIds)) // &&$accessible) 
                {
                    if ($items[$parentkey]["children"] == null)
                        $items[$parentkey]["children"] = array();

      	            $items[$parentkey]["children"][] = $itemId;
                }
            } 
        }

        // go through and make our links table
        $links = array();
        foreach($itemIds as $itemId) 
        {
            $parent = $items[$itemId];
        
            $children = $parent["children"];
            if ($children != null) 
            {
                // for each parent
                foreach($children as $child) 
                {
                    // if a node has NO URL, and NO children..   then we just 
                    // won't show it,   how about that?
                    if (!goesSomewhere($items, $child, 
                            $items[$child]["parents"][$itemId]["access"],
                            $accessRights)) 
                    {
     	                continue;
                    } 
  
  	                // save link
  	                if ($links[$items[$child]["parents"][$itemId]["order"]] == null)
  	                    $links[$items[$child]["parents"][$itemId]["order"]] = array();
  	  
                    $links[$items[$child]["parents"][$itemId]["order"]][] = array($itemId, $child);
                }
            }
        }

        // sort
        $orders = array_keys($links);
        sort($orders);

        // create the links
        foreach ($orders as $order) 
        {
            $parentsToChildren = $links[$order];
            foreach ($parentsToChildren as $parentToChild) 
            {
	            $parentId = $parentToChild[0];
	            $childId = $parentToChild[1];

                $result .= "$prefix$parentId.addItem($prefix$childId);\n";
            }
        }

        return $result;
    }
}

// there is no object/method support for the XML lib,
// so let's do something ugly

//
// private variables
//

$_SiteMap_system = new System();
$_SiteMap_menuDir = $_SiteMap_system->getConfig("menuDir");
$_SiteMap_defaultMenuOrder = $_SiteMap_system->getConfig("defaultMenuOrder");

$_SiteMap_currentItemId = null;
$_SiteMap_currentParentId = null;

// a hash of item ids to items
$_SiteMap_items = array();

$_SiteMap_localePreference;

//
// private functions
//

// description: get the Javascript representation of the site map
// returns: a hash of item ids to item hashes
function _SiteMap_load() {
  global $_SiteMap_items;
  global $_SiteMap_menuDir;

  // scan the whole tree
  $dirTree = new DirTree($_SiteMap_menuDir);
  $allFiles = $dirTree->getAllFiles();
  for($i = 0; $i < count($allFiles); $i++) {
    $file = $allFiles[$i];

    // only look at XML files
    $ext = substr($file, -4, 4);
    if($ext != ".xml" && $ext != ".XML")
      continue;

    if(!($handle = fopen($file, "r"))) {
      error_log("_SiteMap_load(): Could not open $file", 0);
      continue;
    }

    // construct a XML parser
    $xmlParser = xml_parser_create();
    xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, false);
    xml_set_element_handler($xmlParser, "_SiteMap_startElementHandler", "_SiteMap_nullHandler");

    // parse the XML file
    while($data = fread($handle, 4096)) {
      if(!xml_parse($xmlParser, $data, feof($handle)))
      error_log(sprintf("_SiteMap_load(): XML error in file $file: %s at line %d",
	  xml_error_string(xml_get_error_code($xmlParser)),
	  xml_get_current_line_number($xmlParser)), 0);
    }

    // free the parser
    xml_parser_free($xmlParser);

    fclose($handle);
  }

  return $_SiteMap_items;
}


function _SiteMap_nullHandler($parser, $name, $attributes=array()) {
}

function _SiteMap_startElementHandler($parser, $name, $attributes) {
  switch($name) {
    case "parent":
      _SiteMap_parentStartHandler($attributes);
      break;

    case "item":
      _SiteMap_itemStartHandler($attributes);
      break;

    case "access":
      _SiteMap_accessStartHandler($attributes);
      break;
    case "param":
      _SiteMap_scriptVarStartHandler($attributes);
      break;
  }
}

function _SiteMap_itemStartHandler($attributes) {
  global $_SiteMap_currentItemId;
  global $_SiteMap_items;

  // get ID
  $itemId = $attributes["id"];

  // check if the ID is unique
  if($_SiteMap_items[$itemId] != null)
    error_log("_SiteMap_itemStartHandler(): Site map item ID $itemId is being used already.", 0);

  // make item
  $item = $_SiteMap_items[$itemId];
  $item = array();
  $item["description"] = $attributes["description"];
  $item["id"] = $itemId;
  $item["label"] = $attributes["label"];
  $item["type"] = $attributes["type"];
  $item["url"] = $attributes["url"];
  $item["window"] = $attributes["window"];
  $item["imageOff"] = $attributes["imageOff"];
  $item["imageOn"] = $attributes["ImageOn"];
  $item["requiresChildren"] = $attributes["requiresChildren"];
  $item["parents"] = array();
  $item["params"] = array();

  // save item
  $_SiteMap_items[$itemId] = $item;

  // set current item
  $_SiteMap_currentItemId = $itemId;
}

function _SiteMap_parentStartHandler($attributes) {
  global $_SiteMap_currentItemId;
  global $_SiteMap_currentParentId;
  global $_SiteMap_defaultMenuOrder;
  global $_SiteMap_items;

  // make parent
  $parent = array();
  $parent["id"] = $attributes["id"];
  $parent["order"] = $attributes["order"];
  $parent["access"] = array();

  // set default
  if($parent["order"] == "")
    $parent["order"] = $_SiteMap_defaultMenuOrder;

  // save parent
  $_SiteMap_items[$_SiteMap_currentItemId]["parents"][$parent["id"]] = $parent;

  // set current parent
  $_SiteMap_currentParentId = $parent["id"];
}

function _SiteMap_accessStartHandler($attributes) {
  global $_SiteMap_currentItemId;
  global $_SiteMap_currentParentId;
  global $_SiteMap_items;

  // make access
  $access = array();
  $access["require"] = $attributes["require"];

  // save parent
  $_SiteMap_items[$_SiteMap_currentItemId]["parents"][$_SiteMap_currentParentId]["access"][] = $access;
}

function _SiteMap_scriptVarStartHandler($attributes) {
  global $_SiteMap_currentItemId;
  global $_SiteMap_items;

  // make var
  $var = array();
  $var["id"] = $attributes["name"];
  $var["value"] = $attributes["value"];

  // save var
  $_SiteMap_items[$_SiteMap_currentItemId]["params"][$var["id"]] = $var["value"];
}

/*
// some debug code
$itemIds = array_keys($this->items);
for($i = 0; $i < count($itemIds); $i++) {
  $item = $this->items[$itemIds[$i]];

  $itemKeys = array_keys($item);
  for($j = 0; $j < count($itemKeys); $j++)
    print("// item $itemKeys[$j] = ".$item[$itemKeys[$j]]."\n");

  $parents = $item["parents"];
  $parentIds = array_keys($parents);
  for($j = 0; $j < count($parentIds); $j++) {
    $parent = $parents[$parentIds[$j]];
    print("//   parent id = ".$parent["id"]."\n");
    print("//   parent order = ".$parent["order"]."\n");

    $access = $parent["access"];
    for($k = 0; $k < count($access); $k++) {
      $access = $access[$k];
      $require = $access["require"];
      print("//     access require = $require\n");
    }
  }

  print("//\n");
}
*/

function getAccesses($nodeId, &$items) 
{
    // collect the access of all our parents
    $ret = array();
    if ($items[$nodeId]["parents"] != null) 
    {
        foreach ($items[$nodeId]["parents"] as $parentId => $parentReq) 
        {
            $ret = array_merge($ret, $parentReq["access"]);
            $ret = array_merge($ret, getAccesses($parentId,$items));
        }
    }
    return $ret;
}

function goesSomewhere(&$items, $nodeId, &$access, &$accessRights) 
{
    // If I dont have any accesses,  let's find some 
    // collection of accesses as we chain up 
    if (count($access) == 0)
        $access = getAccesses($nodeId, $items); 
        
    $requiresChildren = $items[$nodeId]["requiresChildren"];
    // check if this is a dead end.
//      if (count($access)==0 )
//          return 1;

    $capabilities =& getGlobalCapabilitiesObject();

    // check the access rights
    $accessible = 0;
    if ($access != null && count($access) > 0) 
    {
        foreach($access as $accessItem) 
        {
            // Show all for systemAdministrators
            if ($capabilities->getAllowed("systemAdministrator")
                || $capabilities->getAllowed($accessItem["require"])) 
            {
                $accessible = 1;
                break;
            }
        }
    } 
    else 
    {
        // No access requirements..    default is accessible
        $accessible = 1;
    }

    // See if children go anywhere
    $children = $items[$nodeId]["children"];
    $childrenGoSomewhere = 0;
    if ($children != null) 
    {
        foreach($children as $child) 
        {
            // chain default accesses down the tree
            $childAccess = $items[$child]["parents"][$nodeId]["access"];
            if ($childAccess != null) 
                $newAccess = $childAccess;
            else 
                $newAccess = $access;
      
            if (goesSomewhere($items, $child, $newAccess, $accessRights)) 
            {
                $childrenGoSomewhere = 1;
                break;
            }
        }
    }
  
    // am I accessible as far as my current access rights are concerned?
    if (!$accessible) 
    {
        // by default I shouldn't be shown
        if (!$childrenGoSomewhere) 
        {
            // no children, and I'm not accessible, therefore I fold
            return 0;
        } 
        else 
        {
            // I shouldn't be shown,  but one of my children might 
            // override this
            return 1;
        }
    } 
    else 
    {
        // I should be shown, but I still need to see if I require children
        if ($requiresChildren) 
        {
            if ($childrenGoSomewhere) 
            {
                return 1;
            } 
            else 
            {
                // children may not go somewhere, but if I have an url, i
                // still show
                // if ($items[$nodeId]["url"] != null 
                //    && $items[$nodeId]["url"] != "") 
                // {
                //    return 1;
                // } 
                // else 
                // {

                // if a menu item requiresChildren, don't show it no matter
                // what
                return 0;
                // }
            }
        } 
        else 
        {
            // default is to show
            return 1;
        }
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
