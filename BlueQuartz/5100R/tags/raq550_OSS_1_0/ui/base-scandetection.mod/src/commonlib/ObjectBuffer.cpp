/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/ObjectBuffer.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  06-Nov-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:36 $

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

#include "ObjectBuffer.h"
#include "Exception.h"

void ObjectBuffer::copyBytes (char * buf, int count)
{
    if (count > (size - beginning))
    {
	throw IllegalArgumentException (
	    "ObjectBuffer::copyBytes(): count too large");
    }

    memcpy (buf, buffer + beginning, count);
    beginning += count;
}

const char * ObjectBuffer::reserveBytes (int count)
{
    if (count > (size - beginning))
    {
	throw IllegalArgumentException (
	    "ObjectBuffer::reserveBytes(): count too large");
    }

    const char * result = buffer + beginning;
    beginning += count;

    return result;
}

void ObjectBuffer::setBytes (const char * buf, int count)
{
    if (count > (size - end))
    {
	throw IllegalArgumentException (
	    "ObjectBuffer::setBytes(): count too large");
    }

    memcpy (buffer + end, buf, count);
    end += count;
}
