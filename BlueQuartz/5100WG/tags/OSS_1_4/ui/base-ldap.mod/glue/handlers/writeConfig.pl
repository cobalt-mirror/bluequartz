#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ldap

use CCE;
use Sauce::Util;
use ldapExport;
use strict;

# Hardcoded defines...
my $LDIFFile = "/var/lib/ldap/ldif";

my $cce = new CCE();
$cce->connectfd( \*STDIN, \*STDOUT );

my $oid = $cce->event_oid();
my $obj = $cce->event_object();

my $CREATED = $cce->{createflag};
my $DESTROYED = $cce->{destroyflag};


my $SysOID = $cce->find("System");

my $ok;
my $LdapExport;
($ok, $LdapExport) = $cce->get($SysOID, "LdapExport");

my $System;
($ok, $System) = $cce->get($SysOID);

my $baseDn = $LdapExport->{baseDn};

my $emailSuffix = $LdapExport->{emailBase};
if ($emailSuffix eq "") {
	$emailSuffix = $System->{hostname} . "." . $System->{domainname};
}

my $newData;

$newData .= ldapExport::makeEntry($cce, $obj, {baseDn => $baseDn , emailSuffix => $emailSuffix});

# Set LDAP Export info:


system("/bin/touch $LDIFFile");
chmod 0600, $LDIFFile;
Sauce::Util::editfile( $LDIFFile, \&editBlock, $oid, $newData, $DESTROYED, $CREATED);

ldapExport::restartService( $cce, $LdapExport->{exportEnabled} );

$cce->bye("SUCCESS");

sub editBlock {
	my $fin = shift;
	my $fout = shift;
	my $oid = shift;
	my $newData = shift;
	my $DESTROYED = shift;
	my $CREATED = shift;

	my $lock = 0;
	my $found = 0;

	while (<$fin>) {
		if (/^$oid$/) {
			# reached the record!
			$lock = 1;
			$found = 1;
			if ($DESTROYED) {
				# removing entry!
			} else {
				print $fout "$oid\n";
				print $fout "$newData";
					
			}
		}
		($lock) || (print $fout $_);
		($lock) && (/^\s*$/) && ($lock=0);
	}
	if (!$found) {
		print $fout "$oid\n";
		print $fout "$newData";
	}
}
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
