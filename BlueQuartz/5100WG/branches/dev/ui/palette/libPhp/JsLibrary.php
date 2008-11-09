<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: JsLibrary.php 201 2003-07-18 19:11:07Z will $

global $isJsLibraryDefined;
if($isJsLibraryDefined)
  return;
$isJsLibraryDefined = true;

include("DirTree.php");
include("System.php");

// description: this class represents a Javascript library
class JsLibrary {
  //
  // private variables
  //

  var $MAX_LINE_LEN = 10240;

  // description: searches the whole subdirectory of webDir (defined in ui.cfg)
  //     to find all the Javascript files with a all lowercase .js extension
  // param: isOptimized: make output with shorter load time
  //     Not recommended for debugging
  // returns: HTML script tags that links to all the javascript libraries
  function toHTML($isOptimized = false) {
    $system = new System();
    $webBase = $system->getConfig("webDir");

    $dirTree = new DirTree($webBase);
    $allFiles = $dirTree->getAllFiles();

    $javascriptLibs = array();
    for($i = 0; $i < count($allFiles); $i++) {
      $file = $allFiles[$i];
      $ext = substr($file, -3, 3);
      if($ext == ".js")
	$javascriptLibs[] = $file;
    }

    // sort file name (not full path) to ensure order is defined
    $nameToPath = array();
    for($i = 0; $i < count($javascriptLibs); $i++)
      $nameToPath[basename($javascriptLibs[$i])] = $javascriptLibs[$i];
    ksort($nameToPath);
    $javascriptLibs = array_values($nameToPath);

    $html = "";

    if(!$isOptimized) {
      for($i = 0; $i < count($javascriptLibs); $i++) {
        $path = substr($javascriptLibs[$i], strlen($webBase));
        $html .= "<SCRIPT LANGUAGE=\"javascript\" SRC=\"".$path."\"></SCRIPT>\n";
      }
    }
    else {
      $html .= "<SCRIPT LANGUAGE=\"javascript\">";

      for($i = 0; $i < count($javascriptLibs); $i++) {
	$handle = fopen($javascriptLibs[$i], "r");
	while(!feof($handle)) {
	  $line = fgets($handle, $this->MAX_LINE_LEN);

	  // skip whitespaces
	  $line = ltrim($line);

	  // skip comments
	  if(substr($line, 0, 2) == "//")
	    continue;

	  $html .= $line;
	}
      }

      $html .= "</SCRIPT>";
    }

    return $html;
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

