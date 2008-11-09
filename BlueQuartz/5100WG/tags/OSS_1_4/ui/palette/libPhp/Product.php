<?php
// Author: Patrick Bose
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: Product.php 3 2003-07-17 15:19:15Z will $

// This class reveals product specific information 
// such as product type and available features.

global $isProductDefined;
if($isProductDefined)
  return;
$isProductDefined = true;

class Product {

	// private vars
	var $type;     // product type as returned by getProductType
	var $arch;     // product architecture (hardware generation)
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
		if ( $this->type ) 
			return $this->type;

		$system = $this->cce->getObject("System", array(), "");
		$productCode = $system["productBuild"];

		if ( ereg("[0-9]R", $productCode) ) 
			$this->type = "RAQ";
		else if ( ereg("[0-9]WG", $productCode) ) 
			$this->type = "QUBE";
		else if ( ereg("[0-9]CR", $productCode) ) 
			$this->type = "CACHERAQ";
	
		return $this->type;		
	}

	// Returns the hardware generation, as in "III" or "V"
	function getArchitecture () {
		if ( $this->arch )
			return $this->arch;

		$system = $this->cce->getObject("System", array(), "");
		$this->arch = $system["productGen"];
	
		return $this->arch;		
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

