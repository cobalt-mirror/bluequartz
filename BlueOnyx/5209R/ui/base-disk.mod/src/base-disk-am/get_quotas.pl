#!/usr/bin/perl
# get_quotas.pl

use lib qw(/usr/sausalito/perl);
use Base::HomeDir qw(homedir_get_group_dir);
use Data::Dumper;
use Getopt::Long;
#use strict;
use CCE;
use Quota;
use Unix::PasswdFile;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $site = '';
my $sort = '';
my $descending = '';
my $help = '';
my $users_only = 1;
my $sites_only = '';
my $pw = new Unix::PasswdFile "/etc/passwd";

GetOptions( 'site=s' => \$site, 
            'sort=s' => \$sort, 
            'descending' => \$descending, 
            'ascending' => sub { $descending = 0 },
            'help' => \$help, 
            'users' => sub {$users_only = 1; $sites_only = 0}, 
            'sites' => sub { $sites_only = 1; $users_only = 0 }
            );

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

    # Rewritten from scratch by mstauber

    my ($name, $null, $all_gid, $user_gid, $dir);

    # all CCE users are NO LONGER in the "users" group - so we have do do it differently:
    my @all_users = ();

    foreach $name ($pw->users) {
        my $uid = $pw->uid($name);
        my $user_gid = $pw->gid($name);
        my $dir = $pw->home($name);
        my @groupworkaround = split(/\//, $dir);

        if ((($uid >= 500)&&($uid != 65534)) && ($groupworkaround[5] ne "logs")) {
            push @all_users, $name;
        }
    }
    return @all_users;
}

sub sites {
    my @sites = ();

    # find all disks
    my $cce = new CCE;
    $cce->connectuds();

    my (@AllDisks) = $cce->find('Disk', { 'internal' => 1 });
    my $num_of_disks = @AllDisks;
    &debug_msg("Found " . $num_of_disks . " internal disk(s). \n");

    my (@disks) = $cce->find('Disk', { 'isHomePartition' => 1 });

    # find all mountpoints
    my @mounts = ();
    foreach my $disk (@disks) {
        my ($ok, $obj) = $cce->get($disk);
        push @mounts, $obj->{mountPoint};
    }

    # find all numeric hashes
#    my @hashdirs = ();
#    foreach my $mount (@mounts) {
#        &debug_msg("Processing disk for mountpoint " . $mount . "\n");
#        if (($num_of_disks == "0") && ($mount eq "/")) {
#            $mount = '/home';
#            &debug_msg("As this is the only disk, we look for the Vsites on " . $mount . " instead.\n");
#        }
#        opendir(SITEDIR, "$mount/.sites");
#        my @dirs = map { "$mount/.sites/$_" } grep /^\d+$/, readdir(SITEDIR);
#        push @hashdirs, @dirs;
#        close(SITEDIR);
#    }
#
# This code above kinda sucks, as it may not find the Vsites under $mount/.sites/ if /home is a 
# subdirectory of the partition "/". As we currently only support Vsites on /home/, we can as well
# strip this down and just search /home and be done with it. See below:

# Replacement start
    $mount = '/home';
    &debug_msg("As this is the only disk, we look for the Vsites on " . $mount . " instead.\n");
    opendir(SITEDIR, "$mount/.sites");
    my @dirs = map { "$mount/.sites/$_" } grep /^\d+$/, readdir(SITEDIR);
    push @hashdirs, @dirs;
    close(SITEDIR);
# Replacement end


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

    my (@AllDisks) = $cce->find('Disk', { 'internal' => 1 });
    my $num_of_disks = @AllDisks;
    &debug_msg("Found " . $num_of_disks . " internal disk(s). \n");

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
#    foreach my $mount (@mounts) {
#        &debug_msg("Processing disk for mountpoint " . $mount . "\n");
#        if (($num_of_disks == "1") && ($mount eq "/")) {
#            $mount = '/home';
#            &debug_msg("As this is the only disk, we look for the Vsites on " . $mount . " instead.\n");
#        }
#        opendir(SITEDIR, "$mount/.sites");
#        my @dirs = map { "$mount/.sites/$_" } grep /^\d+$/, readdir(SITEDIR);
#        push @hashdirs, @dirs;
#        close(SITEDIR);
#    }
#
# This code above kinda sucks, as it may not find the Vsites under $mount/.sites/ if /home is a 
# subdirectory of the partition "/". As we currently only support Vsites on /home/, we can as well
# strip this down and just search /home and be done with it. See below:

### replacement start
        $mount = '/home';
        &debug_msg("As this is the only disk, we look for the Vsites on " . $mount . " instead.\n");
        opendir(SITEDIR, "$mount/.sites");
        my @dirs = map { "$mount/.sites/$_" } grep /^\d+$/, readdir(SITEDIR);
        push @hashdirs, @dirs;
        close(SITEDIR);
### replacement end

    # find all dirs in all hashes
    my @some_sites = ();
    foreach my $hash (@hashdirs) {
        opendir(HASH, $hash);
        &debug_msg("Processing Hash for " . $hash . "\n");
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
    
    # Rewritten by mstauber

    my %hash = ();
    my ($name, $null, $uid, $user_gid, $all_gid, $dir);

    # all CCE users are NO LONGER in the "users" group - so we have to do it differently:

    foreach $name ($pw->users) {
        my $uid = $pw->uid($name);
        my $user_gid = $pw->gid($name);
        my $dir = $pw->home($name);
        my @groupworkaround = split(/\//, $dir);

        # Ignore all users with an UID below 500 and also the SITEXX-logs users:
        if (($uid < 500)||($uid == 65534)) {
            next;
        }
        elsif ($groupworkaround[5] eq "logs") {
            next;
        }
        elsif ($name eq "nfsnobody") {
            next;
        }
        else {
            my $dev = Quota::getqcarg($dir);
            my ($used, $quota) = Quota::query($dev, $uid);

            $hash{$name}{used} = $used;
            $hash{$name}{quota} = $quota;
        }
    }
    return %hash;
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

# 
# Copyright (c) 2014-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2017 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
#