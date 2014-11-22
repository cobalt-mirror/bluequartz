<?php

/**
 * I18nNative.php
 *
 * BlueOnyx Native I18n Internationalization Class for Codeigniter
 *
 * @package   I18nNative
 * @author    Michael Stauber
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 *
 * Description:
 * ============
 *
 * So far BlueOnyx (and the predecessors BlueQuartz and the RaQ550) used a loadable PHP 
 * Zend API module to supply PHP with capability to handle Internationalization of displayed
 * text. The reason for this is that the i18n support in PHP itself was in its infancy when
 * the Sausalito GUI was initially designed. The drawback of this is naturally that the 
 * PHP module must be compatible to the PHP version that AdmServ uses and it must be compiled
 * against said PHP version. Upgrading PHP then (naturally) was out of the question as it
 * would have required a recompile of the 'i18n.so' module again.
 *
 * Now with PHP-5.4 (and later) we have the problem that the Zend API for modules has changed.
 * The current code of the i18n.so module is no longer compatible and (as is) this is a bit
 * beyond our limited capabilities of fixing. 
 *
 * But these days PHP has a usable i18n engine, even if it is a bit clunky. So our parent class
 * i18n.php checks if the function i18n_new() as provided by the PHP module is present and 
 * accounted for. If so, it will use it for internationalization. If it's not loaded and ready,
 * then this new I18nNative.php class will be used instead and provides (more or less) the same
 * functionality via native PHP functions.
 *
 * I say "more or less" and that's true. For now all the i18n_get*() or i18n_interpolate*()
 * functions (for returning plain text, JavaScript-escaped or HTML'ized text) simply return
 * plain text. Although this *can* be changed (and might be in the future), we don't really
 * need that. The new GUI (as far as I can tell right now) handles plain UTF-8 text just fine
 * and I don't see a specific need to change this.
 *
 */

global $isI18nNativeDefined;
if($isI18nNativeDefined)
  return;
$isI18nNativeDefined = true;

//
// private class variables
//

class I18nNative {
  //
  // private variables
  //

  var $handle;

  var $language;

  var $domain;

  //
  // public methods
  //

  function setLanguage($language = "en_US") {
    $this->language = $language;
  }

  function getLanguage() {
    if (!isset($this->language)) {
      $CI =& get_instance();
      $this->language = initialize_languages(FALSE);
    }
    return $this->language;
  }

  function setDomain($domain) {
    $this->domain = $domain;
  }

  function getDomain() {
    if (!isset($this->domain)) {
      $this->domain = "palette";
    }
    if ($this->domain == "") {
      $this->domain = "palette";
    }
    return $this->domain;
  }

  function i18n_new($domain="", $langs="en_US") {

    i18nNative::setLanguage($langs);
    i18nNative::setDomain($domain);
    $directory = "/usr/share/locale";

    if ($domain != "") {
      bindtextdomain($domain, $directory);
    }
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);
  }

  function i18n_get($tag, $domain, $vars="") {
    if ($domain == "") {
      $domain = i18nNative::getDomain();
    }
    if (preg_match('/\[\[(.*)\]\]/', $tag)) {
      return i18nNative::i18n_interpolate($tag, $vars);
    }
    $message = i18nNative::i18n_do_it($tag, $domain, $vars);
    return $message;
  }

  function i18n_get_html($tag, $domain, $vars="") {
    if ($domain == "") {
      $domain = i18nNative::getDomain();
    }
    if (preg_match('/\[\[(.*)\]\]/', $tag)) {
      return i18nNative::i18n_interpolate($tag, $vars);
    }
    $message = i18nNative::i18n_do_it($tag, $domain, $vars);
    return $message;
  }

  function i18n_get_js($tag, $domain, $vars="") {
    if ($domain == "") {
      $domain = i18nNative::getDomain();
    }
    if (preg_match('/\[\[(.*)\]\]/', $tag)) {
      return i18nNative::i18n_interpolate($tag, $vars);
    }
    $message = i18nNative::i18n_do_it($tag, $domain, $vars);
    return $message;
  }

  function i18n_interpolate($magicstr, $vars) {
    $zpattern = '/\[\[(.*)\]\]/';
    preg_match($zpattern, $magicstr, $found);
    if (isset($found[1])) {
      $segments = explode('.', $found[1]);
      if (isset($segments[1])) {
        $kmessage = i18nNative::i18n_do_it($segments[1], $segments[0], $vars);
        $ypattern = "/\[\[$segments[0]\.$segments[1]\]\]/";
        $message = preg_replace($ypattern, $kmessage, $magicstr);
      }
      else {
        $message = i18nNative::i18n_do_it($found[1], i18nNative::getDomain(), $vars);
      }
    }
    else {
      $segments = explode('.', $magicstr);
      if (isset($segments[1])) {
        $message = i18nNative::i18n_do_it($segments[1], $segments[0], $vars);
      }
      else {
        $message = i18nNative::i18n_do_it($magicstr, i18nNative::getDomain(), $vars); 
      }
    }
    return $message;
  }

  function i18n_interpolate_js($magicstr, $vars) {
    $zpattern = '/\[\[(.*)\]\]/';
    preg_match($zpattern, $magicstr, $found);
    if (isset($found[1])) {
      $segments = explode('.', $found[1]);
      if (isset($segments[1])) {
        $kmessage = i18nNative::i18n_do_it($segments[1], $segments[0], $vars);
        $ypattern = "/\[\[$segments[0]\.$segments[1]\]\]/";
        $message = preg_replace($ypattern, $kmessage, $magicstr);
      }
      else {
        $message = i18nNative::i18n_do_it($found[1], i18nNative::getDomain(), $vars);
      }
    }
    else {
      $segments = explode('.', $magicstr);
      if (isset($segments[1])) {
        $message = i18nNative::i18n_do_it($segments[1], $segments[0], $vars);
      }
      else {
        $message = i18nNative::i18n_do_it($magicstr, i18nNative::getDomain(), $vars); 
      }
    }
    return $message;
  }

  function i18n_interpolate_html($magicstr, $vars) {
    $zpattern = '/\[\[(.*)\]\]/';
    preg_match($zpattern, $magicstr, $found);
    if (isset($found[1])) {
      $segments = explode('.', $found[1]);
      if (isset($segments[1])) {
        $kmessage = i18nNative::i18n_do_it($segments[1], $segments[0], $vars);
        $ypattern = "/\[\[$segments[0]\.$segments[1]\]\]/";
        $message = preg_replace($ypattern, $kmessage, $magicstr);
      }
      else {
        $message = i18nNative::i18n_do_it($found[1], i18nNative::getDomain(), $vars);
      }
    }
    else {
      $segments = explode('.', $magicstr);
      if (isset($segments[1])) {
        $message = i18nNative::i18n_do_it($segments[1], $segments[0], $vars);
      }
      else {
        $message = i18nNative::i18n_do_it($magicstr, i18nNative::getDomain(), $vars); 
      }
    }
    return $message;
  }

  function i18n_do_it($tag, $domain, $vars="") {
    $lang = i18nNative::getLanguage();
    putenv("LANG=$lang"); 
    setlocale(LC_ALL, $lang);
    if ($domain == "") {
      $domain = i18nNative::getDomain();
    }
    $directory = "/usr/share/locale";
    setlocale( LC_MESSAGES, $lang);
    bindtextdomain($domain, $directory);
    textdomain($domain);
    bind_textdomain_codeset($domain, 'UTF-8');

    $message = dcgettext($domain, $tag, '5');

    // Check if the $message contains [[STUFF]] that needs to be replaced:
    $pattern = '/\[\[[a-zA-Z0-9\-\_\.]{1,99}\]\]/';
    preg_match_all($pattern, $message, $matches);
    $numCount = count($matches[0], COUNT_RECURSIVE);

    if ($numCount > 0) {
      // Do the actual replacing:
      $newVars[] = array();
      $VarReplacements = array();
      $newTag = ' ';
      $newDomain = '';
      foreach ($matches[0] as $key => $value) {
        $patterns = array();
        $patterns[0] = '/\[\[/';
        $patterns[1] = '/\]\]/';
        $value = preg_replace($patterns, "", $value);
        $segments = explode('.', $value);
        if ((isset($segments[0])) && (isset($segments[1]))) {
          if ($segments[0] != "VAR") {
            if (isset($segments[2])) {
              $newDomain = $segments[0] . "." . $segments[1];
              $newTag = $segments[2];
            }
            else {
              $newDomain = $segments[0];
              $newTag = $segments[1];
            }
            $VarReplacements["$newDomain.$newTag"] = i18nNative::i18n_interpolate($value, $vars);
          }
          else {
            $id = $segments[0] . '.' . $segments[1];
            if (isset($vars[$segments[1]])) {
              $VarReplacements[$id] = $vars[$segments[1]];
            }
          }
        }
      }

      // Do the actual replacing:
      if (count($VarReplacements) > 0) {
          foreach ($VarReplacements as $key => $replacement) {
            $xpattern[0] = '/\[\[' . $key . '\]\]/';
            $xreplacement[0] = $replacement;
            $message = preg_replace($xpattern, $xreplacement, $message);
          }
      }
    }
    return $message;
  }

  // description: get a list of negotiated locales
  // param: domain: i18n domain in string. Optional
  // returns: an array of locale strings.
  //          The first one being to most important and so on
  function i18n_locales($domain) {
    if ($domain == "") {
      $domain = i18nNative::getDomain();
    }
    // Otherwise examine the locale directories for all locales of this $domain:
    $directory = '/usr/share/locale/*/LC_MESSAGES/' . $domain . '.mo';
    $map = `/bin/ls -k1 $directory`;
    // Turn search results into an Array:
    $loc_langs_raw = explode("\n", $map);
    // Remove empty elements:
    $loc_langs_raw = array_filter($loc_langs_raw);

    // Now extract the actual languages from the paths:
    $detected_langs = array();
    foreach ($loc_langs_raw as $id => $loc_lang) {
      $ll_raw = explode("/", $loc_lang);
      $detected_langs[] = $ll_raw[4];
    }

    // Move 'en_US' to top if it is present:
    if (in_array('en_US', $detected_langs)) {
      if (($key = array_search('en_US', $detected_langs)) !== false) {
          unset($detected_langs[$key]);
          array_unshift($detected_langs , 'en_US');
      }
    }

    // Return the results:
    return $detected_langs;
  }

}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
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