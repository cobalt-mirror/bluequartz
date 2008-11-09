/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/DataOutput.cpp,v $
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

#include "DataOutput.h"

void DataOutput::write (char b[], int offset, int len)
{
    setBytes (b+offset, len);
}

void DataOutput::writeBoolean (bool b)
{
    char buffer (b);

    setBytes (&buffer, 1);
}

void DataOutput::writeByte (char b)
{
    char buffer (b);

    setBytes (&buffer, 1);
}

void DataOutput::writeInt (int val)
{
    unsigned long buffer = htonl (val);

    setBytes (reinterpret_cast<const char *>(&buffer), sizeof(buffer));
}

void DataOutput::writeShort (short val)
{
    unsigned short buffer = htons (val);
    setBytes (reinterpret_cast<char*>(&buffer), sizeof(buffer));
}

void DataOutput::writeString (const string& str)
{
    writeInt (str.length());
    setBytes (reinterpret_cast<const char*>(str.data()), str.length());
}


