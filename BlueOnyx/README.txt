Explanation of the SVN layout:
===============================

5106R: 	Modules with modifications that only work for 5106R on CentOS5

5107R: 	Modules with modifications that only work for 5107R *and* 5108R on RHEL6, SL6 or CentOS6

5207R:	Development tree of the 5207R/5208R line of BlueOnyx. Contains fork of "ui" and "utils" as well.

5207R:  Code tree for 5209R. The "ui" directory of it *only* contains the modules that are incompatible
        with and more advanced than in 5207R. Other than that 5209R uses the same modules as in 5207R/ui/

docs: 	Documentation.

ui:		GUI modules that work on all (older) platforms such as 5106R/5107R/5108R.

utils:	Utility modules such as CCE, shell-tools, dns-toolbox and Swatch. Should work everywhere.

