LOCALEDIR = /usr/share/locale
MESSAGEDIR = $(LOCALEDIR)/en/LC_MESSAGES

install:
	install -d -o root -g root -m 755 $(MESSAGEDIR)
	msgfmt -o $(MESSAGEDIR)/dhcpd.mo dhcpd.po
