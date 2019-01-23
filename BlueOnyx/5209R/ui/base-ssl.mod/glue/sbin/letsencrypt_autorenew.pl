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
use Net::SSL::ExpireDate;
use Sys::Hostname;
use POSIX qw(isalpha);
use MIME::Lite;
use Encode::Encoder;
use Encode qw(from_to);
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

my $host = hostname();

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

# Get swatch.conf:
my $conf = '/etc/swatch.conf';
open(CONF, "< $conf");
my @failed_list;
my @success_list;
my @email_list;
my $body = "";
my $lang = "en";
my $enabled = "true";
while (<CONF>) {
  chomp;
  my($key, $val) = split /\s*=\s*/, $_, 2;
  if ($key eq "email_list") {
    @email_list = split /\s*,\s*/, $val;
  } elsif ($key eq "lang") {
    $lang = $val;
  } elsif ($key eq "enabled") {
    $enabled = $val;
  }
}
close(CONF);

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
    if (($System_SSL->{uses_letsencrypt} eq "1") && ($System_SSL->{ACME} eq '0')) {
        &debug_msg("Renewing SSL certificate for 'AdmServ'\n\n");
        $cce->set($sysoid, 'SSL', { 'uses_letsencrypt' => $uses_letsencrypt, 'performLEinstall' => time() });
    }
    elsif (($System_SSL->{uses_letsencrypt} eq "1") && ($System_SSL->{ACME} eq '1')) {
        &debug_msg("LE SSL certificate for 'AdmServ' is already managed by ACME.\n\n");
    }
    else {
        &debug_msg("SSL certificate for 'AdmServ' is not using Let's Encrypt.\n\n");
    }
}

# Check all Vsites:
@vhosts = ();
(@vhosts) = $cce->findx('Vsite');
foreach  $vsiteOID (@vhosts) {
    ($ok, $vsite) = $cce->get($vsiteOID);
    ($ok, $vsite_SSL) = $cce->get($vsiteOID , 'SSL');

    # We skip Vsites that don't have SSL enabled:
    if ($vsite_SSL->{enabled} ne "1") {
        next;
    }

    if (($vsite_SSL->{uses_letsencrypt} eq "1") && ($vsite_SSL->{ACME} eq '0')) {
        &debug_msg("Renewing SSL certificate for '$vsite->{fqdn}'\n\n");
        $cce->set($vsite->{'OID'}, 'SSL', { 'uses_letsencrypt' => $uses_letsencrypt, 'performLEinstall' => time() });
    }
    elsif (($vsite_SSL->{uses_letsencrypt} eq "1") && ($vsite_SSL->{ACME} eq '1')) {
        &debug_msg("LE SSL certificate for '$vsite->{fqdn}' is already managed by ACME.\n\n");
    }
    else {
        &debug_msg("SSL certificate for '$vsite->{fqdn}' is not using Let's Encrypt.\n\n");
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
# Copyright (c) 2017-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017-2018 Team BlueOnyx, BLUEONYX.IT
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
