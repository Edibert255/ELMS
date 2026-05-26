<?php
function resizeImage($source_path, $destination_path, $max_width = 150, $max_height = 150) {
    // Get image info
    $image_info = getimagesize($source_path);
    $image_type = $image_info[2];
    
    // Load image based on type
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        default:
            return false;
    }
    
    // Get original dimensions
    $width = imagesx($source);
    $height = imagesy($source);
    
    // Calculate new dimensions
    if ($width > $height) {
        $new_width = $max_width;
        $new_height = intval($height * $max_width / $width);
    } else {
        $new_height = $max_height;
        $new_width = intval($width * $max_height / $height);
    }
    
    // Create new image
    $resized = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG
    if ($image_type == IMAGETYPE_PNG) {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save resized image
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($resized, $destination_path, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($resized, $destination_path, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($resized, $destination_path);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($resized);
    
    return true;
}
?>