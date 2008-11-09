LOCALEDIR = $(PREFIX)/usr/share/locale
MESSAGEDIR = $(LOCALEDIR)/ja/LC_MESSAGES

install:
	install -d -o root -g root -m 755 $(MESSAGEDIR)
	msgfmt -e -o $(MESSAGEDIR)/palette.mo palette.po
