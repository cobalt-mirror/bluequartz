IMPORTANT INFO FOR PKG MAKERS:
==============================

In the past it was possible to install PKGs with really broad wildcards in the product field.

Examples from packing_list:

Product:               5...R

This is no longer supported on BlueOnyx due to a small change in our glue/sbin/SWUpdate.pm

Wildcards are good and fun and maybe choosing 5106R as product number for BX wasn't the best choice.

However, if we end up with people breaking their servers because they install BlueQuartz PKGs on
BlueOnyx, then we had to swing the ugly nerf bat and dump the wildcard support.

To indicate that your PKG is for BlueOnyx, use the correct and full product number instead:

Product:               5106R 	- BX on CentOS5 32-bit
Product:               5107R 	- BX on CentOS6 32-bit
Product:               5108R 	- BX on CentOS6 64-bit

To cover the BlueApp as well, use this:

Product:               5106R
Product:	       5160R
Product:               5161R

At the worst, use smaller wildcards which are not that broad. 

Example:

Product:	      516.R
