/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/LogSettings.h,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  06-Nov-2000
    Originating Author :  Brian Adkins

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

#ifndef _LOGSETTINGS_H_
#define _LOGSETTINGS_H_

# include <string>
# include "Externalizable.h"
# include "ObjectInput.h"
# include "ObjectOutput.h"
# include "libphoenix.h"

using namespace std;

class LogSettings : public Externalizable
{
  private:
    //  Increment serialVersion each time the database representation changes.
    static const int serialVersionId = 1;

  public:
    static const unsigned char ROTATE_CUSTOM   = 1;
    static const unsigned char ROTATE_DAILY    = 2;
    static const unsigned char ROTATE_MONTHLY  = 3;
    static const unsigned char ROTATE_NONE     = 4;
    static const unsigned char ROTATE_WEEKLY   = 5;

    enum { SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY, 
	   FRIDAY, SATURDAY };

  private:
    bool          compressArchiveFiles;   // Compress log files?
    time_t        rotationTime;           // Time field
    unsigned char dayOfMonth;             // dailyTimeTable
    bool          daysOfWeek[7];          // weeklyTimeTable 0=Sun, 1=Mon
    short         customHours;            // customTimeTable (hrs. nxt rotate)
    unsigned char rotationFrequency;      // none, daily, weekly, etc.
    
    unsigned int  maxLogFileSize;         // In megabytes (k is better)
    int           maxNumberLogFiles;
    unsigned int  minFreeLogPartition;    // In megabytes (k is better)
    string        outputFile;             // name of log file on disk

    void init ();
    void init (const LogSettings &);

  public:
    LogSettings ();
    LogSettings (const LogSettings&);
    LogSettings& operator=(const LogSettings&);
    ~LogSettings () {}

    bool        getCompressArchiveFiles() const { return compressArchiveFiles;}
    time_t        getRotateTime()         const { return rotationTime; }
    unsigned char getDayOfMonth()         const { return dayOfMonth; }
    bool          isWeekdaySet(int day)   const 
    { if (day < SUNDAY || day > SATURDAY)
          return false;
      return daysOfWeek[day]; 
    }
    short         getCustomHours()        const { return customHours; }
    unsigned char getRotationFrequency()  const { return rotationFrequency; }

    unsigned int getMaxLogFileSize()      const { return maxLogFileSize; }
    int          getMaxNumberLogFiles()   const { return maxNumberLogFiles; }
    unsigned int getMinFreeLogPartition() const { return minFreeLogPartition; }
    string       getOutputFile()          const { return outputFile; }

    void setMaxLogFileSize(unsigned int m)      { maxLogFileSize = m; }
    void setMaxNumberLogFiles(int m)            { maxNumberLogFiles = m; }
    void setMinFreeLogPartition(unsigned int m) { minFreeLogPartition = m; }
    void setOutputFile(const string& f)         { outputFile = f; }

    static Externalizable * createExternalizable () 
        { return new LogSettings(); }

    virtual void readExternal (ObjectInput&);
    virtual void writeExternal (ObjectOutput&) const;

# ifdef DEBUG
    // These should only be needed for testing
    void setCompressArchiveFiles(bool s) { compressArchiveFiles = s; }
    void setRotationTime(time_t t) { rotationTime = t; }
    void setDayOfMonth(unsigned char dom) { dayOfMonth = dom; }
    void setDaysOfWeek(bool *dow) { for (int i=SUNDAY; i <= SATURDAY; i++) 
                                         daysOfWeek[i] = dow[i]; }
    void setWeekday(int day) { daysOfWeek[day] = true; }
    void setCustomHours(short ch) { customHours = ch; }
    void setRotationFrequency(unsigned char rf) { rotationFrequency = rf; }

    // Testing only
    void printSettings(int debuglvl = LOG_BASE) const
      { printSet(*this, debuglvl); }
    void printSettings(const LogSettings& obj, int debuglvl = LOG_BASE) const
      { printSet(obj, debuglvl); }

 private:
    void printSet(const LogSettings& obj, int debuglvl) const;
# endif /* DEBUG */

};

#endif /* #ifndef _LOGSETTINGS_H_ */


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
