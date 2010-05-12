<?php
// $Id: AutoFeatures.php 259 2004-01-03 06:28:40Z shibuya $
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// AutoFeatures.php
// This class allows PHP scripts to use automatic feature detection with the
// Sausalito architecture.  This allows most of the functionality to be
// hidden, and it requires minimal changes to PHP scripts to use automatic
// feature detection.

// hint: set tabstop=4 in vi

if (defined("AUTOFEATURES_PHP"))
{
    return 1;
}
define("AUTOFEATURES_PHP", 1);

include_once('System.php');
include_once('Error.php');

class AutoFeatures
{
    var $helper;
    var $extension_dir;
    var $debug;
    var $display_order;

    /*
        Arguments:
        $ServerScriptHelper is a ServerScriptHelper object.

        Returns a new AutoFeatures object.
    */
    function AutoFeatures(&$ServerScriptHelper)
    {
        // assign default settings
        // fail if a value wasn't give to these
        if (!$ServerScriptHelper)
        {
            error_log("AutoFeatures.php:  null argument passed to new AutoFeatures()");
            return;
        }

        $this->helper = &$ServerScriptHelper;

        $system = new System();
        $this->extension_dir = $system->getConfig('extensionDir');
        $this->debug = false;
    }


    /*
        THIS IS ONLY HERE FOR FUTURE EXPANSION.  IT SHOULD NOT CURRENTLY BE USED.
        $autoFeatures->setDisplayWidgets($HtmlComponentFactory, $PagedBlock, $defaultPageId);
        Description:
        Sets the information needed by the automatically detected features to display
        themselves.

        Arguments:
        $HtmlComponentFactory is an HtmlComponentFactory object.
        
        $PagedBlock is a PagedBlock object.

        $defaultPageId is the page id to give the default tab if the services will
        require multiple tabs for display.  All simple services (those needing only
        an enabled/disabled checkbox) will be added to the tab specified by
        $defaultPageId.

        Returns nothing.
    function setDisplayWidgets(&$HtmlComponentFactory, &$PagedBlock, $defaultPageId)
    {
        // fill in
    }
    */

    /*
        $autoFeatues->display($container, $domain, $parameters);
        Description:
        Add all automatically detected features in the current domain to the container.

        Arguments:
        $container is the UIFC container that the features found should add their
        form fields too (e.g. PagedBlock or ScrollList, although more than likely PagedBlock).

        $domain is a string of the form "domain.Class" used to load the correct extensions
        from the ui/extensions/ directory (e.g. defaults.Vsite, create.User, modify.System):

            "defaults.<Class>" means that default values in the ClassServices object specified
            by $classServicesOID are going to be read or modified.
            
            "create.<Class>" means that a new object is being created, so values should be read
            from the object specified by $classServicesOID, and values will be set for
            the object specified by $modifyObjectOID.

            "modify.<Class>" means that values should be read from and set for the object
            specified by $modifyObjectOID.

        $parameters is a hash of key/value pairs to pass down to the display functions
        of the UI extensions.  Below are the key/value pairs that should be in $parameters
        for features using CCE.

            $parameters["CCE_SERVICES_OID"]  is the CCE object id of the ClassServices 
            object to use as the registry for feature detection.

            $parameters["CCE_OID"] is the CCE object id of the object from/for which to 
            get/set values for a feature.  It only needs to be specified when handling 
            object creation or when displaying or handling the modification of a 
            pre-existing object.

        
        Returns 0 on failure and 1 on success.
    */
    function display(&$container, $domain, $parameters)
    {
        if(!$this->_include_domain($domain))
        {
            if ($this->debug)
                error_log(__FILE__ . '.' . __LINE__ . ": the specified domain, $domain, does not exist!");

            return 0;
        }

        $cce = $this->helper->getCceClient();
        if (isset($this->display_order['alpha']))
        {   // put these ones at the end
            $temp = $this->display_order['alpha'];
            unset($this->display_order['alpha']);
        }
        
        ksort($this->display_order, SORT_NUMERIC);
        reset($this->display_order);

        // add on the unordered ones if necessary
        if (isset($temp))
            $this->display_order[] = $temp;

        foreach ($this->display_order as $order => $classes)
        {
            if ($this->debug)
            {
                error_log("order $order, classes " . implode(', ', $classes));
            }

            foreach($classes as $namespace)
            {
                $class = $namespace . "Extension";
                if ($this->debug)
                {
                    error_log(__FILE__ . '.' . __LINE__ . 
                        ": current extension is $class");
                }
    
                if (!class_exists($class))
                {
                    // this is mostly for debugging, the class should always exist
                    // if not someone needs to write a UIExtension
                    if ($this->debug)
                    {
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
    function handle($domain, $parameters)
    {
        if (!$this->_include_domain($domain))
        {
            if ($this->debug)
            {
                error_log("In AutoFeatures->handle, the specified domain, $domain, does not exist.");
            }
            
            return array();
        }

        $errors = array();

        $cce = $this->helper->getCceClient();
        if ($this->debug)
        {
            foreach ($parameters as $key => $value)
            {
                error_log(__FILE__ . '.' . __LINE__ . ": $key => $value");
            }
        }

        $features = $cce->names($parameters["CCE_SERVICES_OID"]);
        foreach ($features as $namespace)
        {
            if ($this->debug)
            {
                error_log(__FILE__ . '.' . __LINE__ .
                    ": current namespace is $namespace.");
            }

            $class = $namespace . 'Extension';
            if (!class_exists($class))
            {
                // this is mostly for debugging, the class should always exist
                // if not someone needs to write a UIExtension
                if ($this->debug)
                {
                    error_log(__FILE__ . '.' . __LINE__ . 
                        ":  missing UI extension, $class!");
                }
                continue;
            }

            $feature = new $class();
            $feature->handle($this->helper, $errors, $parameters);
        }
        
        return $errors;
    }


    // private functions below
    function _include_domain($domain)
    {
        $dir_name = $this->extension_dir . '/' . $domain;

        // make sure the specified domain exists
        if(!is_dir($dir_name))
        {
            if ($this->debug)
            {
                error_log(__FILE__ . '.' . __LINE__ . 
                    ":  The domain given, $domain, does not exist at $dir_name.");
            }
            return 0;
        }

        if ($this->debug)
            error_log("searching for extensions");

        $this->display_order = array();
        if($dir = @opendir($dir_name))
        {
            while (($file = readdir($dir)) !== false)
            {
                if($file != "." && $file != "..")
                {
                    if ($this->debug)
                        error_log("examining $file for inclusion");

                    include_once($dir_name . '/' . $file);
                    if (preg_match("/^([0-9]+)_([^\.]+)\.php$/", $file, $matches))
                    {
                        $this->display_order[$matches[1]][] = $matches[2];
                    }
                    else if (preg_match("/^([^\.]+)\.php$/", $file, $matches))
                    {
                        $this->display_order['alpha'][] = $matches[1];
                    }
                    else
                    {
                        error_log("$file does not follow naming restrictions!");
                    }
                }
            }
            closedir($dir);
        }
        else
        {
            return 0;
        }

        return 1;
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
