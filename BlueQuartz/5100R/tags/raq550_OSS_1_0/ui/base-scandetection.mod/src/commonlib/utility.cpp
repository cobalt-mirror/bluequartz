/*

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/utility.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  30-Oct-2000
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

#include <cstdio>
#include <cstddef>
#include <cstdlib>

#define USE_STATVFS 1

#if USE_STATVFS
#include <sys/statvfs.h>
#else
#include <sys/statfs.h>
#endif

#include "utility.h"

static const int MAX_FORMATTED_STRING = 1024;

string formatString (const char * fmt, ...)
{
    va_list ap;
    char buffer[MAX_FORMATTED_STRING+1];

    va_start (ap, fmt);
    vsnprintf (buffer, sizeof(buffer), fmt, ap);
    va_end (ap);
    buffer[sizeof(buffer)-1] = '\0';
    return string (buffer);
}

/*
 *  getAvailableDisk() written by Ge Weijers
 *
 *  return the amount of available disk space in megabytes
 */

#define PROPAGATE_ONES(X) ((X) == -1 ? (unsigned long)-1 : (unsigned long)(X))

int getAvailableDisk (const char *path, unsigned long *psize)
{
     unsigned long blocksize, blocksfree;
     unsigned long bpm;
#if USE_STATVFS
     struct statvfs fsd;
     if(statvfs(path, &fsd) < 0)
	  return -1;
     blocksize = PROPAGATE_ONES(fsd.f_frsize ? fsd.f_frsize : fsd.f_bsize);
#else
     struct statfs fsd;
     if(statfs(path, &fsd) < 0)
	  return -1;
     blocksize = PROPAGATE_ONES(fsd.f_bsize);
#endif

     blocksfree = PROPAGATE_ONES(fsd.f_bavail);
     if(blocksfree == -1)
	  blocksfree = PROPAGATE_ONES(fsd.f_bfree);
     if(blocksize == -1 || blocksfree == -1)
	  return -1;
     bpm = (1024lu * 1024lu)/blocksize;
     *psize = blocksfree/bpm;
     return 0;
}
