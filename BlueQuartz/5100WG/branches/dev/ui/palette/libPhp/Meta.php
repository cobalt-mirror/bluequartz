<?php
// Author: Patrick Bose
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Meta.php 201 2003-07-18 19:11:07Z will $

// This class provides a subset of Meta.pm functionality, 
// i.e. it provides access to the Cobalt database.
// The API and behavior have been replicated as much as possible.
// See the function comments for more info.
//
// Create object of specified type:
//   $obj = new Meta( array( "type" => $type ) );
// 
// populate the object with values from the database, based on the key
// $name:
//   $obj->retrieve( "name" );
//
// get the value of an attribute of the object based on the key:
//   $value = $obj->get($key);
//

// todos:
// set encoding
//

global $isMetaDefined;
if($isMetaDefined)
  return;
$isMetaDefined = true;

class Meta {

	// private vars
	var $PGDATABASE="cobalt";
	var $PGUSER="admin";
	var $PGPORT=5432;
	var $PGPASSWORD="";
	var $Meta_id="/etc/cobalt/.meta.id";
	var $dbc;
	var $type;

	// creates a Meta object of specified type, establishes db connection 
	// params:
	// hash: hash with one attribute, type. type refers to the type of 
	//   object being created, and corresponds to the table name in the database
	function Meta( $hash ) {
		$this->type = $hash['type'];
		$this->PGPASSWORD = ( file_exists ( $this->Meta_id )) ? `cat /etc/cobalt/.meta.id` : "";
		putenv("PGPASSWORD=$this->PGPASSWORD");
		$this->dbc = $this->DB_connect();
	}

	// builds the object from the database from the row specified by $name
	// params: name of the object to get
	// returns:  $this object, false on failure
	function retrieve ( $name ) {
		if ( ! ( $name && $this->type ) ) {
			return false;
		}

		$query = "SELECT DISTINCT * FROM $this->type WHERE name='$name'";
		$result = pg_exec( $this->dbc, $query );
		$row = false;
		if ( $result ) {
			$row = pg_fetch_row( $result, 0 );
		}
		if ( $row ) {
			for ( $j=0; $j<count($row); $j++ ) {
				$field=pg_fieldname($result,$j);
				$this->$field = $row[$j];
			}
			return $this; 
		} else {
			return false;
		}
	}

	// returns the specified attribute of the object
	// params: $key - the attribute to retrieve
	// returns: on|off if boolean (t|f), else $this->$key, false if
	//   $key isn't defined
	// notes: doesn't check if $this->$key exists since php doesn't
	//   seem to have a way to test if a variable exists vs. == ""
	function get ( $key ) {
		if ( ! $key )
			return false;

		// handle booleans
		if ( $this->$key == 't' ) return 'on';
		if ( $this->$key == 'f' ) return 'off';
		
		return $this->$key;	
	}


	// makes a connection to the database
	// params: none
	// returns: connection index on success, else false
	function DB_connect() {
		$command = "dbname=$this->PGDATABASE user=$this->PGUSER port=$this->PGPORT";
		return pg_connect( $command );
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

