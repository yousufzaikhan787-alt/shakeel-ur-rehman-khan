<?php
/**
 * Bulk user upload utility for Moodle (CLI).
 * Reads CSV -> validates -> creates users with cohort assignment.
 * Usage: php bulk_upload_users.php users.csv
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

global $DB;

if ($argc < 2) {
    echo "Usage: php bulk_upload_users.php <csvfile>\n";
    exit(1);
}

$csvfile = $argv[1];
$content = file_get_contents($csvfile);
$importid = csv_import_reader::get_new_iid('uploaduser');
$cir = new csv_import_reader($importid, 'uploaduser');
$cir->load_csv_content($content, 'UTF-8', 'comma');
$columns = $cir->get_columns();

$required = ['username', 'firstname', 'lastname', 'email', 'cohort'];
$missing = array_diff($required, $columns);
if (!empty($missing)) {
    echo "Missing columns: " . implode(', ', $missing) . "\n";
    exit(1);
}

$cir->init();
$created = 0;
while ($record = $cir->next()) {
    $record = array_combine($columns, $record);

    if ($DB->record_exists('user', ['username' => $record['username']])) {
        echo "Skip existing: {$record['username']}\n";
        continue;
    }

    $user = new stdClass();
    $user->username   = trim($record['username']);
    $user->firstname  = trim($record['firstname']);
    $user->lastname   = trim($record['lastname']);
    $user->email      = trim($record['email']);
    $user->password   = hash_internal_user_password('Changeme!2026');
    $user->confirmed  = 1;
    $user->mnethostid = $CFG->mnet_localhost_id;
    $user->timecreated = time();

    $userid = $DB->insert_record('user', $user);

    if ($cohort = $DB->get_record('cohort', ['idnumber' => $record['cohort']])) {
        cohort_add_member($cohort->id, $userid);
    }

    $created++;
    echo "Created user #{$userid}: {$user->username}\n";
}

echo "Done. {$created} users created.\n";
