# $Id: globalLib.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc. http://www.cobalt.com

This spec contains definitions for the glabal library.

Functions:

Hash readConf() 
     Desc: reads the global conf file containing default system variables and
           locations
     Args: None
     Ret:  A hash of the following form is returned:
	    createOS    => 
	    versionOS   => version of Cobalt OS installed
	    updates     => array of cobalt updates installed
	    software    => sw installed relavent to migration i.e. Chilisoft
	    tarLocation => location of tar on the system
	    diskFree    => amount of free disk space on system
	       

Hash init()
     Desc: reads cmuConfig.xml and the cobalt and third party xml file it
           specifies. This means the application specific files which contain
	   what executables to run is also loaded here.
     Args: None
     Ret:  Data structure containing all info from XML files
