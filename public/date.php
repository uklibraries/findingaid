<?php

date_default_timezone_set('America/New_York');

$now = time();

if (isset($_GET) && isset($_GET['request'])) {
    if (preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)Z(\d\d):(\d\d)$/', $_GET['request'], $matches)) {
        $year = $matches[1];
        $month = $matches[2];
        $day = $matches[3];
        $hour = $matches[4];
        $minute = $matches[5];
        $now = strtotime("$year-$month-$day $hour:$minute:00");
    }
}

$request_date = date('Y-m-d', $now);

$watershed_hour = 15;
$watershed_minute = 30;
$watershed_minutes = $watershed_hour * 60 + $watershed_minute;

$hour = intval(date('H', $now));
$minute = intval(date('i', $now));
$minutes = $hour * 60 + $minute;

if ($minutes >= $watershed_minutes) {
    $earliest_date = date('Y-m-d', strtotime("$request_date +1 Weekday"));
}
else {
    if (date('N', strtotime($request_date)) >= 6) {
        $earliest_date = date('Y-m-d', strtotime("$request_date +1 Weekday"));
    }
    else {
        $earliest_date = $request_date;
    }
}

$iso_date = $earliest_date;
$earliest_date = date('m/d/Y', strtotime($iso_date));

$results = array(
    'earliest' => $earliest_date,
    'iso_earliest' => $iso_date,
    'request' => $request_date,
);

print json_encode($results);
