#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: letsencrypt_autorenew.pl
#

#
### Load required Perl modules:
#

use Getopt::Std;
use CCE;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/ssl);
use Sauce::Service;
use Base::Vsite qw(vsite_update_site_admin_caps);
use Base::HomeDir qw(homedir_get_group_dir);
use SSL qw(ssl_get_cert_info ssl_create_directory);
use Data::Dumper;

# Debugging switch (0|1|2):
# 0 = off
# 1 = log to syslog
# 2 = log to screen
#
$DEBUG = "2";
if ($DEBUG) {
    if ($DEBUG eq "1") {
        use Sys::Syslog qw( :DEFAULT setlogsock);
    }
}

#
### CCE:
#

use CCE;
$cce = new CCE;
$cce->connectuds();

#
### Check if we are 'root':
#
&root_check;

#
### Command line option handling
#

%options = ();
getopts("ahn:", \%options);

# Handle display of help text:
if ($options{h}) {
    &help;
}

$do_admserv = '0';
if ($options{a}) {
    $do_admserv = '1';
}

# Only do Vsites specified on CLI:
$do_all = "1";
@do_Vsites = ();
if ($options{n}) {
    $do_all = "0";
    if ($options{n} =~ /,/) {
        @do_Vsites = split(',', $options{n});
        @do_Vsites = uniq(@do_Vsites);
    }
    else {
        push @do_Vsites, $options{n};
        @do_Vsites = uniq(@do_Vsites);
    }
}

# Show header:
&header;

#
### Check which Vsites have Let's Encryp SSL certificates:
#

# Check AdmServ:
if ($do_admserv eq "1") {
    # Find and get System Object:
    ($sysoid) = $cce->find('System');
    ($ok, $System_SSL) = $cce->get($sysoid, 'SSL');
    if ($System_SSL->{uses_letsencrypt} eq "1") {
        $cert_dir = '/etc/admserv/certs';
        # Check if we have an LE cert::
        ($subject, $issuer, $expires) = ssl_get_cert_info($cert_dir);

        # Make sure this is really a Let's Encrypt cert:
        $uses_letsencrypt = '0';
        if ($issuer->{'O'} eq 'Let\'s Encrypt') {
            $uses_letsencrypt = '1';

            # Check expiration date:
            $renew_time = $System_SSL->{LEcreationDate} + ($System_SSL->{autoRenewDays} * 86400);
            if ($renew_time lt time()) {
                &debug_msg("Renewing SSL certificate for 'AdmServ'.\n\n");
                $cce->set($sysoid, 'SSL', { 'uses_letsencrypt' => $uses_letsencrypt, 'performLErenew' => time() });
            }
            else {
                &debug_msg("NOT renewing SSL certificate for 'AdmServ' as it's still good.\n\n");
            }
        }
        else {
            # We should not have the 'uses_letsencrypt' set. Resetting it:
            $uses_letsencrypt = '0';
            &debug_msg("'AdmServ' is not using a Let's Encrypt certificate. Skipping.\n\n");
            $cce->set($sysoid, 'SSL', { 'uses_letsencrypt' => $uses_letsencrypt});
        }
    }
}

# Check all Vsites:
@vhosts = ();
(@vhosts) = $cce->findx('Vsite');
foreach  $vsiteOID (@vhosts) {
    ($ok, $vsite_SSL) = $cce->get($vsiteOID , 'SSL');
    if ($vsite_SSL->{uses_letsencrypt} eq "1") {
        ($ok, $vsite) = $cce->get($vsiteOID);

        # Get cert_dir:
        if ($vsite->{basedir}) {
            $cert_dir = "$vsite->{basedir}/$SSL::CERT_DIR";
        }
        else {
            $cert_dir = homedir_get_group_dir($group, $vsite->{volume}) . '/' . $SSL::CERT_DIR;
        }

        # Check if we have a good cert:
        ($subject, $issuer, $expires) = ssl_get_cert_info($cert_dir);

        # Munge date because they changed the strtotime function in php:
        $expires =~ s/(\d{1,2}:\d{2}:\d{2})(\s+)(\d{4,})/$3$2$1/;

        if ($issuer->{'O'} eq 'Let\'s Encrypt') {
            $uses_letsencrypt = '1';

            # Check expiration date:
            $renew_time = $vsite_SSL->{LEcreationDate} + ($vsite_SSL->{autoRenewDays} * 86400);
            if ($renew_time lt time()) {
                &debug_msg("Renewing SSL certificate for Vsite '$vsite->{fqdn}'.\n");
                $cce->set($vsite->{'OID'}, 'SSL', { 'uses_letsencrypt' => $uses_letsencrypt, 'performLErenew' => time() });
            }
            else {
                &debug_msg("NOT renewing SSL certificate for Vsite '$vsite->{fqdn}' as it's still good.\n");
            }
        }
        else {
            # We should not have the 'uses_letsencrypt' set. Resetting it:
            $uses_letsencrypt = '0';
            &debug_msg("Vsite '$vsite->{fqdn}' is not using a Let's Encrypt certificate. Skipping.\n");
            $cce->set($vsite->{'OID'}, 'SSL', { 'uses_letsencrypt' => $uses_letsencrypt});
        }
    }
}

&debug_msg("\nDone!\n\n");

$cce->bye("SUCCESS");
exit(0);

#
### Subs:
#

sub header {
    print "############################################################## \n";
    print "# letsencrypt_autorenew.pl: Renew 'Let's Encrypt!' SSL certs #\n";
    print "##############################################################\n\n";
}

sub help {
    $error = shift || "";
    &header;
    if ($error) {
        print "ERROR: $error\n\n";
    }
    print "usage:   letsencrypt_autorenew.pl [OPTION]\n";
    print "         -a renew AdmServ SSL as well\n";
    print "         -n only renew SSL only these sites ie -n \"ftp.foo.com,www.bar.com\"\n";
    print "         -h help, this help text\n\n";
    $cce->bye("SUCCESS");
    exit(0);
}

sub root_check {
    my $id = `id -u`;
    chomp($id);
    if ($id ne "0") {
        #print "$0 must be run by user 'root'!\n\n";
        &help("$0 must be run by user 'root'!");
    }
}

sub debug_msg {
    if ($DEBUG eq "1") {
        $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
    if ($DEBUG eq "2") {
        my $msg = shift;
        print $msg;
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