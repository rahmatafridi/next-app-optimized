<?php
// Function to generate slug from a string
function generateSlug($string) {
    // Convert to lowercase
    $slug = strtolower($string);
    // Remove special characters
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    // Replace multiple spaces or hyphens with a single hyphen
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    // Trim hyphens from beginning and end
    $slug = trim($slug, '-');
    return $slug;
}
?>
