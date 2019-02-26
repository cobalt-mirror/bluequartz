# $Id: Global.pm

package Global;
use strict;

require Exporter;

use vars qw(@ISA @EXPORT @EXPORT_OK);
@ISA    = qw(Exporter); 
@EXPORT = qw(
        &cmuLog
        &getBuild
        &parseD
        &memInfo
        $validExport
        $validImport
        %adjustPath
);
@EXPORT_OK = qw();

use vars qw($validExport $validImport %adjustPath);
$validExport = { 
    Qube2   =>  1, 
    Qube3   =>  1, 
    RaQ2    =>  1,
    RaQ3    =>  1,
    RaQ4    =>  1,
    RaQXTR  =>  1,
    RaQ550  =>  1,
    '5100R' =>  1,
    '5106R' =>  1,
    '5107R' =>  1,
    '5108R' =>  1,
    '5160R' =>  1,
    '5161R' =>  1,
    '5207R' =>  1,
    '5208R' =>  1,
    '5209R' =>  1,
    '5210R' =>  1,
    '5200R' =>  1,
    TLAS1HE =>  1,
    TLAS2   =>  1
};
$validImport = { 
    Qube3   =>  1, 
    RaQ3    =>  1,
    RaQ4    =>  1,
    RaQXTR  =>  1,
    RaQ550  =>  1,
    '5100R' =>  1,
    '5106R' =>  1,
    '5107R' =>  1,
    '5108R' =>  1,
    '5160R' =>  1,
    '5161R' =>  1,
    '5207R' =>  1,
    '5208R' =>  1,
    '5209R' =>  1,
    '5210R' =>  1,
    '5200R' =>  1,
    TLAS1HE =>  1,
    TLAS2   =>  1
}; 

%adjustPath = (
               Qube2 => 'Qube3',
               RaQ2 => 'RaQ3',
               RaQ3 => 'RaQ4',
               RaQ4 => 'RaQXTR',
               RaQXTR => 'RaQ550',
               RaQ550 => 'TLAS1HE',
               TLAS1HE => '5100R',
               '5100R' => 'TLAS2',
               'TLAS2' => '5106R',
               '5106R' => '5107R',
               '5107R' => '5108R',
               '5108R' => '5106R',
               '5108R' => '5107R',
               '5108R' => '5160R',
               '5160R' => '5161R',
               '5108R' => '5106R',
               '5106R' => '5107R',
               '5107R' => '5108R',
               '5108R' => '5207R',
               '5207R' => '5208R',
               '5208R' => '5209R',
               '5209R' => '5210R'
               '5210R' => '5210R'
               );
        
    # Note: The adjustPath isfrom the oldest model to the newest. This is for
    # a reason. The last entry is:
    #
    # '5210R' => '5210R'
    #
    # This allows migrations from 5210R to 5210R w/o loosing config options
    # such as the PHP-FPM stuff.

1;

sub cmuLog 
# This function will print error messages to screen and the log file.
# Arguments: code, message
# Returns: none
# Side Effects: writes info to the log file and maybe the screen
{
    my $code = shift;
    my $string = shift;

    my $logFile = "/home/cmu/cmu.log";

    if($string =~ /^(INFO: )(.*)/) { 
        print $2, "\n";
    } elsif($string =~ /^ERROR/) {
        print $string;
    }
    # Everything get logged, biaaaatch!
    my $msg = $code.": ".$string;
    system("echo -n \"$msg\" >> $logFile");
    
}

sub getBuild
# Translates all of build tags into basic product names
# Arguments: none
# Returns: name of build
# Side Effects: selling out
{
    my $bldFile = "/etc/build";
    my %bldHash = ( 
        # Qube Builds
        "2800WG",       "Qube2",
        "4000WG",       "Qube3",
        "4010WG",       "Qube3",
        "4100WG",       "Qube3",
        # RaQ Builds
        "2700R",            "RaQ1",
        "2799R",            "RaQ2",
        "2800R",            "RaQ2",
        "3000R",            "RaQ3",
        "3001R",            "RaQ4",
        "3100R",            "RaQ4",
        "3500R",            "RaQXTR",
        "3599R",            "RaQXTR",
        "4100R",            "RaQ550",
        # BlueQuartz builds
        "5100BQ",           "Qube3",
        "5100WG",           "Qube3",
        "4200R",            "RaQ550",
        "4900R",            "RaQ550",
        "5100R",            "5100R",
        "5101R",            "5100R",
        "5102R",            "5100R",
        "5103R",            "5100R",
        "5104R",            "5100R",
        "5105R",            "5100R",
        # BlueOnyx builds
        "5106R",            "5106R",
        "5107R",            "5107R",
        "5108R",            "5108R",
        "5160R",            "5160R",
        "5161R",            "5161R",
        "5207R",            "5207R",
        "5208R",            "5208R",
        "5209R",            "5209R",
        "5210R",            "5210R",
        "5200R",            "5200R",
        # TLAS
        "TLAS1HE",          "TLAS1HE",
        "TLAS2",            "TLAS2"
    );
 
    open(FH, "< $bldFile") 
        || die "Error opening file $bldFile: $!";
    my $data;
    while(<FH>) {
        $data .= $_;
    }
    close(FH);
    for (sort keys %bldHash) {
        return $bldHash{$_} if ($data =~ /\Q$_\E/);
    }
}

sub parseD
# Arguments: the -d option
# Returns: the full path to the dir
# Side effects: will create dirs on export
{
    my $dir = shift;

    my $pwd;
    if ($dir eq ".") {
        $pwd = `/bin/pwd`;
        chomp $pwd;
        $dir = $pwd;
    }
    unless($dir  =~ /^\//) {
        $pwd = `/bin/pwd`;
        chomp $pwd;
        $dir = $pwd."/".$dir;
    }
    
    if($0 =~ /Export/ && -d $dir) {
        print "Directory already exists: $dir Overwrite? (Yes/No) ";
        my $opt = <STDIN>;
        unless($opt =~ /(yes|y)/oi) {
            cmuLog("ERROR","Need valid export directory\n");
            exit 1;
        }
    }
    return $dir;
}

sub memInfo
{
    my $pid = $$;
    open(FH, "< /proc/$pid/status") || return "Cannot open file /proc/$pid:$!\n";
    
    my $mem;    
    while(<FH>) {
        if(/^VmSize:\s*(.*)$/) {
            $mem = $1;
            last;
        }
    }
    close(FH);
    ($mem) ? (return $mem) : (return "memInfo: FAILED");
}

1;
# 
# Copyright (c) 2016 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2016 Team BlueOnyx, BLUEONYX.IT
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