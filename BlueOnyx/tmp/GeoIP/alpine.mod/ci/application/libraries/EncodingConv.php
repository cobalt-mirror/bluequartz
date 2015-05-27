<?php

/**
 * EncodingConv.php
 *
 * BlueOnyx EncodingConv for Codeigniter
 *
 * @package   EncodingConv
 * @author    Michael Stauber, Hisao Shibuya
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

class EncodingConv {
  var $stringVal;
  var $encoding;
  var $lang;
  var $japanese_converter = "/usr/sausalito/perl/JConv.pl";
  
  function EncodingConv($string, $lang, $encoding = "") {
    $this->stringVal = $string;
    $this->lang = $lang;
    $this->encoding = $encoding;
  }

  function doJapanese($oldString, $newEncoding, $oldEncoding = "") {
    // we trust jstring to tell us the correct encoding
    if(!$oldEncoding)
      $oldEncoding = mb_detect_encoding($oldString, "EUC-JP, SJIS, ASCII, JIS, UTF-8");

    // but we don't trust jstring to do the conversion because it can't
    // handle SJIS
    // We use i18n_convert here, which is an undocumented function
    // that Shibuya found somewhere. The documented iconv_convert
    // that we previously used doesn't support some extensions to
    // certain character encodings (IBM codes in JIS)
    if (!$oldEncoding) {
      $oldEncoding = 'EUC-JP';
    }

      return mb_convert_encoding($oldString, $newEncoding, $oldEncoding);
  }

  function toSJIS() {
    if ($this->lang != "japanese") 
      return 0;
    
    return $this->doJapanese($this->stringVal, "sjis", $this->encoding);
  }

  function toUnicode() {
    if ($this->lang != "japanese") 
      return 0;
    return $this->doJapanese($this->stringVal, "unicode", $this->encoding);
  }

  function toUTF8() {
    if ($this->lang != "japanese") 
      return 0;
    return $this->doJapanese($this->stringVal, "utf8",  $this->encoding);
  }

  function toJIS() {
    if ($this->lang != "japanese") 
      return 0;
    return $this->doJapanese($this->stringVal, "jis", $this->encoding);
  }

  function toEUC() {
    if ($this->lang != "japanese") 
      return 0;
    return $this->doJapanese($this->stringVal, "euc",  $this->encoding);
  }    

  function toISO2022() {
    if ($this->lang != "japanese") 
      return 0;
    return $this->doJapanese($this->stringVal, "iso-2022-jp",  $this->encoding);
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