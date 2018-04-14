#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: reload.pl

use Sauce::Service;
use CCE;

my $cce = new CCE;
$cce->connectfd();

#Sauce::Service::service_run_init('admserv', 'reload');
# Please note: I disabled the AdmServ reload here, because
# it also kicks in on Product Language Changes initiated
# through the GUI. That is NOT desirable, as the reload
# doesn't clear Semaphores and leaves AdmServ often dead
# in the water. So it has to go for now for sake of better
# reliability:

@SysOID = $cce->find('System');
($ok, $Nginx) = $cce->get($SysOID[0], 'Nginx');

if ($Nginx->{enabled} eq "1") {
    Sauce::Service::service_set_init('nginx', 'on');
    Sauce::Service::service_run_init('httpd', 'reload');
    Sauce::Service::service_run_init('nginx', 'restart');
}
else {
    Sauce::Service::service_set_init('nginx', 'off');
    Sauce::Service::service_run_init('nginx', 'stop');
    Sauce::Service::service_run_init('httpd', 'reload');
}

$cce->bye('SUCCESS');

exit 0;

# 
# Copyright (c) 2014-2018 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014-2018 Team BlueOnyx, BLUEONYX.IT
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