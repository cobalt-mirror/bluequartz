#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/ssl
# $Id: ca-certs.pl
#
# update vsite or system ssl configuration to include 
# an ssl ca cert file as necessary

use CCE;
use SSL qw(ssl_rem_ca_cert);
use Base::HomeDir qw(homedir_get_group_dir);
use Base::Httpd qw(httpd_get_vhost_conf_file);

my $cce = new CCE;
$cce->connectfd();

my $site = $cce->event_object();
my ($ok, $ssl, $ssl_old) = $cce->get($cce->event_oid(), 'SSL');
if (not $ok) {
    $cce->bye('FAIL', '[[base-ssl.cantReadSSLNS]]');
    exit(1);
}

# figure out whether cas are being added or removed
my @cas = $cce->scalar_to_array($ssl->{caCerts});
my @old_cas = $cce->scalar_to_array($ssl_old->{caCerts});

my @remove = ();
for my $old_ca (@old_cas) {
    if (!grep(/^$old_ca$/, @cas)) {
        push @remove, $old_ca;
    }
}

my $ssl_conf = '/etc/admserv/conf.d/ssl.conf';
if ($site->{CLASS} eq 'System') {
    my @ca_certs = $cce->scalar_to_array($ssl->{caCerts});
    if (!Sauce::Util::editfile($ssl_conf, *edit_ssl_conf, scalar(@ca_certs))) {
        $cce->bye('FAIL', '[[base-ssl.cantUpdateSSLConf]]');
        exit(1);
    }

    # remove cas if necessary
    for my $remove (@remove) {
        if ($remove && !ssl_rem_ca_cert('/etc/admserv/certs', $remove)) {
            $cce->bye('FAIL', '[[base-ssl.cantRemoveCAs]]');
            exit(1);
        }
    }
}
else {
    my $cert_dir;
    if ($site->{basedir}) {
        $cert_dir = "$site->{basedir}/$SSL::CERT_DIR";
    }
    else {
        $cert_dir = homedir_get_group_dir($site->{name}, $site->{volume});
    }
    
    # take care of removals
    for my $remove (@remove) {
        if ($remove && !ssl_rem_ca_cert($cert_dir, $remove)) {
            $cce->bye('FAIL', '[[base-ssl.cantRemoveCAs]]');
            exit(1);
        }
    }

    # check if the ca-certs file should be removed
    if ((stat("$cert_dir/ca-certs"))[7] == 0) {
        print STDERR "removing $cert_dir/ca-certs\n";
        # no need to worry about rollback here, since editfile was called above
        unlink("$cert_dir/ca-certs");
        $site_conf = httpd_get_vhost_conf_file($site->{name});
        Sauce::Util::editfile($site_conf, *remove_site_conf);
        service_run_init('httpd', 'restart');
    }
}

# the server gets restarted by the ui script after this succeeds
$cce->bye('SUCCESS');
exit(0);

sub edit_ssl_conf {
    my ($in, $out, $add) = @_;
    my $found = 0;
    while (<$in>) {
        if (/^SSLCACertificateFile/) {
            $found = 1;
            if (!$add) { next; }
        }
        print $out $_;
    }

    if ($add && !$found) {
        print $out "SSLCACertificateFile /etc/admserv/certs/ca-certs\n";
    }
    return 1;
}

sub remove_site_conf {
    my ($in, $out) = @_;
    while (<$in>) {
        if (/^SSLCACertificateFile/) {
            next;
        }
        print $out $_;
    }
    return 1;
}

# 
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
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