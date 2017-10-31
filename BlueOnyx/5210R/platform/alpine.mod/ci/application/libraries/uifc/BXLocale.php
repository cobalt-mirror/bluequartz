<?php
// Author: Kevin K.M. Chiu
// Author: Michael Stauber
// $Id: BXLocale.php

global $isLocaleDefined;
if($isLocaleDefined)
  return;
$isLocaleDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class BXLocale extends FormField {
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
    if($possibleLocales == null) {
      $possibleLocales = array_merge("browser", $i18n->getAvailableLocales());
    }

    // make label to value hash
    $labelToValues = array();
    for($i = 0; $i < count($possibleLocales); $i++) {
      $possibleLocale = $possibleLocales[$i];

      // browser is a special case
      if($possibleLocale == "browser") {
        $labelToValues[$i18n->getHtml("browser", "palette")] = "browser";
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
      if($language == $languageTag) {
        continue;
      }

      // get country code
      $country = "";
      if(strlen($possibleLocale) >= 5) {
        $countryTag = strtolower(substr($possibleLocale, 3, 2));
        $country = $i18n->get($countryTag, "palette");

        // screen out country codes that are not in ISO 3166 standard
        if($country == $countryTag) {
          continue;
        }
      }

      if($country == "") {
        $labelToValues[$i18n->get("localeLanguage", "palette", array("language" => $language))] = $possibleLocale;
      }
      else {
        $labelToValues[$i18n->get("localeLanguageCountry", "palette", array("language" => $language, "country" => $country))] = $possibleLocale;
      }
    }

    // Ever tried to find your locale in Japanese? So we add the locale identifier to the pulldown as well:
    foreach ($labelToValues as $desc => $shortval) {
      $labelToValues_new[$desc . ' [' . $shortval . ']'] = $shortval;
    }

    $builder->setSorted(TRUE);

    return $builder->makeSelectField($id, $this->getAccess(), $i18n, 1, $GLOBALS["_FormField_width"], false, $formId, "", array_keys($labelToValues_new), array_values($labelToValues_new), array($value));
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