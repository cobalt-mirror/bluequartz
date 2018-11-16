#!/usr/bin/perl -I/usr/sausalito/perl/ -I/usr/sausalito/handlers/base/ssl
# $Id: sslNamechange.pl
# update the admin server's certificate on changes to System attributes
# such as hostname, domain name, and identity information - but only if
# the SSL certificate is *NOT* self-signed!

use Time::Local qw( timelocal ); 
use POSIX;
use CCE;
use SSL qw(
            ssl_set_identity ssl_get_cert_info 
            ssl_error ssl_create_directory
            ssl_check_days_valid
            );

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

# globals
my $cert_dir = '/etc/admserv/certs';
my $current_cert = '/etc/admserv/certs/certificate';

my $cce = new CCE(Domain => 'base-ssl');
my $errors;

$cce->connectfd();

my $system = $cce->event_object();
my ($ok, $ssl) = $cce->get($cce->event_oid(), 'SSL');

if (! -d $cert_dir)
{
    ssl_create_directory(0700, 0, $cert_dir);
}

# Examine current AdmServ certificate:
if (-e $current_cert) {
    &debug_msg("AdmServ certificate present. Checking it. \n");
    # read the expiration date from the new certificate
    my ($sub, $iss, $date) = ssl_get_cert_info($cert_dir);

    if ($sub->{'CN'} ne $iss->{'CN'}) {
        &debug_msg("Current certificate is NOT self signed. Exit.\n");
        # Current certificate is NOT self signed. So we exit here. And we exit gracefully w/o raising an error:
        $cce->bye('SUCCESS');
        exit(0);
    }
    else {
        &debug_msg("Current certificate is self signed. Replacing it.\n");
        ($ok) = $cce->set($cce->event_oid(), 'SSL', { 'createCert' => time() });
    }
}

$cce->bye('SUCCESS');
exit(0);

#
### Subs:
#

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
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
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