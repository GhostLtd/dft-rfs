[Home](../README.md) > RoRo

# RoRo Survey

## Overview

The Roll-on/roll-off survey is set to ferry operators.

* The survey is **monthly**.
* The survey counts the number of vehicles **over 3.5 tonnes** carried **outwards from GB** by **country of registration**.
* Included:
  * Rigid lorries
  * Tractor & trailer (counts as one unit)
  * Tractive units only
* Excluded:
  * Vehicles being exported
* Totals are collected for:
  * A selection of 43 countries
  * "Other countries" (i.e. any not in the specified list)
  * "Unknown countries" (i.e. where the country of registration is not known)
  * "Unaccompanied trailers" (i.e. trailer without tractive unit)
* Operators can also make a survey return that states that a given route is not active (some routes only operate at certain times of the year).

## Frontend features

Users can log in and file returns for any RoRo Survey that has been issued to their Operator (i.e. Ferry company).

## Admin features

### Management domains

In addition to standard admin users (authenticated via IAP), there is additionally a "management domains" setting that can be configured via the `MANAGEMENT_DOMAINS` environment variable.

A JSON list of domains can be passed, which denotes admins of those domains as being "managers".

Managers have access to more dangerous functionality. This currently only related to port editing.

### Ports

* Ports can now be added and edited (separated into UK ports and Foreign ports).
* Ports can only be deleted if they are not currently in use by either a route or a survey. 
* Ports can only be edited if:
  1. The port is not currently in use by a survey
  2. The admin is a manager

### Routes

* Routes can be added and edited, and comprise a pair of ports (a UK port and a foreign port) and an isActive flag.
* If a route's isActive flag is set to false, then the route is not shown in relation to either RoRo or IRHS surveys.  
* Routes can only be deleted if they are not currently in user by a survey.
  * N.B. IRHS keeps port names as strings, and so although this would remove the route from the list of available ports, it would not affect already-filled surveys.
* Routes edited:
  * isActive flag can *always* be edited
  * ports can *never* be edited

### Operators / User

* Operators represent Ferry operators, and can have a number of Routes and a number of Users.
* Any user can fill out and submit any outstanding survey that an operator has been given.
* Operators cannot be deleted, but can be set as inactive, in which case they will not be issued surveys.

## Reminders / Notifications

* Reminders get sent out at 6am every morning.
* If there are any non-completed surveys from the previous month which **haven't** received an initial notification:
  * The survey will be included in an initial notification email.
  * The emails sent will list all outstanding surveys for the given Operator. 
    (i.e. a user will receive at most one of these per day) 
  * Every user of an Operator will receive these emails.
* If there are any non-completed surveys from the previous month which **have** received an initial notification **more than seven days ago**:
  * The survey will be included in a reminder email.
  * These emails list all overdue surveys (i.e. max one reminder email per day).
