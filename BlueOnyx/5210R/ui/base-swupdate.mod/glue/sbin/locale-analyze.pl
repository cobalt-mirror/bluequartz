#!/usr/bin/perl -I/usr/sausalito/perl

# Debugging switch (0|1|2):
# 0 = off
# 1 = log to syslog
# 2 = log to screen
#
$DEBUG = "2";
if ($DEBUG) {
    if ($DEBUG eq "1") {
        use Sys::Syslog qw( :DEFAULT setlogsock);
    }
}

#
### CCE:
#

use CCE;
$cce = new CCE;
$cce->connectuds();

#
### Load required Perl modules:
#

use Getopt::Std;
use Data::Dumper;
use List::Flatten;
use Fcntl qw( :flock );
use List::MoreUtils qw(uniq);
use POSIX;

#
### Check if we are 'root':
#
&root_check;

#
### Command line option handling
#

%options = ();
getopts("skf:c:", \%options);

# Handle display of help text:
if ($options{h}) {
    &help;
}

# Show keys only:
$keys_only = "1";
$sorted_keys = "0";
if ($options{k}) {
    $keys_only = "1";
}
if ($options{s}) {
    $keys_only = "1";
    $sorted_keys = "1";
}

# Primary file:
$primary_file = "";
if ($options{f}) {
    $primary_file = $options{f};
}

# Secondary file:
$secondary_file = "";
$compare = "0";
if ($options{c}) {
    $compare = "1";
    $secondary_file = $options{c};
}

#
### Start:
#

&header;
&debug_msg("This utility allows to analyze BlueOnyx locale files.\n");

if (!-f $primary_file) {
    &debug_msg("Please specify a *.po file via the -f option.\n\n");
    &help;
    exit;
}

if (($compare eq "1") && (!-f $secondary_file)) {
    &debug_msg("Please specify a *.po file via the -f option and one via the -c option to compare them.\n\n");
    &help;
    exit;
}

# Check if the specified file is a GetText file:
$prim_num = check_po($primary_file);
if ($prim_num eq "0") {
    &debug_msg("ERROR: $primary_file seems to be a GetText locale file, but the number of msgid/msgstr fields is not equal.\n\n");
    exit;
}
elsif ($prim_num eq "-1") {
    &debug_msg("ERROR: $primary_file does not appear to be a valid GetText locale file.\n\n");
    exit;
}
else {
    &debug_msg("\nThe file $primary_file contains $prim_num msgid/msgstr fields.\n");
}

# Check if the specified file is a GetText file:
if (($compare eq "1") && (-f $secondary_file)) {
    $sec_num = check_po($secondary_file);
    if ($sec_num eq "0") {
        &debug_msg("ERROR: $secondary_file seems to be a GetText locale file, but the number of msgid/msgstr fields is not equal.\n\n");
        exit;
    }
    elsif ($prim_num eq "-1") {
        &debug_msg("ERROR: $secondary_file does not appear to be a valid GetText locale file.\n\n");
        exit;
    }
    else {
        &debug_msg("The file $secondary_file contains $sec_num msgid/msgstr fields.\n\n");
    }
}

# Key-List:
if ($compare eq "1") {
    if (-f $primary_file) {
        if (($keys_only eq "1") && ($sorted_keys eq "0")) {
            # Get list of keys:
            $primary_keys = `cat $primary_file |grep ^msgid |cut -d \\" -f2`;
            chomp($primary_keys);
            &debug_msg("\nPrimary Keys:\n");
            &debug_msg("===============\n");
            &debug_msg("$primary_keys \n\n");
        }
        if (($keys_only eq "1") && ($sorted_keys eq "1")) {
            # Get list of keys:
            $primary_keys = `cat $primary_file |grep ^msgid |cut -d \\" -f2|sort -fh`;
            chomp($primary_keys);
            &debug_msg("\nPrimary Keys:\n");
            &debug_msg("===============\n");
            &debug_msg("$primary_keys \n\n");
        }
        open(PRIMARY, ">/tmp/loc_primary.txt") || die "Can't open /tmp/loc_primary.txt";
        print PRIMARY "$primary_keys\n";
        close(PRIMARY);
    }
    if (-f $secondary_file) {
        if (($keys_only eq "1") && ($sorted_keys eq "0")) {
            # Get list of keys:
            $secondary_keys = `cat $secondary_file |grep ^msgid |cut -d \\" -f2`;
            chomp($secondary_keys);
            &debug_msg("\nSecondary Keys:\n");
            &debug_msg("=================\n");
            &debug_msg("$secondary_keys \n\n");
        }
        if (($keys_only eq "1") && ($sorted_keys eq "1")) {
            # Get list of keys:
            $secondary_keys = `cat $secondary_file |grep ^msgid |cut -d \\" -f2|sort -fh`;
            chomp($secondary_keys);
            &debug_msg("\nSecondary Keys:\n");
            &debug_msg("=================\n");
            &debug_msg("$secondary_keys \n\n");
        }
        open(SECONDARY, ">/tmp/loc_secondary.txt") || die "Can't open /tmp/loc_secondary.txt";
        print SECONDARY "$secondary_keys\n";
        close(SECONDARY);
    }
}

# Compare:
if (($compare eq "1") && (-f "/tmp/loc_primary.txt") && (-f "/tmp/loc_secondary.txt")) {
    &debug_msg("\nDiff Reults:\n");
    &debug_msg("==============\n\n");
    system("diff /tmp/loc_primary.txt /tmp/loc_secondary.txt");
    &debug_msg("\nDone!\n");
}

&debug_msg("\n");

system("rm -f /tmp/loc_primary.txt");
system("rm -f /tmp/loc_secondary.txt");

# Fin:
$cce->bye("SUCCESS");
exit(0);

#
### Subroutines:
#

sub check_po {
    $file_name = shift;
    $check_msgid = `cat $file_name |grep ^msgid|wc -l`;
    $check_msgstr = `cat $file_name |grep ^msgid|wc -l`;
    chomp($check_msgid);
    chomp($check_msgstr);
    if (($check_msgid eq $check_msgstr) && ($check_msgid gt "0") && ($check_msgstr gt "0")) {
        return $check_msgid;
    }
    else {
        return "-1";
    }
}

sub debug_msg {
    if ($DEBUG eq "1") {
        $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
    if ($DEBUG eq "2") {
        my $msg = shift;
        print $msg;
    }
}

sub is_array {
    my ($ref) = @_;
    # Firstly arrays need to be references, throw
    #  out non-references early.
    return 0 unless ref $ref;

    # Now try and eval a bit of code to treat the
    #  reference as an array.  If it complains
    #  in the 'Not an ARRAY reference' then we're
    #  sure it's not an array, otherwise it was.
    eval {
        my $a = @$ref;
    };
    if ($@=~/^Not an ARRAY reference/) {
        return 0;
    }
    elsif ($@) {
        die "Unexpected error in eval: $@\n";
    }
    else {
        return 1;
    }
}

sub in_array {
    my ($arr,$search_for) = @_;
    my %items = map {$_ => 1} @$arr; # create a hash out of the array values
    return (exists($items{$search_for}))?1:0;
}

# pack and unpack arrays
sub array_to_scalar {
    my $scalar = "&";
    if ($_ eq "undef") {
        next;
    }
    while (defined($_ = shift)) {
        $scalar .= $_ . '&';
    }
    if ($scalar eq "&") { 
        $scalar = ""; # special case
    }
    return $scalar;
}

sub scalar_to_array {
    my $scalar = shift || "";
    $scalar =~ s/^&//;
    $scalar =~ s/&$//;
    my @data = split(/&/, $scalar);
    for ($i = 0; $i <= $#data; $i++) {
        $data[$i] =~ s/\+/ /g;
        $data[$i] =~ s/%([0-9a-fA-F]{2})/chr(hex($1))/ge;
    }
    return @data;
}

sub header {
    print "########################################################## \n";
    print "# locale-analyze.pl: BlueOnyx Locale Analyzer            #\n";
    print "##########################################################\n\n";
}

sub help {
    $error = shift || "";
    &header;
    if ($error) {
        print "ERROR: $error\n\n";
    }
    print "usage:   locale-analyze.pl [OPTION]\n";
    print "         -f Examine single locale file. Full path to file.\n";
    print "         -c Compare one file (specified via -f) against a second.\n";
    print "         -k Only show keys of locale file(s)\n";
    print "         -s Only show keys (sorted) of locale file(s)\n";
    print "         -h help, this help text\n\n";
    $cce->bye("SUCCESS");
    exit(0);
}

#sub uniq {
#    my %seen;
#    grep !$seen{$_}++, @_;
#}

sub root_check {
    my $id = `id -u`;
    chomp($id);
    if ($id ne "0") {
        #print "$0 must be run by user 'root'!\n\n";
        &help("$0 must be run by user 'root'!");
    }
}

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