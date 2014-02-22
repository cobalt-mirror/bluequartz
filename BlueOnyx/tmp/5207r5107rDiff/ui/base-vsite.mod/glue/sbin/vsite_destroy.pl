#!/usr/bin/perl -I/usr/sausalito/perl
#
# $Id: vsite_destroy.pl,v 1.1.2.1 2002/02/28 03:08:03 pbaltz Exp $
# Copyright 2002 Sun Microsystems, Inc.  All rights reserved.
#
# Actually removes all site users and the site itself updating the
# status as each user is destroyed.
#
# Usage:  vsite_destroy.pl <site name> <page to redirect to when finished>
#

use strict;
use CCE;
use FileHandle;
use File::Path;

# sane umask
umask(002);

# figure out where the status should be written
my $status_file = '';
my $redirect = $ARGV[1];

open(CFG, "/usr/sausalito/ui/conf/ui.cfg");
while (my $line = <CFG>) {
	if ($line =~ /^statusDir=(\S+)/) {
		my $dir = $1;
		$status_file = "$dir/remove$ARGV[0]";
		if (! -d $dir) {
			mkpath($dir, 0, 0755);
			chown(scalar(getpwnam('httpd')),
				scalar(getgrnam('httpd')), $dir);
			system('/bin/touch', $status_file);
			chown(scalar(getpwnam('httpd')),
				scalar(getgrnam('httpd')), $status_file);
		}
		last;
	}
}
close(CFG);

&update_status({
		'task' => '[[base-vsite.removingUsers]]',
		'progress' => 0
	       });

my $cce = new CCE;
$cce->connectuds();

$cce->authkey($ENV{'CCE_USERNAME'}, $ENV{'CCE_SESSIONID'});

# make sure the passed site name is valid
my ($site_oid) = $cce->find('Vsite', { 'name' => $ARGV[0] });
if (!$site_oid) {
	&update_status({
				'done' => 1,
				'error' => "[[base-vsite.noSuchSite,site=$ARGV[0]]]"
		       });
	$cce->bye();
	exit(1);
}

# find all the users to destroy
my @users = $cce->find('User', { 'site' => $ARGV[0] });

my $processed = 0;
my $total = scalar(@users) + 1;

for my $user (@users) {
	# override file check since this is a site destroy
	$cce->set($user, '', { 'noFileCheck' => 1 });
	my ($ok, @info) = $cce->destroy($user);
	if (!$ok) {
		# failed. update status and exit.
		&update_status({
					'done' => 1,
					'error' => &grab_error(@info)
			       });
		$cce->bye();
		exit(1);
	} else {
		$processed++;
		&update_status({
				'progress' => int(100 * ($processed / $total)),
				'task' => '[[base-vsite.removingUsers]]'
			       });
	}
}

# now destroy the site
&update_status({
		'progress' => int(100 * ($processed / $total)),
		'task' => '[[base-vsite.removingSite]]'
	       });
my ($ok, @info) = $cce->destroy($site_oid);
my $exit = 0;
if ($ok) {
	&update_status({ 'done' => 1 });
} else {
	&update_status({
			'done' => 1,
			'error' => &grab_error(@info)
		       });
	$exit = 1;
}

$cce->bye();
exit($exit);

sub update_status
{
	my $status = shift;

	# open file if necessary
	my $status_fh = new FileHandle(">$status_file");
	$status_fh->autoflush();
	if ($status->{done}) {
		if (exists($status->{error})) {
			print $status_fh "title: [[base-vsite.removeFailed]]\n";
			print $status_fh "message: $status->{error}\n";
			print $status_fh "isNoRefresh: true\n";
			print $status_fh "backUrl: $redirect\n";
		} else {
			print $status_fh "redirectUrl: $redirect\n";
		}
	} else {
		print $status_fh "title: [[base-vsite.deletingSite]]\n";
		print $status_fh "message: $status->{task}\n";
		print $status_fh "progress: $status->{progress}\n";
	}

	$status_fh->close();
}

sub grab_error
{
	my @info = @_;

	for my $msg (@info) {
		if (($msg =~ /^305 /) &&
		    ($msg =~ /WARN\s+"(\[\[.+?\]\])"\s*$/)) {
			return $1;
		}
	}

	return '';
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
