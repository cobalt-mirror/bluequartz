
#########
# some useful defines
INSTALL=install
INSTALL_BIN=$(INSTALL) -m 755
INSTALL_SBIN=$(INSTALL) -m 700
INSTALL_OTH=$(INSTALL) -m 644
TMPDIR=/tmp

install:
	mkdir -p $(PREFIX)/usr/bin
	mkdir -p $(PREFIX)/usr/local/bin
	mkdir -p $(PREFIX)/usr/local/sbin
	mkdir -p $(PREFIX)/var/state/acct
	mkdir -p $(PREFIX)/etc/cron.daily
	mkdir -p $(PREFIX)/etc/cron.hourly
	mkdir -p $(PREFIX)/etc/logrotate.d
	mkdir -p $(PREFIX)/etc/rc.d/init.d
	@$(INSTALL_BIN) generateGraph.pl $(PREFIX)/usr/local/bin
	@$(INSTALL_BIN) log_traffic $(PREFIX)/etc/cron.hourly
	@$(INSTALL) ftplog2commonlog $(PREFIX)/usr/local/sbin
	@$(INSTALL) maillog2commonlog.pl $(PREFIX)/usr/local/sbin
	@$(INSTALL) anonip.py $(PREFIX)/usr/local/sbin
	@$(INSTALL_SBIN) split_logs $(PREFIX)/usr/local/sbin
	@$(INSTALL_SBIN) sitestats_purgeOmatic.pl $(PREFIX)/etc/cron.daily
	@$(INSTALL_OTH) analog.cfg.tmpl $(PREFIX)/etc
	@$(INSTALL_OTH)	sitestats $(PREFIX)/etc/logrotate.d
	@$(INSTALL_OTH) apache.logrotate $(PREFIX)/etc/logrotate.d/apache
	@$(INSTALL_OTH) letsencrypt.logrotate $(PREFIX)/etc/logrotate.d/letsencrypt
	@$(INSTALL_BIN)	tmpwatch_sitestats $(PREFIX)/etc/cron.daily
	@$(INSTALL_BIN) grab_logs.pl $(PREFIX)/usr/local/sbin
	@$(INSTALL_BIN) webalizer.pl $(PREFIX)/usr/bin

