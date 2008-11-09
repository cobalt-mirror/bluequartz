# dummy Makefile to make sure non-standard init.d directory gets
# installed

all:

install:
	-mkdir -p $(PREFIX)/etc/rc.d/init.d
	-(tar -c -C init.d --exclude '*CVS*' --exclude '.\/.*' . | \
		tar -x -C $(PREFIX)/etc/rc.d/init.d/)
