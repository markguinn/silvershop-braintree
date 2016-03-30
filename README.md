# SilverShop Braintree Support Module

[![Build Status](https://travis-ci.org/markguinn/silvershop-braintree.svg?branch=master)](https://travis-ci.org/markguinn/silvershop-braintree)

Braintree uses a little different payment flow than other processors in
that you have to do some clientside javascript work to set it up and
you get a nonce back instead of credit card processing fields.

This module uses Omnipay's Braintree adapter but overrides SilverShop's
default checkout component to inject the right JavaScript. It can handle
either their Drop In UI or Hosted Fields UI and should work more or less
transparently.

## Installation

```
composer require markguinn/silvershop-braintree
```

## License

Copyright 2016 Mark Guinn, All rights reserved.

See LICENSE file. (MIT)
