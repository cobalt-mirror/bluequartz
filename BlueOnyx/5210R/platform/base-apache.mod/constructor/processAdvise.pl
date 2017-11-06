#!/usr/bin/perl -I/usr/sausalito/perl 
# $Id: processAdvise.pl
# 
# processAdvise.pl - Defines maximum number of Apache processes, the max spare
#  process count, & the min spare process count.
#
# Algorithms by Adrian, Tim & Will.

use CCE;

my $cce = new CCE;
$cce->connectuds();

my @oids = $cce->find('System');
if (not @oids) {
	$cce->bye('FAIL');
	exit 1;
}

my ($ok, $obj) = $cce->get($oids[0], 'Web');
unless ($ok and $obj) {
	$cce->bye('FAIL');
	exit 1;
}

my $minSpare = $obj->{'minSpare'};
my $maxSpare = $obj->{'maxSpare'};
my $maxClients = $obj->{'maxClients'};
    
my $totmem;
open( MEM, "/proc/meminfo" );
while (<MEM>) {
	if (/^MemTotal:\s+(\d+)/) {
		$totmem=$1
	}
}
close(MEM);
if (!$totmem) { $totmem = 65536; }

#
# Adrian and Tim's special formula
# Only allow 75% of physical RAM for apache processes, and assume 3 MB per
# apache process
#
my $server_procs = int((($totmem / 1024) * .75) / 3);

my $minSpareAdvised = $server_procs;
$minSpareAdvised = 50 if ($minSpareAdvised > 50);
my $maxSpareAdvised = $server_procs;
$maxSpareAdvised = 100 if ($maxSpareAdvised > 100);
my $maxClientsAdvised = 2*$server_procs;

# Build an argument hash
my %update = (
	'minSpareAdvised' => $minSpareAdvised,
	'maxSpareAdvised' => $maxSpareAdvised,
	'maxClientsAdvised' => $maxClientsAdvised,
	);

# detect virgin system & set http process counts
if($obj->{'minSpareAdvised'} eq '0') { 
	my $max = 2*$server_procs;
	$max = 125 if ($max > 125);
	$max = 30 if ($max < 30);

	$update{'minSpare'} = 10;
	$update{'maxSpare'} = 25;
	$update{'maxClients'} = $max;
}

$cce->update($oids[0], 'Web', \%update);

$cce->bye('SUCCESS');

exit 0;

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#   notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#   notice, this list of conditions and the following disclaimer in 
#   the documentation and/or other materials provided with the 
#   distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#   contributors may be used to endorse or promote products derived 
#   from this software without specific prior written permission.
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
