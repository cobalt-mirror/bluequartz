<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: DirTree.php 201 2003-07-18 19:11:07Z will $

global $isDirTreeDefined;
if($isDirTreeDefined)
  return;
$isDirTreeDefined = true;

// description: This class represents a directory tree
class DirTree {
  //
  // private variables
  //

  var $root;

  //
  // public methods
  //

  // description: constructor
  // param: root: the root path of the directory tree
  // returns: nothing
  function DirTree($root) {
    if(!file_exists($root))
      return null;

    $this->root = $root;
  }

  // description: gets all files under a directory tree, excluding hidden files
  // returns: an array of full paths of files, not directories, under root
  function getAllFiles() {
    $fileList = array();

    $dirQueue[0] = $this->root;
    $dirQueueCount = 1;

    while($dirQueueCount > 0) {
      $dir = $dirQueue[--$dirQueueCount];
      $handle = opendir($dir);

      while($file = readdir($handle)) {
	$fullPath = $dir."/".$file;

	// skip all dot files
	if(substr($file, 0, 1) == ".")
	  continue;

	// do not follow symlinks
	// no looping can occur
	if(is_link($fullPath))
	  continue;

	// traverse sub-directories recursively
	if(is_dir($fullPath)) {
	  $dirQueue[$dirQueueCount++] = $fullPath;
 	  continue;
	}

	$fileList[] = $fullPath;
      }
      closedir($handle);
    }

    return $fileList;
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

