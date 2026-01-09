<?php
// Generate and download CSV template
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_template.csv"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

// Create CSV template with headers and sample data
$output = fopen('php://output', 'w');

// CSV Headers
fputcsv($output, ['student_id', 'first_name', 'last_name', 'email', 'course', 'year_level']);

// Sample data rows (optional - remove if you want empty template)
fputcsv($output, ['2020-123456', 'John', 'Doe', 'john.doe@ust.edu.ph', 'BSIT', '3']);
fputcsv($output, ['2020-123457', 'Jane', 'Smith', 'jane.smith@ust.edu.ph', 'BSCS', '2']);
fputcsv($output, ['2020-123458', 'Bob', 'Johnson', 'bob.johnson@ust.edu.ph', 'BSIT', '4']);

fclose($output);
exit;
?>
