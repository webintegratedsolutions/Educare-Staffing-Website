<?php
include("Config.php");
$wiw = new Wheniwork($myLoginToken);

function getShiftsListingResult() {
    global $wiw;
    date_default_timezone_set('America/Toronto');
    $startDate = date("Y-m-d G:i:s");
    $endDate = date("Y-m-d G:i:s", strtotime('+90 days'));
    return $wiw->get("shifts", [
        "include_open" => true,
        "include_allopen" => true,
        "start" => $startDate,
        "end" => $endDate
    ]);
}

function getlistingJobSitesResult() {
    global $wiw;
    return $wiw->get("sites");
}

function getlistingUsersResult() {
    global $wiw;
    return $wiw->get("users");
}

$shiftsResult = getShiftsListingResult();
$jobSitesResult = getlistingJobSitesResult();
$usersResult = getlistingUsersResult();

if (!is_object($shiftsResult) || !isset($shiftsResult->shifts)) {
    die("Error fetching shifts");
}

if (!is_object($jobSitesResult) || !isset($jobSitesResult->sites)) {
    die("Error fetching job sites");
}

if (!is_object($usersResult) || !isset($usersResult->users)) {
    die("Error fetching users");
}

$previousShifts = [];
if (($handle = fopen('previous_shifts.csv', 'r')) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $previousShifts[] = [
            'client_id' => base64_decode($data[0]),
            'client_name' => base64_decode($data[1]),
            'employee_name' => base64_decode($data[2]),
            'shift_id' => base64_decode($data[3])
        ];
    }
    fclose($handle);
}

function isShiftInPrevious($shift, $previousShifts, $clientName, $employeeName) {
    foreach ($previousShifts as $prevShift) {
        if ($prevShift['client_id'] == $shift->site_id && 
            $prevShift['client_name'] == $clientName && 
            $prevShift['employee_name'] == $employeeName && 
            $prevShift['shift_id'] == $shift->id) {
            return true;
        }
    }
    return false;
}

$newShiftsFile = fopen('shifts_export.csv', 'w');
fputcsv($newShiftsFile, [
    'Child Care',   
    'ECA/RECE',       
    'Hours',          
    'Date',           
    'Total Hours',    
    'Employee'        
]);

$handle = fopen('previous_shifts.csv', 'a');

foreach ($shiftsResult->shifts as $shift) {
    $employee = null;
    foreach ($usersResult->users as $user) {
        if ($user->id == $shift->user_id) {
            $employee = $user;
            break;
        }
    }

    $clientName = "Unknown";
    foreach ($jobSitesResult->sites as $site) {
        if ($site->id == $shift->site_id) {
            $clientName = $site->name;
            
            if ($clientName == "St. Alban Boys & Girls Club") {
                $clientName = "St. Alban’s Boys and Girls Club";
            }
            break;
        }
    }

    if ($employee) {
        if (!isShiftInPrevious($shift, $previousShifts, $clientName, "{$employee->first_name} {$employee->last_name}")) {
            $startDateTime = new DateTime($shift->start_time);
            $endDateTime = new DateTime($shift->end_time);

            $eventStartDate = $startDateTime->format("M j");
            $eventStartTime = $startDateTime->format("g:i");
            $eventEndTime = $endDateTime->format("g:i");

            $interval = $startDateTime->diff($endDateTime);
            $totalHours = $interval->h + ($interval->days * 24);
            $totalMinutes = $interval->i;

            if ($totalHours >= 5) {
                $totalHours -= 1;
            }

            // Convert total minutes to decimal hours
            $decimalHours = $totalHours + ($totalMinutes / 60);

            $shift_position = "No Position";
            if (isset($employee->positions) && is_array($employee->positions) && count($employee->positions) > 0) {
                $positions = [];
                foreach ($employee->positions as $position) {
                    if ($position == "2611462") {
                        $positions[] = "ECA";
                    } elseif ($position == "2611465") {
                        $positions[] = "RECE";
                    } else {
                        $positions[] = "No Position";
                    }
                }
                $shift_position = implode(", ", $positions);
            }

            fputcsv($newShiftsFile, [
                $clientName,
                $shift_position,
                "$eventStartTime - $eventEndTime",
                $eventStartDate,
                number_format($decimalHours, 2), // Format decimal hours to 2 decimal places
                "{$employee->first_name} {$employee->middle_name} {$employee->last_name}"
            ]);

            
            fputcsv($handle, [
                base64_encode($shift->site_id),
                base64_encode($clientName),
                base64_encode("{$employee->first_name} {$employee->last_name}"),
                base64_encode($shift->id)
            ]);
        }
    }
}

fclose($newShiftsFile);
fclose($handle);

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="shifts_export.csv"');
readfile('shifts_export.csv');

unlink('shifts_export.csv');

exit;
?>