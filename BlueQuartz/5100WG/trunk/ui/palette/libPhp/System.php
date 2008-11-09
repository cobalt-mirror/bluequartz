<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: System.php 236 2003-09-10 07:50:31Z shibuya $

global $isSystemDefined;
if($isSystemDefined)
  return;
$isSystemDefined = true;

include_once("ConfigFile.php");

//
// class variables
//
// Keep these as class variables to make sure the config file is read only once
// across multiple <cough> instances... 
// actually they need to be globals, because php scoping is confused
// and these will occasionly disappear from the class scope
global $_System_configFile, $_System_expandedConfigHash, $_System_isConfigRead;
$_System_expandedConfigHash = null;
$_System_isConfigRead = false;
$_System_configFile = "/usr/sausalito/ui/conf/ui.cfg";

class System {
  //
  // public methods
  //

    // description: gets a string from the configuration file
    //     expand all the [[key]]
    //     any [[unknown key]] will be removed
    // param: key: a key string
    // returns: the expanded config
    function getConfig($key) 
    {
        global $_System_isConfigRead;
        global $_System_expandedConfigHash;
        global $_System_configFile;

        if(!$_System_isConfigRead) 
        {
            $cfg = new ConfigFile;

            if(!$cfg->readConfigFile($_System_configFile))
            {
                return "";
            }
            else 
            {
	            $_System_expandedConfigHash = $cfg->getConfig();
	            $_System_isConfigRead = true;
            }
        }

        return $_System_expandedConfigHash[$key];
    }

  // description: check if an RPM is installed
  // returns: true if installed, false otherwise
  function isRpmInstalled($rpmname) {
    $rpmResults = exec("rpm -q $rpmname 2>&1");
    if (strstr($rpmResults, "is not installed")) {
      return false;
    } else {
      return true;
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
