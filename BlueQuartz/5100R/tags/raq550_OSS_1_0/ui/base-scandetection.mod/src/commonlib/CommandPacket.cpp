/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/CommandPacket.cpp,v $
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

#include "CommandPacket.h"
#include "Exception.h"

const string CommandPacket::SUCCESS = "OK";

void CommandPacket::readExternal (ObjectInput& in)
{
    ::DataUnit::readExternal (in);

    int version = in.readInt ();

    if (version != serialVersionId)
    {
	throw IOException (
	    "CommandPacket::readExternal(): invalid serial version");
    }

    if (in.readBoolean())
    {
	obj = in.readObject ();
    }
}

void CommandPacket::writeExternal (ObjectOutput& out) const
{
    ::DataUnit::writeExternal (out);

    out.writeInt (serialVersionId);

    if (obj != 0)
    {
	out.writeBoolean (true);
	out.writeObject (*obj);
    }
    else
    {
	out.writeBoolean (false);
    }
}



