#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: sitestats_purgeOmatic.pl
# Will DeHaan <null@sun.com>
#
# Scan Virtual Site statistics to purge outdated entries and 
# consolidate daily logs to monthly should the site be configured
# to do so.

use File::Copy;
use CCE;

if (-f '/usr/bin/analogbx') {
    $analog      = '/usr/bin/analogbx';
}
else {
    $analog      = '/usr/bin/analog';
}
my $configFn    = '/etc/analog.cfg';
my $configTmpl  = $configFn.'.tmpl';
my @types   = ('web', 'mail', 'net', 'ftp');

my $DEBUG = 0;
$DEBUG && open(STDERR, ">>/tmp/purgeOmatic");
$DEBUG && warn `date`;

my $now = time();

my $cce = new CCE;
$cce->connectuds(); #  || die "Could not connect to CCEd";

my @site_oids = $cce->find('Vsite', {'SiteStats.enabled' => '1'});

$DEBUG && warn "Find Vsite, SiteStats.enabled: ".$#site_oids."\n";

my $oid;
foreach $oid (@site_oids) 
{
    $DEBUG && warn "Processing site oid: $oid\n";

    # load site data
    my ($ok, $vsite) = $cce->get($oid, 'SiteStats');
    next unless ($ok);
    my ($a_ok, $vsitebase) = $cce->get($oid);

    $DEBUG && warn "Loaded site data for ".$vsitebase->{fqdn}.' '.$vsite->{purge}."\n";

    # process expiration date
    trimtree($vsitebase->{basedir}, $vsite->{purge}) if ($vsite->{purge});

    # find daily log files and consolidate to monthly when necessary
    &consolidate_monthly($vsitebase->{basedir}) if($vsite->{consolidate})
}
$cce->bye('SUCCESS');
exit 0;

# Subs

sub wipe
# rm -rf <directoryname>
# returns 1 on success
{
    my $dir = shift;
    return 0 unless (-d $dir);
    
    # Big safety, delete only numeric-ended directories
    return 0 unless ($dir =~ /\d$/);

    $DEBUG && warn "About to /bin/rm -rf $dir\n";

    system('/bin/rm', '-rf', $dir) && return 0;

    $DEBUG && warn "...rm ok\n";

    return 1;
}

sub trimtree
# spiders through the log directory structure and 
# deletes expired directories 
{
    my($dir, $expire) = @_;
    $DEBUG && warn "trimtree invoked with $dir, $expire\n";

    $expire = $expire*86400; # days to seconds
    $expire = $now - $expire; # delta to epoch
    my($cutyear, $cutmonth, $cutday) = 
        (localtime($expire))[5,4,3];
    $cutyear += 1900;
    $cutmonth++;

    $DEBUG && warn "Cutoffs for Vsite oid $oid are: $cutyear, $cutmonth, $cutday\n";

    # find log files that are older than our expiration date
    opendir(LOGROOT, $dir.'/logs') || return 0;
    while($_ = readdir(LOGROOT))
    {
        next unless (/^\d+$/ && (-d $dir.'/logs/'.$_));
        $DEBUG && warn "Scanning logroot, found: $_";
        
        if($_ < $cutyear)
        {
            &wipe($dir.'/logs/'.$_);
        }
        elsif ($_ eq $cutyear)
        {
            # per-month test
            my $yeardir = $dir.'/logs/'.$_;

            $DEBUG && warn "Scanning directory $yeardir\n";

            opendir(MONTHLY, $yeardir) || return 0;
            while($_ = readdir(MONTHLY))
            {
                next unless (/^\d+$/ && (-d $yeardir.'/'.$_));
                my $monthdir = $yeardir.'/'.$_;

                if ($_ < $cutmonth) 
                {
                    &wipe($monthdir) 
                }
                elsif ($_ eq $cutmonth) 
                {
                    $DEBUG && warn "Scanning directory $monthdir\n";
                    opendir(DAILY, $monthdir) || return 0;
                    while($_ = readdir(DAILY)) 
                    {
                        next unless (/^\d+$/ && (-d $monthdir.'/'.$_));
                        &wipe($monthdir.'/'.$_) if ($_ < $cutday);
                    }
                    closedir(DAILY);
                } 
            }
            closedir(MONTHLY);
        }
    }
    closedir(LOGROOT);

    # end-of-spider, success
    $DEBUG && warn "trimtree ok; return 1\n";
    return 1;
}

sub consolidate_monthly
# Arguments: site base director
# Return: boolean success/fail
{
    my $dir = shift;
    
    my ($year, $month) = (localtime())[5,4];
    $year += 1900; $month++;
    $DEBUG && warn "consolidate_monthly year, month: $year, $month\n";

    # find monthly log folders that are older than the current month
    opendir(LOGROOT, $dir.'/logs') || return 0;
    while($_ = readdir(LOGROOT))
    {
        next unless (/^\d+$/ && (-d $dir.'/logs/'.$_));
        $DEBUG && warn "Scanning logroot, found: $_";

        # per-month test
        my $yeardir = $dir.'/logs/'.$_;

        $DEBUG && warn "Scanning directory $yeardir\n";

        opendir(YEAR, $yeardir) || return 0;
        while($_ = readdir(YEAR))
        {
            next if (/\.cache$/); # Alraedy consolidated
            next unless (/^\d+$/ && (-d $yeardir.'/'.$_));
            
            # Skip the current month
            $DEBUG && warn "Skip test $yeardir/$_ ends with /$year/$month\n";
            if ("$yeardir/$_" =~ /\/$year\/$month$/)
            {
                $DEBUG && warn "Skipping current month: $yeardir/$_\n";
                next;
            }

            my (@daily_net, @daily_ftp, @daily_mail, @daily_web);

            my $monthdir = $yeardir.'/'.$_;

            $DEBUG && warn "Scanning directory $monthdir\n";
            opendir(MONTH, $monthdir) || return 0;
            while($_ = readdir(MONTH)) 
            {
                next unless (/^\d+$/ && (-d $monthdir.'/'.$_));
        
                my $daydir = $monthdir.'/'.$_;  
                $DEBUG && warn "Scanning directory $daydir\n";
                opendir(DAY, $daydir) || return 0;
                while($_ = readdir(DAY)) 
                {
                    next unless (/^([a-z]+)\.cache$/);
                    push(@{"daily_$1"}, $daydir.'/'.$_);
                }
                closedir(DAY);
            }
            closedir(MONTH);

            # Now invoke analog to build consolidated stat files
            my ($type, $cachefile); 
            foreach $type (@types)
            {
                # Test for daily cache files                
                next unless ($#{"daily_$type"} >= 0);
                $DEBUG && warn "Found daily cache files for $dir type: $type\n";
    
                # From generateReports.pl:
                my $thisConfigFn = '/var/tmp/analog.cfg.'.rand($$);
                while(-e $thisConfigFn) {
                    $thisConfigFn = '/var/tmp/analog.cfg.'.rand($$);
                }
                $DEBUG && warn "Using temporary analog config file $thisConfigFn\n";
                copy($configTmpl, $thisConfigFn) ||
                    die "Could not copy $configTmpl to $thisConfigFn: $!";
                open(CFG, ">>$thisConfigFn") ||
                    die "Could not write staging analog config file $thisConfigFn: $!";
                
                # append CACHEFILE references
                foreach $cachefile (@{"daily_$type"})
                {
                    $DEBUG && warn "appending CACHEFILE $cachefile\n";
                    print CFG "CACHEFILE $cachefile\n" if (-r $cachefile);
                }
                my $outfile = $monthdir.'/'.$type.'.cache';
                
                $DEBUG && warn "using output file: $outfile\n";
                print CFG "CACHEOUTFILE $outfile\n";
                close(CFG);

                # Invoke analog
                my $analogCmd = "$analog -G +g$thisConfigFn >> /tmp/consolidate 2>&1";
                my $ret = system ( $analogCmd );
                $DEBUG && warn "Analog command \"$analogCmd\" returned $ret\n";
        
                chmod(0664, $outfile);
            
                $DEBUG || unlink($thisConfigFn);

                # Now delete the daily directories
                my $ret = system("/bin/rm -rf $monthdir/1* > /dev/null 2>&1; /bin/rm -rf $monthdir/2* > /dev/null 2>&1; /bin/rm -rf $monthdir/3* > /dev/null 2>&1;");
                $DEBUG && warn "Delete daily stat directories ret: $ret\n";
            }
        }
        closedir(YEAR);
    }
    close(LOGROOT);

    return 1;
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#    notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#    notice, this list of conditions and the following disclaimer in 
#    the documentation and/or other materials provided with the 
#    distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#    contributors may be used to endorse or promote products derived 
#    from this software without specific prior written permission.
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