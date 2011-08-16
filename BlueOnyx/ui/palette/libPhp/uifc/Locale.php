<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Locale.php 1050 2008-01-23 11:45:43Z mstauber $

global $isLocaleDefined;
if($isLocaleDefined)
  return;
$isLocaleDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class Locale extends FormField {
  //
  // private variables
  //

  var $possibleLocales = null;

  //
  // public methods
  //

  // description: get the list of possible locales
  // returns: an array of locale strings
  // see: setPossibleLocales()
  function getPossibleLocales() {
    return $this->possibleLocales;
  }

  // description: set the list of possible locales
  // param: possibleLocales: an array of locale strings
  //     "browser" is also a possible special locale string case
  // see: getPossibleLocales()
  function setPossibleLocales($possibleLocales) {
    $this->possibleLocales = $possibleLocales;
  }

  function toHtml($style = "") {
    $page =& $this->getPage();
    $i18n =& $page->getI18n();
    $form =& $page->getForm();
    $formId = $form->getId();
    $id = $this->getId();
    $value = $this->getValue();

    $builder = new FormFieldBuilder();

    // find out possible locales
    $possibleLocales = $this->getPossibleLocales();
    if($possibleLocales == null)
      $possibleLocales = array_merge("browser", $i18n->getAvailableLocales());

    // make label to value hash
    $labelToValues = array();
    for($i = 0; $i < count($possibleLocales); $i++) {
      $possibleLocale = $possibleLocales[$i];

      // browser is a special case
      if($possibleLocale == "browser") {
	$labelToValues[$i18n->get("browser", "palette")] = "browser";
	continue;
      }

      // see if the whole locale is defined
      // this allows special translation for specific locales
      $localeTag = "locale_".$possibleLocale;
      $fullLocale = $i18n->get($localeTag, "palette");
      if($fullLocale != $localeTag) {
	$labelToValues[$fullLocale] = $possibleLocale;
	continue;
      }

      // get language code
      $languageTag = "lang_".substr($possibleLocale, 0, 2);
      $language = $i18n->get($languageTag, "palette");

      // screen out language codes that are not in ISO 639 standard
      if($language == $languageTag)
	continue;

      // get country code
      $country = "";
      if(strlen($possibleLocale) >= 5) {
	$countryTag = strtolower(substr($possibleLocale, 3, 2));
	$country = $i18n->get($countryTag, "palette");

	// screen out country codes that are not in ISO 3166 standard
	if($country == $countryTag)
	  continue;
      }

      if($country == "")
	$labelToValues[$i18n->get("localeLanguage", "palette", array("language" => $language))] = $possibleLocale;
      else
	$labelToValues[$i18n->get("localeLanguageCountry", "palette", array("language" => $language, "country" => $country))] = $possibleLocale;
    }

    // sort by labels
    // this is not internationalized, but at least it gives a defined order
    ksort($labelToValues);

    // find selected index
    $selectedIndex = -1;
    $sortedLocales = array_values($labelToValues);
    for($i = 0; $i < count($sortedLocales); $i++) {
      if($sortedLocales[$i] == $value) {
	$selectedIndex = $i;
	break;
      }
    }

    return $builder->makeSelectField($id, $this->getAccess(), 1, $GLOBALS["_FormField_width"], false, $formId, "", array_keys($labelToValues), array_values($labelToValues), array($selectedIndex));
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
