/*

$Header: /home/cvs/base-scandetection.mod/src/commonlib/serial.c,v 1.1 2001/09/18 16:59:37 jthrowe Exp $

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/serial.c,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  Tue Oct 27 12:53:00 EDT 1998
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $ 
    Date Last Modified :  $Date: 2001/09/18 16:59:37 $

   **********************************************************************

   Copyright (c) 1997-1998 Progressive Systems Inc.
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

   **********************************************************************

*/

#include <stdlib.h>
#include "serial.h"

typedef unsigned char BYTE;

static void ensureCapacity (SerialObject * obj, int size);

//----------------------------------------------------------------------------
//  *NOTE* The caller is responsible for freeing the returned SerialObject
//----------------------------------------------------------------------------

SerialObject * so_create (BYTE * buffer, int size)
{
    SerialObject * obj = (SerialObject*) malloc (sizeof(SerialObject));

    obj->buffer    = buffer;
    obj->size      = size;
    obj->offset    = 0;
    obj->ourBuffer = 0;

    return obj;
}

SerialObject * so_createAlloc ()
{
    SerialObject * obj = (SerialObject*) malloc (sizeof(SerialObject));

    obj->size      = SERIAL_DEFAULT_BUFFER_SIZE;
    obj->buffer    = (unsigned char *) malloc (obj->size);

    if (obj->buffer == NULL)
    {
	return NULL;
    }

    obj->offset    = 0;
    obj->ourBuffer = 1;

    return obj;
}

void so_destroy (SerialObject * obj)
{
    if (obj->ourBuffer)
    {
	free (obj->buffer);
    }

    obj->buffer = NULL;
    obj->size   = 0;
    obj->offset = 0;
    free (obj);
}

const unsigned char * so_getBuffer (SerialObject * obj)
{
    return obj->buffer;
}

int so_getBoolean (SerialObject * obj)
{
    return obj->buffer[obj->offset++];
}

void so_setBoolean (SerialObject * obj, int b)
{
    ensureCapacity (obj, obj->offset + 1);
    obj->buffer[obj->offset++] = b ? 1 : 0;
}

BYTE so_getByte (SerialObject * obj)
{
    return obj->buffer[obj->offset++];
}

void so_setByte (SerialObject * obj, BYTE i)
{
    ensureCapacity (obj, obj->offset + 1);
    obj->buffer[obj->offset++] = i;
}

int so_getInt (SerialObject * obj)
{
    int i =
        ((obj->buffer[obj->offset]    << 24)) +
        ((obj->buffer[obj->offset+1]  << 16) & 0x00ff0000) +
        ((obj->buffer[obj->offset+2]  << 8) &  0x0000ff00) +
        (obj->buffer[obj->offset+3] &  0x000000ff);

    obj->offset += 4;
    return i;
}

void so_setInt (SerialObject * obj, int i)
{
    ensureCapacity (obj, obj->offset + 4);
    obj->buffer[obj->offset++] = (i >> 24) & 0xFF;
    obj->buffer[obj->offset++] = (i >> 16) & 0xFF;
    obj->buffer[obj->offset++] = (i >> 8) & 0xFF;
    obj->buffer[obj->offset++] = i & 0xFF;
}

short so_getShort (SerialObject * obj)
{
    short i = (short)
        (
            ((obj->buffer[obj->offset]  << 8) &  0x0000ff00) +
            (obj->buffer[obj->offset+1] &  0x000000ff)
        );

    obj->offset += 2;
    return i;
}

void so_setShort (SerialObject * obj, int i)
{
    ensureCapacity (obj, obj->offset + 2);
    obj->buffer[obj->offset++] = (i >> 8) & 0xFF;
    obj->buffer[obj->offset++] = i & 0xFF;
}

//----------------------------------------------------------------------------
//  *NOTE* The caller is responsible for freeing the returned character array
//----------------------------------------------------------------------------

char * so_getString (SerialObject * obj)
{
    int     len = so_getInt (obj);
    char *  str = NULL;

    str = (char*) malloc (len + 1);

    if (len > 0)
    {
        memcpy (str, &(obj->buffer[obj->offset]), len);
    }

    str[len] = '\0';

    obj->offset += len;

    return str;
}

void so_setString (SerialObject * obj, const char * str)
{
    int len;

    if (str == NULL)
    {
        so_setInt (obj, 0);
    }
    else
    {
        len = strlen(str);
        so_setInt (obj, len);
	ensureCapacity (obj, obj->offset + len);
        memcpy (&(obj->buffer[obj->offset]), str, len);
        obj->offset += len;
    }
}

BYTE * so_getBytes (SerialObject * obj, int * length)
{
    *length = obj->offset;
    return obj->buffer;
}

int so_getOffset (SerialObject *obj)
{
  return obj->offset;
}

void so_setOffset (SerialObject * obj, int i)
{
  obj->offset = i;
}

static void ensureCapacity (SerialObject * obj, int size)
{
    if (size > obj->size)
    {
	if (obj->ourBuffer)
	{
	    unsigned char * temp;

	    obj->size = (int) (size * 1.5);
	    temp = (unsigned char *) malloc (obj->size);
	    memcpy (temp, obj->buffer, obj->offset);
	    free (obj->buffer);
	    obj->buffer = temp;
	}
	else
	{
	    /* todo handle this error */
	}
    }
}
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
