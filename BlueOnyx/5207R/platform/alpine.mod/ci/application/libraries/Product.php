<?php

/**
 * Product.php
 *
 * BlueOnyx Product for Codeigniter
 *
 * @package   Product
 * @author    Michael Stauber, Patrick Bose
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

// This class reveals product specific information 
// such as product type and available features.

global $isProductDefined;
if($isProductDefined)
  return;
$isProductDefined = true;

class Product {

    // private vars
    var $type;     // product type as returned by getProductType
    var $cce;  

    // creates a Product object 
    // params: cce reference
    // returns: nothing
    function Product(& $cce) {
        $this->cce = & $cce;
    }

    // tells you if you have a product in the raq family
    // more generic than getProductType 
    // params: none
    // returns:  boolean
    function isRaq () {
        if ( !$this->type ) 
            $this->getProductType();

        if ( $this->type == "RAQ" || $this->type =="CACHERAQ" )
            return true;
        else
            return false;   
    }

    // gets the product code from cce and converts to known type,
    // this should return something 
    // e.g. raq, qube..
    function getProductType () {
        $system = $this->cce->getObject("System", array(), "");
        $productCode = $system["productBuild"];

        if ( preg_match("/[0-9]+R$/", $productCode) ) 
            $this->type = "RAQ";
        else if ( preg_match("/[0-9]+WG$/", $productCode) ) 
            $this->type = "QUBE";
        else if ( preg_match("/[0-9]+CR$/", $productCode) ) 
            $this->type = "CACHERAQ";
        else
            $this->type = "UNKNOWN";
    
        return $this->type;     
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