IMPORTANT: PLEASE NOTE:
=======================

The module "base-dns.mod" uses a different method to generate GUI pages. 

It uses an external library called CobaltUI.php, which typically is located 
here: /usr/sausalito/ui/libPhp/CobaltUI.php

Usually our GUI pages use a method called UIFC to generate pages. CobaltUI.php 
adds a layer on top of that to simplify page generation, but this comes at 
the cost that some things simply don't work right anymore.

CobaltUI.php even pre-dates PHP-4 and although some attempts were made over the
years to make it fully PHP-4 and PHP-5.1 to PHP-5.2 ready: Under PHP-5.3 it
breaks hard in certain areas. 

base-dns.mod is the one module that used CobaltUI.php extensively. It is also
used here and there elsewhere. But we are slowly but surely away from it and 
code all new pages only in UIFC. In fact parts of base-dns.mod just got
redone using proper UIFC methods to get around some broken bits and pices.


Advice to programmers:
======================

If you intend to create your own GUI pages and are looking for example code 
that you can use to create your own pages, then THIS module is NOT what you
are looking for!


Some examples: 
==============

The ease of CobaltUI.php is easily show in examples:

1a) Creation of a text form field using CobaltUI.php

    $Ui->DomainName( "mx_domain_name" );

1b) Creation of the same text form field using UIFC:

    $mx_domain_name_Field = $factory->getTextField("mx_domain_name");
    $block->addFormField($mx_domain_name_Field, $factory->getLabel("mx_domain_name"), $pageID);

2a) Creation of a pulldown menu using CobaltUI.php:

    $Ui->Alters( "mx_priority", array('very_high', 'high', 'low', 'very_low') );

2b) Creation of the same pulldown menu using UIFC:

    $mx_priority_select = $factory->getMultiChoice("mx_priority", array_values(array("very_high", "high", "low", "very_low")));
    $mx_priority_select->setSelected($systemObj['mail_server_priority'], true);
    $block->addFormField($mx_priority_select, $factory->getLabel("mx_priority"), $pageID);


So you can easily see how and why CobaltUI.php can make it easier to create GUI pages.

But as also mentioned: Some things no longer work right anymore and this includes
the function Alters() that CobaltUI.php uses to create pulldown menus. In UIFC we
call them 'MultiChoice' instead.

Would it be desireable to fix CobaltUI.php so that we can continue to use it? 

Not really.

Why? Simply put: CobaltUI.php is too unflexible and to watered down. There are simply 
some things you cannot do just in CobaltUI.php alone and you can also hardly mix
CobaltUI.php and UIFC code in the same GUI page without adding a lot of confusion.

So imagine someone started to code a new page using CobaltUI.php, because it was 
"good enough" for the initially intended purpose. Some months later, someone else
wants to extend the original code by adding new stuff to it. But he needs pulldowns 
(or 'MultiChoice') for it, which CobaltUI.php no longer really supports at the
present. So the initial choice of using CobaltUI.php now makes the life of the 
code maintainer a hell of a lot more complicated.

Even if Alters() from CobaltUI.php would still create working MultiChoice() 
output: The additional layer that CobaltUI.php puts on top of UIFC makes it
hard for some of the more advanced features of the GUI to filter through all the way.

So in short: PLEASE DO NOT CODE NEW GUI PAGES in CobaltUI.php. Use UIFC instead!

