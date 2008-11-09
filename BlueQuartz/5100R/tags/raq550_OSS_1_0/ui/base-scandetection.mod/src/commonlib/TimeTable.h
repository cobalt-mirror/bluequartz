/*

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/TimeTable.h,v $
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

#ifndef _TIMETABLE_H_
#define _TIMETABLE_H_

#include <cstdio>
#include <ctime>
#include <string>
#include "Exception.h"
#include "Externalizable.h"

using namespace std;

class TimeTable : public Externalizable
{
 public:
    virtual ~TimeTable () {}

    virtual time_t nextTime (const time_t current) = 0;
    virtual string timeDescription (void) = 0;
};


class DailyTimeTable : public TimeTable
{
 private:
    static const int serialVersionId = 1;
    unsigned int hour, minute, second;

 protected:
    DailyTimeTable () : hour(0), minute(0), second(0) {}
    time_t scheduledTime(const time_t currentTime, const int daysAhead = 0);

    unsigned int getHour()   { return hour; }
    unsigned int getMinute() { return minute; }
    unsigned int getSecond() { return second; }

 public:
    DailyTimeTable (const unsigned int Hour, 
                    const unsigned int Minute, 
                    const unsigned int Second = 0) :
                    hour(Hour), minute(Minute), second(Second) 
    {
        if (hour >= 24)
          throw IllegalArgumentException("::DailyTimeTable: hour != 0-23");
        if (minute >= 60)
          throw IllegalArgumentException("::DailyTimeTable: minute != 0-59");
        if (second >= 60)
          throw IllegalArgumentException("::DailyTimeTable: second != 0-59");
    }
    virtual ~DailyTimeTable () {}

    static Externalizable * createExternalizable ()
	{ return new DailyTimeTable (); }

    virtual time_t nextTime (const time_t current);
    virtual string timeDescription (void);

    virtual void readExternal (ObjectInput&);
    virtual void writeExternal (ObjectOutput&) const;
};


class WeeklyTimeTable : public DailyTimeTable
{
 private:
    static const int serialVersionId = 1;
    unsigned char weekday;

    bool isWeekdaySet (const int currentDay)
    {
        // currentDay: 0=Sun, 1=Mon, ...
        return (((1 << currentDay) & weekday) != 0);
    }

 protected:
    WeeklyTimeTable () : weekday(0) {}

 public:
    // Use as WeeklyTimeTable::SUNDAY, etc.
    static const unsigned char SUNDAY    = 0x1;
    static const unsigned char MONDAY    = 0x2;
    static const unsigned char TUESDAY   = 0x4;
    static const unsigned char WEDNESDAY = 0x8;
    static const unsigned char THURSDAY  = 0x10;
    static const unsigned char FRIDAY    = 0x20;
    static const unsigned char SATURDAY  = 0x40;

 public:
    WeeklyTimeTable (const int Hour, const int Minute, 
                     const unsigned char w) : 
                     DailyTimeTable(Hour, Minute), weekday(w) 
    {
      if ((weekday & 0x7F) == 0)
          throw IllegalArgumentException("::WeeklyTimeTable: weekday must have at least one day set");
    }
        
    virtual ~WeeklyTimeTable () {}

    static Externalizable * createExternalizable ()
	{ return new WeeklyTimeTable (); }

    virtual time_t nextTime (const time_t current);
    virtual string timeDescription (void);

    virtual void readExternal (ObjectInput&);
    virtual void writeExternal (ObjectOutput&) const;
};


class MonthlyTimeTable : public DailyTimeTable
{
 private:
    static const int serialVersionId = 1;
    unsigned char dayOfMonth;

 protected:
    MonthlyTimeTable () : dayOfMonth(0) {}

 public:
    MonthlyTimeTable(const int Hour, const int Minute, const int d ) : 
                     DailyTimeTable(Hour, Minute), dayOfMonth(d) 
    {
        if (dayOfMonth < 1 || dayOfMonth > 31)
            throw IllegalArgumentException("::MonthlyTimeTable: dayOfMonth must be 1-31");
    }

    virtual ~MonthlyTimeTable () {}

    static Externalizable * createExternalizable ()
	{ return new MonthlyTimeTable (); }

    virtual time_t nextTime (const time_t current);
    virtual string timeDescription (void);

    virtual void readExternal (ObjectInput&);
    virtual void writeExternal (ObjectOutput&) const;
};


class CustomTimeTable : public TimeTable
{
 private:
    static const int serialVersionId = 1;
    int     customHours;
    time_t  firstTime;

 protected:
    CustomTimeTable () : customHours(0), firstTime(0) {}

 public:
    CustomTimeTable (time_t t, int n) : customHours(n), firstTime(t)
    {
        if (customHours <= 0)
          throw IllegalArgumentException("::CustomTimeTable: customHours must be > 0");
    }

    virtual ~CustomTimeTable () {}

    static Externalizable * createExternalizable ()
	{ return new CustomTimeTable (); }

    virtual time_t nextTime (const time_t current);
    virtual string timeDescription (void);

    virtual void readExternal (ObjectInput&);
    virtual void writeExternal (ObjectOutput&) const;
};

#endif /* #ifndef _TIMETABLE_H_ */
/* Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * -Redistribution of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 * 
 * -Redistribution in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution. 
 *
 * Neither the name of Sun Microsystems, Inc. or the names of contributors may
 * be used to endorse or promote products derived from this software without 
 * specific prior written permission.

 * This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
 */
