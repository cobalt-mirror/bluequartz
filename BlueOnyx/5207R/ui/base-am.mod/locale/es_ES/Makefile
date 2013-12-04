LOCALEDIR = /usr/share/locale
MESSAGEDIR = $(LOCALEDIR)/en_US/LC_MESSAGES

install:
	install -d -o root -g root -m 755 $(MESSAGEDIR)
	msgfmt -o $(MESSAGEDIR)/base-am.mo am.po
