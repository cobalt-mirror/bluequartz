#!/usr/bin/perl -w -I/usr/sausalito/perl -I.
# $Id: userwebs.pl
#

use CCE;
use Base::Httpd qw(httpd_get_vhost_conf_file);
use Sauce::Util;

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;
$cce->connectfd();

&debug_msg("Init of userwebs.pl\n");

my $vsite = $cce->event_object();

($ok, my $userwebs) = $cce->get($cce->event_oid(), 'USERWEBS');

if(not $ok)
{
    $cce->bye('FAIL', '[[base-apache.cantReadUserWebs]]');
    exit(1);
}

$documentRoot = $vsite->{basedir};
$siteNumber = $vsite->{name};

if(!Sauce::Util::editfile(httpd_get_vhost_conf_file($vsite->{name}), *edit_vhost, $userwebs))
{
    $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
    exit(1);
}

&edit_UserDir_settings;

$cce->bye('SUCCESS');
exit(0);

sub edit_UserDir_settings {

    if ($userwebs->{enabled}) {
        $data = '   UserDir enabled';
    }
    else {
        $data = '   UserDir disabled';
    }

    &debug_msg("Editing " . httpd_get_vhost_conf_file($vsite->{name}) . "\n");

    $ok = Sauce::Util::replaceblock(httpd_get_vhost_conf_file($vsite->{name}), '<IfModule mod_userdir.c>', $data, '</IfModule>');

    # Error handling:
    unless ($ok) {
        $cce->bye('FAIL', "Error while editing httpd_get_vhost_conf_file($vsite->{name})!");
        exit(1);
    }
}

sub edit_vhost
{
    my ($in, $out, $userwebs) = @_;

    my $script_conf = '';

    if ($userwebs->{enabled}) {
        $script_conf .= "AliasMatch ^/~([^/]+)(/(.*))?           $documentRoot/users/\$1/web/\$3\n";
    }
    else {
        $script_conf .= "#AliasMatch ^/~([^/]+)(/(.*))?          $documentRoot/users/\$1/web/\$3\n";
    }

    # Do the actual editing:
    my $last;
    while(<$in>) {
        if (/^Include/i) { 
            # If we get to this point, we're past the area of interest.
            # We store it in the string $last and end the charade.
            $last = $_; 
            last; 
        }

        if ((/^AliasMatch/) || (/^#AliasMatch/)) {
            # If we find this line, we ignore it and later add our own.
            $found = "1";
            next;
        }
        else {
            # Anything else stays and gets printed out straight away.
            print $out $_;
        }
    }

    # Print out or new Alias line:
    print $out $script_conf;

    # Print out all the rest of the config unaltered:
    print $out $last;

    # preserve the remainder of the config file and write it out:
    while(<$in>) {
        print $out $_;
    }

    return 1;
}

# For debugging:
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
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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