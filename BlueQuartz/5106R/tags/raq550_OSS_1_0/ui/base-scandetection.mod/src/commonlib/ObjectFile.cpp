/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/ObjectFile.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  12-Nov-2000
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

#include <fcntl.h>
#include <unistd.h>
#include <netinet/in.h>
#include <sys/types.h>
#include <sys/stat.h>

#include "ObjectFile.h"
#include "ObjectBuffer.h"
#include "Exception.h"

ObjectFile::ObjectFile (const string& fn) : fileName (fn)
{
    fd = open (fileName.c_str(), O_RDWR | O_CREAT, S_IRUSR | S_IWUSR);

    if (fd < 0)
    {
	throw IOException ("ObjectFile::ObjectFile(): open failed");
    }
}

ObjectFile::~ObjectFile ()
{
    close (fd);
}

static const int MAX_BUFFER = 4096;

Externalizable * ObjectFile::readObject ()
{
    unsigned long objectLength;

    // Read length

    int len = read (fd, &objectLength, sizeof(int));

    if (len == 0)
    {
	throw EOFException ();
    }
    else if (len != sizeof(int))
    {
	throw IOException ("ObjectFile::readObject(): length read failed");
    }

    objectLength = ntohl (objectLength);
    char buffer[MAX_BUFFER];

    if (objectLength > sizeof(buffer))
    {
	throw RuntimeException (
	    "ObjectFile::readObject(): object length exceeds buffer length");
    }

    ObjectBuffer ob (buffer, sizeof(buffer));

    len = read (fd, buffer, objectLength);

    if (len == 0)
    {
	throw EOFException ();
    }
    else if (len != objectLength)
    {
	throw IOException ("ObjectFile::readObject(): object read failed");
    }

    Externalizable * object = ob.readObject ();

    return object;
}

void ObjectFile::writeObject (const Externalizable& obj)
{
    char buffer[MAX_BUFFER];
    ObjectBuffer ob (buffer, sizeof(buffer));
    ob.writeObject (obj);

    unsigned long objectLength = ob.getOutputLength ();
    unsigned long noLength = htonl (objectLength);

    int len = write (fd, &noLength, sizeof(noLength));

    if (len != sizeof(noLength))
    {
	throw IOException ("ObjectFile::writeObject(): length write failed");
    }

    len = write (fd, buffer, objectLength);

    if (len != objectLength)
    {
	throw IOException ("ObjectFile::writeObject(): object write failed");
    }
}
