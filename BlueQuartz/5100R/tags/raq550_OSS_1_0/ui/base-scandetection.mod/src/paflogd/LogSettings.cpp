/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/paflogd/LogSettings.cpp,v $
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

#include "LogSettings.h"
#include "Exception.h"
#include "libphoenix.h"


LogSettings::LogSettings ()
{
    init ();
}

LogSettings::LogSettings (const LogSettings& obj)
{
    init (obj);
}

LogSettings& LogSettings::operator=(const LogSettings& obj)
{
    if (this != &obj)
    {
	init (obj);
    }

    return *this;
}

void LogSettings::init ()
{
    compressArchiveFiles = false;
    customHours          = 0;
    dayOfMonth           = 0;
    
    for (int i = 0; i < 7; i++)
    {
	daysOfWeek[i] = false;
    }

    maxLogFileSize      = 0;
    maxNumberLogFiles   = 0;
    minFreeLogPartition = 0;  // 2/20/2001: will not implement; can be removed
    rotationFrequency   = ROTATE_NONE;
    rotationTime        = 0;
    outputFile          = "";
}

void LogSettings::init (const LogSettings& obj)
{
    compressArchiveFiles = obj.compressArchiveFiles;
    customHours          = obj.customHours;
    dayOfMonth           = obj.dayOfMonth;
    
    for (int i = 0; i < 7; i++)
    {
	daysOfWeek[i] = obj.daysOfWeek[i];
    }

    maxLogFileSize      = obj.maxLogFileSize;
    maxNumberLogFiles   = obj.maxNumberLogFiles;
    minFreeLogPartition = obj.minFreeLogPartition;
    rotationFrequency   = obj.rotationFrequency;
    rotationTime        = obj.rotationTime;
    outputFile          = obj.outputFile;
}

void LogSettings::readExternal (ObjectInput& obj)
{
    int version = obj.readInt ();

    if (version != serialVersionId)
    {
	throw IOException ("LogSettings::readExternal(): invalid version");
    }

    compressArchiveFiles    = obj.readBoolean    ();
    customHours             = obj.readShort      ();
    dayOfMonth              = obj.readByte       ();

    short bits = obj.readShort ();

    for (int i = 0; i < 7; i++)
    {
	if ((bits & (1 << (6-i))) != 0)
	{
	    daysOfWeek[i] = true;
	}
            else
            {
                daysOfWeek[i] = false;
            }
    }

    maxLogFileSize          = obj.readInt    ();
    maxNumberLogFiles       = obj.readInt    ();
    minFreeLogPartition     = obj.readInt    ();
    rotationFrequency       = obj.readByte   ();
    rotationTime            = obj.readInt    ();
    outputFile              = obj.readString ();
}

void LogSettings::writeExternal (ObjectOutput& obj) const
{
    obj.writeInt (serialVersionId);

    obj.writeBoolean (compressArchiveFiles);
    obj.writeShort (customHours);
    obj.writeByte (dayOfMonth);

    short bits = 0;

    for (int i = 0; i < 7; i++)
    {
	if (daysOfWeek[i])
	{
	    bits |= (1 << (6-i));
	}
    }

    obj.writeShort    (bits);

    obj.writeInt      (maxLogFileSize);
    obj.writeInt      (maxNumberLogFiles);
    obj.writeInt      (minFreeLogPartition);
    obj.writeByte     (rotationFrequency);
    obj.writeInt      (rotationTime);
    obj.writeString   (outputFile);
}

# ifdef DEBUG

#include "utility.h"

void LogSettings::printSet(const LogSettings& obj, int debuglvl) const
{
    LOG(debuglvl, "Compress=%d RotateTime=%ld",  
	obj.compressArchiveFiles, obj.rotationTime - TIME);

    const char *days[] = { "S", "M", "T", "W", "H", "F", "S" };
    string day;

    for (int i = 0; i < 7; i++)
      if (isWeekdaySet(i))
	day += days[i];

    LOG(debuglvl, "Day=%d Week=%s Custom=%hd Freq=%d",
	obj.dayOfMonth, day.c_str(), obj.customHours, obj.rotationFrequency);

    LOG(debuglvl, "LogFileSize=%lu MaxNum=%d MinFree=%lu OutF=%s",
	obj.maxLogFileSize, obj.maxNumberLogFiles, obj.minFreeLogPartition, 
	obj.outputFile.c_str());
}

#endif /* DEBUG */
