#!/usr/bin/perl -I /usr/sausalito/perl
# $Id: sa_purgeOmatic.pl
#
# Scan SendmailAnalyzer statistics to purge outdated entries.
#

$DEBUG = 0;

use CCE;
my $now = time();

my $cce = new CCE;
$cce->connectuds();

my @sysoids = $cce->find('System');
my ($ok, $System) = $cce->get($sysoids[0]);
my ($ok, $sitestats) = $cce->get($sysoids[0], 'Sitestats');

# Statistics directory:
$sa_dir = '/home/.sendmailanalyzer/' . $System->{'hostname'};

$DEBUG && warn "Firing up! - \$sa_dir: $sa_dir\n";

# Early exit if we don't have stats or retain stats forever:
if (( !-d $sa_dir) || ($sitestats->{purge} eq "0")) {
    $DEBUG && warn "Early exit!\n";
    $cce->bye('SUCCESS');
    exit(0);    
}
else {
    # process expiration date
    $DEBUG && warn "Processing $sa_dir ...\n";
    trimtree($sa_dir, $sitestats->{purge});
    $DEBUG && warn "Processing of $sa_dir done!\n";
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
    my($cutyear, $cutmonth, $cutday) = (localtime($expire))[5,4,3];
    $cutyear += 1900;
    $cutmonth++;

    $DEBUG && warn "Cutoffs are: $cutyear, $cutmonth, $cutday\n";

    # find log files that are older than our expiration date
    opendir(LOGROOT, $dir) || return 0;
    while($_ = readdir(LOGROOT))
    {
        next unless (/^\d+$/ && (-d $dir.'/'.$_));
        $DEBUG && warn "Scanning logroot, found: $_";
        
        if($_ < $cutyear)
        {
            &wipe($dir.'/'.$_);
        }
        elsif ($_ eq $cutyear)
        {
            # per-month test
            my $yeardir = $dir.'/'.$_;

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

# 
# Copyright (c) 2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2018 Team BlueOnyx, BLUEONYX.IT
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