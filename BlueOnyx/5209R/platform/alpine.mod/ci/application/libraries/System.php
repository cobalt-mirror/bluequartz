<?php

/**
 * System.php
 *
 * BlueOnyx System for Codeigniter
 *
 * @package   System
 * @author    Michael Stauber, Kevin K.M. Chiu
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */


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

            if(!$cfg->readConfigFile($_System_configFile)) {
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