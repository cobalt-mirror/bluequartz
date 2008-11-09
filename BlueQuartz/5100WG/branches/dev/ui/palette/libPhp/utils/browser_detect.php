<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: browser_detect.php 201 2003-07-18 19:11:07Z will $

unset ($BROWSER_AGENT);
unset ($BROWSER_VER);
unset ($BROWSER_PLATFORM);

function browser_get_agent () {
    global $BROWSER_AGENT;
    return $BROWSER_AGENT;
}

function browser_get_version() {
    global $BROWSER_VER;
    return $BROWSER_VER;
}

function browser_get_major_version() {
    global $BROWSER_MAJOR_VER;
    return $BROWSER_MAJOR_VER;
}

function browser_get_platform() {
    global $BROWSER_PLATFORM;
    return $BROWSER_PLATFORM;
}

function browser_is_mac() {
    if (browser_get_platform()=='Mac') {
	return true;
    } else {
	return false;
    }
}

function browser_is_windows() {
    if (browser_get_platform()=='Win') {
	return true;
    } else {
	return false;
    }
}

function browser_is_ie() {
    if (browser_get_agent()=='IE') {
	return true;
    } else {
	return false;
    }
}

function browser_is_netscape() {
    if (browser_get_agent()=='MOZILLA') {
	return true;
    } else {
	return false;
    }
}


/*
    Determine browser and version
*/


if (ereg( 'MSIE ([0-9]).([0-9]{1,2})',$HTTP_USER_AGENT,$log_version)) {
    $BROWSER_VER=$log_version[1].".".$log_version[2];
    $BROWSER_MAJOR_VER=$log_version[1];
    $BROWSER_AGENT='IE';
} elseif (ereg( 'Opera ([0-9]).([0-9]{1,2})',$HTTP_USER_AGENT,$log_version)) {
    $BROWSER_VER=$log_version[1].".".$log_version[2];
    $BROWSER_MAJOR_VER=$log_version[1];
    $BROWSER_AGENT='OPERA';
} elseif (ereg( 'Mozilla/([0-9]).([0-9]{1,2})',$HTTP_USER_AGENT,$log_version)) {
    $BROWSER_VER=$log_version[1].".".$log_version[2];
    $BROWSER_MAJOR_VER=$log_version[1];
    $BROWSER_AGENT='MOZILLA';
} else {
    $BROWSER_VER=0;
    $BROWSER_MAJOR_VER=0;
    $BROWSER_AGENT='OTHER';
}

/*
    Determine platform
*/

if (strstr($HTTP_USER_AGENT,'Win')) {
    $BROWSER_PLATFORM='Win';
} else if (strstr($HTTP_USER_AGENT,'Mac')) {
    $BROWSER_PLATFORM='Mac';
} else if (strstr($HTTP_USER_AGENT,'Linux')) {
    $BROWSER_PLATFORM='Linux';
} else if (strstr($HTTP_USER_AGENT,'Unix')) {
    $BROWSER_PLATFORM='Unix';
} else {
    $BROWSER_PLATFORM='Other';
}

/*
     Debug info
*/
function browser_get_debug_info()
{
	global $HTTP_USER_AGENT;

	echo "<PRE>";
	echo "\n\nAgent: $HTTP_USER_AGENT";
	echo "\nIE: ".browser_is_ie();
	echo "\nNetscape: ".browser_is_netscape();
	echo "\nMac: ".browser_is_mac();
	echo "\nWindows: ".browser_is_windows();
	echo "\nPlatform: ".browser_get_platform();
	echo "\nVersion: ".browser_get_version();
	echo "\nMajor Version: ".browser_get_major_version();
	echo "\nAgent: ".browser_get_agent();
	echo "</PRE>";
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

