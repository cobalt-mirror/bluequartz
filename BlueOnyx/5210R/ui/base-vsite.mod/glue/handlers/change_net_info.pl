#!/usr/bin/perl -I/usr/sausalito/perl -I/usr/sausalito/handlers/base/vsite
# $Id: change_net_info.pl
# do things like making sure casp, httpd.conf, aliases, maillists, email info,
# etc. all get updated properly if the fqdn or ip address change for a vsite

use CCE;
use Vsite;
use Sauce::Util;
use Sauce::Config;
use Base::HomeDir qw(homedir_get_group_dir homedir_create_group_link);

# Debugging switch:
$DEBUG = "0";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

my $cce = new CCE;

$cce->connectfd();

&debug_msg("change_net_info.pl starting up.\n");

# gather some useful information
my $vsite = $cce->event_object();
my $vsite_new = $cce->event_new();
my $vsite_old = $cce->event_old();

my $msg;

# stuff to do if either the ip or fqdn has changed
if ($vsite_new->{ipaddr} || $vsite_new->{ipaddrIPv6} || $vsite_new->{fqdn} || $vsite_new->{webAliases}) {
    # modify VirtualHost entry for this site
    my ($vhost) = $cce->find('VirtualHost', { 'name' => $vsite->{name} });

    &debug_msg("Updating VirtualHost object.\n");

    my ($ok) = $cce->set($vhost, '', { 'ipaddr' => $vsite->{ipaddr}, 'ipaddrIPv6' => $vsite->{ipaddrIPv6}, 'fqdn' => $vsite->{fqdn}, 'webAliases' => $vsite->{webAliases} });

    if (not $ok) {
        &debug_msg("FAILED: Updating VirtualHost object.\n");
        $cce->bye('FAIL', '[[base-vsite.cantUpdateVhost]]');
        exit(1);
    }
}

if ($vsite_new->{fqdn}) {
    # set umask or symlinks get created with funky permissions
    my $old_umask = umask(000);
    
    # update symlink in the filesystem
    my ($old_link, $old_target) = homedir_create_group_link($vsite->{name}, $vsite_old->{fqdn}, $vsite->{volume});
    my ($new_link, $link_target) = homedir_create_group_link($vsite->{name}, $vsite->{fqdn}, $vsite->{volume});

    unlink($old_link);
    Sauce::Util::addrollbackcommand("umask 000; /bin/ln -sf \"$old_target\" \"$old_link\"");
    Sauce::Util::linkfile(
            $link_target, 
            $new_link);

    # restore umask
    umask($old_umask);
} # end of fqdn change specific

&debug_msg("INFO: \$vsite_new->{ipaddr}: " . $vsite_new->{ipaddr} . " - \$vsite_old->{ipaddr}: " . $vsite_old->{ipaddr} . "\n");

# handle ip address change
if ($vsite_new->{ipaddr}) {
    # Add used IPs ro network interfaces:
    vsite_add_network_interface($cce, $vsite_new->{ipaddr});
    # Remove unused IPs from being bound to network interfaces:
    if ($vsite_old->{ipaddr}) {
        vsite_del_network_interface($cce, $vsite_old->{ipaddr});
    }
} 
if ((!$vsite_new->{ipaddr}) && ($vsite_old->{ipaddr})) {
    vsite_del_network_interface($cce, $vsite_old->{ipaddr});
}
# end of ip address change specific

# handle ip address change
if ($vsite_new->{ipaddrIPv6}) {
    # Add used IPs ro network interfaces:
    vsite_add_network_interface($cce, $vsite_new->{ipaddrIPv6});
    # Remove unused IPs from being bound to network interfaces:
    if ($vsite_old->{ipaddrIPv6}) {
        vsite_del_network_interface($cce, $vsite_old->{ipaddrIPv6});
    }

    #
    ### IPv6 extra-IP cleanup:
    #
    my ($sysoid) = $cce->find('System');
    my ($ok, $System) = $cce->get($sysoid);

    if ($System->{extra_ipaddr_IPv6}) {
        @extra_ipaddr_IPv6 = $cce->scalar_to_array($System->{extra_ipaddr_IPv6});
        foreach my $ip_extra (@extra_ipaddr_IPv6) {
            &debug_msg("Checking if Vsite uses $ip_extra\n");
            my @vsite_oids = $cce->find('Vsite', { 'ipaddrIPv6' => $ip_extra });
            if (scalar(@vsite_oids) == 0) {
                # Remove element from array:
                &debug_msg("Removing $ip_extra\n");
                @extra_ipaddr_IPv6 = grep {!/^$ip_extra$/} @extra_ipaddr_IPv6;
            }
            else {
                &debug_msg("Still being used: $ip_extra - by # of vsites: " . scalar(@vsite_oids) . "\n");
            }
        }
        # Remove duplicates:
        my @filtered_ipv6 = uniq(@extra_ipaddr_IPv6);
        # Sort:
        @extra_ipaddr_IPv6 = sort @filtered_ipv6;
        # Convert Array to Scalar and send it back into CODB:
        $new_extra_ipaddr_IPv6 = $cce->array_to_scalar(@extra_ipaddr_IPv6);

        if (($System->{IPType} eq 'VZv6') || ($System->{IPType} eq 'VZBOTH')) {
            &debug_msg("OpenVZ: Setting 'System' - 'nw_update' \n");
            ($ok) = $cce->set($sysoid, '', { 'nw_update' => time() });
        }
        else {
            &debug_msg("NOT OpenVZ: NOT setting 'System' - 'nw_update' \n");
            ($ok) = $cce->update($sysoid, '', { 'extra_ipaddr_IPv6' =>  $new_extra_ipaddr_IPv6 });
        }
    }

} # end of ip address change specific


$cce->bye('SUCCESS');
exit(0);

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

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

sub uniq {
    my %seen;
    grep !$seen{$_}++, @_;
}

# 
# Copyright (c) 2014-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2018 Team BlueOnyx, BLUEONYX.IT
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