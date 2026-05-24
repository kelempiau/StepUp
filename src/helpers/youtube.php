<?php
/**
 * YouTube URL Helper
 * Converts any YouTube URL format to embed URL
 */

function convertToEmbedURL($url) {
    if (empty($url)) return '';
    
    // Remove whitespace
    $url = trim($url);
    
    // Short URL: youtu.be/xxxxx
    if (strpos($url, 'youtu.be/') !== false) {
        preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches);
        $id = $matches[1] ?? '';
        if ($id) {
            return "https://www.youtube.com/embed/$id?rel=0&modestbranding=1";
        }
    } 
    // Standard URL: youtube.com/watch?v=xxxxx
    elseif (strpos($url, 'youtube.com/watch') !== false) {
        preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches);
        $id = $matches[1] ?? '';
        if ($id) {
            return "https://www.youtube.com/embed/$id?rel=0&modestbranding=1";
        }
    }
    // Embed URL: youtube.com/embed/xxxxx
    elseif (strpos($url, 'youtube.com/embed/') !== false) {
        // Already embed, ensure params
        if (strpos($url, '?') === false) {
            return $url . '?rel=0&modestbranding=1';
        }
        return $url;
    }
    
    // Return original if can't parse
    return $url;
}
?>
