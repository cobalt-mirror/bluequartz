<?php
include 'wizard/web/PDFClass.php';

class SettingsSheetPDF {

	// Set some generic values up.
	var $baseTextLine = 548;
	var $pdf;

	function SettingsSheetPDF()
	{
		// Make a new pdf object.
		$this->pdf = new Cpdf();

		$this->pdf->selectFont('./fonts/Helvetica.afm');

		$this->pdf->setColor(0,0,0);
	}

	function WriteSettings($hostname, $buildinfo,
												 $systemSettings, $networkSettings)
	{
		for ( $i = 0; $i < count( $networkSettings ); $i++ )
		{

			// Title Line
			$this->pdf->setColor(.140625,.2773438,.5351562);
			$this->pdf->addText( 42,$this->baseTextLine,13,"<b>$networkSettings[$i]</b>" );
			$this->baseTextLine -= 15;

			// Setting Line
			$i++;
			$this->pdf->setColor(0,0,0);
			$this->pdf->addText( 42,$this->baseTextLine,18,"<b>$networkSettings[$i]</b>" );
			$this->baseTextLine -= 25;
		}

		for ( $i = 0; $i < count( $systemSettings ); $i++ )
		{

			// Title Line
			$this->pdf->setColor(.140625,.2773438,.5351562);
			$this->pdf->addText( 42,$this->baseTextLine,13,"<b>$systemSettings[$i]</b>" );
			$this->baseTextLine -= 15;

			// Setting Line
			$i++;
			$this->pdf->setColor(0,0,0);
			$this->pdf->addText( 42,$this->baseTextLine,18,"<b>$systemSettings[$i]</b>" );
			$this->baseTextLine -= 25;
		}

		$this->pdf->setColor(.140625,.2773438,.5351562);
		$this->pdf->filledRectangle( 34, ( $this->baseTextLine + 25 ) ,5,( 529 - $this->baseTextLine  ) );
		$this->pdf->filledRectangle( 37, 0, 2, 700 );

		$this->pdf->addJpegFromFile('./SettingsSheet-Header.jpg',30,606,552,155);
		$this->pdf->setColor(1,1,1);

		$serverString = "settings for <b>$hostname</b>";
		 
		$stringPosition = 574 - ($this->pdf->getTextWidth(15, $serverString));
		$this->pdf->addText($stringPosition,730,15,$serverString);

		$this->pdf->stream("QubeSettings");
	}
}

/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>

