# $Id: Network.pm
#
# hidden functions only used by scripts in this module

package Network;

require Exporter;

use vars qw(@ISA @EXPORT_OK);

@ISA = qw(Exporter);
@EXPORT_OK = qw(find_eth_ifaces $NET_SCRIPTS_DIR);

# files and directories
$Network::NET_SCRIPTS_DIR = '/etc/sysconfig/network-scripts';
$Network::ETC_HOSTS = '/etc/hosts';

# programs
$Network::IFCONFIG = '/sbin/ifconfig';
$Network::ROUTE = '/sbin/route';

# exportable routines

# find the interface names for all real and alias interfaces
sub find_eth_ifaces
{
    my @eth_ifaces = ();

    # first find real physical intefaces
	if (defined(open(IFCONFIG, "$Network::IFCONFIG -a 2>/dev/null |")))
	{
		while (<IFCONFIG>)
		{
			if (! -f "/proc/user_beancounters") {
				# Normal network interfaces:
				if (!/^(eth\d+)(:){0,1}\s/) { next; }
				# found an existing interface
				push @eth_ifaces, $1;
			}
			else {
				# OpenVZ network interfaces:
				if (!/^(venet\d+)(:){0,1}\s/) { next; }
				# found an existing interface
				push @eth_ifaces, $1;
			}
		}
		close(IFCONFIG);
    }
	else
	{
		return ();
	}

    # now search /etc/sysconfig/network-scripts for aliases
    if (opendir(IFCFG, $Network::NET_SCRIPTS_DIR)) {
        while (my $filename = readdir(IFCFG)) {
			if (! -f "/proc/user_beancounters") {
				if ($filename =~ /\-(eth\d+\:\d+)$/) {
					push @eth_ifaces, $1;
				}
			}
			else {
				if ($filename =~ /\-(venet\d+\:\d+)$/) {
					push @eth_ifaces, $1;
				}
			}
    	}
        closedir(IFCFG);
    }
    else {
		return ();
    }

    return @eth_ifaces;
}

# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
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