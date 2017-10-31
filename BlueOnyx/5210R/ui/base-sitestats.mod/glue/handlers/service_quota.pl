#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: service_quota.pl
#
# manages the per-site log user account and ServiceQuota object
# for site-level split logs and usage stats

use CCE;

my $DEBUG = 0;
$DEBUG && open STDERR, ">/tmp/service_quota";
$DEBUG && warn `date`."$0\n";

my $cce = new CCE;
$cce->connectfd();

my $err; # Global error message/return state

# We're triggered on Vsite create/mod/edit
my $oid = $cce->event_oid(); 

# Fetch quotas
my ($ok, $obj) = $cce->get($oid); # Vsite
my ($aok, $obj_disk) = $cce->get($oid, 'Disk'); # Vsite.Disk

$DEBUG && warn "Disk object load: $aok, $obj_disk->{quota}\n";

# Find matching ServiceQuota objects
my @oids = $cce->find('ServiceQuota', {
    'site' => $obj->{name},
    'label' => '[[base-sitestats.statsQuota]]',
    }); 

if ($obj_disk->{quota})
{
    foreach my $i (@oids)
    {
        my ($ret) = $cce->set($i, '', {
            'quota' => $obj_disk->{quota}
            });
        $err .= '[[base-sitestats.couldNotUpdateStatsQuotaMon]]' 
            unless ($ret);
    }
} 

if($err)
{
    $cce->bye('FAIL', $err);
    exit 1;
}
else
{
    $cce->bye('SUCCESS');
    exit 0;
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