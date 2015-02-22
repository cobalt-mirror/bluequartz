<?php
// $Id: AutoFeatures.php
//
// AutoFeatures.php
// This class allows PHP scripts to use automatic feature detection with the
// Sausalito architecture.  This allows most of the functionality to be
// hidden, and it requires minimal changes to PHP scripts to use automatic
// feature detection.

if (defined("AUTOFEATURES_PHP")) {
    return 1;
}
define("AUTOFEATURES_PHP", 1);

include_once('System.php');
include_once('Error.php');

class AutoFeatures {
    var $helper;
    var $extension_dir;
    var $debug;
    var $display_order;
    public $attributes;

    /*
        Arguments:
        $ServerScriptHelper is a ServerScriptHelper object.

        Returns a new AutoFeatures object.
    */
    function AutoFeatures(&$ServerScriptHelper, &$attributes="") {
        // assign default settings
        // fail if a value wasn't give to these
        if (!$ServerScriptHelper) {
            error_log("AutoFeatures.php:  null argument passed to new AutoFeatures()");
            return;
        }

        $this->helper = &$ServerScriptHelper;
        if (is_array($attributes)) {
            $this->attributes = &$attributes;
        }
        else {
            $this->attributes = array();
        }

        $system = new System();
        $this->extension_dir = $system->getConfig('extensionDir');
        $this->debug = false;

    }

    /*
        Takes the domain (example: 'modifyWeb.Vsite') as an option and returns a list of available Extensions.
    */

    function ListFeatures($domain) {
        $map = "";
        $dir_name = $this->extension_dir . '/' . $domain . '/';
        // make sure the specified domain exists
        if (!is_dir($dir_name)) {
            return NULL;
        }
        else {
            $map = directory_map($dir_name, FALSE, FALSE);
            sort($map);
            $features = array();

            foreach ($map as $key => $value) {
                $file = $dir_name . $value;
                $file_contends = read_file($file);
                $pattern = '/class (.*)Extension extends UIExtension/';
                preg_match_all($pattern, $file_contends, $matches);
                if (isset($matches[1][0])) {
                    $features[] = $matches[1][0];
                }
            }
        }
        return $features;
    }

    function display(&$container, $domain, $parameters) {

        if(!$this->_include_domain($domain)) {
            if ($this->debug)
                error_log(__FILE__ . '.' . __LINE__ . ": the specified domain, $domain, does not exist!");

            return 0;
        }

        $cce = $this->helper->getCceClient();
        if (isset($this->display_order['alpha'])) {   
            // put these ones at the end
            $temp = $this->display_order['alpha'];
            unset($this->display_order['alpha']);
        }
        
        ksort($this->display_order, SORT_NUMERIC);
        reset($this->display_order);

        // add on the unordered ones if necessary
        if (isset($temp)) {
            $this->display_order[] = $temp;
        }

        foreach ($this->display_order as $order => $classes) {
            if ($this->debug) {
                error_log("order $order, classes " . implode(', ', $classes));
            }

            foreach($classes as $namespace) {
                $class = $namespace . "Extension";
                if ($this->debug) {
                    error_log(__FILE__ . '.' . __LINE__ . 
                        ": current extension is $class");
                }
    
                if (!class_exists($class)) {
                    // this is mostly for debugging, the class should always exist
                    // if not someone needs to write a UIExtension
                    if ($this->debug) {
                        error_log(__FILE__ . '.' . __LINE__ . 
                            ":  missing UI extension, $class!");
                    }
                    continue;
                }
    
                $feature = new $class();

                $feature->display($this->helper, $container, $parameters);
            }
        }

        return 1;
    }

    /*
        $errors = $autoFeatures->handle($domain, $parameters);
    
        Description:
        Handle saving all settings for automatically detected features.

        Arguments:
        $domain is the same as that passed to the AutoFeatures display function.

        $parameters is the same kind of hash as that passed to the display
        function.

        Returns an array of all errors that occurred while saving settings for
        auto-detected features.
    */
    function handle($domain, $parameters) {
        if (!$this->_include_domain($domain)) {
            if ($this->debug) {
                error_log("In AutoFeatures->handle, the specified domain, $domain, does not exist.");
            }
            
            return array();
        }

        $errors = array();

        $cce = $this->helper->getCceClient();
        if ($this->debug) {
            foreach ($parameters as $key => $value) {
                error_log(__FILE__ . '.' . __LINE__ . ": $key => $value");
            }
        }

        $features = $cce->names($parameters["CCE_SERVICES_OID"]);
        foreach ($features as $namespace) {
            if ($this->debug) {
                error_log(__FILE__ . '.' . __LINE__ .
                    ": current namespace is $namespace.");
            }

            $class = $namespace . 'Extension';
            if (!class_exists($class)) {
                // this is mostly for debugging, the class should always exist
                // if not someone needs to write a UIExtension
                if ($this->debug) {
                    error_log(__FILE__ . '.' . __LINE__ . 
                        ":  missing UI extension, $class!");
                }
                continue;
            }

            $feature = new $class();
            $feature->handle($this->helper, $errors, $parameters, $this->attributes);
        }

        return $errors;
    }


    // private functions below
    function _include_domain($domain) {
        $dir_name = $this->extension_dir . '/' . $domain;

        // make sure the specified domain exists
        if (!is_dir($dir_name)) {
            if ($this->debug) {
                error_log(__FILE__ . '.' . __LINE__ . 
                    ":  The domain given, $domain, does not exist at $dir_name.");
            }
            return 0;
        }

        if ($this->debug) {
            error_log("searching for extensions");
        }

        $this->display_order = array();
        if ($dir = @opendir($dir_name)) {
            while (($file = readdir($dir)) !== false) {
                if($file != "." && $file != "..") {
                    if ($this->debug) {
                        error_log("examining $file for inclusion");
                    }

                    include_once($dir_name . '/' . $file);
                    if (preg_match("/^([0-9]+)_([^\.]+)\.php$/", $file, $matches)) {
                        $this->display_order[$matches[1]][] = $matches[2];
                    }
                    else if (preg_match("/^([^\.]+)\.php$/", $file, $matches)) {
                        $this->display_order['alpha'][] = $matches[1];
                    }
                    else {
                        error_log("$file does not follow naming restrictions!");
                    }
                }
            }
            closedir($dir);
        }
        else {
            return 0;
        }

        return 1;
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