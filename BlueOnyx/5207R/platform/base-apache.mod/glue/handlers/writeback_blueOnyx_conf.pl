#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: writeback_blueOnyx_conf.pl
#
# This handler is responsible for updating /etc/httpd/conf.d/blueonyx.conf

my $confdir = '/etc/httpd/conf.d';
my $blueonyx_conf = "$confdir/blueonyx.conf";

use Sauce::Util;
use CCE;

my $cce = new CCE;
$cce->connectfd();

my ($oid) = $cce->find("System");
my ($ok, $obj) = $cce->get($oid);
my ($status, $web) = $cce->get($oid, "Web");

if (!Sauce::Util::editfile(($blueonyx_conf),*edit_blueonyx, $web)) {
    $cce->bye('FAIL', '[[base-apache.cantEdit_BlueOnyx_conf]]');
    exit(1);
}

$cce->bye('SUCCESS');
exit(0);

sub edit_blueonyx {
    my ($in, $out, $webdata) = @_;
    my $begin = '<Directory /home/.sites/>';
    my $script_conf = '';

    my @Options = ();
    if ($web->{Options_Indexes} == "1") {
        push(@Options, "Indexes");
    }
    if ($web->{Options_SymLinksIfOwnerMatch} == "1") {
        push(@Options, "SymLinksIfOwnerMatch");
    }
    if ($web->{Options_FollowSymLinks} == "1") {
        push(@Options, "FollowSymLinks");
    }
    if ($web->{Options_Includes} == "1") {
        push(@Options, "Includes");
    }
    if ($web->{Options_MultiViews} == "1") {
        push(@Options, "MultiViews");
    }
    if ($web->{Options_All} == "1") {
        push(@Options, "All");
    }

    my @AllowOverride = ();
    if ($web->{AllowOverride_AuthConfig} == "1") {
        push(@AllowOverride, "AuthConfig");
    }
    if ($web->{AllowOverride_Indexes} == "1") {
        push(@AllowOverride, "Indexes");
    }
    if ($web->{AllowOverride_Limit} == "1") {
        push(@AllowOverride, "Limit");
    }
    if ($web->{AllowOverride_FileInfo} == "1") {
        push(@AllowOverride, "FileInfo");
    }
    if ($web->{AllowOverride_Options} == "1") {
        if ($web->{AllowOverride_All} == "1") {
        }
        else {
            push(@AllowOverride, "Options");
        }
    }
    if ($web->{AllowOverride_All} == "1") {
        if ($web->{Options_MultiViews} == "1") {
            push(@AllowOverride, "Options=All,MultiViews");
        }
        else {
            push(@AllowOverride, "All");
        }
    }

    $out_options = join(" ", @Options);
    $out_AllowOverride = join(" ", @AllowOverride);
    $script_conf .= "<Directory /home/.sites/>\n";
    $script_conf .= "Options " . $out_options . "\n";
    $script_conf .= "AllowOverride " . $out_AllowOverride . "\n";
    $script_conf .= "\n";
    $script_conf .= "# ignore .ht*\n";

    my $last;
    while(<$in>) {
        if(/^Alias \/libImage\/(.*)$/) { $last = $_; last; }

        if(/^$begin$/) {
            while(<$in>) {
                if(/^# ignore .ht(.*)$/) { last; }
            }
            print $out $script_conf;
        }
        else {
            print $out $_;
        }
    }
    print $out $last;

    # preserve the remainder of the config file
    while(<$in>) {
        print $out $_;
    }

    return 1;
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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