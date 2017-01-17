# Silverstripe Simple Booking Module

Adds a simple booking system to the Silverstripe Commerce system, allowing users
to add a "Bookable Product" to the shopping cart and pay, which will then
create a booking in the admin.

The module also checks to ensure that more items are not booked than is alowed
by the Bookable Product.

## Author

This module is created and maintained by [ilateral](http://ilateralweb.co.uk)

## Dependancies

* SilverStripe Framework 3.x
* Silverstripe Commerce

## Installation

Install this module either by downloading and adding to:

[silverstripe-root]/commerce-simplebookings

Then run: dev/build/?flush=all

Or alternativly via Composer

`i-lateral/silverstripe-commerce-simplebookings`

## Usage

Once installed, visit the Catalogue in the site admin.

Add a new "Bookable Product" and fill in it's details, as you would a
regular product.

### Defining the maximum number of spaces  

In order to cap the number of spaces that can be booked on a given date,
you must go to the settings tab and then set the "Available Places" field
to the relevent number.

The site will then never allow a user to order more than that number of
places for any given date.