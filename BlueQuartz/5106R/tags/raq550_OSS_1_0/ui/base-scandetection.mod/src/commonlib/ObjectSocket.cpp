/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/ObjectSocket.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  04-Oct-2000
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

#include "ObjectSocket.h"

void ObjectSocket::fillBuffer ()
{
    // Move existing data to beginning of buffer

    int len = inBufferEnd - inBufferBegin;

    if (len > 0)
    {
	memmove (inBuffer, inBuffer+inBufferBegin, len);
	inBufferBegin = 0;
	inBufferEnd = len;
    }

    len = socket->read (inBuffer+inBufferEnd, BUF_SIZE - len);
    
    inBufferEnd += len;
}

void ObjectSocket::flushBuffer ()
{
    int len = outBufferEnd - outBufferBegin;

    if (len < 1)
    {
	return;
    }

    len = socket->write (outBuffer+outBufferBegin, len);

    outBufferBegin += len;

    len = outBufferEnd - outBufferBegin;

    if (len > 0)
    {
	memmove (outBuffer, outBuffer+outBufferBegin, len);
    }

    outBufferBegin = 0;
    outBufferEnd = len;
}

const char * ObjectSocket::reserveBytes (int count)
{
    while (inAvailable() < count)
    {
	fillBuffer ();
    }

    char * buffer = inBuffer + inBufferBegin;
    inBufferBegin += count;
    return buffer;
}

void ObjectSocket::copyBytes (char * buf, int count)
{
    while (inAvailable() < count)
    {
	fillBuffer ();
    }

    memcpy (buf, inBuffer+inBufferBegin, count);
    inBufferBegin += count;
}

void ObjectSocket::setBytes (const char * buf, int count)
{
    while (outAvailable() < count)
    {
	flushBuffer ();
    }

    memcpy (outBuffer+outBufferEnd, buf, count);
    outBufferEnd += count;
}

