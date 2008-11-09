PDEPEND=.depend
include $(PDEPEND)

NOTHING:=

ifeq ($(RELVERSION),$(NOTHING))
RELV=\"0.0.0\"
else
RELV:=\"$(strip $(RELVERSION))\"
endif

# See http://pauillac.inria.fr/~xleroy/linuxthreads/faq.html
# Errno needs to be local to each thread.
REENTRANT = -D_REENTRANT

CC=g++
#CFLAGS=-g $(REENTRANT) $(DEBUG) -Wall -DRELVERSION=$(RELV)
# Final builds
CFLAGS=-O $(REENTRANT) -DRELVERSION=$(RELV)

# May need to be conditional
CCEINCLUDE = -I/usr/sausalito/include `glib-config --cflags`
CCE_SOURCES = CceClient.c FileParse.c readconfig.c
CCE_INCLUDES = CceClient.h readconfig.h
CCE_OBJS = CceClient.o FileParse.o readconfig.o

PCSOURCES=Alert.cpp ArrayList.c BufferedQueue.cpp BufferedQueue.test.cpp CommandPacket.cpp DataInput.cpp DataOutput.cpp DataUnit.cpp ErrorPacket.cpp Event.cpp EventScheduler.cpp EventScheduler.test.cpp ObjectBuffer.cpp ObjectFile.cpp ObjectFile.test.cpp ObjectInput.cpp ObjectOutput.cpp ObjectSocket.cpp ObjectSocket.test.cpp Profile.c serial.c ServerSocket.cpp SmtpClient.cpp SmtpClient.test.cpp SmtpNotifier.cpp Socket.cpp SynchronizedDeque.cpp SignalHandler.cpp Thread.cpp Thread.test.cpp TimeTable.cpp TimeTable.test.cpp utility.cpp spawn.c processutil.cpp $(CCE_SOURCES)

PINCLUDE=Alert.h AlertNotifier.h ArrayList.h BufferedQueue.h CommandPacket.h DataInput.h DataOutput.h DataUnit.h ErrorPacket.h Event.h EventScheduler.h Exception.h Externalizable.h ObjectBuffer.h ObjectFile.h ObjectInput.h ObjectOutput.h ObjectSocket.h Profile.h Profile_impl.h serial.h ServerSocket.h SmtpClient.h SmtpNotifier.h Socket.h SynchronizedDeque.h SignalHandler.h Thread.h TimeTable.h utility.h processutil.h $(CCE_INCLUDES)

OBJS=libphoenix.o Alert.o ArrayList.o BufferedQueue.o CommandPacket.o DataInput.o DataOutput.o DataUnit.o ErrorPacket.o Event.o EventScheduler.o ObjectBuffer.o ObjectFile.o ObjectInput.o ObjectOutput.o ObjectSocket.o Profile.o serial.o ServerSocket.o SmtpClient.o SmtpNotifier.o Socket.o SynchronizedDeque.o SignalHandler.o Thread.o TimeTable.o utility.o spawn.o processutil.o $(CCE_OBJS)

TESTS=BufferedQueue.test EventScheduler.test ObjectFile.test ObjectSocket.test SmtpClient.test SynchronizedDeque.test Thread.test TimeTable.test

#all:	libphoenix.a create_profile
all:	libphoenix.a

tests:	all $(TESTS) 

build:	libphoenix.a nvdbget nvdbput
	# ******************** NV DataBase Support commands Build

install:	nvdbget nvdbput
	# ******************** NV DataBase support commands Install
	install -d $(RPM_BUILD_ROOT)/usr/sbin
	install -m 755 nvdbget $(RPM_BUILD_ROOT)/sbin
	install -m 755 nvdbput $(RPM_BUILD_ROOT)/sbin

libphoenix.a: $(OBJS)
	ar -cr $@ $(OBJS)

btar:
	tar cvzf commonlib.tar.gz *.h *.cpp

nvdbget:	nvdbget.c libnvdb.o 
	$(CC) $(CFLAGS) -o $@ nvdbget.c libnvdb.o -L. -lphoenix -ldl

nvdbput:	nvdbput.c libnvdb.o 
	$(CC) $(CFLAGS) -o $@ nvdbput.c libnvdb.o -L. -lphoenix -ldl

nvdbtest: libphoenix.a nvdbtest.c
	$(CC) $(CFLAGS) -o $@ nvdbtest.c libnvdb.o -L. -lphoenix -ldl

testcommlib: libphoenix.a testcommlib.c
	$(CC) $(CFLAGS) -o $@ testcommlib.c -L. -lphoenix -ldl

clean:
	rm -f *.o *.a a.out $(PDEPEND) nvdbtest testcommlib junk.nvdb *.test

$(PDEPEND):	$(PCSOURCES) $(PINCLUDE) Makefile
	$(CC) -MM $(CFLAGS) -I. -I../paflogd $(CCEINCLUDE) $(PCSOURCES) > $@

.cpp.o:
	$(CC) $(CFLAGS) -c $<

# This should really be gcc, but don't change it or lots will break
# If you need to compile with gcc then add a build rule at EOF.
.c.o:
	$(CC) $(CFLAGS) -c $<

libphoenix.c: libphoenix.h

# test harness for Profile.c
Profile:	Profile.o serial.o
	$(CC) $(CFLAGS) -DRELVERSION=\"7.7.1\" -o Profile Profile.o serial.o

create_profile:	create_profile.c Profile.h serial.h Profile.o serial.o
	$(CC) $(CFLAGS) -o create_profile create_profile.c Profile.o serial.o

BufferedQueue.test:	BufferedQueue.test.o libphoenix.a
	$(CC) $(CFLAGS) -o BufferedQueue.test BufferedQueue.test.o -L. -lphoenix -lpthread

EventScheduler.test:	EventScheduler.test.o libphoenix.a
	$(CC) $(CFLAGS) -o EventScheduler.test EventScheduler.test.o -L. -lphoenix -lpthread

ObjectFile.test:	ObjectFile.test.o libphoenix.a
	$(CC) $(CFLAGS) -o ObjectFile.test ObjectFile.test.o -L. -lphoenix

ObjectSocket.test:	ObjectSocket.test.o libphoenix.a
	$(CC) $(CFLAGS) -o ObjectSocket.test ObjectSocket.test.o -L. -lphoenix -lpthread

SmtpClient.test:	SmtpClient.test.o libphoenix.a
	$(CC) $(CFLAGS) -o SmtpClient.test SmtpClient.test.o -L. -lphoenix -lpthread

Thread.test:	Thread.test.o libphoenix.a
	$(CC) $(CFLAGS) -o Thread.test Thread.test.o -L. -lphoenix -lpthread

TimeTable.test:	TimeTable.test.o libphoenix.a
	$(CC) $(CFLAGS) -o TimeTable.test TimeTable.test.o -L. -lphoenix -lpthread

SynchronizedDeque.test:	SynchronizedDeque.test.o libphoenix.a
	$(CC) $(CFLAGS) -o SynchronizedDeque.test SynchronizedDeque.test.o -L. -lphoenix -lpthread

# Special gcc rules
CceClient.o:
	gcc $(CFLAGS) $(CCEINCLUDE) -c $<
