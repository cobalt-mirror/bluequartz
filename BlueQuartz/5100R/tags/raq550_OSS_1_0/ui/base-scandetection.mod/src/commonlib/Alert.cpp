/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/Alert.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  01-Nov-2000
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

#include "Alert.h"
#include "ObjectInput.h"
#include "ObjectOutput.h"
#include "Exception.h"

Alert::Alert (const Alert& obj)
{
    init (obj);
}

Alert& Alert::operator=(const Alert& obj)
{
    if (this != &obj)
    {
	init (obj);
    }

    return *this;
}

void Alert::init (const Alert& obj)
{
    type      = obj.type;
    timestamp = obj.timestamp;
    message   = obj.message;
}

void Alert::readExternal (ObjectInput& in)
{
    int version = in.readInt ();

    if (version != serialVersionId)
    {
	throw IOException ("Alert::readExternal(): invalid serial version");
    }

    type      = static_cast<AlertType>(in.readInt());
    timestamp = in.readInt ();
    message   = in.readString ();
}

void Alert::writeExternal (ObjectOutput& out) const
{
    out.writeInt (serialVersionId);

    out.writeInt (type);
    out.writeInt ((int) timestamp);
    out.writeString (message);
}
