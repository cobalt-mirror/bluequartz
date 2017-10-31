<?php

/**
 * ConfigFile.php
 *
 * BlueOnyx ConfigFile for Codeigniter
 *
 * Decription: Generic Configuration File Reader Class
 *
 * - it's assumed you probably want sub-values interpolated right away
 *   (but you can re-interpolate again if you really want)
 * - Use of slow php regex code is minimized
 * - config file will still be re-read for multiple frames, etc,
 *   but it's fast enough with, say, ui.cfg (about 0.07 sec in total)
 *   so it's prolly not an issue.
 * - No values are stored, so it's up to you to save them
 *   since we'll just return by default if you try to read again 
 *
 * @package   ConfigFile
 * @author    Michael Stauber, Eric Braswell  ebraswell@sun.com, Kevin Chiu
 * @copyright Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
 * @copyright Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
 * @copyright Copyright (c) 2003 Sun Microsystems, Inc.  All rights reserved.
 * @copyright Copyright (c) 2000 Cobalt Networks.  All rights reserved.
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */
 
global $isConfigFileDefined;
if($isConfigFileDefined)
  return;
$isConfigFileDefined = true;

//
// private class variables
//
// actually globals otherwise in certain instances these aren't available
// to the class
global $_ConfigFile_IS_READ, $_ConfigFile_DELIMITER, $_ConfigFile_MAX_EXPAND;
global $_ConfigFile_MAX_LINE_LEN;
$_ConfigFile_IS_READ = array(); // a hash of config file names and their read state
$_ConfigFile_DELIMITER = "=";
$_ConfigFile_MAX_EXPAND = 10;
$_ConfigFile_MAX_LINE_LEN = 10240;

class ConfigFile {

    var $config;    // hash of config info

    var $depth;
    
    // Read all the config info from a file
    // configFile - the full path to the config file
    // interpolate - (boolean) if true, interpolate embedded sub-values 
    // once - (boolean) if true, don't ever re-read file
    //      if you need to re-read the file later, set once to false
    function readConfigFile($configFile, $interpolate=true, $once=true) {
        global $_ConfigFile_IS_READ;
        global $_ConfigFile_DELIMITER;
        global $_ConfigFile_MAX_LINE_LEN;

        //if($once and $_ConfigFile_IS_READ[$configFile]) { return true; } // already read it

        if(!($handle = fopen($configFile, "r"))) {
            return false;
            error_log("ConfigFile.readConfigFile: Could not open config file $configFile");

        }
        $lncount = '0';
        while(!feof($handle)) {
            $line = fgets($handle, $_ConfigFile_MAX_LINE_LEN);

                  // skip empty lines
            if(empty($line) or $line == "\n") { continue; }

                  // skip comments
                  // could use funkier regex to eliminate 2nd preg_match
            if(preg_match("/^\s*\#/", $line) or preg_match("/^\s/", $line)) { continue; }
                
            $matches = explode ($_ConfigFile_DELIMITER, $line);
                  // save result
            $this->config[trim($matches[0])] =  $value = str_replace ("\n", "", $matches[1]); 
            
            $lncount++;
        }
        fclose($handle);

            // look for sub-values, which we do here because
            // of course we need all the values already
        if($interpolate) {
            $this->interpolateAll();
        }
        
        $_ConfigFile_IS_READ[$configFile] = true; // save the state of this config file
        return true;
        
      }


        // Set an item in the config hash (not the config file)
        // To temporarily override or add a setting
    function setConfig($item, $value) {
        $this->config[$item] = $value;
    }
    
    
        // Get a config value for $item.
        // item - If item is blank, a hash containing all key/value config pairs
        // is returned.
        // interpolate - if true, item value will have any sub-values replaced
    function getConfig($item="", $interpolate=false) {
    
        if(empty($item)) {  // return all items
        
            if($interpolate) {
                $this->interpolateAll();
            } 
            
            return ($this->config);     
            
        } else {        // return single item
            
            if($interpolate) {
                return ($this->interpolateValue($item));
            } else {        
                return($this->config[$item]);
            }
        }
    }
    
    
        // interpolate values for all items in $this->config
    function interpolateAll() {
        foreach($this->config as $key=>$val) {
            $this->depth = 0;
            $this->config[$key] = $this->interpolateValue($key);
                    
        }
    }
    
    
        // interpolate the value for a single item up to _ConfigFile_MAX_EXPAND items deep
    function interpolateValue($item) {
        global $_ConfigFile_MAX_EXPAND;

        $value = $this->config[$item];

        while(preg_match("/\[\[([^\]]+)\]\]/", $value, $matches) and ($this->depth <= $_ConfigFile_MAX_EXPAND)) {

            $newKey = $matches[1];
            $newValue = $this->interpolateValue($newKey);

            $value = str_replace ("[[$newKey]]", $newValue, $value);  

            $this->depth++;
        }
        
        if(empty($value)) {
            $value = $this->config[$item];
        }
        return($value);

    
    }


        // print out all the config values
    function printConfig() {
        echo "<HR><PRE>";
        print_r($this->config);
        echo "</PRE>";
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