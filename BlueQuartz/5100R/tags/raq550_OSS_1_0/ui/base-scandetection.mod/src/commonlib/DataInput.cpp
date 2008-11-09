/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/DataInput.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  27-Sep-2000
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

#include "DataInput.h"

bool DataInput::readBoolean ()
{
    char buffer;

    copyBytes (&buffer, 1);

    return (buffer != 0);
}

char DataInput::readByte ()
{
    char buffer;

    copyBytes (&buffer, 1);

    return buffer;
}

void DataInput::readFully (char * b, int offset, int len)
{
    copyBytes (b+offset, len);
}

int DataInput::readInt ()
{
    unsigned long buffer;

    copyBytes (reinterpret_cast<char *>(&buffer), sizeof(buffer));

    return (int) ntohl (buffer);
}

short DataInput::readShort ()
{
    unsigned short buffer;
    copyBytes (reinterpret_cast<char*>(&buffer), sizeof(buffer));
    return ntohs(buffer);
}

string DataInput::readString ()
{
    int count = readInt ();
    const char * buffer = reserveBytes (count);
    return string (buffer, count);
}

