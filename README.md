# Singapore NRIC/FIN number generator and validator

[![Latest version](https://img.shields.io/packagist/v/ion-bazan/nric.svg)](https://packagist.org/packages/ion-bazan/nric)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/IonBazan/NRIC/Tests)](https://github.com/IonBazan/NRIC/actions)
[![PHP version](https://img.shields.io/packagist/php-v/ion-bazan/nric.svg)](https://packagist.org/packages/ion-bazan/nric)
[![Codecov](https://img.shields.io/codecov/c/gh/IonBazan/NRIC)](https://codecov.io/gh/IonBazan/NRIC)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FIonBazan%2FNRIC%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/IonBazan/NRIC/main)
[![Downloads](https://img.shields.io/packagist/dt/ion-bazan/nric.svg)](https://packagist.org/packages/ion-bazan/nric)
[![License](https://img.shields.io/packagist/l/ion-bazan/nric.svg)](https://packagist.org/packages/ion-bazan/nric)

This package provides a self-validating value object for storing, generating and validating Singapore NRIC and FIN numbers in PHP.

## Usage

```php
<?php

use IonBazan\NRIC\Exception\InvalidChecksumException;
use IonBazan\NRIC\Exception\InvalidFormatException;
use IonBazan\NRIC\NRIC;

$nric = NRIC::generateNric(new DateTime('1990-01-01')); // Generate a random NRIC number
$fin = NRIC::generateFin(new DateTime('1990-01-01')); // Generate a random FIN number

try {
    $invalid = NRIC::fromString('S0000001A'); // Create a self-validating (invalid) instance
} catch (InvalidChecksumException|InvalidFormatException $e) {
    var_dump('invalid');
}

$valid = NRIC::fromString('S0000001I'); // Create a self-validating instance from valid input
var_dump($valid->__toString()); // Thanks to Stringable interface
```
