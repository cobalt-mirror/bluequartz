#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: le_install.pl
#

# Debugging switch (0|1):
# 0 = off
# 1 = log to syslog
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

use CCE;
use lib qw(/usr/sausalito/perl /usr/sausalito/handlers/base/ssl);
use Sauce::Service;
use Base::Vsite qw(vsite_update_site_admin_caps);
use Base::HomeDir qw(homedir_get_group_dir);
use SSL qw(ssl_get_cert_info ssl_create_directory);
use Data::Dumper;

my $cce = new CCE('Domain' => 'base-ssl');
$cce->connectfd();

# Get Vsite and ssl information for the Vsite:
$vsite = $cce->event_object();
$oid = $cce->event_oid();
($ok, $ssl_info) = $cce->get($oid, 'SSL');
$ssl = $cce->event_object();
$ssl_old = $cce->event_old();
$ssl_new = $cce->event_new();

if ($vsite->{'name'}) {
    $siteName = $vsite->{'name'};
}
else {
    $siteName = '';
}

&debug_msg("Performing LE SSL install for $vsite->{'CLASS'} $siteName\n");

if (($vsite->{'CLASS'} eq "Vsite") || ($vsite->{'CLASS'} eq "System")) {

    if ($vsite->{'CLASS'} eq "Vsite") {
        $fqdn = $vsite->{'fqdn'}
    }
    elsif ($vsite->{'CLASS'} eq "System") {
        $fqdn = $vsite->{'hostname'} . '.' . $vsite->{'domainname'};
    }
    else {
        &debug_msg("WARNING: Unable to detect fqdn!\n");
        $cce->bye('FAIL', "[[base-ssl.LE_CA_Request_FQDN_Error]]"); 
        exit(1); 
    }

    &debug_msg("FQDN: $fqdn\n");

    # Get WebAliases:
    $alias_line = '';
    if ($vsite->{'CLASS'} eq "Vsite") {
        @webAliases = $cce->scalar_to_array($vsite->{webAliases});
        foreach $alias (@webAliases) {
            if ($alias ne "") {
                $alias_line .= '-d ' . $alias . ' ';
            }
        }
        chop($alias_line);
    }
    &debug_msg("Web-Aliases: $alias_line\n");

    # Get webroot:
    if ($vsite->{'CLASS'} eq "Vsite") {
        $webroot = $vsite->{'basedir'} . "/web";

        # Find and get System Object:
        ($sysoid) = $cce->find('System');
        ($ok, $System_web) = $cce->get($sysoid, 'Web');
    }
    else {
        $webroot = '/var/www/html';
    }

    # Auto-Renew:
    $autoRenew = '';
    if ($ssl_info->{'autoRenew'} eq "1") {
        $autoRenew = ' --renew-by-default';
    }

    # Email:
    $email = ' --register-unsafely-without-email';
    if ($ssl_info->{'LEemail'} ne "") {
        $email = ' --email ' . $ssl_info->{'LEemail'};
    }

    $well_known_location = $webroot . '/.well-known';
    if ($vsite->{'CLASS'} eq "Vsite") {
        # Make sure acme dir gets right perms, because on EL6 this will not work well otherwise:
        system("mkdir -p $well_known_location");
        system("chmod 755 $well_known_location");
    }

    # Obtain SSL cert:
    # --duplicate
    &debug_msg("Running: /usr/sausalito/letsencrypt/letsencrypt-auto certonly -a webroot --webroot-path $webroot -d $fqdn $alias_line $email --rsa-key-size 4096 --agree-tos $autoRenew --user-agent BlueOnyx.it\n");
    $result = `/usr/sausalito/letsencrypt/letsencrypt-auto certonly -a webroot --webroot-path $webroot -d $fqdn $alias_line $email --rsa-key-size 4096 --agree-tos $autoRenew --user-agent BlueOnyx.it 2>&1`;
    &debug_msg("Result: $result\n");
    $result =~ s/^Updating letsencrypt(.*)\n//g;
    $result =~ s/^Running with virtualenv:(.*)\n//g;
    $result =~ s/\n/<br>/g;
    $cce->set($vsite->{'OID'}, 'SSL', { 'LEclientRet' => $result }); 

    # Make sure the $well_known_location directory is gone:
    if (-d $well_known_location) {
        system("rm -R $well_known_location");
    }

    # Handle errors:
    if (($result =~ /Error:/) || ($result =~ /An unexpected error occurred:/)) {
        &debug_msg("WARNING: Error during SSL cert request!\n");
        $cce->bye('FAIL', "[[base-ssl.LE_CA_Request_Error,msg=\"$result\"]]"); 
        exit(1); 
    }

    # Find out where the certs were stored:
    if ($result =~ /\/etc\/letsencrypt\/live\/(.*)\/fullchain\.pem/) {
        $cert_path = '/etc/letsencrypt/live/' . $1;
        &debug_msg("Path to cert-directory: $cert_path\n");
    }
    else {
        $cce->bye('FAIL', "[[base-ssl.LE_CA_Request_Error_noPathFound]]"); 
        exit(1); 
    }

    # Check if we got an expiry date:
    if ($result =~ /will expire on (.*). To obtain/) {
        $expiryDate = $1;
        &debug_msg("Expiry Date: $expiryDate\n");
    }

    if ((-d $cert_path) && ($cert_path ne "")) {
        # Cleanup CA-Cert and disable SSL:
        if ($vsite->{'CLASS'} eq "Vsite") {
            $cce->set($vsite->{'OID'}, 'SSL', { 'caCerts' => '', 'enabled' => '0', 'uses_letsencrypt' => '0' });
        }
        else {
            # Don't you dare to turn off SSL for AdmServ!
            $cce->set($vsite->{'OID'}, 'SSL', { 'caCerts' => '', 'uses_letsencrypt' => '0' });
        }

        # Create cert directory for Vsite:
        if ($vsite->{'CLASS'} eq "Vsite") {
            if ($vsite->{basedir}) {
                $cert_dir = "$vsite->{basedir}/$SSL::CERT_DIR";
                &debug_msg("Cert-Directory: $vsite->{basedir}/$SSL::CERT_DIR \n");
            }
            else {
                $cert_dir = homedir_get_group_dir($vsite->{name}, $vsite->{volume}) . '/' . $SSL::CERT_DIR;
            }
        }
        else {
            $cert_dir = '/etc/admserv/certs';
            &debug_msg("Cert-Directory: $cert_dir \n");
        }
        if ($vsite->{'CLASS'} eq "Vsite") {
            if (!-d $cert_dir) {
                if (!ssl_create_directory(02770, scalar(getgrnam($vsite->{name})), $cert_dir)) {
                    &debug_msg("Couldn't create $cert_dir!\n");
                    $cce->bye('FAIL', "[[base-ssl.CouldnotCreateCertDir]]");
                    exit(1);
                }
            }
        }

        # Import Intermediate:
        $intermediate_path = $cert_path . '/chain.pem';
        $intermediate_target = $cert_dir . '/ca-certs';
        system("cp $intermediate_path $intermediate_target");
        $cce->set($vsite->{'OID'}, 'SSL', { 'caCerts' => '&LetsEncrypt&' });

        # Convert PKCS#8 key to PKCS#1:
        $privkey_in_path = $cert_path . '/' . 'privkey.pem';
        $privkey_out_path = $cert_path . '/' . 'privkey_new.pem';
        &debug_msg("Running: openssl rsa -in $privkey_in_path -out $privkey_out_path\n");
        system("openssl rsa -in $privkey_in_path -out $privkey_out_path");

        # Move key:
        system("mv $privkey_out_path $cert_dir/key");

        # Copy cert:
        $ssl_cert_path = $cert_path . '/' . 'cert.pem';
        system("cp $ssl_cert_path $cert_dir/certificate");

        # Delete request (if present):
        if (-f "$cert_dir/request") {
            system("rm -f $cert_dir/request");
        }

        # Check if we have a good cert:
        ($subject, $issuer, $expires) = ssl_get_cert_info($cert_dir);

        # Make sure this is really a Let's Encrypt cert:
        $uses_letsencrypt = '0';
        if ($issuer->{'O'} eq 'Let\'s Encrypt') {
            &debug_msg("SSL issuer: Let's Encrypt\n");
            $uses_letsencrypt = '1';
        }

        if (($expires ne "") && ($uses_letsencrypt eq "1")) {
            # Munge date because they changed the strtotime function in php:
            $expires =~ s/(\d{1,2}:\d{2}:\d{2})(\s+)(\d{4,})/$3$2$1/;
            &debug_msg("expires: $expires\n");

            # Update CODB to activate the whole shebang:
            $cce->set($vsite->{'OID'}, 'SSL', { 'uses_letsencrypt' => $uses_letsencrypt, 'country' => 'US', 'state' => 'Other', 'expires' => $expires, 'enabled' => '1', 'email' => $ssl_info->{'LEemail'}, 'orgName' => "Let's Encrypt", 'LEcreationDate' => time() });
        }
        else {
            # Turn off the 'uses_letsencrypt' flag and fail:
            $cce->set($vsite->{'OID'}, 'SSL', { 'uses_letsencrypt' => $uses_letsencrypt });
            &debug_msg("Did not get a valid certificate back!\n");
            $cce->bye('FAIL', "[[base-ssl.doNotHaveValidLECert]]");
            exit(1);
        }

        if ($vsite->{'CLASS'} eq "Vsite") {
            # Reload httpd:
            service_run_init('httpd', 'reload');
        }
        else {
            # Reload admserv:
            service_run_init('admserv', 'reload');
        }
    }
}

$cce->bye('SUCCESS');
exit(0);

#
### Subroutines:
#

sub debug_msg {
    if ($DEBUG eq "1") {
        $msg = shift;
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