# Segment Plugin #

## Description ##

The plugin allows you to send a campaign to a segment, a subset, of the subscribers who belong to the selected lists.

The plugin adds a tab to the Send a campaign page that lets you define conditions. A condition comprises a field, an operator, and a target value.
When the campaign is sent only those subscribers who meet either any or all of the conditions will be selected. 

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

Requires the Common Plugin to be installed. See <https://github.com/bramley/phplist-plugin-common>

### Set the plugin directory ###
The default plugin directory is `/lists/admin/plugins` but you can use a directory outside of the web root by
changing the definition of `PLUGIN_ROOTDIR` in config.php.
The benefit of this is that plugins will not be affected when you upgrade phplist.

### Install through phplist ###
Install on the Plugins page (menu Config > Plugins) using the package URL `https://github.com/bramley/phplist-plugin-segment/archive/master.zip`

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

##Usage##

For guidance on usage see the plugin page within the phplist documentation site <https://resources.phplist.com/plugin/segment>

##Support##

Questions and problems can be reported in the phplist user forum topic <http://forums.phplist.com/viewtopic.php?f=7&t=41650>.

## Known issues / To Do ##

* Does not take account of excluded lists when calculating the number of subscribers
* Add link clicking to Campaign Activity

## Donation ##
This plugin is free but if you install and find it useful then a donation to support further development is greatly appreciated.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W5GLX53WDM7T4)

## Version history ##

    version     Description
    2014-09-27  Correct error reporting
    2014-09-26  Pull Request #1
    2014-09-25  Add regexp matching
    2014-09-24  Release to GitHub
