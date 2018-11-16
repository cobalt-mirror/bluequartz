<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * BlueOnyx Password Helper Library
 *
 * BlueOnyx Helper for Codeigniter
 *
 * @package   CI Blueonyx
 * @author    Michael Stauber
 * @copyright Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
 * @link      http://www.solarspeed.net
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 *
 * Example usage:
 *
 * 		$algorithm = "SHA256";
 *		$password = "my password";
 *		$salt = "my salt";
 *		$count = "2048";
 *		$key_length = "12";
 *		$raw_output = FALSE;
 *
 *		$out = pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
 *
 *		print_rp($out); 			<- Password in Hexadecimal output
 *		print_rp(hexttopw($out));   <- ASCII'ized password (subset is somewhat limited)
 *
 *	This ain't perfect. But as long as the input parameters are the same, you will always
 *  get the same password back. So one could use a supersecret $password, use the site you
 *  wanna used it for (amazon.com, paypal.com ... whatever) as $salt and you get a nice
 *  password back. If you forget it, all you need to remember is your supersecret. Visit
 *  the form again, enter the supersecret and the domain name ... voila.
 *
 */

/*
 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
 * $algorithm - The hash algorithm to use. Recommended: SHA256
 * $password - The password.
 * $salt - A salt that is unique to the password.
 * $count - Iteration count. Higher is better, but slower. Recommended: At least 1024.
 * $key_length - The length of the derived key in bytes.
 * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
 * Returns: A $key_length-byte key derived from the password and salt.
 *
 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
 *
 * This implementation of PBKDF2 was originally created by defuse.ca
 * With improvements by variations-of-shadow.com
 *
 *
 */
function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false) {
    $algorithm = strtolower($algorithm);
    if(!in_array($algorithm, hash_algos(), true)) {
        die('PBKDF2 ERROR: Invalid hash algorithm.');
    }
    if($count <= 0 || $key_length <= 0) {
        die('PBKDF2 ERROR: Invalid parameters.');
    }

    $hash_length = strlen(hash($algorithm, "", true));
    $block_count = ceil($key_length / $hash_length);

    $output = "";
    for($i = 1; $i <= $block_count; $i++) {
        // $i encoded as 4 bytes, big endian.
        $last = $salt . pack("N", $i);
        // first iteration
        $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
        // perform the other $count - 1 iterations
        for ($j = 1; $j < $count; $j++) {
            $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
        }
        $output .= $xorsum;
    }

    if ($raw_output) {
        return substr($output, 0, $key_length);
    }
    else {
        return bin2hex(substr($output, 0, $key_length));
    }
}

function hexttopw($hex) {

	$baba = str_split($hex, "2");

			$hextopw = array (
			              "00" => ".",
			              "01" => "a",
			              "02" => "b",
			              "03" => "c",
			              "04" => "d",
			              "05" => "e",
			              "06" => "f",
			              "07" => "g",
			              "08" => "h",
			              "09" => "i",
			              "0a" => "j",
			              "0b" => "k",
			              "0c" => "l",
			              "0d" => "m",
			              "0e" => "n",
			              "0f" => "o",
			              "10" => "p",
			              "11" => "q",
			              "12" => "r",
			              "13" => "s",
			              "14" => "t",
			              "15" => "u",
			              "16" => "v",
			              "17" => "w",
			              "18" => "x",
			              "19" => "y",
			              "1a" => "z",
			              "1b" => "{",
			              "1c" => ":",
			              "1d" => "}",
			              "1e" => "*",
			              "1f" => "(",
			              "20" => "(",
			              "21" => "!",
			              "22" => "Z",
			              "23" => "#",
			              "24" => "$",
			              "25" => "%",
			              "26" => ".",
			              "27" => "'",
			              "28" => "(",
			              "29" => ")",
			              "2a" => "*",
			              "2b" => "+",
			              "2c" => ",",
			              "2d" => "-",
			              "2e" => ".",
			              "2f" => "/",
			              "30" => "0",
			              "31" => "1",
			              "32" => "2",
			              "33" => "3",
			              "34" => "4",
			              "35" => "5",
			              "36" => "6",
			              "37" => "7",
			              "38" => "8",
			              "39" => "9",
			              "3a" => ":",
			              "3b" => ";",
			              "3c" => "<",
			              "3d" => "=",
			              "3e" => ">",
			              "3f" => "?",
			              "40" => "@",
			              "41" => "A",
			              "42" => "B",
			              "43" => "C",
			              "44" => "D",
			              "45" => "E",
			              "46" => "F",
			              "47" => "G",
			              "48" => "H",
			              "49" => "I",
			              "4a" => "J",
			              "4b" => "K",
			              "4c" => "L",
			              "4d" => "M",
			              "4e" => "N",
			              "4f" => "O",
			              "50" => "P",
			              "51" => "Q",
			              "52" => "R",
			              "53" => "S",
			              "54" => "T",
			              "55" => "U",
			              "56" => "V",
			              "57" => "W",
			              "58" => "X",
			              "59" => "Y",
			              "5a" => "Z",
			              "5b" => "[",
			              "5c" => ":",
			              "5d" => "]",
			              "5e" => ".",
			              "5f" => "_",
			              "60" => ".",
			              "61" => "a",
			              "62" => "b",
			              "63" => "c",
			              "64" => "d",
			              "65" => "e",
			              "66" => "f",
			              "67" => "g",
			              "68" => "h",
			              "69" => "i",
			              "6a" => "j",
			              "6b" => "k",
			              "6c" => "l",
			              "6d" => "m",
			              "6e" => "n",
			              "6f" => "o",
			              "70" => "p",
			              "71" => "q",
			              "72" => "r",
			              "73" => "s",
			              "74" => "t",
			              "75" => "u",
			              "76" => "v",
			              "77" => "w",
			              "78" => "x",
			              "79" => "y",
			              "7a" => "z",
			              "7b" => "{",
			              "7c" => "|",
			              "7d" => "}",
			              "7e" => "~",
			              "7f" => "(");

	$output = array();
	foreach ($baba as $key => $value) {
		if (isset($hextopw[$value])) {
			$output[] = $hextopw[$value];
		}
		else {
			$value = dechex(floor(hexdec($value)/2));
			$output[] = $hextopw[$value];
		}
	}
	return implode('', $output);

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