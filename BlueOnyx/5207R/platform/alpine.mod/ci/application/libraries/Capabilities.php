<?php

/**
 * Capabilities.php
 *
 * BlueOnyx Capabilities for Codeigniter
 *
 * Description: A class that facilitates working with Capabilities
 * and capability groups
 *
 * @package   Capabilities
 * @author    Michael Stauber
 * @copyright Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
 * @copyright Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
 * @copyright Copyright (c) 2003 Sun Microsystems, Inc.  All rights reserved.
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

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
  public $loginUser;
  var $_listAllowed;
  var $_gotAllCapabilityGroups;
  
  // Description: Constructor
  // param: a active cceclient. (optional, otherwise it will create a new connection)
  function Capabilities($cce = NULL, $loginName = NULL, $sessionId = NULL) {

    if ($cce != NULL) {
      $this->cceClient =& $cce;
    }
    else {
      $this->cceClient = new CceClient();
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

    function getAllowed($capName, $oid = -1) {
        // this is quicker besides systemAdministrator should be
        // able to view everything whether there is a capability group
        // or not

        if ($oid == -1) 
        {
            $currentuser = 1;
            $oid = $this->loginUser["OID"];
        }

        if ($this->loginUser['systemAdministrator']) {
            // Fast 'yes' to all rights, because we sure *are* system administrator:
            return 1;
        }
        if (!$this->loginUser['systemAdministrator'] && $capName == 'adminUser') { 
          // Fast 'no' to the question for 'adminUser', because we simply aren't.
          // Do not get get confused here. Resellers are 'adminUser', but we do
          // NOT treat them as such unless they also have the 'systemAdministrator'
          // flag. Without that flag, we do not rate them as 'adminUser':
          return 0;
        }

        $caps = $this->listAllowed($oid);

        if (in_array($capName, $caps)) {
            return 1;
        }
        else {
            return 0;
        }
        return 0;
    }

    // description: checks to see if a user is a reseller (createdUser) of a 
    // given Vsite group.
    // param: the group of the Vsite to check
    // param: the user to check for (default: current)
    // returns: true if the current user has this capability, false otherwise

    function getReseller($group, $oid = -1) {
        if ($oid == -1) {
            $currentuser = 1;
            $oid = $this->loginUser["OID"];
        }
        // Find out if the Group exists:
        $site = $this->cceClient->getObject('Vsite', array('name' => $group));
        if (!isset($site['fqdn'])) {
          // Group doesn't exist. So we fail right here:
          return 0;
        }
        if ($this->loginUser['systemAdministrator']) {
            // Fast 'yes' to all rights, because we are system administrator:
            return 1;
        }
        // Check Vsite's 'createdUser':
        if ($site['createdUser'] == $this->loginUser['name']) {
          // This user is listed as 'createdUser', so we return yes:
          return 1;
        }
        return 0;
    }

    // description: checks to see if a user is a siteAdmin of a given Vsite group.
    // param: the group of the Vsite to check
    // param: the user to check for (default: current)
    // returns: true if the current user has this capability, false otherwise

    function getSiteAdmin($group, $oid = -1) {
        if ($oid == -1) {
            $currentuser = 1;
            $oid = $this->loginUser["OID"];
        }
        // Find out if the Group exists:
        $site = $this->cceClient->getObject('Vsite', array('name' => $group));
        if (!isset($site['fqdn'])) {
          // Group doesn't exist. So we fail right here:
          return 0;
        }
        if ($this->loginUser['systemAdministrator']) {
            // Fast 'yes' to all rights, because we are system administrator:
            return 1;
        }
        // Check if this user belongs to the given group:
        if ($this->loginUser['site'] == $group) {
          // This user is listed as 'createdUser', so we return yes:
          return 1;
        }
        else {
          // He might be a siteAdmin elsewhere, but sure not here.
          return 0;
        }
        // Check if this user has the capability 'siteAdmin':
        $caps = $this->listAllowed($oid);
        if (in_array('siteAdmin', $caps)) {
          return 1;
        }
        return 0;
    }

    // description: checks to see if a user is in a certain group or a reseller of it.
    // param: the group of the User/Vsite to check
    // param: the user to check for (default: current)
    // returns: true if the current user has this capability, false otherwise

    function getGroup($group, $oid = -1) {
        if ($oid == -1) {
            $currentuser = 1;
            $oid = $this->loginUser["OID"];
        }
        // Find out if the Group exists:
        $site = $this->cceClient->getObject('Vsite', array('name' => $group));
        if (!isset($site['fqdn'])) {
          // Group doesn't exist. So we fail right here:
          return 0;
        }
        if ($this->loginUser['systemAdministrator']) {
            // Fast 'yes' to all rights, because we are system administrator:
            return 1;
        }
        // Check if this user belongs to the given group OR is Reseller of this group:
        if (($this->loginUser['site'] == $group) || ($this->getReseller($group))) {
          // This user is listed as 'createdUser', so we return yes:
          return 1;
        }
        return 0;
    }

    // description: checks to see if a user is systemAdministrator, siteAdmin 
    // or a reseller of a group and if the group exists.
    // param: the group of the User/Vsite to check
    // param: the user to check for (default: current)
    // returns: true if the current user has this capability, false otherwise

    function getGroupAdmin($group, $oid = -1) {
        if ($oid == -1) {
            $currentuser = 1;
            $oid = $this->loginUser["OID"];
        }
        // Find out if the Group exists:
        $site = $this->cceClient->getObject('Vsite', array('name' => $group));
        if (!isset($site['fqdn'])) {
          // Group doesn't exist. So we fail right here:
          return 0;
        }
        if ($this->loginUser['systemAdministrator']) {
            // Fast 'yes' to all rights, because we are system administrator:
            return 1;
        }
        // Check if this user is Reseller of this group:
        if (($this->loginUser['site'] == "") && ($this->getReseller($group) == "1")) {
          // This is a reseller (has no group) and can manage the specified group as Reseller.
          return 1;
        }
        // Check if this user belongs to this group and is siteAdmin of this group:
        if (($this->loginUser['site'] == $group) && ($this->getSiteAdmin($group))) {
          // This user belongs to this group and is siteAdmin OR Reseller.
          return 1;
        }
        return 0;
    }

  // description:  gets the capabilityGroup and caches it
  function &getCapabilityGroup($capName, $data = null) {
    if ($data) {
      // we are given the data to cache.
      $this->capabilityGroups[$capName] = $data;
      return $this->capabilityGroups[$capName];
    }
    // check if we already checked and couldn't find this capname
    if (isset($this->capabilityGroups[$capName])) {
      if (isset($this->notCapabilityGroups[$capName]) || ($this->capabilityGroups[$capName]==null && $this->_gotAllCapabilityGroups)) {
      return null;
      }
    }
    $cce = $this->cceClient;
    if (isset($this->capabilityGroups[$capName])) {
      if ($this->capabilityGroups[$capName]!=null) {
	       return $this->capabilityGroups[$capName];
      }
    }
    if (($group = $this->cceClient->getObject("CapabilityGroup", array("name"=>$capName)))!=null) {
      $this->capabilityGroups[$capName] = $group;
      return $this->capabilityGroups[$capName];
    }
    $this->notCapabilityGroups[$capName] = 1;
    $null = "NULL";
    return $null;
  }

  // description: returns an array of ALL the capabilityGroups
  function getAllCapabilityGroups() {
    if ($this->_gotAllCapabilityGroups)
      return $this->capabilityGroups;
    $cce =& $this->cceClient;
    //$oids = $cce->findSorted("CapabilityGroup", "sort");
    $oids = $cce->find("CapabilityGroup");

    foreach($oids as $oid) {
      $obj = $cce->get($oid);
      $this->getCapabilityGroup($obj['name'], $obj);
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

    // description: get a list of all the capabilities the given user has
    // param: the oid of the user to check (defaults: current)
    // returns: a list of all the capabilities the current user has
    function listAllowed($oid = -1) {
        if ($oid == -1) {
            $currentuser = 1;
            $oid = $this->loginUser["OID"];
        }
        if (!isset($this->_listAllowed[$oid])) {
            $this->_listAllowed[$oid] = TRUE;
        }

        if (is_array($this->_listAllowed[$oid])) {
            return $this->_listAllowed[$oid];
        }

        $ret = array();

        // get the capLevels from this user 
        if (isset($currentuser)) {
          $caplevels = stringToArray($this->loginUser["capLevels"]);
        }
        else {
            // i'm asking about another user, so I say what I can about them.
            $user = $this->cceClient->get($oid);
            $caplevels = stringToArray($user["capLevels"]);          
        }

        $returnCap = array();

        foreach ($caplevels as $key => $capName) {
          foreach ($this->getAllCapabilityGroups() as $capA => $capContend) {
            if ($capContend['CLASS'] == "CapabilityGroup") {
              if ($capContend['name'] == $capName) {
                if (!in_array($capName, $returnCap)) {
                  $returnCap[] = $capName;
                }
                $tmpreturnCap = scalar_to_array($capContend['capabilities']);
                foreach ($tmpreturnCap as $key => $value) {
                  if (!in_array($value, $returnCap)) {
                    $returnCap[] = $value;
                  }
                }
              }
              else {
                if (!in_array($capName, $returnCap)) {
                  $returnCap[] = $capName;
                }
              }
            }
          }
        }

        $userShell = $this->cceClient->get($oid, 'Shell');
        if ($userShell['enabled'] == "1") {
          $returnCap[] = 'shellAccessEnabled';
        }

        // Remove blank entries, make unique and store:
        $returnCap = array_filter(array_unique($returnCap));
        if ($this->_listAllowed[$oid] == TRUE) {
            $this->_listAllowed[$oid] = $returnCap;
        }
        return $returnCap;
    }

  // description: given a capabilitygroup name, this function will expand it
  //   and it's children into a list composed of both capabilitygroup names and
  //   and cce-level capabilities
  // param: capName - the name of the capability to be expanded.
  // returns: an expanded list of the capabilities entailed by $capName 
  function expandCaps($capName, $seen = array()) {
    // don't cycle around in a graph.
    if (in_array($capName, $seen)) {
      return array();
    }
    // check to see if capName is a group, if so, expand..
    if (($group = &$this->getCapabilityGroup($capName))!=null) {

      if (isset($group["expanded"])) {
        if ($group["expanded"] != null) {
          return $group["expanded"];
        }
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
    }
    else {
      $capList = $this->getAllCapabilities();
      if (in_array($capName, $capList)) {
        return array($capName);
      } 
      elseif ($this->_debug) {
        $msg = "Capability name $capName could not be found in Capabilities" . "::getAllowed()";
        error_log($msg, 0);
      }
    }
  }


} // Class Capabilities

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