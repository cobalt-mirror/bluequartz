/*

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/TimeTable.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  26-Jul-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

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

#include "TimeTable.h"

#include "ObjectInput.h"
#include "ObjectOutput.h"

const int STR_SIZE = 1024;


/* 
 *  scheduledTime - returns time_t for given time parameters.
 *                  If daysAhead set, return time daysAhead from currentTime.
 *                  mktime() will normalize date:

       The  mktime()  function converts a broken-down time struc-
       ture, expressed as local time, to calendar time  represen-
       tation.   The  function  ignores the specified contents of
       the structure members tm_wday and tm_yday  and  recomputes
       them  from  the  other information in the broken-down time
       structure.  If structure members are outside  their  legal
       interval, they will be normalized (so that, e.g., 40 Octo-
       ber is changed into 9 November).   Calling  mktime()  also
       sets  the  external variable tzname with information about
       the current time zone.  If the specified broken-down  time
       cannot  be represented as calendar time (seconds since the
       epoch), mktime() returns a value of (time_t)(-1) and  does
       not  alter  the tm_wday and tm_yday members of the broken-
       down time structure.

 */
time_t DailyTimeTable::scheduledTime (const time_t currentTime, 
				      const int daysAhead)
{
    time_t stime;
    struct tm *ct = localtime( &currentTime );

    ct -> tm_sec  = getSecond();
    ct -> tm_min  = getMinute();
    ct -> tm_hour = getHour();

    if (daysAhead > 0)
        ct -> tm_mday += daysAhead;

    if ((stime = mktime(ct)) < 0) 
    {
        throw RuntimeException("::scheduledTime: mktime() failed");
    }
    return stime;
}


/*********************  DailyTimeTable  *************************/


time_t DailyTimeTable::nextTime (const time_t currentTime)
{
    time_t stime = scheduledTime(currentTime);

    // Is the currentTime past the scheduled time or not?
    if ( currentTime <= stime )
    {
        return stime;
    }
    else
    {
        return scheduledTime(currentTime, 1);  // tomorrow
    }
}

string DailyTimeTable::timeDescription (void)
{
    char tmp[STR_SIZE+1];

    snprintf( tmp, sizeof(tmp), "every day at %02d:%02d", 
                                 getHour(), getMinute() );

    string str = tmp;

    return str;
}

void DailyTimeTable::readExternal (ObjectInput& obj)
{
    int version = obj.readInt ();

    if (version != serialVersionId)
    {
	throw IOException ("DailyTimeTable::readExternal(): invalid serial version");
    }

    hour = obj.readInt ();
    minute = obj.readInt ();
    second = obj.readInt ();
}

void DailyTimeTable::writeExternal (ObjectOutput& obj) const
{
    obj.writeInt (serialVersionId);
    obj.writeInt (hour);
    obj.writeInt (minute);
    obj.writeInt (second);
}


/*********************  WeeklyTimeTable  *************************/


time_t WeeklyTimeTable::nextTime (const time_t currentTime)
{
    struct tm *ct = localtime( &currentTime ); // now
    int days_between;                          // days between now and sday
    unsigned int sday = ct->tm_wday;           // scheduled day 

    // Is today the day for the scheduled event?
    if (isWeekdaySet(sday))
    {
        time_t stime = scheduledTime(currentTime);

        if ( currentTime <= stime )
            return stime;
    }

    // No.  What is the next closest day after today that is scheduled?
    do 
    {
        sday = (sday+1) % 7;

    } while (! isWeekdaySet(sday));


    if ((unsigned) ct->tm_wday > sday)          
    {   
        days_between = (sday - ct->tm_wday) + 7;    // next week
    }
    else                                 
    {   
        days_between = sday - ct->tm_wday;          // this week or 1 week
    }                                               // from now if zero

    return scheduledTime(currentTime, days_between > 0 ? days_between : 7);
}


string WeeklyTimeTable::timeDescription (void)
{
    char days[STR_SIZE+1] = "every ";
    char *p = days + strlen(days);
    string str;

    // Build the set of days
    int i = 0;
    if (isWeekdaySet(0))
        i += sprintf( p+i, "Sun" );
    if (isWeekdaySet(1))
        i += sprintf( p+i, "%sMon", i ? ", " : "" );
    if (isWeekdaySet(2))
        i += sprintf( p+i, "%sTue", i ? ", " : "" );
    if (isWeekdaySet(3))
        i += sprintf( p+i, "%sWed", i ? ", " : "" );
    if (isWeekdaySet(4))
        i += sprintf( p+i, "%sThu", i ? ", " : "" );
    if (isWeekdaySet(5))
        i += sprintf( p+i, "%sFri", i ? ", " : "" );
    if (isWeekdaySet(6))
        i += sprintf( p+i, "%sSat", i ? ", " : "" );

    // If two or more days are scheduled, then remove the last comma
    // and insert 'and'
    if ((p = strrchr(days, ',')) != 0) 
    {
        *p = '\0';
        str = days;
        str += " and";
        str += p+1;
    }
    else
    {
        str = days;
    }

    snprintf (days, sizeof(days), " at %02d:%02d", 
                                   getHour(), getMinute());
    str += days;

    return str;
}

void WeeklyTimeTable::readExternal (ObjectInput& obj)
{
    int version = obj.readInt ();

    if (version != serialVersionId)
    {
	throw IOException ("WeeklyTimeTable::readExternal(): invalid serial version");
    }

    weekday = obj.readByte ();
}

void WeeklyTimeTable::writeExternal (ObjectOutput& obj) const
{
    obj.writeInt (serialVersionId);
    obj.writeByte (weekday);
}


/*********************  MonthlyTimeTable  *************************/


time_t MonthlyTimeTable::nextTime (const time_t currentTime)
{
    struct tm *ct = localtime( &currentTime );
    time_t stime;

    // Compute scheduled time based on currentTime; everything else
    // remains equal.
    ct -> tm_mday = dayOfMonth;
    ct -> tm_sec  = getSecond();
    ct -> tm_min  = getMinute();
    ct -> tm_hour = getHour();

    if ((stime = mktime(ct)) < 0) 
    {
        throw RuntimeException("::nextTime: mktime() failed");
    }

    // Is currentTime before the scheduled day?
    if (currentTime <= stime)
        return stime;             

    // No, recalculate time_t for same day and time of next month

    ct -> tm_mon = (ct -> tm_mon + 1) % 12;   // increment month
    if (ct -> tm_mon == 0)                    // increment year
        ct -> tm_year++;

    if ((stime = mktime(ct)) < 0) 
    {
        throw RuntimeException("::nextTime: mktime() failed");
    }
    return stime;
}


string MonthlyTimeTable::timeDescription (void)
{
    char tmp[STR_SIZE+1];
    char day[5];

    int len = sprintf( day, "%d", dayOfMonth );  // convert day into string

    switch (day[len-1])               // what is last char of day?
      {                               // append suffix
        case '1':
          strcpy(day+len, "st");
          break;
        case '2':
          strcpy(day+len, "nd");
          break;
        case '3':
          strcpy(day+len, "rd");
          break;
        default:
          strcpy(day+len, "th");
          break;
    }

    snprintf (tmp, sizeof(tmp), "every %s of the month at %02d:%02d", 
                                 day, getHour(), getMinute());

    string str = tmp;

    return str;
}

void MonthlyTimeTable::readExternal (ObjectInput& obj)
{
    int version = obj.readInt ();

    if (version != serialVersionId)
    {
	throw IOException ("MonthlyTimeTable::readExternal(): invalid serial version");
    }

    dayOfMonth = obj.readByte ();
}

void MonthlyTimeTable::writeExternal (ObjectOutput& obj) const
{
    obj.writeInt (serialVersionId);
    obj.writeByte (dayOfMonth);
}


/*********************  CustomTimeTable  ************************/


/*
 *  nextTime is always relative to firstTime
 */
time_t CustomTimeTable::nextTime (const time_t currentTime)
{
    time_t stime = firstTime;

    // currentTime is before the scheduledTime
    if (stime >= currentTime)
        return stime;

    // currentTime is past the scheduledTime
    const unsigned long int period = customHours * 3600;

    // -----f------------p--+--c---+--p---...time
    //                      A      B
    // f = firstTime
    // p = period
    // c = currentTime
    // A = ((currentTime - firstTime) % period))
    // B = period - A
    // return c + B

    stime = currentTime + (period - ((currentTime - firstTime) % period));

    return stime;
}

string CustomTimeTable::timeDescription (void)
{
    struct tm *ct = localtime( &firstTime );
    char tmp[STR_SIZE+1];

    snprintf (tmp, sizeof(tmp), "every %d hours beginning at %02d:%02d", 
                                 customHours, ct -> tm_hour, ct -> tm_min);

    string str = tmp;
    return str;
}

void CustomTimeTable::readExternal (ObjectInput& obj)
{
    int version = obj.readInt ();

    if (version != serialVersionId)
    {
	throw IOException ("CustomTimeTable::readExternal(): invalid serial version");
    }

    customHours = obj.readInt ();
    firstTime = obj.readInt ();
}

void CustomTimeTable::writeExternal (ObjectOutput& obj) const
{
    obj.writeInt (serialVersionId);
    obj.writeInt (customHours);
    obj.writeInt (firstTime);
}

