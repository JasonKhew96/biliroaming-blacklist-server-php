<?php

if (!isset($_GET['ip'])) {
    return;
}

require_once '../vendor/autoload.php';
use GeoIp2\Database\Reader;

// This creates the Reader object, which should be reused across
// lookups.
$reader = new Reader('country.mmdb');

// Replace "city" with the appropriate method for your database, e.g.,
// "country".
$record = $reader->country($_GET['ip']);

print($record->country->isoCode); // 'US'
