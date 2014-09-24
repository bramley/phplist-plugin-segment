# Segment Plugin #

## Description ##

The plugin allows you to send a campaign to a segment, a subset, of the subscribers who belong to the selected lists.

The plugin adds a tab to the Send a campaign page that lets you define conditions. A condition comprises a field, an operator, and a target value.
When the campaign is sent only those subscribers who meet all of the conditions will be selected. 

These subscriber fields and attributes are supported for fields:

* each subscriber attribute
* the subscriber email address
* the subscriber Entered date
* subscriber campaign activity - whether the subscriber opened a specific prior campaign

Each condition has a set of operators, specific to each field, such as 'is', 'is not', 'matches', 'is before', 'opened', etc.

The target value, also specific to each field, can be a text value, a select value, a date, etc.

## Installation ##

### Dependencies ###

Requires php version 5.3 or later.

Requires the Common Plugin to be installed. 

See <https://github.com/bramley/phplist-plugin-common>

### Set the plugin directory ###
You can use a directory outside of the web root by changing the definition of `PLUGIN_ROOTDIR` in config.php.
The benefit of this is that plugins will not be affected when you upgrade phplist.

### Install through phplist ###
Install on the Plugins page (menu Config > Plugins) using the package URL `https://github.com/bramley/phplist-plugin-segment/archive/master.zip`.

In phplist releases 3.0.5 and earlier there is a bug that can cause a plugin to be incompletely installed on some configurations (<https://mantis.phplist.com/view.php?id=16865>). 
Check that these files are in the plugin directory. If not then you will need to install manually. The bug has been fixed in release 3.0.6.

* the file SegmentPlugin.php
* the directory SegmentPlugin

### Install manually ###
Download the plugin zip file from <https://github.com/bramley/phplist-plugin-segment/archive/master.zip>

Expand the zip file, then copy the contents of the plugins directory to your phplist plugins directory.
This should contain

* the file SegmentPlugin.php
* the directory SegmentPlugin

###Settings###
In the Segmentation group on the Settings page you can specify:

* The size of the list of previous campaigns for Campaign activity. The default is 10.

###Usage###

####Add segment conditions####
The plugin adds a tab to the Send a campaign page.
The steps to add a condition are

* select an attribute or a subscriber field from the drop-down list.
* the page will then refresh automatically showing a list of the operators for the selected field and a target input field.
* select an operator and then enter or select the target value
* click the Calculate button to see how many subscribers will be selected when the campaign is actually sent.

The plugin calculates the number of subscribers using the lists chosen on the Lists tab.
It selects only those subscribers who belong to the lists and who meet all of the conditions.
It also ignores unconfirmed or blacklisted subscribers and any subscribers who have already received the campaign.
 
####Empty or missing attribute values####

The way that the plugin handles empty or missing attribute values varies slightly for each type of attribute and its operators.

<u>textline, textarea, hidden attributes</u>

missing, null or empty values are treated as an empty string. So, for example, the operator 'empty' will be true, and the operator 'not empty' will be false.

<u>select, radio button attributes</u>

missing, null or empty values are treated as select list index of 0. So, for example, the operator 'is' will be false, and the operator 'is not' will be true.

<u>checkbox attributes</u>

missing, null or empty values are treated as being unchecked. So the operator 'checked' will be false.

<u>checkboxgroup attributes</u>

missing, null or empty values are treated as all being unchecked. So the operators 'one checked' and 'all checked' will be false.

<u>date attributes</u>

missing, null or empty values are ignored. So subscribers with these values will not be selected for any operator, 'is', 'is after', or 'is before'.

## Donation ##
This plugin is free but if you install and find it useful then a donation to support further development is greatly appreciated.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W5GLX53WDM7T4)

## Version history ##

    version     Description
    2014-09-24  Release to GitHub
