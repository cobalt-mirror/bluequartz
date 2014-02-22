<?php

/**
 * This function returns the server timezone offset in seconds
 * e.g. Sydney in DST returns +1100 / 100 * 60 * 60 = 39600
 */
function getServerTimeZoneOffset()
{
    return date("O") / 100 * 60 * 60; // Seconds from GMT
}

/**
 * This function returns the local timezone offset in seconds
 *   where getTimeZone($userid) returns a timezone see below.
 */
function getLocalTimeZoneOffset($userid)
{
    return getTimeZone($userid) / 100 * 60 * 60; // Seconds from user GMT
}

// Now converting a server timestamp to a local timestamp is very simple.

/**
 * Will take a timestamp and minus off Server GMT and add on user GMT seconds
 * thereby making a local timestamp from a server timestamp.
 */
function getLocalTimestampFromServerTimestamp($userid, $timestamp)
{
    return $timestamp - getServerTimeZoneOffset() + getLocalTimeZoneOffset( $userid );
}

//And getting the local time from a user entered date is a useful function.
//Combine the next two functions to achieve just that.

function getLocalTimestampFromDateTime($userid, $datetime)
{
    $timestamp = strtotime($datetime);
    return getLocalTimestampFromServerTimestamp($userid, $timestamp);
}

/**
 * If you have a localized timestamp and just want to get the date format use this.
 */
function getFormattedDate($userid, $timestamp)
{
    return date("Y-m-d H:i:s " . getTimeZone($userid), $timestamp);
}

/**
 * Returns a timezone in the format +0000
 *  e.g. Perth in DST returns +0900
 */
function getTimeZone($userid)
{
    return "+0900"; // Perth in DST
}

?>
