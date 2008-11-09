#!/usr/bin/perl -w -I/usr/sausalito/perl/

use POSIX;
use CCE;
use strict;

$CCE::DEBUG = 2;

# Globals.
my $Openssl_cmd = "/usr/sbin/openssl";
my $CertValidFor = 9999; # Number of days self signed certs are valid for.
my $ResponsibleUser = "admin"; # User responsible for ssl managment.
my $CertDir = "/etc/httpd/ssl/";

my $cce = new CCE ( Domain => 'base-ssl' );
my $errors;

$cce->connectfd(\*STDIN,\*STDOUT);

# No need to validate as all identity functions are already validated by
# identity.pl and all hostname functions are already validated by system.


if( ! validate($cce) ) {
	$cce->bye("FAIL");
} else {
	set_identity($cce);
	$cce->bye("SUCCESS");
}

sub validate {
	my $cce = shift;
	my $errors;

	if( ! -d $CertDir ) {
		$cce->warn("certDirMissing");
		$errors ++;
	}

	if( ! -x $Openssl_cmd ) {
		$cce->warn("couldntRunOpenssl");
		$errors++;
	}

	return ( ! $errors );
}
	

sub set_identity
{
	my $cce = shift;

	my ($ret,$SysObj) = $cce->get($cce->event_oid());
	my ($reti,$IdentObj) = $cce->get($cce->event_oid(),"Identity");

	# We're starting from scratch here.. Make backups if we're going
	# to be overwriting something.
	for my $file ( qw(request certificate key) ) {
		if( -f "$CertDir/$file") {
			rename("$CertDir/$file","$CertDir/$file.bak");
		}
	}

	# Fork of here as generating certificates takes too long.
	my $pid = fork();
	if( $pid ) {
		return 1;
	}

	# daemonize myself
	close(STDIN); close(STDOUT); close(STDERR);
	my $logfile = "/tmp/ssl.log.$$";
	open(STDOUT, ">$logfile");
	open(STDERR, ">&STDOUT");
	open(STDIN, "</dev/null");
	POSIX::setsid();

	# generate key:
	system("$Openssl_cmd genrsa -out $CertDir/key 1024 1>&2");

	# generate certificate request:
	my $cmnd =  "|$Openssl_cmd req -new -config /usr/lib/openssl.cnf "
		. "-key $CertDir/key -days $CertValidFor -out $CertDir/request 1>&2";

	open(REQ, $cmnd);

	print REQ $IdentObj->{country} ."\n";
	print REQ $IdentObj->{state} . "\n";
	print REQ $IdentObj->{locality} . "\n";
	print REQ $IdentObj->{organisation} . "\n";
	print REQ $IdentObj->{organisationUnit} . "\n";
	print REQ $SysObj->{hostname} .'.'. $SysObj->{domainname} . "\n";
	print REQ "$ResponsibleUser@".$SysObj->{hostname}.'.'. $SysObj->{domainname}. "\n";
	close REQ;	

	# self-sign certificate request to make certificate
	system("$Openssl_cmd x509 -days $CertValidFor -req -signkey $CertDir/key "
		  ."-in $CertDir/request -out $CertDir/certificate 1>&2");

	chmod 0660, "$CertDir/key";
	chmod 0660, "$CertDir/certificate";
	chmod 0660, "$CertDir/request";

	exit 1;
}

1;
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
