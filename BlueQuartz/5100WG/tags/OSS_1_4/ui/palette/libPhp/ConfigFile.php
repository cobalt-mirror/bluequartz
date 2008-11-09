<?php
/* Generic Configuration File Reader Class
 *
 * Portions from System.php
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
 * Author: Eric Braswell  ebraswell@sun.com, Kevin Chiu
 * Copyright 2001, Sun Microsystems.  All rights reserved.
 * $Id: ConfigFile.php 3 2003-07-17 15:19:15Z will $
 *
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

	var $config;	// hash of config info

	var $depth;
	
	// Read all the config info from a file
	// configFile - the full path to the config file
	// interpolate - (boolean) if true, interpolate embedded sub-values 
	// once - (boolean) if true, don't ever re-read file
	//		if you need to re-read the file later, set once to false
	function readConfigFile($configFile, $interpolate=true, $once=true) {
 		global $_ConfigFile_IS_READ;
 		global $_ConfigFile_DELIMITER;
 		global $_ConfigFile_MAX_LINE_LEN;

 		if($once and $_ConfigFile_IS_READ[$configFile]) { return true; } // already read it

	    if(!($handle = fopen($configFile, "r"))) {
	    	return false;
			error_log("ConfigFile.readConfigFile: Could not open config file $configFile");

		}
	
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
			
		} else {		// return single item
			
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

