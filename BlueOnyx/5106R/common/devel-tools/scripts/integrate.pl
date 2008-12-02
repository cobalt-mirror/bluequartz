#!/usr/bin/perl -w
 
my $there=shift @ARGV;
my @exclude = split/\s/,(shift @ARGV);
my $here=`pwd`;
chomp $here;
my $add=0;
 
foreach my $mod (@ARGV){
        chdir "$mod/locale";
        foreach my $lang (split/\n/,`ls -1`){
                chomp $lang;
                next if grep {/^$lang/} @exclude;
		

		foreach(<$lang/*.prop>){
			if(! -d "$there/$mod/locale/$lang"){
                               system("mkdir -p $there/$mod/locale/$lang");
                               $add |= 1;
                        }
			if(! -e "$there/$mod/locale/$_"){
				$add |= 2;
			}
			system("cp -v $_ $there/$mod/locale/$lang/");
        	        chdir "$there/$mod/locale/";
			system("cvs add $lang") if $add & 1;
	                system("cvs add $_") if $add & 2;
        	        chdir "$here/$mod/locale";
			$add=0;
		}
 
                foreach my $po (<$lang/*.po>){
                        chomp $po;
                        system("msgfmt -e -o foo.mo $po");
                        if($?){
                                print STDERR "ERROR: $mod, $po\n";
                        }else{
                                if(! -d "$there/$mod/locale/$lang"){
                                        system("mkdir -p $there/$mod/locale/$lang");
                                        $add |= 1;
                                }
 
                                if(! -e "$there/$mod/locale/$po"){
                                        $add |= 2;
                                }
 
                                system("cp -v $po $there/$mod/locale/$lang/");
                                chdir "$there/$mod/locale";
 
                                if($add & 1){
                                        system("cvs add $lang");
                                }
                                if($add & 2){
                                        system("cvs add $po");
                                }
                                $add=0;
 
                                chdir "$here/$mod/locale";
                        }
                        system("rm -vf foo.mo");
                }
        }
        chdir $here;
}
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
