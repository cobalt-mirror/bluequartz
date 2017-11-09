#!/usr/bin/perl -I/usr/sausalito/perl
# Vsite.pm: Functions for virtual hosting
#
# $Id: Vsite.pm
#
# Original authors:
#   Will DeHaan (will@cobaltnet.com)
#   Brion Moss (brion@cobaltnet.com, brion@nda.com)
#   Duncan Laurie <duncan@cobaltnet.com>
#   Harris Vaegan-Lloyd <harris@cobaltnet.com>
#
# Cannibalized by: 
#   Michael Stauber <mstauber@blueonyx.it>
# 
# Please note: Instead of adding/removing interface aliases, this
# now adds/removes IPs from the 'extra_ip' arrays in the 'System' 
# Object and then sets 'nw_update' to force change_route.pl to
# dynamically bind extra_ips and build the routes for them.
#


package Vsite;

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK);

# Debugging switch:
$DEBUG = "1";
if ($DEBUG) {
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

@ISA    = qw[ Exporter ];
@EXPORT = qw[
        &vsite_disable &vsite_enable

        &vsite_get_xml 
        
        $Webdir
         $Sites_AdminGroup
        %DefaultAliases

        vsite_add_network_interface vsite_del_network_interface
    ];

use integer;
use Sauce::Config;
use File::Copy;
use Base::HomeDir qw(homedir_get_group_dir);

use vars qw(
        $DEFAULT_INTERFACE
        $Webdir
        $Sites_AdminGroup
        
        %DefaultAliases);

# globals used here
if (! -f "/proc/user_beancounters") {
    $DEFAULT_INTERFACE = 'eth0';
}
else {
    $DEFAULT_INTERFACE = 'venet0';
}

# hard-coded configuration options
$Webdir = Sauce::Config::webdir();
$Sites_AdminGroup = 'site-adm';

# Mail
%DefaultAliases =     (
                    "MAILER-DAEMON",   1,
                    "sys",             1,
                    "nobody",          1,
                    "root",            1,
                    'apache',          1
                    );

### Removed;
#                    "postmaster",      1,
#                    'admin',           1,



sub vsite_disable
# disable a virtual site
#   1. disable all virtual site users
#   2. chmod 0000 site home directory
# arguments: vsite groupname
# return value: std
{
    my ($cce, $group) = @_;
    
    # make sure group isn't blank or we disable /home/sites below
    return 0 unless ($group);

    my @users = $cce->find('User', { 'site' => $group });
    
    foreach (@users)
    {
        $cce->set($_, '', { 'enabled' => 0 });    
    }
    
    my $mode = oct '0000';
    my $dir = homedir_get_group_dir($group);
    
    Sauce::Util::chmodfile($mode, $dir);
    
    return 1;
}

sub vsite_enable
# enable a virtual site
#   1. re-enable all virtual site users
#   2. chmod site home directory back to normal
# arguments: vsite groupname
# return value: std
{
    my ($cce, $group) = @_;

    return 0 unless ($group);

    my @users = $cce->find('User', { 'site' => $group });

    foreach (@users)
    {
        $cce->set($_, '', { 'enabled' => 1 });
    }

    my $mode = oct '2775';
    my $dir = homedir_get_group_dir($group);

    Sauce::Util::chmodfile($mode, $dir);

    return 1;
}

sub vsite_add_network_interface
{
    my $cce = shift;
    my $ipaddr = shift;
    my $user = shift;
    my $device = shift;

    $device ||= $DEFAULT_INTERFACE;

    # Get 'System' Object:
    my ($sysoid) = $cce->find('System');
    my ($ok, $System) = $cce->get($sysoid);
    if (!$ok) {
        $cce->bye('FAIL');
        exit 1;
    }
    # Get primary IPs:
    $ipv4_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-eth0 | grep IPADDR= | awk -F "IPADDR=" '{print \$2}'`;
    chomp($ipv4_ip);    
    $ipv6_ip = `LC_ALL=C cat /etc/sysconfig/network-scripts/ifcfg-eth0 | grep IPV6ADDR= | awk -F "IPV6ADDR=" '{print \$2}'`;
    chomp($ipv6_ip);

    # Assemble IP array:
    my @extra_ipaddr = ();
    @extra_ipaddr = $cce->scalar_to_array($System->{extra_ipaddr});
    push (@extra_ipaddr, $ipv4_ip);

    # Check if Vsite IP is already known to the server:
    if (in_array(\@extra_ipaddr, $ipaddr)) {
        # Yes, it is!
        return 1;
    }
    else {
        # No, it is not! Push it into the Array of IPs from CODB:
        push (@extra_ipaddr, $ipaddr);

        # Remove primary IP:
        @extra_ipaddr = grep {!/^$ipv4_ip$/} @extra_ipaddr;

        # Remove duplicates:
        my @filtered_ipv4 = uniq(@extra_ipaddr);

        # Sort IPs:
        @extra_ipaddr = sort {
            pack('C4' => $a =~
              /(\d+)\.(\d+)\.(\d+)\.(\d+)/) 
            cmp 
            pack('C4' => $b =~
              /(\d+)\.(\d+)\.(\d+)\.(\d+)/)
          } @filtered_ipv4;

        # Update 'System' Object with new IPs:
        $new_extra_ipaddr = $cce->array_to_scalar(@extra_ipaddr);
        ($ok) = $cce->set($sysoid, '', { 'extra_ipaddr' =>  $new_extra_ipaddr, 'nw_update' => time() });
        if (not $ok) {
            $cce->bye('FAIL', '[[base-vsite.cantCreateExtraIPv4]]');
            exit(1);
        }
        return 1;
    }
    return 1;
}

# this will bring down a Network interface that is no longer in use
# as long as it is an alias, but more importantly it will remove unused
# extra_ips from the 'System' Object.
sub vsite_del_network_interface
{
    my ($cce, $ipaddr) = @_;

    &debug_msg("Running $0: vsite_del_network_interface: Checking if Vsites still use IP $ipaddr.\n");

    # only destroy the interface if not in use
    my @vsite_oids = $cce->find('Vsite', { 'ipaddr' => $ipaddr });
    if (scalar(@vsite_oids) == 0) {
        # We leave this in for legacy: destroy the interface, but only if it is not real
        my ($net_oid) = $cce->find('Network', { 'ipaddr' => $ipaddr, 'real' => 0 });
        if ($net_oid) {
            $cce->set($net_oid, '', { 'enabled' => 0 });
            $cce->destroy($net_oid);
        }

        #
        ### Remove unusued 'extra_ip' from 'System' Object:
        #

        # Get 'System' Object:
        my ($sysoid) = $cce->find('System');
        my ($ok, $System) = $cce->get($sysoid);
        if (!$ok) {
            &debug_msg("Running $0: Could not find 'System' Object. Aborting.\n");
            $cce->bye('FAIL');
            exit 1;
        }

        # Extract 'extra_ipaddr':
        my @extra_ipaddr = ();
        @extra_ipaddr = $cce->scalar_to_array($System->{extra_ipaddr});
        if (in_array(\@extra_ipaddr, $ipaddr)) {
            # Remove element from array:
            @extra_ipaddr = grep {!/^$ipaddr$/} @extra_ipaddr;
        }

        # Remove duplicates:
        my @filtered_ipv4 = uniq(@extra_ipaddr);

        # Sort IPs:
        @extra_ipaddr = sort {
            pack('C4' => $a =~
              /(\d+)\.(\d+)\.(\d+)\.(\d+)/)

            cmp

            pack('C4' => $b =~
              /(\d+)\.(\d+)\.(\d+)\.(\d+)/)
          } @filtered_ipv4;

        # Convert Array to Scalar and send it back into CODB:
        $new_extra_ipaddr = $cce->array_to_scalar(@extra_ipaddr);
        ($ok) = $cce->set($sysoid, '', { 'extra_ipaddr' =>  $new_extra_ipaddr, 'nw_update' => time() });
        if (not $ok) {
            $cce->bye('FAIL', '[[base-vsite.cantRemoveExtraIPv4]]');
            exit(1);
        }
        return 1;
    }
    else {
        &debug_msg("Running $0: Vsites still seem to be using IP $ipaddr. Doing nothing. Debug: " . scalar(@vsite_oids) . "\n");
    }
    return 1;
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

1;

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