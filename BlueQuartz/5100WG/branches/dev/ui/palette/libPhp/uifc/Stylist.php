<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Stylist.php 201 2003-07-18 19:11:07Z will $

global $isStylistDefined;
if($isStylistDefined)
  return;
$isStylistDefined = true;

include("DirTree.php");
include("I18n.php");
include("System.php");
include("uifc/Style.php");

class Stylist {
  //
  // private variables
  //

  var $styles;
  var $styleObjectCache;

  //
  // public methods
  //

  // description: get a list of IDs for all the available style resources
  // returns: an array of ID strings
  function getAllResourceIds() {
    $system = new System();
    $styleDir = $system->getConfig("styleDir");

    $dirTree = new DirTree($styleDir);
    $allFiles = $dirTree->getAllFiles();

    $resourceIds = array();
    for($i = 0; $i < count($allFiles); $i++) {
      $file = $allFiles[$i];
      $ext = substr($file, -4, 4);
      if($ext == ".xml" || $ext == ".XML")
	// find our id from file name
	$resourceIds[] = substr($file, strlen($styleDir)+1, strlen($file)-strlen($styleDir)-5);
    }

    return $resourceIds;
  }

  // description: get the name of a style resource
  // param: resourceId: ID string of the resource
  // param: localePreference: a comma separated list of preferred locale
  // returns: an i18ned string or "" if not found
  function getResourceName($resourceId, $localePreference) {
    $system = new System();
    $styleDir = $system->getConfig("styleDir");

    // get locale preferences
    $localePreferences = explode(",", $localePreference);

    // get all usable locales from locale hierarchies
    $locales = array();
    for($i = 0; $i < count($localePreferences); $i++) {
      $localeHierarchy = _Stylist_getLocaleHierarchy($localePreferences[$i]);
      $locales = array_merge($locales, array_reverse($localeHierarchy));
    }

    // search locale-insensitive file at the end
    $locales[] = "";

    // extensions we honor
    $extensions = array("xml", "XML");

    // search through extensions
    for($i = 0; $i < count($extensions); $i++)
      // search through the hierarchies of locales
      for($j = 0; $j < count($locales); $j++) {
	$fileName = "$styleDir/$resourceId.$extensions[$i]";
	// add locale to the end of the file name if necessary
	if($locales[$j] != "")
	  $fileName .= ".$locales[$j]";

	// return the name stored in the file if it exists
	if(is_file($fileName)) {
	 return(_Stylist_getResourceId($fileName, $localePreference));
	}
      } 

        
    // not found
    return "";
  }

  // description: get a list of all the style resources available
  // param: localePreference: a comma separated list of preferred locale
  // returns: a hash of style resource id to name
  function getAllResources($localePreference) {
    // the hash to return
    $idToName = array();

    $resourceIds = $this->getAllResourceIds();
    for($i = 0; $i < count($resourceIds); $i++) {
      $resourceId = $resourceIds[$i];
      $idToName[$resourceId] = $this->getResourceName($resourceId, $localePreference);
    }

    return $idToName;
  }

  // description: set the style resource
  // param: styleResource: an ID in string that identifies the style resource
  // param: locale: a locale string for style localization
  function setResource($styleResource, $locale) {
    $this->styles = _Stylist_load($styleResource, $locale);
  }

  // description: set a style object to the stylist
  // param: style: a Style object
  function setStyle($style) {
    $id = $style->getId();
    $variant = $style->getVariant();
    $this->styles["style:$id:$variant"] = $style;
  }

  // description: get a style object with the specified id and variant
  //     if no style of the id and variant can be found, only the id is used
  //     if no style of the id can be found, an empty style is returned
  // param: styleId: the identifier of the style in string
  // param: styleVariant: the variant of the style in string
  // returns: a style object with properties if the style can be found
  //     empty Style object otherwise
  function &getStyle($styleId, $styleVariant = "") {
    // check cache
    $styleObj =& $this->styleObjectCache["style:$styleId:$styleVariant"];
    if(is_object($styleObj))
      return $styleObj;

    // check again without the variant
    $styleObj =& $this->styleObjectCache["style:$styleId:"];
    if(is_object($styleObj))
      return $styleObj;

    $style = $this->styles["style:$styleId:$styleVariant"];

    // try again without the variant
    if(!$style)
      $style = $this->styles["style:$styleId:"];

    // give up
    if(!$style)
      return new Style("", "", $this);

    $styleObj = new Style($style["id"], $style["variant"], $this);

    for($i = 0; $i < count($style["propertyName"]); $i++)
      $styleObj->setProperty($style["propertyName"][$i], $style["propertyTarget"][$i], $style["propertyValue"][$i]);

    // store object in cache
    $this->styleObjectCache["style:".$style["id"].":".$style["variant"]] =& $styleObj;

    return $styleObj;
  }
}


// there is no object/method support for the XML lib,
// so let's do something ugly

//
// private variables
//

$_Stylist_styles = array();
$_Stylist_currentStyle = "";
$_Stylist_localePreference = "";

//
// private functions
//

// description: get the style resource ID from a file
// param: file: path of the file in string
// param: localePreference: a comma separated list of preferred locale
// returns: a style resource ID in string if succeed or false otherwise
function _Stylist_getResourceId($file, $localePreference) {
  global $_Stylist_localePreference;
  global $_Stylist_resourceId;

  // initialize because _Stylist_resourceElementHandler needs it
  $_Stylist_localePreference = $localePreference;
  if(!_Stylist_parseXmlFile($file, "_Stylist_resourceElementHandler"))
    return false;

  return $_Stylist_resourceId;
}

// description: load in a style from "styleDir"
//     defined in the configuration file
// param: styleResource: an identifier string.
//     Style <styleDir>/<styleResource>.xml is loaded
// param: locale: a locale string for style localization
// returns: a hash containing all the style information or empty hash if failed
//     key "id" contains the id in string
//     key "variant" contains the variant in string
//     key "property" contains properties in a hash
function _Stylist_load($styleResource, $locale) {
  global $_Stylist_styles;

  $system = new System();
  $styleDir = $system->getConfig("styleDir");

  $locales = array_merge("", _Stylist_getLocaleHierarchy($locale));

  // load style resource for each locale
  for($i = 0; $i < count($locales); $i++) {
    $localeString = ($locales[$i] == "") ? "" : ".".$locales[$i];
    _Stylist_parseXmlFile("$styleDir/$styleResource.xml$localeString", "_Stylist_startElementHandler");
  }

  return $_Stylist_styles;
}

// description: get all sub-locales from a locale hierarchy
//     For example, "ja_JP.EUC" should return "ja", "ja_JP" and "ja_JP.EUC"
// param: locale: a locale string
// returns: an array of locales from least specific to most specific
function _Stylist_getLocaleHierarchy($locale) {
  $localeLength = strlen($locale);
  // start with no locale
  $locales = array();
  // get language
  if($localeLength >= 2)
    array_push($locales, substr($locale, 0, 2));
  // get country
  if($localeLength >= 5)
    array_push($locales, substr($locale, 0, 5));
  // get variant
  if($localeLength > 6)
    array_push($locales, $locale);

  return $locales;
}

function _Stylist_nullHandler($parser, $name, $attributes=array()) {
}

function _Stylist_startElementHandler($parser, $name, $attributes) {
  switch($name) {
    case "style":
      _Stylist_styleStartHandler($attributes);
      break;

    case "property":
      _Stylist_propertyStartHandler($attributes);
      break;
  }
}

function _Stylist_resourceElementHandler($parser, $name, $attributes) {
  global $_Stylist_resourceId;
  global $_Stylist_localePreference;

  // only look at the interesting element
  if($name != "styleResource")
    return;

  $resourceName = $attributes["name"];

  // do i18n here
  $i18n = new I18n("", $_Stylist_localePreference);
  $resourceName = $i18n->interpolate($resourceName);

  $_Stylist_resourceId = $resourceName;
}

function _Stylist_styleStartHandler($attributes) {
  global $_Stylist_styles;
  global $_Stylist_currentStyle;

  $id = $attributes["id"];
  $variant = $attributes["variant"];

  $key = "style:$id:$variant";

  // make style if it is new
  if(!is_array($_Stylist_styles[$key])) {
    $style = array();
    $style["id"] = $id;
    $style["variant"] = $variant;
    $style["propertyName"] = array();
    $style["propertyTarget"] = array();
    $style["propertyValue"] = array();

    // save style
    $_Stylist_styles[$key] = $style;
  }

  // set the current style for sub-tags
  $_Stylist_currentStyle = $key;
}

function _Stylist_propertyStartHandler($attributes) {
  global $_Stylist_styles;
  global $_Stylist_currentStyle;

  $_Stylist_styles[$_Stylist_currentStyle]["propertyName"][] = $attributes["name"];
  $_Stylist_styles[$_Stylist_currentStyle]["propertyTarget"][] = $attributes["target"];
  // $_Stylist_styles[$_Stylist_currentStyle]["propertyValue"][] = $attributes["value"];

  $system = new System();
  $imageDir = $system->getConfig("webDir");
  $value = $attributes["value"];
  $ext = substr($value, -4, 4);
  $filename = $imageDir . $value;
  if (($ext == ".gif" || $ext == ".jpg") && file_exists($filename))
  {
    $sizespec = getimagesize("$filename");
    // echo "img src=\"$value\" {$sizespec[3]}<BR>";
    // $_Stylist_styles[$_Stylist_currentStyle]["propertyName"][] = $attributes["name"] . "SizeSpec";
    // $_Stylist_styles[$_Stylist_currentStyle]["propertyValue"][] = addslashes($sizespec[3]);
    $len = strlen($sizespec[3]);
    $propertyValue = $attributes["value"] . "\" " . substr($sizespec[3], 0, $len-1);
  }
  else
  {
    $propertyValue = $attributes["value"];
  }

  $_Stylist_styles[$_Stylist_currentStyle]["propertyValue"][] = $propertyValue;
}

function _Stylist_parseXmlFile($file, $startElementHandler) {
  global $_Stylist_styles;

  if(!file_exists($file))
    return false;

        // dumb hack #362: don't use cache when getting resource name
    if($startElementHandler != "_Stylist_resourceElementHandler") {
      // stamp the compiled file with modification time of the original
      // and PHP fingerprint to make sure it is consistent with the original
      $stamp = md5(filemtime($file).php_uname());
      $compiledFile = "$file.compiled.$stamp";
    
      // see if compiled XML file exists
      if(is_file($compiledFile)) {
        // read style resource stored in the compiled file
        $handle = fopen($compiledFile, "r");
        $_Stylist_styles = unserialize(fread($handle, filesize($compiledFile)));
        fclose($handle);
        return true;
      }
    }
  if(!($handle = fopen($file, "r")))
    return false;

  // construct a XML parser
  $xmlParser = xml_parser_create();
  xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, false);
  xml_set_element_handler($xmlParser, $startElementHandler, "_Stylist_nullHandler");

  // parse the XML file
  while($data = fread($handle, 4096)) {
    if(!xml_parse($xmlParser, $data, feof($handle)))
      error_log(sprintf("_Stylist_parseXmlFile(): XML error in file $file: %s at line %d",
      xml_error_string(xml_get_error_code($xmlParser)),
      xml_get_current_line_number($xmlParser)), 0);
  }

  // free the parser
  xml_parser_free($xmlParser);

  fclose($handle);

    // dumb hack #362: don't use cache when getting resource name]
    // for some bizarre reason $startElementHandler gets lower-cased up there
    if(strtolower($startElementHandler) == "_stylist_resourceelementhandler") {
        return(true);
    }
    
    
  // get the directory of the XML file
  $dir = dirname($file);

  // clean up old compiled files
  $handle = opendir($dir);
  while($fileName = readdir($handle)) {
    // remove the file if it is a compiled XML file
    $fullPath = "$dir/$fileName";
    $prefix = "$file.compiled.";
    if(substr($fullPath, 0, strlen($prefix)) == $prefix)
      unlink($fullPath);
  }
  closedir($handle);

  // save style resource into compiled file
  // note that the process that runs this code must be permitted to write to
  // the filesystem for this compiling mechanism to work
  // check writability first before fopen because it prints warnings otherwise
  // PHP is_writable() returns true no matter what and PHP getmyuid() returns
  // 0 even if Apache downgrades itself, so we need to do very ugly things here
  $info = stat($dir);
  $permission = $info[2];
  $ownerUser = $info[4];
  // world writable or Apache writable (just assume non-root here)?
  if(($permission & 02) == 02
      || (($permission & 0200) == 0200 && $ownerUser != 0)) {
    $handle = fopen($compiledFile, "w");
    fwrite($handle, serialize($_Stylist_styles));
    fclose($handle);
  }

  return true;
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

