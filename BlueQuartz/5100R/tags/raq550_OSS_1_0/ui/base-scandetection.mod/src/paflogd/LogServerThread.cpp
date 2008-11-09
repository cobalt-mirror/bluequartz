/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/LogServerThread.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  25-Jul-2000
    Originating Author :  Brian Adkins, Sam Napolitano

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:38 $

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

# include "Exception.h"
# include "utility.h"
# include "libphoenix.h"

# include "BufferedQueue.h"
# include "EventScheduler.h"
# include "Thread.h"
# include "ObjectFile.h"
# include "Event.h"
# include "TimeTable.h"
# include "LogServerThread.h"


static const string LOG_SETTINGS_FILE = "log_settings.cfg";

// For now, just use the defaults.
LogSettings * LogServerThread::readLogSettings ()
{
    return new LogSettings ();
}

void LogServerThread::writeLogSettings (const LogSettings& settings) const
{
    return;
}

#if 0
LogSettings * LogServerThread::readLogSettings ()
{
    try
    {
	ObjectFile of (LOG_SETTINGS_FILE);

	try
	{
	    LogSettings * settings = 
		dynamic_cast<LogSettings*>(of.readObject());
	    
	    if (settings == 0)
	    {
		throw IOException ("readLogSettings(): failed");
	    }

	    return settings;
	}
	catch (EOFException& eof)
	{
	    return new LogSettings ();
	}
    }
    catch (Exception& ex)
    {
        LOG(LOG_BASE, "LogServerThread::readLogSettings: %s", 
	               ex.message().c_str());
	assert (0);
	throw;
    }
}

void LogServerThread::writeLogSettings (const LogSettings& settings) const
{
    try
    {
	ObjectFile of (LOG_SETTINGS_FILE);
	of.writeObject (settings);
    }
    catch (Exception& ex)
    {
        LOG(LOG_BASE, "LogServerThread::writeLogSettings: %s", 
	               ex.message().c_str());
	assert (0);
	throw;
    }
}
#endif

LogServerThread::LogServerThread (EventScheduler& evtsch) : eventScheduler(evtsch)
{
    LOG(LOG_PROCTOL, "LogServerThread:: entered run");

    try
    {
	//------------------------------------------------------------
	// Read LogSettings from disk
	//------------------------------------------------------------
	LogSettings * s = readLogSettings ();
	settings = *s;
	delete s;
    }
    catch (Exception& ex)
    {
        LOG(LOG_BASE, "LogServerThread::LogServerThread: %s", 
                       ex.message().c_str());
    }
    catch (...)
    {
	assert (0);
    }
}

void LogServerThread::modifySettings (const LogSettings& s)
{
    MutexLock lock (mutex);
    settings = s;

# ifdef DEBUG
    settings.printSettings();
# endif

    writeLogSettings (settings);

    // Settings have changed; update EventScheduler and reschedule new events
    createEvents();
}
 
LogSettings LogServerThread::getSettings () const
{
    MutexLock lock (mutex);
    return settings;
}

//////////////////////////////////////////////////////////////////////////

/*
   clear scheduler
   create a new RotateLogs object passing the LogServerThread as an arg
     so RotateLogs can read the settings.
   schedule ONE rotate logs event; RotateLogs will reschedule the next time
     it should rotate
*/
void LogServerThread::createEvents ()
{
    time_t eventTime = settings.getRotateTime();
    struct tm *tm = localtime((const time_t *) &eventTime);
    int sec  = tm -> tm_sec;
    int min  = tm -> tm_min;
    int hour = tm -> tm_hour;

    // Erase the current scheduler
    eventScheduler.clear();

    // Schedule the rotate logs event
    RotateLogs *rlogs = new RotateLogs(*this, true);
    if (rlogs == 0x0) 
    {
      throw RuntimeException ("LogServerThread::createEvents: out of memory");
    }

    switch (settings.getRotationFrequency())
    {
        case LogSettings::ROTATE_NONE:
        case 0:
	{
	  delete rlogs;
	  return;
	}
	    
        case LogSettings::ROTATE_DAILY:
	{
	  DailyTimeTable dailyEvent( hour, min, sec );
	  time_t eventTime = dailyEvent.nextTime( TIME );
	  Event daily( eventTime, rlogs );
	  eventScheduler.addEvent( daily );
	  break;
	}

	// The LogSettings representation for the days of the week is
	// an array of booleans and the WeeklyTimeTable representation is
	// a bitmask.  Although weeks don't change very often, I'm trying
	// to make use of each public interface to avoid future problems.
        case LogSettings::ROTATE_WEEKLY:
	{
	  unsigned char daysOfWeek = 0;
	  
	  for( int i = LogSettings::SUNDAY; i <= LogSettings::SATURDAY; i++ )
	  {
	    if (settings.isWeekdaySet(i))
  	        switch (i)
		{
		case LogSettings::SUNDAY:
		    daysOfWeek |= WeeklyTimeTable::SUNDAY;	break;
		case LogSettings::MONDAY:
		    daysOfWeek |= WeeklyTimeTable::MONDAY;	break;
		case LogSettings::TUESDAY:
		    daysOfWeek |= WeeklyTimeTable::TUESDAY;	break;
		case LogSettings::WEDNESDAY:
		    daysOfWeek |= WeeklyTimeTable::WEDNESDAY;	break;
		case LogSettings::THURSDAY:
		    daysOfWeek |= WeeklyTimeTable::THURSDAY;	break;
		case LogSettings::FRIDAY:
		    daysOfWeek |= WeeklyTimeTable::FRIDAY;	break;
		case LogSettings::SATURDAY:
		    daysOfWeek |= WeeklyTimeTable::SATURDAY;    break;
		} // switch
	  } // for

	  WeeklyTimeTable weeklyEvent( hour, min, daysOfWeek );
	  eventTime = weeklyEvent.nextTime( TIME );
	  Event weekly( eventTime, rlogs );
	  eventScheduler.addEvent( weekly );
	  break;
	}

        case LogSettings::ROTATE_MONTHLY:
        {
	  MonthlyTimeTable monthlyEvent( hour, min, settings.getDayOfMonth() );
	  eventTime = monthlyEvent.nextTime( TIME );
	  Event monthly( eventTime, rlogs );
	  eventScheduler.addEvent( monthly );
	  break;
	}

        case LogSettings::ROTATE_CUSTOM:
	{
	  CustomTimeTable customEvent( settings.getRotateTime(), 
				       settings.getCustomHours() );
	  eventTime = customEvent.nextTime( TIME );
	  Event custom( eventTime, rlogs );
	  eventScheduler.addEvent( custom );
	  break;
	}

        default:
        {
 LOG(LOG_BASE, "LogServerThread::createEvents: Unknown rotationFrequency: %d",
	        settings.getRotationFrequency());
	  return;
	}
    }

    // Schedule other events here
}


void RotateLogs::run()
{
    LOG(LOG_PROCTOL, "RotateLogs:: entered run");

    LogSettings settings = logServer.getSettings();
    const char *ROTATE_SCRIPT = "/etc/phoenix/scripts/rotatelogs";

    // libphoenix.h
    extern int spawnprocess( const char *command, char *argv[],
			     char *result, int maxreslen );

    int ret;
    char result[1024];

    // itoa
    char maxr[sizeof(unsigned int)+1];
    snprintf( maxr, sizeof(maxr), "%u", settings.getMaxNumberLogFiles() );

    const char *argv[] = {
	ROTATE_SCRIPT,
	settings.getCompressArchiveFiles() ? "-c" : "",
	"-r",
	maxr,
	0x0
    };

    ret = spawnprocess( argv[0], (char **)argv, &result[0], sizeof(result) );
    if (ret != 0) 
    {
        LOG(LOG_BASE, "spawnprocess(%s) failed:", argv[0]);
        LOG(LOG_BASE, "%s", result);
    }

    // If the EventScheduler called us, then reschedule the next event.
    // If WriterThread called us, then we're done.
    if (reschedule) 
    {
	// Reschedule the next event
	logServer.createEvents();

	// Now we are finished with this object, free it
	delete this;
    }
}
