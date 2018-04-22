# Log of changes for Simple Bookings Module

This file lists changes made to this module

## 0.1.0

* Initial release

## 0.2.0

* Add ability to set a minimum quantity on a booking

## 0.3.0

* Improve date filtering when checking bookings
* Add ability to sync booking with order
* Update dependencies
* Additional minor amends

## 0.4.0

* Switch to using dedicated resources on bookings
* Add ability to pre-allocate spaces of `BookableProduct`s

## 0.5.0

* Simplify how bookings work in admin slightly

## 0.5.1

* Add `Syncroniser` class to handle syncing bookings and orders
* Add extension hooks to syncronisation points

## 0.5.2

* Use new PSR2 function names.
* Fix bugs in resource allocation checks.
* Clean up resource allocation code.

## 0.5.3

 * Ensure booking end date is generated more accuratly
 * Fix recursive error when syncing bookings and orders
 * Add ability to disable the automatic order sync
 * Add fiedl to display attached order