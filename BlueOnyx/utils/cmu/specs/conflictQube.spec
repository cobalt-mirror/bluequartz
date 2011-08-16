# $Id: conflictQube.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com/

This spec defines all possible conflicts that are currently 
known on the Qube platform. 

Conflict					Type				Severity
--------------------------------------------------------
Group Name					collision			critical
Group data/quota            limit				serious
User name					collision			critical
User aliases     			collision			critical
User data/quota				limit				serious
Mail List name				collision			serious

Group Name:
    This is an likely conflict. Two groups with the same name are 
	likely to exist in the same context. 

Group data/quota:
    This conflict occurs if the amount of data being imported exceeds 
	the group quota.

User Name:
    This is a conflict that occurs when two users on the system have 
	them user name.

User data/quota:
    This conflict occurs if the amount of data being imported exceeds the 
	user quota.

Mail List name:
    This conflict occurs if the mailing list name is also a user name or 
	a user email alias. 



