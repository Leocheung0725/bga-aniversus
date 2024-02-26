<?php

function read_card_infos($csvData) {
    // Split the CSV data into lines
    $lines = explode("\n", trim($csvData)); // Trim to remove possible whitespace

    // Extract the headers
    $headers = str_getcsv(array_shift($lines));

    // Process the lines into an array of associative arrays
    $data = array_map(function($line) use ($headers) {
        // Combine the header and data lines into an associative array
        $row = array_combine($headers, str_getcsv($line));

        // Cast numeric values
        foreach ($row as $key => $value) {
            if (is_numeric($value)) {
                $row[$key] = $value + 0; // This will cast to int or float automatically
            }
        }

        return $row;
    }, $lines);

    return $data;
};

?>
