<?php
// Author: Kevin K.M. Chiu
// $Id: Collator.php

global $isCollatorDefined;
if($isCollatorDefined)
  return;
$isCollatorDefined = true;

class BXCollator {
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