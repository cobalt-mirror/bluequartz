#!/usr/bin/perl

use lib qw(/usr/sausalito/perl);
use Base::HomeDir qw(homedir_get_group_dir);
use Data::Dumper;
use Getopt::Long;
use strict;
use CCE;
use Quota;

my $site = '';
my $sort = '';
my $descending = '';
my $help = '';
my $users_only = 1;
my $sites_only = '';

GetOptions('site=s' => \$site, 'sort=s' => \$sort, 
	   'descending' => \$descending, 'ascending' => sub { $descending = 0 },
	   'help' => \$help, 'users' => sub {$users_only = 1; $sites_only = 0}, 
	   'sites' => sub { $sites_only = 1; $users_only = 0 });

if ($help) {
    print "Usage: get_quotas.pl [ --users ] [ --sites ] \n\t\t\t[ --sort=type ] [ --site=name ] [ --descending ] [ --ascending] [ --help ]\n";
    print " --users\t Get quotas for users. Default.\n";
    print " --sites\t Get quotas for sites.\n";
    print " --sort=type\t Type is one of 'quota', 'usage', 'name'. \n\t\t\tIf none are specified, defaults to 'name'.\n";
    print " --site=name\t Return the quotas for users that are on site called 'name'.\n\t\t\tName is the 'name' property is CCE of the site you wish to find. \n\t\t\tUsually, it's 'siteN' where N is some number. \n\t\t\tIf this option is not present, \n\t\t\tthe script will return all users on all sites\n";
    print " --ascending\t Sort in ascending order. Default.\n";
    print " --descending\t Sort in descending order.\n";
    exit;
}

my (@items, %quotas) = ();

if ($sites_only) {
    # return site quotas
    @items = sites();
    %quotas = siteusage();
} elsif ($users_only && $site) {
    # return user quotas on site $site
    @items = site_users($site);
    %quotas = userusage();
} elsif ($users_only && !$site) {
    # return user quotas on all sites
    @items = all_users();
    %quotas = userusage();
}

my (@results) = ();

foreach my $item (@items) {
    push @results, [ $item, $quotas{$item}{used},  $quotas{$item}{quota} ];
}

if ($sort eq "usage") {
    @results = sort { @{$a}[1] <=> @{$b}[1] } @results;
} elsif ($sort eq "quota") {
    @results = sort { if (@{$a}[2] == 0) { return 1; } # no quota = unlimited
		    elsif (@{$b}[2] == 0) { return -1; } 
		    else { return (@{$a}[2] <=> @{$b}[2]) } } @results;
} else {
    # sort == name
    @results = sort { @{$a}[0] cmp @{$b}[0] } @results;
}


if ($descending) {
    @results = reverse(@results);
}

my @output = ();
foreach my $user (@results) {
    print join ("\t", @{$user});
    print "\n";
}

# arg $site="site1" find all CCE users in the site $site
sub site_users {
    # if site is passed in, find all users on that site 
    my $site = shift;
    my $site_ok;

    my $cce = new CCE;
    $cce->connectuds();
    my ($oid) = $cce->find('Vsite', { 'name' => $site });
    if (!$oid) {
	print STDERR "couldn't find site $site in CCE\n";
	return;
    }
    ($site_ok, $site) = $cce->get($oid);

    my $sitedir = $site->{basedir};
    
    opendir(USERS, "$sitedir/users");
    my @users = grep !/^\./, readdir(USERS);
    closedir(USERS);

    return @users;
  
}

# find all CCE users
sub all_users {
    my ($name, $null, $all_gid, $user_gid, $dir);

    # all CCE users are in the "users" group
    ($name, $null, $all_gid) = getgrnam('users');
    # now we do getpwent() and only save users who are in the "users" group
    my @all_users = ();
    setpwent();
    while (($name, $null, $null, $user_gid, $null, $null, $null, $dir) = getpwent()) {
	if ($user_gid != $all_gid) {
	    next;
	}

	if ($name eq 'games') {
	    next;
	}
	push @all_users, $name;
    }
    endpwent();
    return @all_users;
}

sub sites {
    my @sites = ();

    # find all disks
    my $cce = new CCE;
    $cce->connectuds();
    my (@disks) = $cce->find('Disk', { 'isHomePartition' => 1 });
    
    # find all mountpoints
    my @mounts = ();
    foreach my $disk (@disks) {
	my ($ok, $obj) = $cce->get($disk);
	push @mounts, $obj->{mountPoint};
    }

    # find all numeric hashes
    my @hashdirs = ();
    foreach my $mount (@mounts) {
	opendir(SITEDIR, "$mount/.sites");
	my @dirs = map { "$mount/.sites/$_" } grep /^\d+$/, readdir(SITEDIR);
	push @hashdirs, @dirs;
	close(SITEDIR);
    }

    # find all dirs in all hashes
    my @some_sites = ();
    foreach my $hash (@hashdirs) {
	opendir(HASH, $hash);
	@some_sites = grep !/^\./, readdir(HASH);
	close(HASH);

	push @sites, @some_sites;
    }

    $cce->bye();
    return @sites;
}


# this is almost a cut and paste of sites() found below.
# this was done at GM time and so I couldn't reorg things.
sub siteusage {
    my %hash = ();

    # find all disks
    my $cce = new CCE;
    $cce->connectuds();
    my (@disks) = $cce->find('Disk', { 'isHomePartition' => 1 });

    # find all mountpoints
    my @mounts = ();
    foreach my $disk (@disks) {
	my ($ok, $obj) = $cce->get($disk);
	push @mounts, $obj->{mountPoint};
    }

    # this relies on Alpine's hashing scheme. if the hashing scheme changes
    # or this is installed on another product, then this will need to change.
    # find all numeric hashes
    my @hashdirs = ();
    foreach my $mount (@mounts) {
	opendir(SITEDIR, "$mount/.sites");
	my @dirs = map { "$mount/.sites/$_" } grep /^\d+$/, readdir(SITEDIR);
	push @hashdirs, @dirs;
	close(SITEDIR);
    }

    # find all dirs in all hashes
    my @some_sites = ();
    foreach my $hash (@hashdirs) {
	opendir(HASH, $hash);
	@some_sites = grep !/^\./, readdir(HASH);
	close(HASH);

	# lookup dev
	my $dev = Quota::getqcarg($hash);
	my $is_group = 1;

	foreach my $site (@some_sites) {
	    # lookup gid
	    my ($name, $null, $gid) = getgrnam($site);

	    # do query
	    my ($used, $quota) = Quota::query($dev, $gid, $is_group);

	    $hash{$name}{used} = $used;
	    $hash{$name}{quota} = $quota;

	}
    }

    return %hash;
}

sub userusage {
    my %hash = ();
    my ($name, $null, $uid, $user_gid, $all_gid, $dir);

    # all CCE users are in the "users" group
    ($name, $null, $all_gid) = getgrnam('users');

    # now we do getpwent() and only lookup users who are in the "users" group
    setpwent();
    while (($name, $null, $uid, $user_gid, $null, $null, $null, $dir) = getpwent()) {
	if ($user_gid != $all_gid) {
	    next;
	}

	my $dev = Quota::getqcarg($dir);
	my ($used, $quota) = Quota::query($dev, $uid);

	$hash{$name}{used} = $used;
	$hash{$name}{quota} = $quota;
    }
    endpwent();
    return %hash;
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
