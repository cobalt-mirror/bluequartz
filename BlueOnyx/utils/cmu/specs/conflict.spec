# $Id: conflict.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com/

This spec contains definitions of conflicts and their severity.  
This is to be used as a guide in generating and resolving conflicts.

Types of conflicts:
    Collision - This is a name space conflict, this also 
	can be based upon a value

    Disobedient - This is where a child has conflicting information 
	with the parent

    Limit - The sum of values is greater then a present value

    Informational - The context or the values in the object could 
	cause a problem.

Severity of conflicts:
    Critical - The object and all children cannot be added to the system.

    Serious - The object may be added to the system, but a loss of data 
	or services will occur

    Non critical - The object may be added to the system, but it will 
	trigger a system event (active monitor)


