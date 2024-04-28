<?php
// Get the camera, year, month, and day from the query parameters
$camera = $_GET['cam'] ?? '';
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';
$day = $_GET['day'] ?? '';

// Validate the input parameters
if (empty($camera) || empty($year) || empty($month) || empty($day)) {
  http_response_code(400);
  echo "Missing required parameters";
  exit;
}

// Construct the directory path
$directory = "/var/www/corolive.nz/api/{$camera}/archive/{$year}/{$month}/{$day}";

// Check if the directory exists
if (!is_dir($directory)) {
  http_response_code(404);
  echo "Date for this date not found";
  exit;
}

// Set the appropriate headers for JSON output
header('Content-Type: application/json');

// Get all the .webp files in the directory
$files = glob("{$directory}/snap-*.webp");

// Sort the files in the desired order (e.g., snap-05:00.webp to snap-22:00.webp)
usort($files, function ($a, $b) {
  return strcmp($a, $b);
});

if (isset($_GET['count'])) {
  $totalCount = count($files);
  echo json_encode(['totalCount' => $totalCount]);
  exit;
}

// Get the start and end offsets from the query parameters
$startOffset = $_GET['startOffset'] ?? 0;
$endOffset = $_GET['endOffset'] ?? PHP_INT_MAX;

// Create an array to store the image data for the current chunk
$chunkImageDataArray = [];
$currentOffset = 0;

// Add each WebP image's data to the chunk array until the end offset is reached
foreach ($files as $file) {
  $fileSize = filesize($file);

  if ($currentOffset >= $startOffset && $currentOffset < $endOffset) {
    $imageData = base64_encode(file_get_contents($file));
    $chunkImageDataArray[] = $imageData;
  }

  $currentOffset += $fileSize;

  if ($currentOffset >= $endOffset) {
    break;
  }
}

// Output the chunk image data as a JSON array
echo json_encode($chunkImageDataArray);
?>