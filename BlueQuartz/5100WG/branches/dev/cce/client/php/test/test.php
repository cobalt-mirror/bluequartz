<?

dl("/home/harris/cvsraq/cce/client/php/src/cce.so");

$foo = cce_new();
print "Conencting\n";
cce_connect($foo,"/usr/sausalito/cced.socket");


print "Testing Auth.\n";
print "CCE auth foo bar " . cce_auth($foo,"foo","bar") . "\n";
print "CCE auth bar foo " . cce_auth($foo,"bar","foo") . "\n";

print "Testing commit.\n";
print "Commit returns ". cce_commit($foo) . "\n";


print "Testing commit errors.\n";
print_array(cce_errors($foo));

print "Testing create\n";
print "Create returned " . cce_create($foo,"class",
	array("foo" => "bar", "bar" => "foo")) . "\n";

print "Testing create errors\n";
print_array(cce_errors($foo));

print "Testing set.\n";
print "Set returned " . cce_set($foo, 12, "", array("foo" => "New Foo!")) . "\n";
print "Set returned " . cce_set($foo, 12, "namespace", array("foo" => "New Foo!")) . "\n";

print "Testing Get.\n";
print_array( cce_get($foo,12,"") );

print "Testing handler get\n";
print_array( cce_handler_get($foo,21,"") );
print_array( cce_handler_get($foo,22,"") );

print "Testing find\n";
$bar = cce_find($foo, "class", array("foo" => "bar"));
print_array($bar);

$bar = cce_names($foo,12);
print "cce_names 12 returned\n";
print_array($bar);
$bar = cce_names($foo,"class");
print "cce_names class returned\n";
print_array($bar);

print "cce_destroy " . cce_destroy($foo, 12) . "\n";
print_array(cce_errors($foo));

function print_array( $error, $depth = 1) {
	while( list($key,$val) = each($error ) ) {
		$i = $depth;
		while( $i ) {
			print "  ";
			$i--;
		}
		print "$key == '''$val'''\n";
		if(is_array($val)) {
			print_array($val,$depth + 1);
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

