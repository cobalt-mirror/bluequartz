<?php

/**
* @author "Anonymous"
* @package Palette
* @version 1.0
* @link http://www.php.net/manual/en/function.http-negotiate-language.php
* @license Modified BSD
*/

class BXBrowserLocale {
    
  public function prefered_language() {

      $supported_languages = array(
              "da" => "da_DK", 
              "de" => "de_DE", 
              "es" => "es_ES",
              "en" => "en_US",
              "fr" => "fr_FR",
              "it" => "it_IT",
              "ja" => "ja_JP",
              "pt" => "pt_PT",
              "nl" => "nl_NL"
              );

      $available_languages = array_keys($supported_languages);
      $http_accept_language="auto";

      // if $http_accept_language was left out, read it from the HTTP-Header
      if ($http_accept_language == "auto") $http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

      // standard  for HTTP_ACCEPT_LANGUAGE is defined under
      // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
      // pattern to find is therefore something like this:
      //    1#( language-range [ ";" "q" "=" qvalue ] )
      // where:
      //    language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
      //    qvalue         = ( "0" [ "." 0*3DIGIT ] )
      //            | ( "1" [ "." 0*3("0") ] )
      preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
                     "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
                     $http_accept_language, $hits, PREG_SET_ORDER);

      // Default language is 'en' if we can't figure it out:
      $bestlang = 'en';
      $bestqval = 0;

      foreach ($hits as $arr) {
          // read data from the array of this hit
          $langprefix = strtolower ($arr[1]);
          if (!empty($arr[3])) {
              $langrange = strtolower ($arr[3]);
              $language = $langprefix . "-" . $langrange;
          }
          else $language = $langprefix;
          $qvalue = 1.0;
          if (!empty($arr[5])) $qvalue = floatval($arr[5]);
       
          // find q-maximal language 
          if (in_array($language,$available_languages) && ($qvalue > $bestqval)) {
              $bestlang = $language;
              $bestqval = $qvalue;
          }
          // if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
          else if (in_array($langprefix,$available_languages) && (($qvalue*0.9) > $bestqval)) {
              $bestlang = $langprefix;
              $bestqval = $qvalue*0.9;
          }
      }
      return $supported_languages[$bestlang];
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