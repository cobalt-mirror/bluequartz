#!/usr/bin/perl -w -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ssl
# $Id: import_cert.pl
#
# suck in imported certificates and keys

use CCE;
use SSL qw(ssl_set_cert ssl_add_ca_cert ssl_create_directory);
use Base::HomeDir qw(homedir_get_group_dir);
use Data::Dumper;

# Debugging switch (0|1):
# 0 = off
# 1 = log to syslog
#
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE('Domain' => 'base-ssl');
$cce->connectfd();

# set a sane umask
umask(022);

my $site = $cce->event_object();
my ($ok, $ssl) = $cce->get($cce->event_oid(), 'SSL');
if (not $ok) {
    $cce->bye('FAIL', 17);
    exit(1);
}

my ($cert_dir);
if (exists($site->{basedir})) {
    # it's a vsite
    if ($site->{basedir}) {
        $cert_dir = "$site->{basedir}/$SSL::CERT_DIR";
    }
    else {
        $cert_dir = homedir_get_group_dir($site->{name}, $site->{volume}) . '/' . $SSL::CERT_DIR;
    }

    # make sure the directory exists
    if (!ssl_create_directory(02770, scalar(getgrnam($site->{name})), $cert_dir)) {
        $cce->bye('FAIL', 16);
        exit(1);
    }
}
else {
    $cert_dir = '/etc/admserv/certs';

    # make sure cert dir exists
    if (!ssl_create_directory(0700, 0, $cert_dir)) {
        $cce->bye('FAIL', 16);
        exit(1);
    }
}

my $type = '';
if (-f "$cert_dir/.import_cert") {
    $type = 'server';
    $cert = &read_file("$cert_dir/.import_cert");
    unlink("$cert_dir/.import_cert");
}
elsif (-f "$cert_dir/.import_ca_cert") {
    $type = 'ca';
    $cert = &read_file("$cert_dir/.import_ca_cert");
    unlink("$cert_dir/.import_ca_cert");
}
else {
    $cce->bye('FAIL', 14);
    exit(1);
}

if ($type eq 'server') {
    $ret = ssl_set_cert($cert, $cert_dir);
    if (not $ret) {
        &debug_msg("Couldn't set certificate!\nCert Dir is $cert_dir\nCertificate is:\n$cert\n");
        $cce->bye('FAIL', 5);
        exit(1);
    }
    elsif ($ret == -1) {
        &debug_msg("private key does not match certificate\n");
        $cce->bye('FAIL', 8);
        exit(1);
    }
}
elsif ($type eq 'ca') {
    $ret = ssl_add_ca_cert(\$cert, $cert_dir);
    if (!$ret) {
        &debug_msg("ssl_add_ca_cert failed: $ret\n");
        $cce->bye('FAIL', 9);
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

#
### Subs:
#

sub debug_msg {
    $msg = shift;
    $user = $ENV{'USER'};
    setlogsock('unix');
    openlog($0,'','user');
    syslog('info', "$ARGV[0]: $msg");
    closelog;
}

sub read_file {
    my $filename = shift;

    if (!open(FILE, $filename))
    {
        return '';
    }

    my $ret = '';
    while (<FILE>)
    {
        $ret .= $_;
    }
    close(FILE);

    return $ret;
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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