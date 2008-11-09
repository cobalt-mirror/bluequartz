#!/usr/bin/perl
# $Id: build_pkgs.pl 3 2003-07-17 15:19:15Z will $
#
# Product manual packager Will DeHaan 12/11/2000
#
use File::Copy;


#
# global defs
my $pkg_version = '1.3';
my $build_dir = '/home/tmp/manuals_pkg';
my $debug = 0;
my $manuals_dir = '../manuals';
my $build_output = '/tmp/manual_pkg.log';
my $rpm_spec = 'manual.spec';
#

# build pkg-global .mo files
print STDERR "Localizing pkg...\n" if ($debug);
my @locales;
if(-d "./pkginfo/locale") {
        opendir(PKGINFO, "./pkginfo/locale");
        while($_ = readdir(PKGINFO)) {
                next if (/^(\.|\.\.)$/);
                push @locales, $_ if (-d "./pkginfo/locale/$_");
        }
        closedir(PKGINFO);
}

my $pkginfo;
foreach $pkginfo (@locales) {
        system("msgfmt ./pkginfo/locale/$pkginfo/generic.po -o ./pkginfo/locale/$pkginfo/generic.mo >> $build_output 2>&1");
}

# find pdf's, populate @pdfs
opendir(PDFS, $manuals_dir) || die "Could not read $manuals_dir: $!";
while($_ = readdir(PDFS)) {
	push @pdfs, $_ if (/pdf/);
	print STDERR "Found manual: $_\n" if ($debug && /pdf/);
}
closedir(PDFS);

# loop per-pdf:
foreach $pdf (@pdfs) {
	print STDERR "Building pkg skel for $pdf in $build_dir...\n" if ($debug);
	$locale = $pdf;
	$locale =~ s/^.*manual-([^\.]+).+$/$1/;
	
	my $pkg = "Qube3-$locale-Manual-$pkg_version.pkg";
	my $rpm = "manual-$locale-$pkg_version-1.noarch.rpm";

	# build pkg skel
	system("/bin/rm -rf $build_dir >/dev/null 2>&1; mkdir -p $build_dir/RPMS");
	system("/bin/tar -cf - ./pkginfo | ( cd $build_dir; tar -xf - )");
	system("/bin/tar -cf - ./scripts | ( cd $build_dir; tar -xf - )");
	system("rm -rf `find $build_dir -name CVS`");		

	print STDERR "Completed skel.  Building spec...\n" if ($debug);
 
	# build spec file
	open(PL, "packing_list") || die "Could not read ./packing_list: $!";
	open(NUPL, ">$build_dir/packing_list") || die "Could not write $build_dir/packing_list: $!";
	while(<PL>) {
		s/\[LOCALE\]/$locale/g;
		s/\[VERSION\]/$pkg_version/g;
		print NUPL;
	}
	close(NUPL);
	close(PL);

	my $nu_spec = "/usr/src/redhat/SPECS/manual.spec.$$";
	open(SPEC, "$rpm_spec") || die "Could not read $rpm_spec: $!";
	open(NUSPEC, ">$nu_spec") || die "Could not write $nu_spec: $!";
	while(<SPEC>) {
		s/\[LOCALE\]/$locale/g;
		s/\[VERSION\]/$pkg_version/g;
		print NUSPEC;
	}
	close(NUSPEC);
	close(SPEC);

print STDERR "Completed spec.  Building tgz...\n" if ($debug);

	# build tar source/compress pdf
	# my $bury_it = '/usr/sausalito/ui/web/base/documentation';
	my $bury_it = '';
	system("/bin/rm -rf /home/tmp/manual$bury_it; mkdir -p /home/tmp/manual-$locale$bury_it");
	copy("../manuals/$pdf", "/home/tmp/manual-$locale$bury_it/$pdf");
	system("cd /home/tmp; /bin/tar -czvf /usr/src/redhat/SOURCES/manual.tgz manual-$locale"); 

	print STDERR "Completed tgz.  rpm -bb'ing manual...\n" if ($debug);

	# build rpm
	system("rpm -bb $nu_spec") && die "Could not build RPM: $!";
	unlink($nu_spec);

	# load RPM
	copy("/usr/src/redhat/RPMS/noarch/$rpm", "$build_dir/RPMS/$rpm") || die "Could not copy rpm: $!";

	# roll pkg
	system("cd $build_dir; tar -czvf ~/$pkg *");
	print STDERR "\nCREATED .pkg: \~/$pkg\n\n";
}
# 

# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
