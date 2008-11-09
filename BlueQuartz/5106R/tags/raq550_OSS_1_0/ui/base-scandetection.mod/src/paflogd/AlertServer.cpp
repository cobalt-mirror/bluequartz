/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/AlertServer.cpp,v $
              Revision :  $Revision: 1.7.2.2 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  12-Nov-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: ge $ 
    Date Last Modified :  $Date: 2002/03/12 16:37:02 $

   **********************************************************************

   Copyright (c) 2000 Progressive Systems Inc.
   All rights reserved.

   This code is confidential property of Progressive Systems Inc.  The
   algorithms, methods and software used herein may not be duplicated or
   disclosed to any party without the express written consent from
   Progressive Systems Inc.

   Progressive Systems Inc. makes no representations concerning either
   the merchantability of this software or the suitability of this
   software for any particular purpose.

   These notices must be retained in any copies of any part of this
   documentation and/or software.

   ********************************************************************** */

#include <cassert>
#include <memory>
#include <sys/statfs.h>
#include <syslog.h>             /* close/openlog() */
#include <errno.h>

#include "Alert.h"
#include "AlertServer.h"
#include "AlertSettings.h"
#include "AlertSocketThread.h"
#include "BufferedQueue.h"
#include "CommandPacket.h"
#include "EventScheduler.h"
#include "libphoenix.h"
#include "LogServer.h"
#include "NotifierThread.h"
#include "ObjectFile.h"
#include "ObjectSocketThread.h"
#include "ReaderThread.h"
#include "SynchronizedDeque.h"
#include "Thread.h"
#include "utility.h"
#include <typeinfo>
#include "Alert.h"
#include "AlertSettings.h"
#include "LogSettings.h"
#include "CommandPacket.h"
#include "DataUnit.h"
#include "ErrorPacket.h"
#include "CceClient.h"
#include "processutil.h"


int debug = LOG_BASE;       // needed by libphoenix

static const string ALERT_SETTINGS_FILE = "alert_settings.cfg";
static const string ALERT_PROC_FILE = "/proc/net/phoenix_alert";

void handleSystemRebootAlert (const AlertServer& server, 
			      SynchronizedDeque<Alert>& alertQueue);

typedef Externalizable * (*PF)();
map<string, PF> io_map;

static void init_io_map ()
{
    // Alert
    io_map[typeid(Alert).name()] = 
	&Alert::createExternalizable;

    // AlertSettings
    io_map[typeid(AlertSettings).name()] = 
	&AlertSettings::createExternalizable;

    // CommandPacket
    io_map[typeid(CommandPacket).name()] = 
	&CommandPacket::createExternalizable;

    // DataUnit
    io_map[typeid(DataUnit).name()] = 
	&DataUnit::createExternalizable;

    // ErrorPacket
    io_map[typeid(ErrorPacket).name()] = 
	&ErrorPacket::createExternalizable;
}

static const char *PAFALERTD_PID_FILE =  "/var/run/pafalertd.pid";

#if 0
void write_pid_file(int argc, char **argv)
{
    FILE *fp;
    int i;

    if ((fp = fopen(PAFALERTD_PID_FILE, "w")) == NULL)
    {
        throw RuntimeException( formatString("cannot open '%s': %s", 
					      PAFALERTD_PID_FILE,
					      strerror(errno)) );
    }

    if (chmod (PAFALERTD_PID_FILE, 0644) < 0)
        LOG( LOG_BASE, "unable to 'chmod 644 %s'", PAFALERTD_PID_FILE );
    
    /* line 1 - the pid */
    fprintf( fp, "%d\n", getpid() );

    /* line 2 - the command line arguments */
    for (i = 0; i < argc; i++)
        fprintf( fp, "%s ", argv[i] );

    fprintf( fp, "\n" );
    fclose( fp );
}
#endif


int main (int argc, char * argv[])
{
    LOG (LOG_BASE, "Alert Server starting");

    init_io_map ();

    SynchronizedDeque<Alert> alertQueue;
    AlertServer server;
    BufferedQueue queue;

    try
    {
	// Become a daemon
	Daemonize(CreatePidfile(PAFALERTD_PID_FILE));
	openlog( "pafalertd", LOG_PID, LOG_LOCAL7 );    

	AlertServer server;

	//------------------------------------------------------------
	// Start notifier thread
	//------------------------------------------------------------

	NotifierThread notifier (server, queue, alertQueue);
	Thread notifierThread (&notifier);
	notifierThread.start ();
#if 0
	//------------------------------------------------------------
	// Send System Reboot alert if requested
	//------------------------------------------------------------

	handleSystemRebootAlert (server, alertQueue);
#endif
	//------------------------------------------------------------
	// Start the input reader thread
	//------------------------------------------------------------

	AlertReaderThread reader (queue, ALERT_PROC_FILE);
	Thread readerThread (&reader);
	readerThread.start ();

	//------------------------------------------------------------
	// Start the object socket thread
	//------------------------------------------------------------

#ifdef WEDONTCAREABOUTSECURITY
	ServerSocket serverSocket (AlertServer::SERVER_PORT);
	AlertSocketThread socketThread (server, serverSocket, alertQueue);
	Thread osThread (&socketThread);
	osThread.start ();
#endif

	//------------------------------------------------------------
	// Start the event scheduler thread
	//------------------------------------------------------------
#if 0
	EventScheduler scheduler;
        CheckLowDisk checkLowDisk (server, alertQueue);
        Event lowDiskEvent (time(0), &checkLowDisk, 0, false, 300);
        scheduler.addEvent (lowDiskEvent);
	Thread eventThread (&scheduler);
	eventThread.start ();
#endif
	//------------------------------------------------------------
	// Join threads
	//------------------------------------------------------------
#if 0
	eventThread.join ();
#endif
#ifdef WEDONTCAREABOUTSECURITY
	osThread.join ();
#endif
	readerThread.join ();
	notifierThread.join ();

	unlink(PAFALERTD_PID_FILE);
    }
    catch (Exception &e)
    {
        LOG(LOG_BASE, e.message().c_str());
        exit(1);
    }
    catch (...)
    {
        LOG(LOG_BASE, "Unknown exception");
	assert (0);
    }

    return 0;
}

void handleSystemRebootAlert (const AlertServer& server, 
			      SynchronizedDeque<Alert>& alertQueue)
{
    AlertSettings settings = server.getSettings ();
    
    if (settings.getSystemReboot())
    {
	Alert alert (Alert::REBOOT_ALERT, time(0), "System Reboot");
	alertQueue.push_back (alert);
    }
}

// For now, we are just going to set the values with defaults
// Later they can be changed via a client program.

AlertSettings * AlertServer::readAlertSettings ()
{
    AlertSettings * settings = new AlertSettings ();
    string emailAddress = "admin@localhost";

    settings->setScanAttack(true);
    settings->setRestrictedSiteAccess(true);

    // Another hack.  For Tadpole, is port scan emailing on?  Zero
    // means strictly no.

    if (isPortScanOn() == 0) {
        settings->setScanAttack(false);
        settings->setRestrictedSiteAccess(false);
        LOG(LOG_BASE, "Port scanning is off");
    }else{
        LOG(LOG_BASE, "Port scanning is on");
    }

    // This will have to be CCE aware in future
    char buf[1024];
    if (get_admin_email(buf, sizeof(buf))) {
        emailAddress = buf;
	LOG(LOG_BASE, "Email address: %s", buf);
    }

    settings->setEmailAddress(emailAddress);    
    settings->setSmtpServer("localhost");

    return settings;
}

void AlertServer::writeAlertSettings (const AlertSettings& settings) const
{
    return;
}

#if 0
// To store on disk
AlertSettings * AlertServer::readAlertSettings ()
{
    try
    {
	ObjectFile of (ALERT_SETTINGS_FILE);

	try
	{
	    AlertSettings * settings = 
		dynamic_cast<AlertSettings*>(of.readObject());
	    
	    if (settings == 0)
	    {
		throw IOException ("readAlertSettings(): failed");
	    }

	    return settings;
	}
	catch (EOFException& eof)
	{
	    return new AlertSettings ();
	}
    }
    catch (Exception& ex)
    {
	assert (0);
	throw;
    }
}


void AlertServer::writeAlertSettings (const AlertSettings& settings) const
{
    try
    {
	ObjectFile of (ALERT_SETTINGS_FILE);
	of.writeObject (settings);
    }
    catch (Exception& ex)
    {
	assert (0);
	throw;
    }
}
#endif

AlertServer::AlertServer ()
{
    try
    {
	//------------------------------------------------------------
	// Read AlertSettings from disk
	//------------------------------------------------------------

	AlertSettings * s = readAlertSettings ();
	settings = *s;
	delete s;
    }
    catch (...)
    {
	assert (0);
        throw;
    }
}

void AlertServer::modifySettings (const AlertSettings& s)
{
    MutexLock lock (mutex);
    settings = s;
    writeAlertSettings (settings);
}
 
AlertSettings AlertServer::getSettings () const
{
    MutexLock lock (mutex);
    return settings;
}
    
void CheckLowDisk::run ()
{
    LOG (LOG_PROCTOL, "CheckLowDisk::run(): entered");

    AlertSettings settings = server.getSettings ();

    if (!settings.getLowDiskSpace())
    {
        return;
    }

    int lowMegs = settings.getLowDiskSpaceValue ();
    unsigned long fsMegs;
    int rc = getAvailableDisk (PHOENIX_LOG, &fsMegs);

    if (rc != 0)
    {
	LOG (LOG_ERROR, "CheckLowDisk::run(): getAvailableDisk() failed");
	return; // todo
    }

    if (fsMegs < static_cast<unsigned long>(lowMegs))
    {
        // Generate an alert
        Alert alert (Alert::LOW_DISK_ALERT, time(0), "low disk space");
        alertQueue.push_back (alert);
    }
}







