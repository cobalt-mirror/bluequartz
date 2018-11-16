
README.txt for base-alpine 1.0 and later
=========================================

This is a Chorizo'fied base-alpine and therefore has a lot more *meat*
than the original base-alpine of BlueOnyx 510XR.

Actually this module now contains enough changes to warrant a complete 
name change. I resisted that urge for a couple of reasons.

Mainly I wanted "all eggs in one basket" and didn't want to spread
CodeIgniter, configs, UIFC Classes, Helpers, Libraries and essential
CodeIgniter mods through various modules and separate RPMs. 

Keeping them in one single RPM makes maintenance much easier. It will
mean "bigger updates" (a fatter RPM) on YUM updates, but that's a 
good compromise.

So this new base-alpine contains the following:

- Anything the old base-alpine had.
- Minus the horribly outdated PDF manuals.
- Minus the /web/error/* pages for AdmServ
- Minus the old /web pages for AdmServ

On top of that it inherited:

- The 'ci' directory that contains our preconfigured CodeIgniter.
- The web/.htaccess that's needed to route all traffic through CI
- The web/index.php of CodeIgniter that handles ALL transactions.
- The /web/.adm/ folder with all the visible baggage of the Adminica
  theme which must be publically accessible.


Notes for code-maintainers:
============================

/usr/sausalito/ui/chorizo/ci/system/
------------------------------------
The CodeIgniter directory /usr/sausalito/ui/chorizo/ci/system/ should be 
hands off. Unless you upgrade CodeIgniter. DO NOT MODIFY stuff in there.
Because otherwise it'll bite you in the ass during the next CodeIgniter
update.

/usr/sausalito/ui/chorizo/ci/application/
------------------------------------------
That is free for grabs and can be modified at will.

/usr/sausalito/ui/chorizo/ci/application/config/
-------------------------------------------------
Config directory of this CodeIgniter instance.

/usr/sausalito/ui/chorizo/ci/application/helpers/
--------------------------------------------------
Directory for helper scripts.

/usr/sausalito/ui/chorizo/ci/application/libraries/
---------------------------------------------------
The real deal. That is where the Chorizified versions of the Sausalito 
PHP Classes and UIFC Classes reside.

/usr/sausalito/ui/chorizo/ci/application/libraries/uifc/
---------------------------------------------------------
These *are* the droids that you are looking for.

/usr/sausalito/ui/chorizo/ci/application/modules/
--------------------------------------------------
That is where all the modules go. No exceptions.


Notes on CodeIgniter:
======================

RTFM: http://ellislab.com/codeigniter/user-guide/toc.html

Seriously. Read it. It is the indispensible documentation of CodeIgniter.
There is no excuse not having it open in at least one tab while you're 
working on this code.

Click on the black "Table of Contents" tab at the top right as well. It is
a timesaver.

Now this CodeIgniter instance is modified. The modifications reside in this
directory: /usr/sausalito/ui/chorizo/ci/application/third_party/MX/

That basically contains an add-on that builds the routes dynamically based on
the route.conf files in the /usr/sausalito/ui/chorizo/ci/application/modules/
directory.


The MVC-Model:
==============

People not familliar with MVC models need a small crash course. So let's have it:

M = Model
V = View
C = Controller

Controller: The Controller contains the actualy code.
View:		The View contains the presentation of the output and is populated
			by the controller.
Model:		This is typically used to model the database storage and to shove
			data into MySQL. As we use CCE and CODB as backend, we usually do
			not use Models in our CodeIgniter classes. Instead the data storage
			is managed inside the Controller.


Routing:
========

The only publically accessible part of the GUI is a single PHP file:

/usr/sausalito/ui/web/index.php

A .htaccess (located at: /usr/sausalito/ui/web/.htaccess) handles anything that
doesn't hit the index.php or is caught by other "fluff" directly. Such as images,
stylesheets, jQuery scripts and therelike. If the called URL doesn't terminate in
an existing file or folder, CodeIgniter will handle it. One way or the other.

Now if you call a page such as http://<IP>:444/vsite/vsiteAdd (for example), then
you are really hitting the index.php instead. Based on the requested URL our
CodeIgniter then looks at the Routing-table to see if any PHP Class of it is 
mapped to that URL segment.

The plugin in /usr/sausalito/ui/chorizo/ci/application/third_party/MX/ eventually
finds /usr/sausalito/ui/chorizo/ci/application/modules/base/vsite/config/routes.php 
which contains a route that matches /vsite/vsiteAdd:

$route['vsite/vsiteAdd/:any'] = "VsiteAdd";

It is mapped to the Class 'VsiteAdd', which resides here:

/usr/sausalito/ui/chorizo/ci/application/modules/base/vsite/controllers/vsiteAdd.php

Hence that class is called and presents the matching GUI page.

So if you have paid attention, then you will already have drawn *two* conclusions:

a.) All custom modules MUST have at the bare minimum:

	- A config/routes.php with the mapping for URL -> Class
	- A controller/ClassName.php

b.) Class-Names MUST be unique. No two Classes may have the same name.

Please keep that in mind.

If your custom module should display within the framework of the BlueOnyx Chorizo
GUI and doesn't need a custom "dresssing", then you do NOT need to provide your own
"view". So this is a "clothes optional" party.


BlueOnyx Menus:
===============

Basically the menus work as before. However, as our URLs have lost the *.php extension, 
we need separate Menu files for the Chorizo GUI. This also makes it easier to have 
certain menu items only visible in the Chorizo GUI, but not the old GUI. This is useful
for modules that are very generic. Such as base-compass.mod or base-webapp.mod, which
ship with both the old and new GUI to be able to install it on all boxes from 5106R up to
5209R.

The Chorizo Menu's therefore reside here: /usr/sausalito/ui/chorizo/menu/

A typical toplevel Menu entry looks like this:

<item 
    id="base_controlpanel" 
    label="[[base-alpine.controlpanel]]" 
    description="[[base-alpine.controlpanelDescription]]"
    icon="download_to_computer"
    requiresChildren="1"
>
    <parent id="base_sysmanage" order="20"/>
</item>

So the format hasn't really changed. Just the "icon" is a new feature and allows to specify
an image to be shown left of the menu text.

Here is another example of a menu XML file at the end of the menu tree:

<item 
  id="base_personalEmail" 
  label="[[base-user.personalEmail]]" 
  description="[[base-user.personalEmail_help]]" 
  url="/user/personalEmail" 
  icon="v_card_2"
  module="user">
  <parent id="base_personalProfile" order="20"/>
</item>

Things you need to know about menus:

a.) IDs must be unique.
b.) Children of the same toplevel menu must not have identical numerical "order".
c.) We only support three levels of menus.

So:

Menu ID AAAA may be parent. It may have BBBB, CCCC and DDDD as children. Each of those Children
may have Child menu entries that lead to actual pages. But the Children may not have more nested
menus that reach any deeper than that. In practiacal terms as ID "root" is the toplevel Menu entry,
you end up with two useable menu levels. Use them wisely.

Class BxPage:
=============

You need to start somewhere to familliarize yourself with the new Chorizo GUI. You can do that
in two ways:

a.) Look at a certain (simple) GUI page and examine the contoller for it to see how it works.
This GUI has a fair share of good and bad examples. A REALLY bad example is this:

URL: 	/user/personalAccount
File:	/usr/sausalito/ui/chorizo/ci/application/modules/base/user/controllers/personalAccount.php

DO NOT USE THAT AS AN EXAMPLE! It sucks. It works, but it ain't pretty and it's not proper UFIC code.

Good example:

URL:	/apache/apache
File:	/usr/sausalito/ui/chorizo/ci/application/modules/base/apache/controllers/apache.php

Why is that a good example? Because the code is very clean and 100% in UIFC. On the other hand the
bad example personalAccount isn't. It uses non-UIFC classes and that should be avoided like the plague.

The Class Apache is lean and mean and well structured. It starts with "lining up the ducks" and 
initializing the needed libraries. It then checks the ACL's to see if the logged in user has the
rights to view the page.

Then comes the data handling segment that parses the Form and POST data (if there is any) and 
performs the CODB transactions if any need to be done.

After that comes the error handling to check if the transaction raised any errors.

Lastly there is the part where the presentation is done and the actual GUI page and the formfields
are shown.

This last part (the formfields) follows the old UIFC format as closely as possible. But there are
subtle differences, new UIFC classes and slightly changed behaviors all around.

Noteable changes:

 - addBXDivider() replaces the old addDivider(), which had serious setbacks. Do not use addDivider()
   anymore. Instead use addBXDivider() instead.

 - getTextField() is a hell of a lot more flexible these days. We do have UIFC classes for all kinds
   of shit. Such as getDomainName(), getBoolean(), getEmailAddress(), getInteger() and many more.
   At the end of the day these are <INPUT> fields that often just differ in the kind of data they
   accept as valid. 

   So if you wanted an <INPUT> field that allows to enter an IP-Address, you could do this:

	$ipaddrField = $factory->getIpAddress("ipaddr", $my_ip, 'rw');
	$block->addFormField(
		$ipaddrField,
		$factory->getLabel("ipaddr", false)
	);

	But you could also do this instead and the result will be the same:

	$ipaddrField = $factory->getTextField("ipaddr", $my_ip, 'rw');
	$ipaddrField->setType("ipaddr");
	$block->addFormField(
		$ipaddrField,
		$factory->getLabel("ipaddr", false)
	);	

	Because you can use setType() to specify a different validator for the input to define which
	kind of data is accepted. As before the supported data types are defined via the Schema files.
	It's just that the more specialized UIFC classes such as getIpAddress() have the data type
	hardwired, while getTextField() is more generic and flexible.

	Additionally there are other "switches" that can be used to change the behaviour of some UIFC
	classes. These vary from Class to Class. Like changing if a label is shown. And if so, if the
	label is on the left (default) or on top. Or if no label is shown. Size and width or length of
	the input field can also be adjusted.

Best idea is: When you want to create a new page, look at an existing page that uses the element you
want and "borrow" that code.

But I said there were TWO ways of understanding the new Code. So here is the SECOND way to do it:


Understanding the Chorizo GUI from the top down:
=================================================

b.) In that case you want to start here:

/usr/sausalito/ui/chorizo/ci/application/libraries/BxPage.php

THAT is the big deal. The main course of the dinner, the 25 year old Whisky, the Cohiba cigar or the 
21 year old chick with pink hair, too much makeup and a daddy complex. Depending on what greases your 
gears.

There are only a handfull of pages that do NOT use BxPage for processing. That would be the Login page,
the error pages and some peripheral "fluff" that goes into the header of most GUI pages. Everything else
uses BxPage.

You could ask "What does BxPage do?" Let me answer with a rethorical question: "What doesn't it do?" It's
our swiss army knife and does:

- Loading of all essential libraries, classes and helpers.
- Localization
- Error handling and display
- Parsing and presentation of the Menus (based on the ACL's)
- Scratchpad (temporary storage) for Label Objects
- Presents headers, page body and footer
- Presents Active Monitor Alerts and Warnings
- Actually checks the RAID status, too (although in an ideal world it wouldn't need to)

Couple of other things, too. A lot of the functionality in BxPage relies on Helper functions that have
been offloaded into these two helpers:

/usr/sausalito/ui/chorizo/ci/application/helpers/blueonyx_helper.php
/usr/sausalito/ui/chorizo/ci/application/helpers/uifc_ng_helper.php

When you understand BxPage, you'll have mastered Chorizo.

When you understand the Controllers and know the most common UIFC classes, then it'll be "good enough" 
to create your own GUI pages.

That's basically Chorizo in a nutshell. It's not perfect. It has its kinks and quirks and glitches and
a lot of room for optimizations and tweaks. We'll get to that one thing after another.

In the longer run the BlueOnyx WIKI will have more information about how to build custom modules for
BlueOnyx. So please check http://wiki.blueonyx.it every now and then.

In closing:
===========

Let me wrap this documentation up with a few words of gratitude and a big thanks to the whole BlueOnyx 
user base and BlueOnyx community:

Chorizo is (at todays date): 1 year, 9 months and 21 days of development by a single person (659 days).
In that time I moved twice. Once from Germany to Colombia and once in Colombia from one apartment block
to another. I learned another language. Watched several seasons of TV-Shows while coding and went through
my MP3 playlist hundreds of times. "Map of the Problematics" (Muse), "Clocks" (Coldplay) and especially
"Dreamscape" (See: https://www.youtube.com/watch?v=2WPCLda_erI), "Breaking the Habit" (Linkin Park) and
others provided the beats to keep the code flowing.

Chorizo itself builds on a foundation that has been laid over the last 15 years by the guys of Cobalt 
Networks Inc. (most noteably Kevin Chiu!), BlueQuartz (Hisao Shibuya) and others who have carried the 
torch in the last one and a half decade and/or contributed bits and pieces here and there. Too many
to mention, really.

But Chorizo wouldn't be Chorizo without the Adminica Theme, which was created by Tricicle Labs:

Product Page:			http://themeforest.net/item/adminica-the-professional-admin-template/160638
Adminica Live Preview: 	http://templates.tricyclelabs.com/adminica/
Bootstrap Live Preview: http://templates.tricyclelabs.com/adminica-bootstrap/

Lastly, Chorizo most defenitely wouldn't be Chorizo if Dirk Estenfeld Black Point Arts Internet 
Solutions GmbH hadn't sprung the idea, the inspiration and (together with the BlueOnyx community) 
provided the much needed funding to help me pull this one off. 

And there are my good friends Chris Gebhardt from Virtbiz.com and Uwe Stache from BB-One.net, who 
for so many years have provided the infrastructure, hardware and bandwith to support the BlueOnyx 
Project with rock solid and top notch hosting. If you need hosting, then these are the places you 
want to go to. 

Then there are the BlueOnyx users, who have supported this project through many ups and downs and
who - with great compassion - contributed ideas, time and money to the project whenever needed. 
People like Meaulnes Legler, who (almost singlehandedly) translated the GUI into French. Which was
a real effort considering the amount of text. There are just too many to mention. So what else
can I say, but this:

Thanks a million to ALL BlueOnyx users! You are the greatest!


With best regards,

Michael Stauber
mstauber@blueonyx.it
12th July 2014
