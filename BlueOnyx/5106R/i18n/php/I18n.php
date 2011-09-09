<?php
/*
 * Copyright 2000-2002 Sun Microsystems, Inc.  All rights reserved.
 * $Id: I18n.php 1136 2008-06-05 01:48:04Z mstauber $
 */
global $isI18nDefined;
if($isI18nDefined)
  return;
$isI18nDefined = true;

// used by encodeString
include_once('EncodingConv.php');

//
// private class variables
//
// set to true to print debug messages
$GLOBALS["_I18n_isDebug"] = false;
// set to true to put the class in stub mode
$GLOBALS["_I18n_isStub"] = false;

class I18n {
  //
  // private variables
  //

  var $handle;

  //
  // public methods
  //

	// description: constructor
	// param: domain: a string that describes the domain
	// param: langs: an optional string that contains a comma separated list
	//     of preferred locale. Most important locales appears first.
	//     e.g. "en_US, en_AU, zh, de_DE"
	function I18n($domain = "", $langs = "") 
	{
		if($GLOBALS["_I18n_isDebug"]) print("I18n($domain, $langs)\n");
		if($GLOBALS["_I18n_isStub"]) return;

		if($langs == "" && getenv("LANG") == "") {
			$langs = "en";
		}

		// If the detected language is not 'de_DE', 'da_DK' or 'ja_JP', we fall back to 'en_US'.
		// This was added particularly for 5107R/5108R as it would default to German otherwise:
		$my_lang = explode(',', $langs);
		if ((($my_lang[0] != "de-DE") && ($my_lang[0] != "de_DE")) && (($my_lang[0] != "da-DK") && ($my_lang[0] != "da_DK")) && (($my_lang[0] != "ja-JP") && ($my_lang[0] != "ja_JP"))) {
			$langs = "en";
		}

		$this->handle = i18n_new($domain, $langs);
	}

  // description: get a localized string
  // param: tag: the tag of the string. Identical to the msgid string in the
  //     .po file
  // param: domain: the domain of the string in string. Identical to the
  //     .po/.mo file name without the extension. Optional. If not supplied,
  //     the one supplied to the I18n constructor is used
  // param: vars: a hash of variable key strings to value strings. Optional.
  //     If the hash contains "name" => "Kevin" and the string in question is
  //     "My name is [[VAR.name]]", then "My name is Kevin" is returned
  // returns: a localized string if it is found or the tag otherwise
  function get($tag, $domain = "", $vars = array()) {
    if($GLOBALS["_I18n_isDebug"]) print("get($tag, $domain, $vars)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n get stub";

    return i18n_get($this->handle, $tag, $domain, $vars);
  }

  // description: get a localized string and encode it into Javascript friendly
  //     encoding
  // param: domain: the domain of the string in string. Identical to the
  //     .po/.mo file name without the extension. Optional. If not supplied,
  //     the one supplied to the I18n constructor is used
  // param: vars: a hash of variable key strings to value strings. Optional.
  //     If the hash contains "name" => "Kevin" and the string in question is
  //     "My name is [[VAR.name]]", then "My name is Kevin" is returned
  // returns: a Javacsript friendly localized string if it is found or the tag
  //     otherwise
  function getJs($tag, $domain = "", $vars = array()) {
    if($GLOBALS["_I18n_isDebug"]) print("getJs($tag, $domain, $vars)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n getJs stub";

    return i18n_get_js($this->handle, $tag, $domain, $vars);
  }

  // description: get a localized string and encode it into HTML friendly
  //     encoding
  // param: tag: the tag of the string. Identical to the msgid string in the
  //     .po file
  // param: domain: the domain of the string in string. Identical to the
  //     .po/.mo file name without the extension. Optional. If not supplied,
  //     the one supplied to the I18n constructor is used
  // param: vars: a hash of variable key strings to value strings. Optional.
  //     If the hash contains "name" => "Kevin" and the string in question is
  //     "My name is [[VAR.name]]", then "My name is Kevin" is returned
  // returns: a HTML friendly localized string if it is found or the tag
  //     otherwise
  function getHtml($tag, $domain = "", $vars = array()) {
    if($GLOBALS["_I18n_isDebug"]) print("getHtml($tag, $domain, $vars)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n getHtml stub";

    return i18n_get_html($this->handle, $tag, $domain, $vars);
  }

  // description: get a localized string out of a fully qualified tag
  // param: magicstr: the fully qualified tag of format
  //     "[[" . <domain> . "." . <tag> (. "," . <key> . "=" . <value>)* . "]]"
  // param: vars: a hash of variable key strings to value strings. Optional
  // returns: a localized string or magicstr if interpolation failed
  function interpolate($magicstr, $vars = array()) {
    if($GLOBALS["_I18n_isDebug"]) print("interpolate($magicstr, $vars)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n interpolate stub";

    return i18n_interpolate($this->handle, $magicstr, $vars);
  }

  // description: get a localized string out of a fully qualified tag and
  //     encode it into Javascript friendly encoding
  // param: magicstr: the fully qualified tag of format
  //     "[[" . <domain> . "." . <tag> (. "," . <key> . "=" . <value>)* . "]]"
  // param: vars: a hash of variable key strings to value strings. Optional
  // returns: a Javacsript friendly localized string or magicstr if
  //     interpolation failed
  function interpolateJs($magicstr, $vars = array()) {
    if($GLOBALS["_I18n_isDebug"]) print("interpolateJs($magicstr, $vars)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n interpolateJs stub";

    return i18n_interpolate_js($this->handle, $magicstr, (array)$vars);
  }

  // description: get a localized string out of a fully qualified tag and
  //     encode it into HTML friendly encoding
  // param: magicstr: the fully qualified tag of format
  //     "[[" . <domain> . "." . <tag> (. "," . <key> . "=" . <value>)* . "]]"
  // param: vars: a hash of variable key strings to value strings. Optional
  // returns: a HTMl friendly localized string or magicstr if
  //     interpolation failed
  function interpolateHtml($magicstr, $vars = array()) {
    if($GLOBALS["_I18n_isDebug"]) print("interpolateHtml($magicstr, $vars)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n interpolateHtml stub";

    return i18n_interpolate_html($this->handle, $magicstr, $vars);
  }

  // description: get a property value from the property file
  //     /usr/share/locale/<locale>/<domain>.prop. Properties are defined as
  //     "<name>: <value>\n" in the file. One line for each property. Comments
  //     starts with "#"
  // param: property: the name of the property in string
  // param: domain: the domain of the property in string. Optional. If not
  //     supplied, the one supplied to I18n constructor is used.
  // param: langs: an optional string that contains a comma separated list
  //     of preferred locale. Most important locales appears first.
  //     e.g. "en_US, en_AU, zh, de_DE". Optional. If not supplied, the one
  //     supplied to I18n constructor is used.
  function getProperty($property, $domain = "", $lang = "") {
    if($GLOBALS["_I18n_isDebug"]) print("getProperty($property, $domain, $lang)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n getProperty stub";

    return i18n_get_property($this->handle, $property, $domain, $lang);
  }

  // descrption: get the path of the file of the most suitable locale
  //     For example, if "/logo.gif" is supplied, locale "ja" is preferred and
  //     "/logo.gif", "/logo.gif.en" and "/logo.gif.ja" are available,
  //     "/logo.gif.ja" is returned
  // param: file: the full path of the file in question
  // returns: the full path of the file of the most suitable locale
  function getFile($file) {
    if($GLOBALS["_I18n_isDebug"]) print("getFile($file)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n getFile stub";

    return i18n_get_file($this->handle, $file);
  }

  // description: get a list of available locales for a domain or everything
  //     on the system
  // param: domain: i18n domain in string. Optional
  // returns: an array of locale strings
  function getAvailableLocales($domain = "") {
    if($GLOBALS["_I18n_isDebug"]) print("getAvailableLocales($domain)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n getAvailableLocales stub";

    return i18n_availlocales($domain);
  }

  // description: get a list of negotiated locales
  // param: domain: i18n domain in string. Optional
  // returns: an array of locale strings
  //     The first one being to most important and so on
  function getLocales($domain = "") {
    if($GLOBALS["_I18n_isDebug"]) print("getLocales($domain)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n getLocales stub";

    return i18n_locales($this->handle, $domain);
  }

  // description: wrapper to strftime()
  // param: format: the format parameter to strftime()
  // param: time: the epochal time
  // returns: a strftime() formatted string
  function strftime ($format = "", $time = 0) {
    if($GLOBALS["_I18n_isDebug"]) print("strftime($format, $time)\n");
    if($GLOBALS["_I18n_isStub"])  return "I18n strftime stub";

    return i18n_strftime($this->handle, $format, $time);
  }

	/*
	 * Encode a string properly based on the current locale.
	 * args:
	 *	string to encode
	 *	To encoding (optional).  Encoding to convert string to.
	 *	From encoding (optional).  Assume the passed in string is in
	 *	this encoding rather than determining the encoding
	 *	automatically.
	 *	locale (optional) to encode for.  If not specified, the locale 
	 *	of the I18n object is used.
	 *	is used.

	 *	
	 * returns:
	 *	The encoded string if successful.
	 *	boolean false if there is an error.
	 */
	function encodeString($string, $toEncoding = '', $fromEncoding = '',
			      $locale = '')
	{
		if ($locale == '') {
			$locales = $this->getLocales();
			if ($locales[0] == '') {
				return false;
			}
			$locale = $locales[0];
		}
	
		// this is kind of a hack, but at least it hides the hack here.
		if (preg_match("/^ja/i", $locale)) {
			$encConv = new EncodingConv($string, 'japanese',
						    $fromEncoding);
			if ($toEncoding == '') {
				return $encConv->toEUC();
			} else {
				return $encConv->doJapanese($string,
							    $toEncoding,
							    $fromEncoding);
			}
		} else {
			/*
			 * okay, so this is only japanese for now, but one day 
			 * this could be useful for unicode
			 */
			return $string;
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
