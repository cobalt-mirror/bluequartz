<?php
// Author: Kevin K.M. Chiu
// $Id: Stylist.php 

// NOTE BY MSTAUBER: Usage of this class is deprecated! 
// Any page that has been ported to "BlueOnyx NG" has the
// style() or stylist() stuff ripped out or disabled.
// Appearance of the GUI is entirely defined by CSS these
// days.

global $isStylistDefined;
if($isStylistDefined)
  return;
$isStylistDefined = true;

include_once("DirTree.php");
include_once("I18n.php");
include_once("System.php");
include_once("uifc/Style.php");

class Stylist {

    //
    // private variables
    //    
    var $_Stylist_styles = array();
    var $_Stylist_currentStyle = "";
    var $_Stylist_localePreference = "";
    var $_Stylist_resourceId;

    // the name of the style 
    var $styleId = array();

    // constructor
    // styleId - Limit the styles retrieved to this style id (eg "Page" or "Login")
    // property - Limit the properties in style(s) retrieved to this property name
    // target - Limit the targets retrieved to this property target
    function Stylist($id="") {
        $this->setStyleId($id);
    }

  //
  // public methods
  //

    // set the style id(s) to which this style object should be limited
    // id - a string or array
    public function setStyleId($id) {
            $this->styleId = $id;
    }

     // return an array of the style ids to which this style object is limited
    public function getStyleId() {
        return($this->styleId);
    }
    
  // description: get a list of IDs for all the available style resources
  // returns: an array of ID strings
  public function getAllResourceIds() {
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
  public function getResourceName($resourceId, $localePreference) {
    $system = new System();
    $styleDir = $system->getConfig("styleDir");

    // get locale preferences
    $localePreferences = explode(",", $localePreference);

    // get all usable locales from locale hierarchies
    $locales = array();
    for($i = 0; $i < count($localePreferences); $i++) {
      $localeHierarchy = $this->_Stylist_getLocaleHierarchy($localePreferences[$i]);
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
  if(is_file($fileName))
    return $this->_Stylist_getResourceId($fileName, $localePreference);
      }

    // not found
    return "";
  }

  // description: get a list of all the style resources available
  // param: localePreference: a comma separated list of preferred locale
  // returns: a hash of style resource id to name
  public function getAllResources($localePreference) {
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
  public function setResource($styleResource, $locale) {
    $this->_Stylist_load($styleResource, $locale);
  }

  // description: set a style object to the stylist
  // param: style: a Style object
  public function setStyle($style) {
    $id = $style->getId();
    $variant = $style->getVariant();
    $this->_Stylist_styles["style:$id:$variant"] = $style;
  }

  // description: get a style object with the specified id and variant
  //     if no style of the id and variant can be found, only the id is used
  //     if no style of the id can be found, an empty style is returned
  // param: styleId: the identifier of the style in string
  // param: styleVariant: the variant of the style in string
  // returns: a style object with properties if the style can be found
  //     empty Style object otherwise
  public function &getStyle($styleId="", $styleVariant = "") {
    // If no style ID is passed in, use the first one 
    // passed into stylist
    if(empty($styleId)) {
       $styleId = $this->getStyleId();
    }

     $style = $this->_Stylist_styles["style:$styleId:$styleVariant"];

    // try again without the variant
    if(!$style)
      $style = $this->_Stylist_styles["style:$styleId:"];

    // give up
    if(!$style)
      return new Style("", "", $this);

    $styleObj = new Style($style["id"], $style["variant"], $this);
    $styleObj->properties = $style["properties"];

    return $styleObj;
  }
 

//
// private functions
//

// description: get the style resource ID from a file
// param: file: path of the file in string
// param: localePreference: a comma separated list of preferred locale
// returns: a style resource ID in string if succeed or false otherwise
function _Stylist_getResourceId($file, $localePreference) {

  // initialize because _Stylist_resourceElementHandler needs it
  $this->_Stylist_localePreference = $localePreference;
  if(!$this->_Stylist_parseXmlFile($file, "_Stylist_resourceElementHandler"))
    return false;

  return $this->_Stylist_resourceId;
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
  $system = new System();
  $styleDir = $system->getConfig("styleDir");

  $locales = array_merge((array)"", $this->_Stylist_getLocaleHierarchy($locale));

  // load style resource for each locale
  for($i = 0; $i < count($locales); $i++) {
    $localeString = ($locales[$i] == "") ? "" : ".".$locales[$i];
    $this->_Stylist_parseXmlFile("$styleDir/$styleResource.xml$localeString", "_Stylist_startElementHandler");
  }
  // return $this->_Stylist_styles;
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
      $this->_Stylist_styleStartHandler($attributes);
      break;

    case "property":
      $this->_Stylist_propertyStartHandler($attributes);
      break;
  }
}

function _Stylist_resourceElementHandler($parser, $name, $attributes) {

  // only look at the interesting element
  if($name != "styleResource")
    return;

  $resourceName = $attributes["name"];

  // do i18n here
  $i18n = new I18n("", $this->_Stylist_localePreference);
  $resourceName = $i18n->interpolate($resourceName);

  $this->_Stylist_resourceId = $resourceName;
}

function _Stylist_styleStartHandler($attributes) {
  $id = $attributes["id"];
  $variant = $attributes["variant"];

  $key = "style:$id:$variant";

  // make style if it is new
  if(!is_array($this->_Stylist_styles[$key])) {
    $style = array();
    $style["id"] = $id;
    $style["variant"] = $variant;

    // save style
    $this->_Stylist_styles[$key] = $style;
  }

  // set the current style for sub-tags
  $this->_Stylist_currentStyle = $key;
}

function _Stylist_propertyStartHandler($attributes) {
    $this->_Stylist_styles[$this->_Stylist_currentStyle] ["properties"] [$attributes["name"] . ":" . $attributes["target"]] = $attributes["value"];
}

function _Stylist_parseXmlFile($file, $startElementHandler) {
  if(!file_exists($file))
    return false;

  // It is assinged in the handler ...
  if ($startElementHandler != "_Stylist_resourceElementHandler") {
  // stamp the compiled file with modification time of the original
  // and PHP fingerprint to make sure it is consistent with the original
  $stamp = md5(filemtime($file).php_uname());
  $compiledFile = "$file.compiled.$stamp";

  // see if compiled XML file exists
 if(is_file($compiledFile)) {
    // read style resource stored in the compiled file
    $handle = fopen($compiledFile, "r");
    $this->_Stylist_styles = unserialize(fread($handle, filesize($compiledFile)));
    fclose($handle);
    $this->_Stylist_trim_styles();
    return true;
  }
  }

  if(!($handle = fopen($file, "r")))
    return false;

  // construct a XML parser
  $this->xmlParser = xml_parser_create();
  xml_set_object($this->xmlParser,$this);
  xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, false);
  xml_set_element_handler($this->xmlParser, $startElementHandler, "_Stylist_nullHandler");

  // parse the XML file
  while($data = fread($handle, 4096)) {
    if(!xml_parse($this->xmlParser, $data, feof($handle)))
      error_log(sprintf("_Stylist_parseXmlFile(): XML error in file $file: %s at line %d",
      xml_error_string(xml_get_error_code($this->xmlParser)),
      xml_get_current_line_number($this->xmlParser)), 0);
  }

  // free the parser
  xml_parser_free($this->xmlParser);
  fclose($handle);

  if ($startElementHandler == "_Stylist_resourceElementHandler") {
    return (true);
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
    fwrite($handle, serialize($this->_Stylist_styles));
    fclose($handle);
  }

    // get rid of style info we don't need
  $this->_Stylist_trim_styles();
  
  return true;
}

    function _Stylist_trim_styles() {
        // If styleId is specified, reduce the list of styles to only 
        // the id[:variant] we were looking for        
        $styleId =& $this->styleId;
        if(!empty($styleId)) {
            $style =& $this->_Stylist_styles["style:$styleId:$styleVariant"];
            
            if(!$style) {
                $style =& $this->_Stylist_styles["style:$styleId:"];
            }
            
            if($style) {
                unset($this->_Stylist_styles);
                 $this->_Stylist_styles["style:$styleId:$styleVariant"] = $style;            
            }
        }    
      
    }
    


} // end Stylist class

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>