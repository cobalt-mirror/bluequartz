##---------------------------------------------------------------------------##
##  File:
##	@(#) mhfile.pl 2.4 99/06/25 14:13:41
##  Author:
##      Earl Hood       mhonarc@pobox.com
##  Description:
##      File routines for MHonArc
##---------------------------------------------------------------------------##
##    MHonArc -- Internet mail-to-HTML converter
##    Copyright (C) 1997-1999	Earl Hood, mhonarc@pobox.com
##
##    This program is free software; you can redistribute it and/or modify
##    it under the terms of the GNU General Public License as published by
##    the Free Software Foundation; either version 2 of the License, or
##    (at your option) any later version.
##
##    This program is distributed in the hope that it will be useful,
##    but WITHOUT ANY WARRANTY; without even the implied warranty of
##    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
##    GNU General Public License for more details.
##
##    You should have received a copy of the GNU General Public License
##    along with this program; if not, write to the Free Software
##    Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
##    02111-1307, USA
##---------------------------------------------------------------------------##

package mhonarc;

##---------------------------------------------------------------------------##

sub file_open {
    local($file) = shift;
    local($handle) = q/mhonarc::FOPEN/ . ++$_fo_cnt;
    local($gz) = $file =~ /\.gz$/i;

    if ($gz) {
	return $handle  if open($handle, "$GzipExe -cd $file |");
	die qq/ERROR: Failed to exec "$GzipExe -cd $file |": $!\n/;
    }
    return $handle  if open($handle, $file);
    if (-e "$file.gz") {
	return $handle  if open($handle, "$GzipExe -cd $file.gz |");
	die qq/ERROR: Failed to exec "$GzipExe -cd $file.gz |": $!\n/;
    }
    die qq/ERROR: Failed to open "$file": $!\n/;
}

sub file_create {
    local($file) = shift;
    local($gz) = shift;
    local($handle) = q/mhonarc::FCREAT/ . ++$_fc_cnt;

    if ($gz) {
	$file .= ".gz"  unless $file =~ /\.gz$/;
	return $handle  if open($handle, "| $GzipExe > $file");
	die qq{ERROR: Failed to exec "| $GzipExe > $file": $!\n};
    }
    return $handle  if open($handle, "> $file");
    die qq{ERROR: Failed to create "$file": $!\n};
}

sub file_exists {
    (-e $_[0]) || (-e "$_[0].gz");
}

sub file_copy {
    local($src, $dst) = ($_[0], $_[1]);
    local($gz) = $src =~ /\.gz$/i;

    if ($gz || (-e "$src.gz")) {
	$src .= ".gz"  unless $gz;
	$dst .= ".gz"  unless $dst =~ /\.gz$/i;
    }
    &cp($src, $dst);
}

sub file_rename {
    local($src, $dst) = ($_[0], $_[1]);
    local($gz) = $src =~ /\.gz$/i;

    if ($gz || (-e "$src.gz")) {
	$src .= ".gz"  unless $gz;
	$dst .= ".gz"  unless $dst =~ /\.gz$/i;
    }
    if (!rename($src, $dst)) {
	die qq/ERROR: Unable to rename "$src" to "$dst": $!\n/;
    }
}

sub file_remove {
    local($file) = shift;

    unlink($file);
    unlink("$file.gz");
}

sub file_utime {
    local($atime) = shift;
    local($mtime) = shift;
    foreach (@_) {
	utime($atime, $mtime, $_, "$_.gz");
    }
}

##---------------------------------------------------------------------------##

sub dir_remove {
    local($file) = shift;

    if (-d $file) {
	local(@files) = ();

	if (!opendir(DIR, $file)) {
	    warn qq{Warning: Unable to open "$file"\n};
	    return 0;
	}
	@files = grep(!/^(\.|\..)$/i, readdir(DIR));
	closedir(DIR);
	foreach (@files) {
	    &dir_remove($file . $mhonarc'DIRSEP . $_);
	}
	if (!rmdir($file)) {
	    warn qq{Warning: Unable to remove "$file": $!\n};
	    return 0;
	}

    } else {
	if (!unlink($file)) {
	    warn qq{Warning: Unable to delete "$file": $!\n};
	    return 0;
	}
    }
    1;
}

##---------------------------------------------------------------------------##
1;
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
