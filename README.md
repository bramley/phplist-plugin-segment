# Segment Plugin #

## Description ##

The plugin allows you to send a campaign to a segment, a subset, of the subscribers who belong to the selected lists.

The plugin adds a tab to the Send a campaign page that lets you define conditions. A condition comprises a field, an operator, and a target value.
When the campaign is sent only those subscribers who meet either any or all of the conditions will be selected. 

These subscriber fields and attributes are supported for fields:

* each subscriber attribute
* the subscriber email address
* the subscriber Entered date
* the subscriber id
* the subscriber unique id
* subscriber campaign activity - whether the subscriber was sent, opened, or clicked a link in a specific prior campaign

Each condition has a set of operators, specific to each field, such as 'is', 'is not', 'matches', 'is before', 'opened', etc.

The target value, also specific to each field, can be a text value, a select value, a date, etc.

## Installation ##

### Dependencies ###

Requires php version 5.4.0 or later. Please check your php version before installing the plugin, otherwise phplist will fail (probably a white page).

This plugin requires the Common Plugin version 3.5.6 or greater to also be installed, and will not work without that.
**You must install that plugin or upgrade to the latest version if it is already installed**.
See <https://github.com/bramley/phplist-plugin-common>

### Set the plugin directory ###
The default plugin directory is `plugins` within the admin directory.

You can use a directory outside of the web root by changing the definition of `PLUGIN_ROOTDIR` in config.php.
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

### Settings ###
In the Segmentation group on the Settings page you can specify:

* The size of the list of previous campaigns for Campaign activity. The default is 10.

## Usage ##

For guidance on usage see the plugin page within the phplist documentation site <https://resources.phplist.com/plugin/segment>

## Support ##

Please raise any questions or problems in the user forum <https://discuss.phplist.org/>.

## Donation ##
This plugin is free but if you install and find it useful then a donation to support further development is greatly appreciated.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=W5GLX53WDM7T4)

## Version history ##

    version         Description
    2.2.7+20180517  Avoid dependency on php 5.6
    2.2.6+20180328  Reduce the level of php errors that are reported
    2.2.5+20171218  Improve the layout of the Segment tab
    2.2.4+20171109  Improve display of multiple-select
    2.2.3+20171023  Improvements to layout of the Segment tab
    2.2.2+20171018  Display warning when lists have not been selected
    2.2.1+20170208  Copy segment fields when copying a campaign
    2.2.0+20170126  Improvements to use of saved segments
    2.1.11+20161217 Extra validation of from and to dates
                    Use flatpickr for date input
    2.1.10+20160725 Calculate number of subscribers when there are not any conditions
    2.1.9+20160626  Fix problem whereby 0 was not accepted in text fields
    2.1.8+20160515  Fix sql error introduced in version 2.1.6
    2.1.7+20160515  Minor changes
    2.1.6+20160513  Fix for bug when using MESSAGEQUEUE_PREPARE
    2.1.5+20160316  Handle condition for an attribute that no longer exists
    2.1.4+20151117  Internal code refactoring
    2.1.3+20151024  Internal changes to meet coding standards
    2.1.2+20151019  Fix php warning
    2.1.1+20150912  Fix problem when viewing message
    2.1.0+20150903  Added "after interval" operator for date fields
                    Improve validation and warning of invalid conditions
    2.0.0+20150811  Display segment conditions on view message page
    2015-07-16      Can now save and re-use segments
    2015-06-02      Handle attribute not existing
    2015-05-06      Add dependency checks
    2015-04-04      Internal changes to improve memory usage
    2015-03-23      Change to autoload approach
    2015-03-04      Add subscriber id and uniqid as subscriber data fields
    2015-02-13      Add "is between" operator for date fields
    2015-01-06      Remove limit of 15 attributes
    2014-11-21      Allow text to be translated
    2014-10-20      Campaign activity select list is limited to prior campaigns sent to the lists
    2014-10-18      Fix bug in date attribute processing
    2014-10-15      Added list exclusion, internal changes
    2014-10-04      Add sent/not sent a campaign, and clicked/did not click any link
    2014-10-03      Add any/all, multi-value for select list and radio button attributes
    2014-09-27      Correct error reporting
    2014-09-26      Pull Request #1
    2014-09-25      Add regexp matching
    2014-09-24      Release to GitHub
