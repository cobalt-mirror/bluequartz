<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Collator.php 3 2003-07-17 15:19:15Z will $

global $isCollatorDefined;
if($isCollatorDefined)
  return;
$isCollatorDefined = true;

class Collator {
  //
  // public methods
  //

  // description: collate 2 numeric values
  // param: valueA: the first value
  // param: valueB: the second value
  // param: collator: a Collator object
  // returns: ">" if A > B, "<" if A < B, "=" if A == B
  function collateNumbers($valueA, $valueB, $collator) {
    if($valueA > $valueB)
      return ">";
    else if($valueA < $valueB)
      return "<";
    else if($valueA == $valueB)
      return "=";
  }

  // description: collate 2 string values
  // param: valueA: the first value
  // param: valueB: the second value
  // param: collator: a Collator object
  // returns: ">" if A > B, "<" if A < B, "=" if A == B
  function collateStrings($valueA, $valueB, $collator) {
    if($valueA > $valueB)
      return ">";
    else if($valueA < $valueB)
      return "<";
    else if($valueA == $valueB)
      return "=";
  }

  // description: sort an array of keys and values
  // param: keys: an array of Collatable objects
  //     sorted when the method returns
  // param: values: an array of values of any type. Optional
  //     sorted when the method returns
  // returns: none
  function sort(&$keys, &$values) {
    // this method makes use of quicksort

    // no sorting needed?
    if(count($keys) == 0)
      return $keys;

    // got values?
    $isValues = true;
    if($values != "" && count($values) == 0)
      $isValues = false;

    // start with the whole array
    $jobs = array("0:".(count($keys)-1));

    // finish all jobs
    while(count($jobs) > 0) {
      // get the job
      $job = array_pop($jobs);

      // find the start and the end
      $startEnd = explode(":", $job);
      $startIndex = $startEnd[0];
      $endIndex = $startEnd[1];

      // let pivot be the end
      $pivotIndex = $endIndex;
      $pivotValue = $keys[$pivotIndex]->getCollatableValue();

      // index of the greater value
      $greaterIndex = $startIndex;
      // index of the lesser value
      $lesserIndex = $endIndex;

      // partition
      while($greaterIndex < $lesserIndex) {
	// find the first element greater than pivot from the start
	while($greaterIndex < $endIndex) {
	  $greaterValue = $keys[$greaterIndex]->getCollatableValue();

	  $result = $keys[$pivotIndex]->collate($greaterValue, $pivotValue, $this);
	  if($result == ">" || $result == "=")
	    break;

	  $greaterIndex++;
	}

        // find the first element lesser than pivot from the end
        while($lesserIndex > $startIndex) {
	  $lesserValue = $keys[$lesserIndex]->getCollatableValue();

	  if($keys[$pivotIndex]->collate($lesserValue, $pivotValue, $this) == "<")
	    break;

	  $lesserIndex--;
	}

	// swap
	if($greaterIndex < $lesserIndex) {
	  // keys
	  $tmp = $keys[$greaterIndex];
	  $keys[$greaterIndex] = $keys[$lesserIndex];
	  $keys[$lesserIndex] = $tmp;

	  // values
	  if($isValues) {
	    $tmp = $values[$greaterIndex];
	    $values[$greaterIndex] = $values[$lesserIndex];
	    $values[$lesserIndex] = $tmp;
	  }
	}
      }

      // swap key with pivot
      $tmp = $keys[$greaterIndex];
      $keys[$greaterIndex] = $keys[$pivotIndex];
      $keys[$pivotIndex] = $tmp;

      // swap value with pivot
      if($isValues) {
	$tmp = $values[$greaterIndex];
	$values[$greaterIndex] = $values[$pivotIndex];
	$values[$pivotIndex] = $tmp;
      }

      // make new jobs if needed
      if($startIndex < $greaterIndex-1)
	$jobs[] = $startIndex.":".($greaterIndex-1);
      if($greaterIndex+1 < $endIndex)
	$jobs[] = ($greaterIndex+1).":".$endIndex;
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

