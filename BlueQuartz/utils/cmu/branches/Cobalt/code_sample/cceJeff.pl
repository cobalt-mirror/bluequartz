#!/usr/bin/perl
# $Id: cceJeff.pl 922 2003-07-17 15:22:40Z will $

# Jeff's script for cce type of tests;


use lib "/usr/sausalito/perl";
use CCE;
use Data::Dumper;

$cce = new CCE;
$cce->connectuds();

#listClasses($cce);
listVsite($cce);
#addUser();
#listUser();
#listGroup();
#listList();

$cce->bye("bye");

sub listList
{
#	my @oid = $cce->find("MailList", { 'name' => "sendit" } );
	my @oid = $cce->find("MailList");

	my ($ok,$obj);
    foreach my $curOid (@oid) {
		($ok, $obj) = $cce->get($curOid);
		print Dumper($obj);
		($ok, $a) = $cce->get($curOid, "Archive");
		print Dumper($a);


	}


}

sub listVsite
{
	my $cmu = shift;
	#my @oid = $cce->find("Vsite");
	my @oid = $cce->find("Vsite", { fqdn => 'www.suspend.com' });

	my ($ok,$obj);
    foreach my $curOid (@oid) {
		($ok, $obj) = $cce->get($curOid);
		print Dumper($obj);
		($ok, @names) = $cce->names($curOid);
		print "Names: ", @names, "\n";


		foreach my $ent (@names) {
			my ($k, $info) = $cce->get($curOid, $ent);
			warn "ok: ", $k, " ent: ", $ent, "\n";
			print Dumper($info);
		}

	}



}
	

sub listClasses
{
	my $cmu = shift;
		
	print Dumper($cmu->classes);
}

sub listGroup
{

		use MIME::Base64;
		use I18n;
	my $i = new I18n();
	$i->setLocale(I18n::i18n_getSystemLocale($self));

	my ($ok, $group);
	my @oid = $cce->find("Workgroup", { 'name' => "all" } );

  #  my @oid = $cce->find("Workgroup");


    foreach my $curOid (@oid) {
		($ok, $group) = $cce->get($curOid);
		#warn "ok: ", $ok, " group name: ", $group->{name}, "\n";
		print Dumper($group);
		($ok, @names) = $cce->names($curOid);
		warn "Names: ", @names, "\n";


		foreach my $ent (@names) {
			my ($k, $info) = $cce->get($curOid, $ent);
			warn "ok: ", $k, " ent: ", $ent, "\n";
			warn Dumper($info);
		}


		
#		print Dumper($group);
		#my $ret = encode_base64($group->{description});
		#print Dumper($group);
		#($ok, $object) = $cce->get($curOid, 'Disk');

		#print Dumper($object);
	}

}


sub addUser
{
	my $h = {
		name => 'luser',
		fullName => 'cce test',
        password => 'pass',
        localePreference => 'en',
        stylePreference => 'trueBlue',
        description => 'test test'
	};

	my ($ok, $badkeys, @info) = $cce->create('User', $h);
	print "OK = $ok\n";
	foreach my $type ( keys %$badkeys) { print "$type\n" }
	print "info = @info\n";

	my $oid = $cce->oid();
	warn "new oid is: ", $oid, "\n";
	my $e = {
		forwardEnable => '&forwardEnablea&',
		forwardEmail => '&test@test.com&sad@sad.com&'
	};

	($ok, $badkeys, @info) = $cce->set($oid, 'Email', $e);
	print "OK = $ok\n";
	foreach my $type ( keys %$badkeys) { print "$type\n" }
	print "info = @info\n";


}

sub listUser
{

	#my @oid = $cce->find("User");
	my @oid = $cce->find("User", { 'name' => "jeffb" } );

	foreach my $curOid (@oid) {
		#warn "curOid is: ", $curOid, "\n";

		($ok, $user) = $cce->get($curOid);
		warn "ok: ", $ok, " user name: ", $user->{name}, "\n";
		print Dumper($user);
		($ok, @names) = $cce->names($curOid);
		warn "Names: ", @names, "\n";


		foreach my $ent (@names) {
			my ($k, $info) = $cce->get($curOid, $ent);
			warn "ok: ", $k, " ent: ", $ent, "\n";
			warn Dumper($info);
		}


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
