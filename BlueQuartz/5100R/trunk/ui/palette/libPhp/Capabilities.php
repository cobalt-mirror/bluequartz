<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: Capabilities.php 1050 2008-01-23 11:45:43Z mstauber $
// Description: A class that facilitates working with Capabilities
// and capability groups

include_once("ArrayPacker.php");

global $CAPABILITIESCLASS;
if ($CAPABILITIESCLASS)
  return; 
$CAPABILITIESCLASS = 1;

global $CAPABILITIESGLOBALOBJECT;
$CAPABILITIESGLOBALOBJECT = null;

function getGlobalCapabilitiesObject($cce = null) {
  global $CAPABILITIESGLOBALOBJECT;
  if ($CAPABILITIESGLOBALOBJECT != null) {
    return $CAPABILITIESGLOBALOBJECT;
  }
  $CAPABILITIESGLOBALOBJECT = new Capabilities($cce);
  return $CAPABILITIESGLOBALOBJECT;
}

class Capabilities {

  // Internal caching of expanded data
  var $capabilityGroups;
  var $notCapabilityGroups;
  var $capabilities;
  var $cceClient;
  var $loginUser;
  var $_listAllowed;
  var $_gotAllCapabilityGroups;
  
  // Description: Constructor
  // param: a active cceclient. (optional, otherwise it will create a new connection)
  function Capabilities($cce = null) {
    global $loginName;
    global $sessionId;

    if ($cce != null)
      $this->cceClient =& $cce;  
    else {
      include_once("CceClient.php");
      $this->cceClient =& new CceClient();
      // FIXME check connect and authkey for failure
      $this->cceClient->connect();
      $this->cceClient->authkey($loginName, $sessionId);
      $this->myCce = 1;
    }

    $iam = $this->cceClient->whoami();
    $this->loginUser = $this->cceClient->get($iam);
    $this->capabilityGroups = array();
    $this->capabilities = array();
    $this->notCapabilityGroups = array();
    $this->_listAllowed = array();
    $this->_debug = false;

    // this makes us get all the capgroup stuff right away, making CCE not 
    // to worry about pulling capgroups out by indexed names
    $this->getAllCapabilityGroups();
    $this->getAllCapabilities();
    $this->listAllowed();
  }

    // description: checks to see if a user is granted the given capability.
    // param: the name of the CapabilityGroup or CCE-Level capability to check
    // param: the user to check for (default: current)
    // returns: true if the current user has this capability, false otherwise
    function getAllowed($capName, $oid = -1) 
    {
        // this is quicker besides systemAdministrator should be
        // able to view everything whether there is a capability group
        // or not
        if ( (-1 == $oid) && $this->loginUser['systemAdministrator'])
            return true;

        $caps = $this->listAllowed($oid);

        if (in_array($capName, $caps))
        {
            return 1;
        }
        else 
        {
            if (($group = &$this->getCapabilityGroup($capName)) != null) 
            {
                $retval = 1;
                $children = stringToArray($group["capabilities"]);
                
                if ($children == null || count($children) == 0)
                    return 0;

                foreach($children as $child) 
                {
                    if (!$this->getAllowed($child, $oid)) 
                        $retval = 0;
                } 
         
                return $retval;
            }
        }
        return 0;
    }

    // description: get a list of all the capabilities the given user has
    // param: the oid of the user to check (defaults: current)
    // returns: a list of all the capabilities the current user has
    function listAllowed($oid = -1) 
    {
        if ($oid == -1) 
        {
            $currentuser = 1;
            $oid = $this->loginUser["OID"];
        }
        
        if ($this->_listAllowed != null 
                && $this->_listAllowed[$oid] != null)
            return($this->_listAllowed[$oid]);

        $ret = array();
        // get all the capLevels and expand them.
        if ($currentuser) 
        {
            $uirights = stringToArray($this->loginUser["uiRights"]);
            if (in_array("systemAdministrator", $uirights)
                || $this->loginUser["systemAdministrator"]) 
            {
                // I am god, so I get ALL the capgroups :)
                $groups = $this->getAllCapabilityGroups();
                $caplevels = array();
                foreach($groups as $groupkey=>$groupval) 
                {
                    $caplevels[] = $groupkey;
                }
            } 
            else 
            { // get the capLevels from this user 
                $caplevels = stringToArray($this->loginUser["capLevels"]);
            }
        } 
        else 
        { // i'm asking about another user, so I say what I can about them.
            $user = $this->cceClient->get($oid);
            $caplevels = stringToArray($user["capLevels"]);
        } 
  
        foreach ($caplevels as $caplevel) 
        {
            $ret = array_merge((array)$ret, (array)$this->expandCaps($caplevel));
        }

        // remember to add the uirights in
        if ($currentuser) 
        {
            // self
            $ret = array_merge($ret, stringToArray($this->loginUser["uiRights"]));
        } 
        else 
        {
            $ret = array_merge($ret, stringToArray($user["uiRights"]));
        }

        // make unique and store
        $ret = array_unique($ret);
        if ($this->_listAllowed == null) 
            $this->_listAllowed = array();
   
        $this->_listAllowed[$oid] = $ret;

        return $ret;
    }
 
  // description: given a capabilitygroup name, this function will expand it
  //   and it's children into a list composed of both capabilitygroup names and
  //   and cce-level capabilities
  // param: capName - the name of the capability to be expanded.
  // returns: an expanded list of the capabilities entailed by $capName 
  function expandCaps($capName, $seen = array()) {
    // don't cycle around in a graph.
    if (in_array($capName, $seen)) 
      return array();
    // check to see if capName is a group, if so, expand..
    if (($group = &$this->getCapabilityGroup($capName))!=null) {
      if ($group["expanded"] != null) {
        return $group["expanded"];
      }
      $children = stringToArray($group["capabilities"]);
      $kids = array();
      array_push($seen, $capName);
      foreach($children as $child) {
        $kids = array_merge((array)$kids, (array)$this->expandCaps($child, $seen));
      }
      array_push($kids, $capName);
      $kids = array_unique($kids); 
      $group["expanded"] =& $kids;
      return $kids;
    // check as a cce-capability
    } else {
      $capList = $this->getAllCapabilities();
      if (in_array($capName, $capList)) {
        return array($capName);
      } 
      else if ($this->_debug)
      {
        $msg = "Capability name $capName could not be found in Capabilities"
             . "::getAllowed()";
        error_log($msg, 0);
      }
    }
  }

  // description:  gets the capabilityGroup and caches it
  function &getCapabilityGroup($capName, $data = null) {
    if ($data) {
      // we are given the data to cache.
      $this->capabilityGroups[$capName] = $data;
      return $this->capabilityGroups[$capName];
    }
    // check if we already checked and couldn't find this capname
    if ($this->notCapabilityGroups[$capName] || ($this->capabilityGroups[$capName]==null && $this->_gotAllCapabilityGroups)) {
      return null;
    }
    $cce = $this->cceClient;
    if ($this->capabilityGroups[$capName]!=null) {
      return $this->capabilityGroups[$capName];
    }
    if (($group = $this->cceClient->getObject("CapabilityGroup", array("name"=>$capName)))!=null) {
      $this->capabilityGroups[$capName] = $group;
      return $this->capabilityGroups[$capName];
    }
    $this->notCapabilityGroups[$capName] = 1;
    return null;
  }

  // description: returns an array of ALL the capabilityGroups
  function getAllCapabilityGroups() {
    if ($this->_gotAllCapabilityGroups)
      return $this->capabilityGroups;
    $cce =& $this->cceClient;
    $oids = $cce->findSorted("CapabilityGroup", "sort");
    foreach($oids as $oid) {
      $obj = $cce->get($oid);
      $this->getCapabilityGroup($obj["name"], $obj);
    }
    $this->_gotAllCapabilityGroups = 1;
    return $this->capabilityGroups;
  }
 
  // description: returns an array of all the declared cce-level capabilities
  function getAllCapabilities() {
    if (count($this->capabilities)) {
      return ($this->capabilities); 
    }
    $this->capabilities = $this->cceClient->names("Capabilities");
    return $this->capabilities;
  } 
 

} // Class Capabilities


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
