#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: phptoggle.pl

use Getopt::Long;
use CCE;
$cce = new CCE;
$cce->connectuds();

# Debugging switch (0|1|2):
# 0 = off
# 1 = log to syslog
# 2 = log to screen
#
$DEBUG = "2";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

# Root check:
$id = `id -u`;
chomp($id);
if ($id ne "0") {
    print "$0 must be run by user 'root'!\n";

    $cce->bye('FAIL');
    exit(1);
}

#
### Command line option handling
#

GetOptions ('all' => sub { $action = "all" },
            'dso' => sub { $action = "dso" },
            'ruid' => sub { $action = "ruid" },
            'suphp' => sub { $action = "suphp" },
            'fpm' => sub { $action = "fpm" },
            'help|h'  => sub { HelpMessage() });

if ($action eq "") {
    &HelpMessage;
    $cce->bye('SUCCESS');
    exit(0);
}

# Find all Vsites:
@vhosts = ();

if ($action eq "all") {
    (@vhosts) = $cce->findx('Vsite', {'PHP.enabled' => '1'});
}
elsif ($action eq "dso") {
    (@vhosts) = $cce->findx('Vsite', {'PHP.enabled' => '1', 'PHP.mod_ruid_enabled' => '0', 'PHP.suPHP_enabled' => '0', 'PHP.fpm_enabled' => '0'});
}
elsif ($action eq "ruid") {
    (@vhosts) = $cce->findx('Vsite', {'PHP.enabled' => '1', 'PHP.mod_ruid_enabled' => '1', 'PHP.suPHP_enabled' => '0', 'PHP.fpm_enabled' => '0'});
}
elsif ($action eq "suphp") {
    (@vhosts) = $cce->findx('Vsite', {'PHP.enabled' => '1', 'PHP.mod_ruid_enabled' => '0', 'PHP.suPHP_enabled' => '1', 'PHP.fpm_enabled' => '0'});
}
elsif ($action eq "fpm") {
    (@vhosts) = $cce->findx('Vsite', {'PHP.enabled' => '1', 'PHP.mod_ruid_enabled' => '0', 'PHP.suPHP_enabled' => '0', 'PHP.fpm_enabled' => '1'});
}
else {
    @vhosts = ();
}

print "Going through all Vsites to toggle $action PHP off an back on again ... \n\n";

# Walk through all Vsites:
for $vsite (@vhosts) {
    ($ok, $my_vsite) = $cce->get($vsite);

    print "Processing Vsite: $my_vsite->{fqdn} \n";

    ($ok, $my_php) = $cce->get($vsite, 'PHP');

    ($ok) = $cce->set($vsite, 'PHP', {
        'suPHP_enabled' => "0",
        'mod_ruid_enabled' => "0",
        'fpm_enabled' => "0",
        'enabled' => "0"
       });

    ($ok) = $cce->set($vsite, 'PHP', $my_php);
}

print "\nDone.\nRunning Swatch ...\n";
system("/usr/sausalito/sbin/swatch.sh");
print "\nAll done!\n";

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

#
### Subs:
#

# Debug:
sub debug_msg {
    if ($DEBUG) {
        $msg = shift;
        if ($DEBUG eq "1") {
            $user = $ENV{'USER'};
            setlogsock('unix');
            openlog($0,'','user');
            syslog('info', "$ARGV[0]: $msg");
            closelog;
        }
        else {
            print $msg;
        }
    }
}

sub handle_error {
    $message = shift;
    #write error message wherever you want
    &debug_msg("Debug: " . $message . "\n");
    $Error = '1';
}

sub HelpMessage {
    print "Command line options for re-appliyng PHP settings to Vsites: \n";
    print " -all    All Vsites with any form of PHP enabled.\n";
    print " -dso    Only Vsites which use PHP as DSO without mod_ruid2.\n";
    print " -ruid   Only Vsites which use PHP as DSO *with* mod_ruid2.\n";
    print " -suphp  Only Vsites which use suPHP.\n";
    print " -fpm    Only Vsites which use PHP-FPM.\n\n";
    # tell cce everything is okay
    $cce->bye('SUCCESS');
    exit(0);
}

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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
