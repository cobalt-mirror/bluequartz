#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ldap

use CCE;
use Sauce::Util;
use ldapExport;
use strict;
use Data::Dumper;

# Hardcoded defines...
my $LDIFFile = "/var/lib/ldap/ldif";
my $SLAPDCONF = "/etc/openldap/slapd.conf";

my $cce = new CCE({Namespace => "LdapExport"});

if ($ARGV[0] eq '-i') {
	$cce->connectuds();
} else {
	$cce->connectfd(\*STDIN, \*STDOUT);
}

my $oid = $cce->event_oid();
my $obj = $cce->event_object;
my $CREATED = $cce->{createflag};
my $DESTROYED = $cce->{destroyflag};

my $ok;

my $SysOID = $cce->find("System");

my $LdapExport;
if ($obj->{NAMESPACE} eq "LdapExport") {
	$LdapExport = $obj; 
} else {
	($ok, $LdapExport) = $cce->get($SysOID, "LdapExport");
}

my $System;
if ($obj->{CLASS} eq "System" && $obj->{NAMESPACE} eq "") {
	$System = $obj;	
} else {
	($ok, $System) = $cce->get($SysOID);
}

my $baseDn = $LdapExport->{baseDn};

my $emailSuffix = $LdapExport->{emailBase};
if ($emailSuffix eq "") {
	$emailSuffix = $System->{hostname} . "." . $System->{domainname};
}

my $newData;

my @OIDs = ($cce->find("User"), $cce->find("Workgroup"));

# Set LDAP Export info:
$newData = "$SysOID\n";
$newData .= ldapExport::ldapAttr("dn", $baseDn) . "\n";

for my $oid (@OIDs) {
	$newData .= "$oid\n";
	$newData .= ldapExport::makeEntry($cce, ($cce->get($oid))[1], {baseDn => $baseDn, emailSuffix => $emailSuffix});
}

system("/bin/touch $LDIFFile");
chmod 0600, $LDIFFile;
Sauce::Util::editfile( $LDIFFile, \&editBlock, $newData);
Sauce::Util::editblock( $SLAPDCONF, \&editConf, "# Cobalt Configuration Start", "# Cobalt Configuration Stop", $baseDn);

ldapExport::restartService( $cce, $LdapExport->{exportEnabled} );

$cce->bye("SUCCESS");

sub editConf {
	my $fin = shift;
	my $fout = shift;
	my $baseDn = shift;

	print $fout "database\tldbm\n";
	$baseDn =~ s/\\/\\\\/g;
	$baseDn =~ s/\"/\\\"/g;
	print $fout "suffix\t\"" . $baseDn . "\"\n";
	print $fout "readonly\ton\n";
	print $fout "directory\t/var/lib/ldap/\n";
	print $fout "rootdn  \"cn=admin, $baseDn\"\n";
	print $fout "access to attr=userpassword by self read by dn=\"cn=admin, $baseDn\" read by * none\n";
	print $fout "access to attr=gid by dn=\".+\" read by * none\n";
	print $fout "access to attr=systemadministrator by dn=\".+\" read by * none\n";
	print $fout "access to attr=quota by dn=\".+\" read by * none\n";
	print $fout "access to attr=uidnumber by dn=\".+\" read by * none\n";
	print $fout "access to attr=gidnumber by dn=\".+\" read by * none\n";
	print $fout "access to attr=homedirectory by dn=\".+\" read by * none\n";
	print $fout "access to attr=loginshell by dn=\".+\" read by * none\n";
	print $fout "access to attr=memberuid by dn=\".+\" read by * none\n";
	print $fout "access to attr=diskquota by dn=\".+\" read by * none\n";

	# new objectClass: organizational person ACLs
	print $fout "access to attr=seeAlso by dn=\".+\" read by * none\n";
	print $fout "access to attr=telephoneNumber by dn=\".+\" read by * none\n";
	print $fout "access to attr=facsimileTelephoneNumber by dn=\".+\" read by * none\n";
	print $fout "access to attr=description by dn=\".+\" read by * none\n";
	
	
}

sub editBlock {
	my $fin = shift;
	my $fout = shift;
	my $newData = shift;

	print $fout $newData;
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
