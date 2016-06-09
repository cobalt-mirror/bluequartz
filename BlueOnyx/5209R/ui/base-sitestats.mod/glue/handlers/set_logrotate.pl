#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: set_logrotate.pl
#
# turn on/off log file rotation for sites, and adjust size if Vsite quota
# changes

use CCE;
use Sauce::Util;
use Base::HomeDir qw(homedir_get_group_dir);

my $LOGROTATE_DIR = '/etc/logrotate.d';
my $LOG_DIR = 'logs';
my $DEFAULT_SIZE = 25; # default size to rotate logs at (in MB))

my $cce = new CCE;
$cce->connectfd();

my $vsite = {};
my ($ok, $disk);
if ($cce->event_is_destroy())
{
    $vsite = $cce->event_old();
}
else
{
    $vsite = $cce->event_object();

    if ($cce->event_is_create() && !$vsite->{name})
    {
        $cce->bye('DEFER');
        exit(0);
    }

    ($ok, $disk) = $cce->get($cce->event_oid(), 'Disk');

    if (!$ok)
    {
        $cce->bye('FAIL', '[[base-sitestats.systemError]]');
        exit(1);
    }
}

my $logrotate_file = "$LOGROTATE_DIR/$vsite->{name}";

# on destroy just get rid of the file
if ($cce->event_is_destroy())
{
    Sauce::Util::unlinkfile($logrotate_file);
}
else # create or quota change
{
    my $log_dir = homedir_get_group_dir($vsite->{name}, $vsite->{volume}) .
                    "/$LOG_DIR";
    my $size = int($disk->{quota} / 10) || 1;

    # disk quota can be -1 to specify unlimited, so deal with it
    if ($disk->{quota} == -1) { $size = $DEFAULT_SIZE; }

    if (!Sauce::Util::editfile($logrotate_file, *edit_logrotate, 
                $log_dir, $size))
    {
        $cce->bye('FAIL', '[[base-sitestats.cantEnableLogrotate]]');
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

sub edit_logrotate
{
    my($in, $out, $log_dir, $size) = @_;

    $size .= 'M';
    my($rotate) = <<EOF;
$log_dir/mail.log {
   missingok
   compress
   size $size
}

$log_dir/ftp.log {
   missingok
   compress
   size $size
}

$log_dir/web.log {
   missingok
   compress
   size $size
}
EOF

    print $out $rotate;

    return 1;
}

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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