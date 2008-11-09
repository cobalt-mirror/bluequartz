#!/usr/bin/perl -w -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/ldap/

use strict;

use CCEMPD;
use Net::LDAP qw( LDAP_SUCCESS );
use IO::File;
use Data::Dumper;
use POSIX qw( tmpnam );
use I18n;
use Jcode;
use vars qw( @warnings $fh $maxCount $count $statusMessage $defaultUserQuota $defaultGroupQuota);

$maxCount = 0;
my $cce = new CCEMPD ( Namespace => 'LdapImport',
                       Domain => 'base-ldap' );

if( defined($ARGV[0]) && $ARGV[0] eq 'client' ) {
	$cce->client( 1 );
} else {
	$cce->handler( 1 );
}

$cce->connect();

my $ldap_obj = $cce->event_object();


my $ldap;
my $mesg;

# create a data fh to keep mem clean
my ($dataGroupfh, $group_filename);
do { $group_filename = tmpnam() }
        until $dataGroupfh = IO::File->new($group_filename, O_RDWR|O_CREAT|O_EXCL);
$dataGroupfh->autoflush(1);
my $dataUserfh = IO::File->new_tmpfile;
$dataUserfh->autoflush(1);

$ldap_obj ||
	$cce->fail("couldnt_fetch_system_object");

$ldap = Net::LDAP->new( $ldap_obj->{server} );
$ldap ||
	$cce->fail('couldnt_connect_to_ldap_server',
		{ server => $ldap_obj->{server} } );


if( $ldap_obj->{bindDn} ) {
	$mesg = $ldap->bind(
		dn => $ldap_obj->{bindDn},
		password => $ldap_obj->{passwordAuth} 
	);
} else {
	$mesg = $ldap->bind();
}

( $mesg->code == LDAP_SUCCESS ) || 
	$cce->fail('couldnt_bind_to_ldap_server');


my $filter = ( $ldap_obj->{groupFilter} ) ?
	( $ldap_obj->{groupFilter} ) :
	( "(|(objectClass=cobaltGroup)(objectClass=posixGroup))" );
	
my $mesg_group = $ldap->search(
		base => $ldap_obj->{base},
		filter => $filter
);

foreach my $indGroup ($mesg_group->all_entries) {
	my $data = Dumper($indGroup);
	$data =~ s/\\/\\\\/g;
	$data =~ s/\n/\\n/g;
	$data =~ s/\r/\\r/g;
	print $dataGroupfh $data . "\n";
}

if ( $mesg->code != LDAP_SUCCESS ){
	$cce->fail('couldnt_search', { 'error' => $mesg_group->error(),
		'code' => $mesg_group->code, filter=>$filter  } );
}

$dataGroupfh->close();

$filter = ( $ldap_obj->{userFilter} ) ?
	( $ldap_obj->{userFilter} ) :
	( "(|(objectClass=cobaltAccount)(objectClass=posixAccount))" );

my $mesg_user = $ldap->search(
		base => $ldap_obj->{base},
		filter => $filter);

foreach my $indUser ($mesg_user->all_entries) {
	my $data = Dumper($indUser);
	$data =~ s/\\/\\\\/g;
	$data =~ s/\n/\\n/g;
	$data =~ s/\r/\\r/g;
	print $dataUserfh $data . "\n";
}

if ( $mesg_user->code != LDAP_SUCCESS ){
	$cce->fail('couldnt_search', { 'error' => $mesg_user->error(),
		'code' => $mesg_user->code, filter=>$filter  } );
}

$maxCount += scalar $mesg_group->all_entries;
$maxCount += scalar $mesg_user->all_entries;

undef($mesg_group);
undef($mesg_user);

# break off here
# start off by creating a tempfile
my $filename;
do { $filename = tmpnam() }
	until $fh = IO::File->new($filename, O_RDWR|O_CREAT|O_EXCL);
chown((getpwnam("httpd"))[2], (getpwnam("httpd"))[3], $filename);
print STDERR "TEMP filename = $filename\n";
# push the filename back to the browser
$filename =~ /^.*\/(\S*)$/;
$statusMessage = "[[base-ldap.queryingLdap]]";
rewriteConfigFile();
$cce->info("logFilename", { filename => $1 });
$cce->bye("SUCCESS");

# fork and leave
my $pid;
defined($pid = fork) || die "Can't fork: $!";
exit(0) if $pid;
POSIX::setsid() || die "Can't start a new session: $!";

#close the stdouts
close (STDOUT); open (STDOUT, "> /dev/null");
close (STDERR); open (STDERR, "> /dev/null");
$cce = new CCEMPD ( Namespace => 'LdapImport',
                       Domain => 'base-ldap' );

$cce->client(1);
$cce->handler(0);
$cce->connect();

# grab the default quota size..
my $Defaults = $cce->get(($cce->find("System"))[0], "UserDefaults");
$defaultUserQuota = $Defaults->{quota};

$Defaults = $cce->get(($cce->find("System"))[0], "GroupsDefaults");
$defaultGroupQuota = $Defaults->{quota};

$statusMessage = "[[base-ldap.addingGroup]]";

my $VAR1;
#seek($dataGroupfh, 0,0);
$dataGroupfh->open($group_filename);
$fh->close();
$fh->open("+< $filename");
$fh->autoflush(1);
while (<$dataGroupfh>) {
	s/\\n/\n/g;
	s/\\r/\r/g;
	s/\\\\/\\/g;
	eval;
	my $mesg = ($VAR1);
	add_many($mesg, \&add_group, $cce);
}
$dataGroupfh->close();

$statusMessage = "[[base-ldap.addingUser]]";
seek($dataUserfh, 0, 0);
while (<$dataUserfh>) {
	s/\\n/\n/g;
	s/\\r/\r/g;
	s/\\\\/\\/g;
	eval;
	my $mesg = ($VAR1);
	add_many($mesg, \&add_user, $cce);
}

$cce->bye("SUCCESS");
$fh->close();
#system("rm -f $filename");
system("rm -f $group_filename");
exit(0);

sub add_many {
	my $search = shift;
	my $handler = shift;

	my @args = @_;
	my $problems;
	my @info;



	foreach my $entry ( $search ) {
		$count++;
		bless($entry,"ScalarLdapEntry");
		($problems, @info) = &$handler( $entry, @args );
		if( ref ($problems) eq "HASH") {
			report_errors($entry, $problems, @info);
		}
		rewriteConfigFile($args[0]);
	}
}

sub report_errors {
	my $entry = shift;
	my $problems_temp = shift;
	my $info = shift;
	my %problems = %$problems_temp;
	my $cn = $entry->{asn}->{objectName};
print STDOUT "err\n";
	foreach my $error ( values(%problems) ) {
		foreach my $realerror (values(%$error)) {
			print STDERR "This is an Error!: $realerror\n";
			$realerror =~ qq/\"(.*)\"/;
			$info->{entry} = $cn;
			$info->{err} = $1;
			add_warn($cn, "problems_occured_with_entry", $info);
			#$cce->warn("problems_occured_with_entry", { entry => $cn , err => $1 } ); 
		}
	}

}

sub rewriteConfigFile {
	my $cce = shift;
	my $warningCount = @warnings;
	seek($fh, 0, 0);
	if ($maxCount) {
		print $fh ($count/$maxCount)*100 . "\n";
	} else {
		print $fh "100\n";
	}
	print $fh $statusMessage. "\n";	
	print $fh $warningCount . "\n";
	foreach my $warning (@warnings) {
		print $fh $warning->{cn} . "\n";
		print $fh $cce->msg_create($warning->{errorMsg}, $warning->{attrs}) . "\n";
	}
}

sub add_warn {
	my $cn = shift;
	my $errorMsg = shift;
	my $attrs = shift;
	push (@warnings, {cn => $cn, errorMsg => $errorMsg, attrs => $attrs});
}

sub add_group {
	my $entry = shift;
	my $cce = shift;

	my $success;
	my $badData;
	my @info;

	my $baseNs = {};
	my $diskNs = {};

	my $oid;

	$baseNs->{name} =  $entry->get('cn');
	$baseNs->{enabled} = $entry->get('enabled');
	my $members = $entry->get('memberuid');
	if (ref($members) eq 'ARRAY') {
		$baseNs->{members} = $cce->array_to_scalar(@$members);
	} else {
		$baseNs->{members} = $members;
	}
		
	if ($entry->get('diskquota')) {
		$diskNs->{quota} = $entry->get('diskquota');
	} else {
		$diskNs->{quota} = $defaultGroupQuota;
	}


	($success, $badData, @info) = $cce->create("Workgroup", $baseNs);

	if(! $success ) {
		return ($badData, @info);
	}
print STDOUT Dumper $badData;
	($success, $badData, @info) = $cce->set($cce->oid(), 'Disk', $diskNs);
print STDOUT Dumper $badData;
	if(! $success ) {
		return ($badData, @info);
	}

	return undef;
}

sub add_user {
	my $entry = shift;
	my $cce = shift;


	my $baseNs = {};
	my $emailNs = {};
	my $diskNs = {};

	my (@result);

	$baseNs->{name} = $entry->get("uid");
	#$baseNs->{md5_password} = $entry->get("userpassword");
	my $passwords = $entry->get("userpassword");
	if (ref ($passwords) eq 'ARRAY') {
		for my $each (@$passwords) {
			if ($each =~ /^{md5}/i) {
				$baseNs->{md5_password} = $each;
			}
		}
		$baseNs->{md5_password} =~ s/^{md5}(.*)$/$1/ig;
	} else {
		$baseNs->{md5_password} = $passwords;
		$baseNs->{md5_password} =~ s/^{md5}(.*)$/$1/ig;
	}

	my $locale=I18n::i18n_getSystemLocale($cce);
	my $tmpName;

	if(grep {/cn;lang_$locale/} $entry->attributes){
		$tmpName=$entry->get("cn;lang_$locale");
	}else{
		$tmpName=$entry->get("cn");
	}

	#if we still have an array, just grab the first element.
	if($tmpName=~/^&.*&$/){
		$tmpName=($cce->scalar_to_array($tmpName))[0];
	}
	
	my $newval;		# catchy name

	if($locale eq "ja"){
		$newval = Jcode::convert($tmpName,'sjis','utf8');
	}elsif($locale eq "zh" || $locale =~ /zh[-_]CN/i){
                $newval = `/bin/echo '$tmpName' | /usr/bin/iconv -t gb2312 -f utf8`;
                chomp $newval;
        }elsif($locale =~ /zh[-_]TW/i){
                $newval = `/bin/echo '$tmpName' | /usr/bin/iconv -t big5 -f utf8`;
                chomp $newval;
        }else{
                $newval = `/bin/echo '$tmpName' | /usr/bin/iconv -t iso-8859-1 -f utf8`;
                chomp $newval;
        }

	$baseNs->{fullName} = $newval;

	$baseNs->{systemAdministrator} = (
		$entry->get("systemAdministrator") eq "true" ||
				$entry->get("systemAdministrator") eq "1"
			? "1" : "");

	$emailNs->{forwardEmail} = $entry->get("forwardEmail");
	$emailNs->{vacationOn} = $entry->get("autoReplyOn");
	$emailNs->{vacationMsg} = $entry->get("autoReplyMessage");
	# We need to filter out the ending domain name and our own user name
	# from the mail list.
	$emailNs->{aliases} = filter_aliases(
		$cce,
		$entry->get("uid"),
		$entry->get("mail"));

	if ($entry->get("quota")) {
		$diskNs->{quota} = $entry->get("quota");
	} else {
		$diskNs->{quota} = $defaultUserQuota;
	}

	# @result = ($success, $badData{}, @info);
	# Default Null password
	$baseNs->{password} = "";

	my($success, $badData, @info) = $cce->create("User", $baseNs);
	if(! $success ) {
		#return \@info;
		return $badData;
	}

	my $userOID = $cce->oid();	# user OID just created

	#
	# Because handle_user.pl is lame, set md5_password manually
	# handle_user.pl will default to "*" for encrypted password
	# fields if you give it a null plaintext password.  (we do here!)
	#
	($success, $badData, @info) = $cce->set($userOID, "",
				{ md5_password => $baseNs->{md5_password} },
	);
	if(! $success) {
		return $badData;
	}

	($success, $badData, @info) = $cce->set($userOID, "Email", $emailNs);
	if(! $success ) {
		return $badData;
	}

	($success, $badData, @info) = $cce->set($userOID, "Disk", $diskNs);
	if(! $success ) {
		return $badData;
	}

	return undef;
}

sub filter_aliases {
	my $cce = shift;
	my $name = shift;
	my $aliases = shift;

	if( ref($aliases) ne 'ARRAY' ) {
		# Well, we only have one aemail adress, and thus no aliases.
		# so we can ignore this whole thing.
		return "";
	}

	my @aliases = @{ $aliases };

	# Filter out entries that have our username in them and remove
	# the domainname from the end of email aliaes.
	@aliases = grep( $_ =~ s/\@.*$//, @aliases);
	@aliases = grep( !/^$name$/, @aliases);

	return \@aliases;
}

package ScalarLdapEntry;

sub ref_to_scal {
	my $self = shift;
	my $ref = shift;
	if(! defined( $ref ) ) {
		return "";
	} elsif( ref($ref) ne 'ARRAY' ) {
		return $ref;
	} elsif ( $#$ref == 0 ) {
		return ${$ref}[0];
	} elsif ( $#$ref == -1 ) {
		return "";
	} else {
		# CCE.pm deal with array refs itself.
		# we just have to filter out things that shouldn't
		# be array refs.
		return $ref;
	}
}

sub get {
	my $self = shift;
	my $ret = $self->Net::LDAP::Entry::get(@_);
	return $self->ref_to_scal( $ret );
}

sub attributes {
	my $self=shift;
	return $self->Net::LDAP::Entry::attributes();

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
