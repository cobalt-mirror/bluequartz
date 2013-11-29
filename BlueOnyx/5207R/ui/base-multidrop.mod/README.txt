README: base-multidrop.mod:
============================

This is the old "Fetchmail" module from the Qube 3 with just some minor changes and cleanups to make it
half way ready for BlueOnyx. 

This module works, but in a very unsatisfying fashion: 

It only allows to configure a single fetchmail remote retrieval option. Emails from that remote source are then
pulled into the 'admin' account, which really limits usability.

This is actually configured through /etc/fetchmail/multidrop:localdomain, which is really an odd place for this.

Additionally it runs fetchmail as 'root', which is discouraged since about a decade ago. 


What needs to be done to turn this into something ready for release:
====================================================================

The GUI needs to be modified to just enable or disable Fetchmail via "Network Services" / "Remote Retrieval".

Next a menu entry needs to be added under "Personal Profile" and "Site Management" / "User Management" to allow 
us to configure Fetchmail down to the individual user level - IF Fetchmail is enabled on the server to begin 
with.

Respective Schemas and Handlers need to be created for that purpose, too. Ideally these should go as sub-object
under the 'User' object, so that we retain the configs through CMU migrations.

Likewise the Fetchmail config file for users should reside as '.fetchmailrc' in their home directory to make this
more in line with general Fetchmail usage protocols. This will also automatically deal with cases where a user
is disabled. In that case the '.fetchmailrc' file in his home directory will be ignored. At least I hope so. This
needs to be checked, of course.

-------------------------------------------------------------------------------------

Fetchmail usage:
-----------------

How do I configure fetchmail to retrieve mail from ISP mail server?

Open .fetchmailrc file:

$ cd; touch .fetchmailrc
$ chmod 600 .fetchmailrc
$ vi .fetchmailrc

Append following text:

poll pop3.net4india.com with proto POP3
user d12356 there with password "password" is "vivek" here

Where,

    pop3.net4india.com - My POP3 server
    proto POP3 – You are using POP3 protocol
    d12356 - POP3 username
    "password" - POP3 password
    "vivek" - Local mailbox name

To fetch mail or to run fetchmail type command:

$ fetchmail
-------------------------------------------------------------------------------------

All in all this is rather simple to code. Will work on this as time permits. No promised ETA.
