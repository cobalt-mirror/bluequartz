#!/usr/bin/perl -I/usr/sausalito/perl -I.
# $Id: writeback_blueOnyx_conf.pl Fri 12 Jun 2009 07:10:43 PM CEST mstauber $
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
	push(@AllowOverride, "Options");
    }
    if ($web->{AllowOverride_All} == "1") {
	push(@AllowOverride, "All");
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

