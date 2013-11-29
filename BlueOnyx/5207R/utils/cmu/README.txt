README for Cobalt Migration Utility (CMU)

Manifest
========

Directory layout:
conf - contains the xml configuration files
lib - contains perl modules for different Cobalt platforms
locale - not currently used in command line version
man - contains the groff files for the man pages
packages - contains the framework for building packages for all the different Cobalt platforms
scripts - The core script that run a CMU migration
perl_modules - The core perl modules need by the CMU scripts
specs - Development documentation, this was written at the start of the project is out of date
code_sample - tools I created to debug and test certain parts of the code, might contain useful examples on how to do simple tasks.
rpms - the extra rpms needed for building the packages
cpan_orig - the original perl module source code from CPAN

How to Build CPR
================

Update the version:
If you want to update the CMU version that will be built edit the two files:
Makefile:
VERSION   =   2.80

perl_modules/TreeXml.pm:
$VERSION = 2.53;

To compile the complete packages you need to have the following rpms placed in /usr/src/redhat/RPMS/noarch
perl-Compress-Zlib-1.11-1.i386.rpm
perl-Compress-Zlib-1.11-1.mipsel.rpm
perl-Jcode-0.75-1.i386.rpm
perl-MIME-Base64-2.11-1.mips.rpm
perl-XML-Parser-2.29-2.mips.rpm

Then from the cpr directory you can do the following:
make qube2
make qube3
make raq2
make raq3
make raq4
make raqxtr
make raq550

The rpm will be placed in /usr/src/redhat/RPMS/noarch/$BUILD-cmu-$VERSION.noarch.rpm
and the package will be placed in /tmp/$BUILD-All-CMU-$VERSION.pkg

For doing development and testing on a platform I create the following aliases and vars to build and install the current state:
export CMUPRODUCT="RaQ550"
export lcCMUPRODUCT="raq550"
export CMUVERSION="2.53-0"
export CMURPM="/usr/src/redhat/RPMS/noarch/$CMUPRODUCT-cmu-$CMUVERSION.noarch.rpm"
alias build_cmu='(cd /home/cpr;make $lcCMUPRODUCT);rpm -U --force $CMURPM'

