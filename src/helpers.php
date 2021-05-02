<?php
/**
 * Author: Amir Hossein Jahani | iAmir.net
 * Last modified: 11/28/20, 4:41 PM
 * Copyright (c) 2021. Powered by iamir.net
 */

function grib2_path($path = null)
{
    $path = trim($path, '/');
    return __DIR__ . ($path ? "/$path" : '');
}

function grib2_clear_lon_lat($number, $ratio = 0.25) {
    return ((int)(round($number, 2) / $ratio)) * $ratio;
}

function grib2_find_index($longitude, $latitude, $ratio = 0.25) {
    $lon = grib2_clear_lon_lat($longitude);
    $lat = grib2_clear_lon_lat($latitude);
    $index = ((($lat - 90) / $ratio) * (360 / $ratio));
    $index = $index < 0 ? $index * -1 : $index;
    $index += ($lon / $ratio);
    return $index;
}
