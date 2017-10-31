#!/usr/bin/perl -w -I/usr/sausalito/perl
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

package Vsite;

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK);

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

    my ($net_if) = $cce->find('Network', 
                        { 
                            'ipaddr' => $ipaddr,
                            'enabled' => 1
                        });
    if (not $net_if)
    {
        # need to create a new network interface
        my ($eth) = $cce->find("Network", { 'device' => $device });
        (my $ok, $eth) = $cce->get($eth);

        for (my $alias = 0;; $alias++)
        {
            ($net_if) = $cce->find("Network", { 'device' => ($eth->{device} . ":$alias") });
            if (not $net_if)
            {
                ($ok) = $cce->create("Network",
                    { 'device' => ($eth->{device} . ":$alias"),
                      'ipaddr' => $ipaddr,
                      'netmask' => $eth->{netmask},
                      'enabled' => 1 });

                if (not $ok)
                {
                    $cce->bye('FAIL', '[[base-vsite.cantCreateNetwork]]');
                    exit(1);
                }
                
                last;
            }
        }
    }

    return 1;
}

# this will bring down a Network interface that is no longer in use
# as long as it is an alias
sub vsite_del_network_interface
{
    my ($cce, $ipaddr) = @_;

    # only destroy the interface if not in use
    my @vsite_oids = $cce->find('Vsite', { 'ipaddr' => $ipaddr });
    if (scalar(@vsite_oids) == 0)
    {
        # destroy the interface, but only if it is not real
        my ($net_oid) = $cce->find('Network', 
                    { 'ipaddr' => $ipaddr, 'real' => 0 });
        if ($net_oid)
        {
            $cce->set($net_oid, '', { 'enabled' => 0 });
            $cce->destroy($net_oid);
        }
    }

    return 1;
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