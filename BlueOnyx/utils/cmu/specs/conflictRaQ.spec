# $Id: conflictRaQ.spec 922 2003-07-17 15:22:40Z will $
# Cobalt Networks, Inc http://www.cobalt.com/

This spec defines all possible conflicts that are currently 
known on the RaQ platform. 

Conflict					Type				Severity
--------------------------------------------------------
Site FQDN					collision			critical
User name					collision			critical
Site anonymous FTP			collision			critical
Site SSL					collision			critical
Site email domains			collision			critical
Site web domains			collision			critical
Site max. users				limit				non-critical
Site quota/FrontPage		limit				serious
Site user aliases			collision			critical
Site data/quota				limit				serious
User FrontPage				disobedient			serious
User quota/FrontPage		limit				serious
User apop					disobedient			serious
User shell					disobedient			serious
User data/quota				limit				serious
Mail List name				collision			serious

Site FQDN:
    This is an unlikely conflict. Two sites with the same FQDN are 
	incredibly unlikely to exist in the same context. 

User Name:
    This is a conflict that occurs when two users on the system have 
	them user name.

Site anonymous FTP:
    This is a conflict if another site has the same IP address and 
	already has anonymous FTP enabled.          

Site SSL:
    This is a conflict if another site has the same IP address and 
	already has SSL enabled.

Site email domains
    This is only a conflict if another virtual site is accepting email 
	for the same domain name(s).
    
Site web domains
    This is only a conflict if another virtual site is accepting web 
	requests for the same domain name(s).

Site max. users:
    This is a conflict when the maximum number of users is greater than 
	the total number of users.

Site quota/FrontPage:
    This conflict is a strange one, and some users don't like it being 
	applied.  If the site has FrontPage enabled it should have a minimum
	quota of max. users times amount of disk space FrontPage consumes 
	per user.

Site user aliases:
    This checks to see if any user email aliases conflict with user names,
	 other user email aliases, and mailing lists.

Site data/quota:
    This conflict occurs if the amount of data being imported exceeds 
	the site quota.

User FrontPage:
    This conflict occurs if the user has FrontPage enabled and the 
	parent site does not.

User quota/FrontPage:
    This conflict occurs if the user has FrontPage enable and the user's 
	disk quota is not greater than the amount of disk space FrontPage uses.

User apop:
    This conflict occurs if the user has apop enabled and the parent site 
	does not.

User shell:
    This conflict occurs if the user has shell enabled and the parent site
	does not.

User data/quota:
    This conflict occurs if the amount of data being imported exceeds the 
	user quota.

Mail List name:
    This conflict occurs if the mailing list name is also a user name or 
	a user email alias. 
