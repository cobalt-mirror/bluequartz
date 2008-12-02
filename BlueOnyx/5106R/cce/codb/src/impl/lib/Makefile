libdb.a:	db.1.85.tar.gz patch.1.1 patch.1.2 patch.1.3 patch.1.4
	tar -xzf db.1.85.tar.gz
	patch -p0 -d db.1.85/PORT/linux/ <patch.1.1
	patch -p0 -d db.1.85 <patch.1.2
	patch -p0 -d db.1.85 <patch.1.3
	patch -p0 -d db.1.85 <patch.1.4
	patch -p0 -d db.1.85 <patch.1.5
	patch -p0 <patch.hasherrno
	make -C db.1.85/PORT/linux
	cp db.1.85/PORT/linux/libdb.a .
	cp db.1.85/PORT/linux/include/db.h .

.PHONY:	clean
clean:
	rm -rf db.1.85 libdb.a db.h
