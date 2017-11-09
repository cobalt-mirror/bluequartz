#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: vsite_destroy.pl
#
# largely based on siteDel.pm in turbo_ui
# handle cleaning up when a Vsite is deleted
#

use CCE;
use Vsite;
use File::Path;
use Sauce::Util;
use Sauce::Config;
use Sauce::Service;
use Base::HomeDir qw(homedir_get_group_dir homedir_create_group_link);
use Base::Group qw(groupdel);

my $DEBUG = 0;

my $cce = new CCE('Domain' => 'base-vsite');

$cce->connectfd();

my ($ok, $vsite);

my ($sysoid) = $cce->find('System');

$vsite = $cce->event_old();

# check if any site members still exist
if (($vsite->{name} ne '') &&
    (scalar($cce->find('User', { 'site' => $vsite->{name} })) > 0)) {
    $cce->bye('FAIL', 'siteMembersFound');
    exit(1);
}

# depopulate dns records
if ($vsite->{dns_auto}) 
{
    my @dns_records = $cce->find('DnsRecord', 
            { 
                'hostname' => $vsite->{hostname}, 
                'domainname' => $vsite->{domain} 
            });

    for my $rec (@dns_records) {
        $cce->destroy($rec);
    }

    # restart dns server
    my $time = time();
    ($ok) = $cce->set($sysoid, "DNS", { 'commit' => $time });

    if (not $ok) 
    {
        $cce->warn('[[base-vsite.cantRestartDns]]');
    }
}

my ($vhost_oid) = $cce->find('VirtualHost', { 'name' => $vsite->{name} });
($ok) = $cce->destroy($vhost_oid);
if (!$ok) {
    $cce->bye('FAIL', 'VirtualHostNotFound');
    exit(1);
}

# things to do if this is the last vsite using this IP
unless (scalar($cce->find("Vsite", { 'ipaddr' => $vsite->{ipaddr} }))) {

    # Use our routine from Vsite.pm to remove the extra-ips if needed:
    ($ok) = vsite_del_network_interface($cce, $vsite->{ipaddr});

    # delete ftp virtual host
    my ($oid) = $cce->find('FtpSite', { 'ipaddr' => $vsite->{ipaddr} });
    if ($oid) {
        ($ok) = $cce->destroy($oid);
        if (!$ok) {
            $cce->bye('FAIL', 'duringDeleteFTPvirtualHost');
            exit(1);
        }
    }
}

# delete system group
groupdel($vsite->{name});

# delete the home directory for this site
my $base = homedir_get_group_dir($vsite->{name}, $vsite->{volume});

# destroy the command line friendly symlink
my ($site_link, $link_target) = homedir_create_group_link($vsite->{name}, $vsite->{fqdn}, $vsite->{volume});
unlink($site_link);
Sauce::Util::addrollbackcommand("umask 000; /bin/ln -sf \"$link_target\" \"$site_link\"");

# remove site directory: slightly redundant, but sometimes it does NOT get deleted by remove_site_dir.pl
if ($base =~ /^\/.+/) {
    rmtree($base);
}

# Handle PHP-FPM:
$fpm_pool_cfg = "/etc/php-fpm.d/" . $vsite->{name} . ".conf";
if (-f $fpm_pool_cfg) {
    # Vsite has a PHP-FPM pool which needs to go as well:
    unlink($fpm_pool_cfg);
    # Reload PHP-FPM:
    service_run_init('php-fpm', 'reload');
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015-2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015-2017 Team BlueOnyx, BLUEONYX.IT
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