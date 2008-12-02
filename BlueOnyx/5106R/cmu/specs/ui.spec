# $Id: ui.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com/

This spec contains definitions for the UI perl module.  

Base functions:
return code = LogIt(string,string) 
	Desc: This would take a two strings severity and messages and 
	print them to the log file defined in the global con fig.
	Arcs: Severity and Message
	Bet:  Logging successful?

data structure = conflict(data structure) 
	Desc: This would present a conflict to the user and return a policy 
	structure (see policy.spec) with the resolution information. 
	Arcs: data structure
	Bet:  policy data structure
	
