#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: java_load_war.pl,v 1.14.2.4 2002/02/20 20:32:42 naroori Exp $
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
# 
# Downloads, unpacks & installs data in .war format
# Complements root-level Apache & Tomcat configurators
#

use strict;
use vars qw($opt_n $opt_g $opt_f $opt_u $opt_h $opt_c $opt_t);
use CCE;
use Getopt::Std;
getopts('hcf:u:n:t:g:'); 

# Global definitions
my $jar_jar = '/usr/bin/jar'; # aka The jar Binary
my $wget_bin = '/usr/bin/wget'; # No Appname (-A) assignment
my $mkdir_bin = '/bin/mkdir'; 
my $filetest_bin = '/usr/bin/file';
my $war_filetest_match = 'Zip archive data';
my $redirect = '> /dev/null 2>&1'; # bash 
my ($user, $group) = ($opt_n, $opt_g); # simply to make the code more legible
my $DEBUG = 0;

$DEBUG && open(STDERR, ">>/tmp/java_load_war");
$DEBUG && warn `date`."$0\n";
$DEBUG && warn `ps auxwwww`;
$DEBUG && warn "CCE_SESSIONID: ".$ENV{'CCE_SESSIONID'}."\n";

umask(002);

my($uid, $gid) =  ((getpwnam($user))[2], (getgrnam($group))[2]);

# Must specify user, group, and either -f or -u
# FIXME: i18n
unless (($uid && $gid) && ($opt_f || $opt_u))
{
	$DEBUG && warn "Help text.  Exiting\n";
	print <<EOF;
Java War File Deployer.

Usage: 
 $0 -n <username> -g <groupname> \\
  [-u <url>] [-f <filepath>] [-h] [t <target>] [-c]

 Either -u or -f must be specified
 <filepath> is the path & filename of a local .war archive
 -h flag targets a user web, not the group (site) web 
 <target> is the path from the site web root for extraction
 -c deletes the archive file after extraction (cleanup)

Sample syntax for loading example.war on site1 at site1/web/examples: 

 $0 \\
 -n admin -g site1 -f /home/sites/140/site1/web/example.war \\
 -t examples -c

EOF
	exit(0);
}

die "Can not find the UID and GID for the specified user and group\n" 
	unless ($uid && $gid);
$DEBUG && warn "UID: $uid, GID: $gid";

my $cce = new CCE;
$cce->connectuds();
$cce->authkey($ENV{'CCE_USERNAME'}, $ENV{'CCE_SESSIONID'});

# Find the Vsite object, then the Java namespace for update status control
my(@oids) = $cce->find('Vsite', {'name' => $group});
die "Could not find site with group: $group\n" unless($oids[0]);

my($site_oid) = $oids[0]; # To make the script more readable
my($ok, $jsite) = $cce->get($site_oid, 'Java');

# get the vsite so the we know where the site directory is located
my($thisok, $vsite) = $cce->get($site_oid);

$ok = &uiUpdate(5, '[[base-java.loadingWar]]');

my $unqualified = $opt_u ? $opt_u : $opt_f;
$unqualified =~ s/^.+\/([^\/]+)[\n\r]*$/$1/; # strip url/path

my $web_home = $vsite->{basedir} . '/web/';
if($opt_h)
{
	# use user home dir/web
	$web_home = (getpwnam($user))[5].'/web/';
}
my $local_file = $opt_u ? $web_home . $unqualified : $opt_f;

# seuid/egid, we've already authenticated to CCE
# now change the (e)uid/(e)gid
$( = $) = $gid;
$< = $> = $uid;

if($opt_u)
{
	# use BlueLinQ's proxy servers if defined/installed.  We could move the 
	# proxy server settings from BL to Control Panel->Network TCP/IP alongside 
	# the gateway addr.
	my ($sysoid) = $cce->find('System');
	my ($dont_care, $swobj) = $cce->get($sysoid, 'SWUpdate');
	$ENV{http_proxy} = $swobj->{httpProxy} if $swobj->{httpProxy};
	$ENV{ftp_proxy} = $swobj->{ftpProxy} if $swobj->{ftpProxy};
	$DEBUG && warn "Using http, ftp proxies: $ENV{http_proxy},$ENV{ftp_proxy}\n";

	# download to $local_file -- ripped out of BL
	my $failure = 0;
    
	unlink($local_file);
	die "Security Notice: Could not unlink $local_file, aborting war install." if (-e $local_file);
	$DEBUG && warn "Invoking wget:\n$wget_bin -t 2 -T 60 \"$opt_u\" -O $local_file \n";
	my $ret = open(WGET, "$wget_bin -t 2 -T 60 \"$opt_u\" -O $local_file 2>&1 |");
	unless($ret) 
	{
		&uiUpdate(100, '[[base-java.downloadFailure]]', 'fail');
		&bye(1);
	}
	while (<WGET>) 
	{
		$DEBUG && warn "Wget Output: $_";
		if (/Host\s+not\s+found/i) {
			&uiUpdate(100, '[[base-java.hostNotFound]]', 'fail');
			$failure = 1;
		} elsif (/404\s+Not\s+Found/i) {
			&uiUpdate(100, '[[base-java.fileNotFound]]', 'fail');
			$failure = 1;
		} elsif (/refused/i) {
			&uiUpdate(100, '[[base-java.connectionRefused]]', 'fail');
			$failure = 1;
		} elsif (/\[\s*(\d+)\%\]/) {
			my $pct = ceil($1);
			&uiUpdate($pct, "[[base-java.dlPercent,percent=$pct]]");
		}
	}
	close(WGET);
    
	# we didn't get a file. error out.
	unless ((-f $local_file) && (-s $local_file)) {
		$DEBUG && warn "missing local downloaded file: $local_file\n";
		&uiUpdate(100, '[[base-java.downloadFailure]]', 'fail') unless ($failure);
		$failure = 1; # could already be at this state
	}
	&bye(1) if ($failure);
}

# Verify file
$DEBUG && warn "Running test:\n$filetest_bin $local_file $redirect\n";
my $test = `$filetest_bin $local_file 2>&1`;
unless($test =~ /$war_filetest_match/)
{
	$DEBUG && warn "File test: $test";
	&uiUpdate(100, '[[base-java.unknownFileFormat]]', 'fail');
	&bye(1);
}

# Unpack & install the archive

# Create the target subdirectory of the web root...
chdir($web_home);
$DEBUG && warn "Untainted -t: $opt_t\n";
$opt_t =~ s/^[\.\/]//g; # Strip leading / and periods
$DEBUG && warn "Tainted -t: $opt_t\n";

#Is the user is thinking of installing to his/her home ?
if ($opt_t =~ /^~/) {
    $DEBUG && warn("control in my code");
	&uiUpdate(100, '[[base-java.notToUserHome]]', 'fail');
	&bye(1);
}

# use system call for mkdir's parent creation functionality
system($mkdir_bin, '-p', $web_home.$opt_t) if ($opt_t);
$DEBUG && warn($mkdir_bin, '-p', $web_home.$opt_t) if ($opt_t);

unless(chdir($web_home.$opt_t))
{
	&uiUpdate(100, '[[base-java.installFailure]]', 'couldNotCreateTarget');
	&fail(1);
}

#remove the old entries in case we are over-writing with a new version
system("source /etc/profile; /bin/rm -rf *");

my $ret = system("source /etc/profile; $jar_jar -xf $local_file $redirect");
if($ret)
{
	&uiUpdate(100, '[[base-java.jarFailure]]', 'fail');
	&fail(1);
}

# Success!  Create the JavaWar object in CCE, but first check if it exists.
# (Note: If an existing JavaWar is being uploaded, it means the user is
#just merely upgrading to a newer version of it; it does not make sense to
#create a newer JavaWar object. Just to trigger the handlers, we destroy
#the existing JavaWar objects and recreate the same.)

my @existing_war_oids = $cce->find('JavaWar', { 
	'name' => $opt_t,
	'group' => $group,
	'user' => $user,
	});

my $tmp_oid; 
foreach $tmp_oid (@existing_war_oids) {
    $cce->destroy($tmp_oid);
}

my($status, $war_oid) = $cce->create('JavaWar', { 
	'war' => $opt_t,
	'name' => $opt_t,
	'group' => $group,
	'user' => $user,
	'enabled' => 1,
	});
$DEBUG && warn "Create JavaWar... Status: $status, OID: $war_oid\n";
&uiUpdate(100, '[[base-java.installedWar]]', 'success');

# optional cleanup
if($opt_c)
{
	unlink($local_file);
}

&bye(0);

# FIN



sub uiReport
{
	my($ok, $jsite) = $cce->get($site_oid, 'Java');
	$DEBUG && warn "message: ".$jsite->{message}.", uiCMD: ".$jsite->{uiCMD}.
		", progress: ".$jsite->{progress};
}

sub uiUpdate
{
	my @args = ('progress', 'message', 'uiCMD');
	my ($argument, %changes);

	foreach $argument (@args)
	{
		my $x = shift;
		$changes{$argument} = $x if ($x); # don't define null elements
		$DEBUG && warn "uiUpdate param $argument => $x\n";
	}
	
	my $ok = $cce->set($site_oid, 'Java', \%changes); # register we've started
	$DEBUG && warn "CCE update $site_oid, Java: $ok";

	return $ok;
}

sub bye
# pass the exit code as the sole argument, bash-style not perl-style.
# as in bye(1) fails, bye(0) succeeds
{
	my $status = shift;
	my $cce_wave = $status ? 'FAILURE' : 'SUCCESS'; 
	$cce->bye($cce_wave); 
	exit($status);
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
