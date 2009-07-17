dnl #
dnl # This is the "bgui config file for sendmail"
dnl #
dnl # Default delivery mode 
dnl #
define(`confDELIVERY_MODE', `background')
dnl #
dnl # Uncomment and edit the following line if your outgoing mail needs to
dnl # be sent out through an external mail server:
dnl #
dnl # Smart host settings
dnl #
define(`SMART_HOST',`')
dnl #
define(`confPRIVACY_FLAGS', `authwarnings,novrfy,noexpn,restrictqrun')dnl
dnl #
dnl # The following causes sendmail to only listen on the IPv4 loopback address
dnl # 127.0.0.1 and not on any other network devices. Remove the loopback
dnl # address restriction to accept email from the internet or intranet.
dnl #
DAEMON_OPTIONS(`Port=smtp, Name=MTA')dnl
dnl #
dnl # The following causes sendmail to additionally listen to port 587 for
dnl # mail from MUAs that authenticate. Roaming users who can't reach their
dnl # preferred sendmail daemon due to port 25 being blocked or redirected find
dnl # this useful.
dnl #
DAEMON_OPTIONS(`Port=submission, Name=MSA, M=Ea')dnl
dnl #
dnl # The following causes sendmail to additionally listen to port 465, but
dnl # starting immediately in TLS mode upon connecting. Port 25 or 587 followed
dnl # by STARTTLS is preferred, but roaming clients using Outlook Express can't
dnl # do STARTTLS on ports other than 25. Mozilla Mail can ONLY use STARTTLS
dnl # and doesn't support the deprecated smtps; Evolution <1.1.1 uses smtps
dnl # when SSL is enabled-- STARTTLS support is available in version 1.1.1.
dnl #
dnl # For this to work your OpenSSL certificates must be configured.
dnl #
DAEMON_OPTIONS(`Port=smtps, Name=TLSMTA, M=s')dnl
dnl #
dnl # We strongly recommend not accepting unresolvable domains if you want to
dnl # protect yourself from spam. However, the laptop and users on computers
dnl # that do not have 24x7 DNS do need this.
dnl #
FEATURE(`accept_unresolvable_domains')dnl
dnl #
dnl # Also accept email sent to "localhost.localdomain" as local email.
dnl # 
LOCAL_DOMAIN(`localhost.localdomain')dnl
dnl #
dnl # The following example makes mail from this host and any additional
dnl # specified domains appear to be sent from mydomain.com
dnl #
MASQUERADE_AS(`')
dnl #
define(`confMAX_MESSAGE_SIZE',0)dnl
dnl I can do a dns lookup on hte mailer, EVERY MAILER should be be able to do this.
define(`_DNSVALID_',1)dnl 
dnl
dnl To enable black whole lists, remove the 'dnl' before the work FEATURE.
dnl - save the file
dnl - type: make 
dnl - hit enter; and then do
dnl - service sendmail restart
dnl FEATURE(enhdnsbl,`bl.spamcop.net',`',`t',`Spam blocked see: http://spamcop.net/bl.shtml?$&{client_addr}')dnl
dnl FEATURE(enhdnsbl,`sbl-xbl.spamhaus.org',`',`t',`Spam blocked - see http://ordb.org/')dnl
dnl FEATURE(enhdnsbl,`relays.ordb.org',`',`t',`Spam blocked - see http://ordb.org')dnl
dnl FEATURE(enhdnsbl,`blackholes.mail-abuse.org', `t',`Spam blocked see: http://www.mail-abuse.org/rbl')dnl
dnl FEATURE(enhdnsbl,`relays.mail-abuse.org', `t',`Spam blocked see: http://www.mail-abuse.org/rss')dnl
dnl FEATURE(enhdnsbl,`rbl-plus.mail-abuse.org', `t',`Spam blocked see: http://www.mail-abuse.org')dnl
dnl FEATURE(enhdnsbl,`dsn.rfc-ignorant.org', `t',`Spam blocked see: http://www.rfc-ignorant.org')dnl
dnl FEATURE(enhdnsbl,`postmaster.rfc-ignorant.org', `t',`Spam blocked see: http://www.rfc-ignorant.org')dnl
dnl FEATURE(enhdnsbl,`abuse.rfc-ignorant.org', `t',`Spam blocked see: http://www.rfc-ignorant.org')dnl
dnl FEATURE(enhdnsbl,`in.dnsbl.org', `t',`Spam blocked see: http://www.dnsbl.org')dnl
dnl #
dnl # additional configuration for Blue Quartz
dnl #
define(`confDONT_BLAME_SENDMAIL', `forwardfileingroupwritabledirpath')dnl
HACK(popauth)dnl

dnl here is the default header in sendmail:$j Sendmail $v/$Z; $b
dnl I change it to remove version information.
dnl #les# define(`confSMTP_LOGIN_MSG',$j Sendmail; $b)dnl
define(`confSMTP_LOGIN_MSG',$?{if_name}${if_name}$|$j$. Sendmail Ready; $b)dnl

