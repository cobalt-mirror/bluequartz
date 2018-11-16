#!/usr/bin/perl -I /usr/sausalito/perl
#
# $Id: generateReports.pl
# 
# Creates new statistics files based on a date range 
#

use Time::Local;
use File::Copy;
use CCE;
use Base::HomeDir qw(homedir_get_group_dir);

my $cce = new CCE;
$cce->connectfd();
my @sysoids = $cce->find('System');
my ($ok, $sitestats) = $cce->get($sysoids[0], 'Sitestats');

my $DEBUG = 0;
$DEBUG && warn $0.' '.`date`;

my $analog  = '/usr/bin/analog';
if (-f '/usr/bin/analogbx') {
    $analog = '/usr/bin/analogbx';
}

my $configFn    = '/etc/analog.cfg';
my $configTmpl  = $configFn.'.tmpl';
my $updatestats = '/usr/sausalito/handlers/base/sitestats/updatestats.sh';

my $lastTs = timelocal(0, 0, 0, $sitestats->{endDay}, 
               $sitestats->{endMonth} - 1, $sitestats->{endYear});
my $firstTs = timelocal(0, 0, 0, $sitestats->{startDay}, 
            $sitestats->{startMonth} - 1, $sitestats->{startYear});
my $nextTs;

# array("mail", "web", "ftp", "net");
my @logtypes = ($sitestats->{report});

my $today = time();

# Grab the latest numbers, trigger updatestats.sh
$DEBUG && warn "Last Ts: $lastTs, Todays: $today\n";

my ($type, $ret);
foreach $type (@logtypes) {
    # Get the latest numbers, if the end date is later than today
    if ($lastTs >= ($today - 86400)) {
        $DEBUG && warn "$updatestats invoked same-day status update\n";
        system($updatestats, $type, '>/dev/null 2>&1');
    }

    my $thisConfigFn = '/var/tmp/analog.cfg.' . rand($$);
    while (-e $thisConfigFn) {
        $thisConfigFn = '/var/tmp/analog.cfg.' . rand($$);
    }
    $DEBUG && warn "Using temporary analog config file $thisConfigFn";

    copy($configTmpl, $thisConfigFn) || die "Could not copy $configTmpl to $thisConfigFn: $!";
    open(CFG, ">>$thisConfigFn") || die "Could not write staging analog config file $thisConfigFn: $!";

    # Need to append CACHEFILE references for each day there was a cachefile
    $nextTs = $firstTs;
    my $basedir = homedir_get_group_dir($sitestats->{site});
    if ($sitestats->{site} eq 'server') {
        $basedir = '/home/.sites/server';
    }

    while ($nextTs <= $lastTs) {
        my @time = localtime($nextTs);
        $time[5] += 1900;
        $time[4]++;
    
        $DEBUG && print STDERR "DATE: $time[5]/$time[4]/$time[3]\n";
        # Monthly cache
        my $cachefile = $basedir . '/logs/' .
            $time[5]. '/' . $time[4] . '/' . $type . '.cache';

        if (-r $cachefile) {
            print CFG "CACHEFILE $cachefile\n";
            $DEBUG && warn "found: CACHEFILE $cachefile\n";
        }
        
        # Daily cache
        $cachefile = $basedir . '/logs/' .
            $time[5] . '/' . $time[4] . '/' . $time[3] . '/' .
            $type . '.cache';
            
        if (-r $cachefile) {
            print CFG "CACHEFILE $cachefile\n";
            $DEBUG && warn "found: CACHEFILE $cachefile\n";
        } else {
            $DEBUG && warn "Did not find CACHEFILE: $cachefile\n";
        }

        $nextTs += 86400; # Increment one day
    }
        
    close(CFG);
    
    if ($DEBUG > 1) {
        warn "Analog config: \n";
        warn `cat $thisConfigFn`."\nFIN cat\n"; 
    }

    my ($pid, $gid);
    my $perms = 0644;
    if ($sitestats->{site} eq 'server') {
        $pid = (getpwnam('admin'))[2];
        $gid = (getgrnam('users'))[2];
    } else {
        my ($void) = $cce->find('Vsite',
                    { 'name' => $sitestats->{site} });
        my($ok, $vsite_stats) = $cce->get($void, 'SiteStats');

        $DEBUG && warn "Site oid: $void\n";

        # load site config
        $pid = (getpwnam($vsite_stats->{owner}))[2];
        $gid = (getgrnam($sitestats->{site}))[2];
        $DEBUG && warn "Pid, gid: $pid, $gid\n";
        $perms = 0664;
    }

    my $outfile = $basedir . "/logs/$type.stats";
    my $analogCmd = "$analog -G +g$thisConfigFn > $outfile";
    $ret = system($analogCmd);

    $DEBUG && warn "Analog command \"$analogCmd\" returned $ret\n";
    chown($pid, $gid, $outfile);
    chmod($perms, $outfile);
    
    $DEBUG || unlink($thisConfigFn);    
    if ($ret) {
        last;
    }
}

if ($ret) {
    $cce->bye('FAIL');
} else {
    $cce->bye('SUCCESS');
}
exit $ret;

# 
# Copyright (c) 2015-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2018 Team BlueOnyx, BLUEONYX.IT
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