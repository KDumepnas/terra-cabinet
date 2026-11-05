<?php
declare(strict_types=1);

/**
 * Validates an uploaded image and saves it into uploads/.
 *
 * The photo is centre-cropped to the site's 4:3 frame, resized (max 1200x900)
 * and saved as a JPEG. If the GD extension is missing, the original file is
 * stored instead.
 *
 * Returns ['path' => 'uploads/abc.jpg'] on success, or ['error' => 'message'].
 * Used by both add-item.php and edit-item.php so every photo looks the same.
 */
function save_specimen_image(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['error' => 'The image upload failed.'];
    }
    if ((int)$file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'Image must be 5 MB or smaller.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $imageTypes = ['image/jpeg' => IMAGETYPE_JPEG, 'image/png' => IMAGETYPE_PNG, 'image/webp' => IMAGETYPE_WEBP, 'image/gif' => IMAGETYPE_GIF];
    if (!is_string($mime) || !isset($extensions[$mime])) {
        return ['error' => 'Upload a JPG, PNG, WEBP, or GIF image.'];
    }
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!is_array($imageInfo) || !isset($imageInfo[0], $imageInfo[1]) || (int)$imageInfo[2] !== $imageTypes[$mime]) {
        return ['error' => 'The uploaded file is not a valid image.'];
    }
    $imageWidth = (int)$imageInfo[0];
    $imageHeight = (int)$imageInfo[1];
    if ($imageWidth < 1 || $imageHeight < 1 || $imageWidth > 8000 || $imageHeight > 8000 || $imageWidth * $imageHeight > 40000000) {
        return ['error' => 'Image dimensions must be no larger than 8000 pixels per side or 40 megapixels total.'];
    }

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['error' => 'The uploads folder could not be created.'];
    }

    $source = false;
    if (function_exists('imagecreatetruecolor')) {
        if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
            $source = @imagecreatefromjpeg($file['tmp_name']);
        } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
            $source = @imagecreatefrompng($file['tmp_name']);
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $source = @imagecreatefromwebp($file['tmp_name']);
        } elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
            $source = @imagecreatefromgif($file['tmp_name']);
        }
    }

    if ($source) {
        // Center-crop to 4:3, then shrink if larger than 1200x900.
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetRatio = 4 / 3;
        $sourceRatio = $sourceWidth / max(1, $sourceHeight);
        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int)round($sourceHeight * $targetRatio);
            $cropX = (int)(($sourceWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int)round($sourceWidth / $targetRatio);
            $cropX = 0;
            $cropY = (int)(($sourceHeight - $cropHeight) / 2);
        }
        $scale = min(1, 1200 / $cropWidth, 900 / $cropHeight);
        $width = max(1, (int)round($cropWidth * $scale));
        $height = max(1, (int)round($cropHeight * $scale));

        $resized = imagecreatetruecolor($width, $height);
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));

        $filename = bin2hex(random_bytes(16)) . '.jpg';
        $saved = imagecopyresampled($resized, $source, 0, 0, $cropX, $cropY, $width, $height, $cropWidth, $cropHeight)
            && imagejpeg($resized, $uploadDir . '/' . $filename, 84);
        if (!$saved) {
            return ['error' => 'The image could not be resized and saved.'];
        }
        return ['path' => 'uploads/' . $filename];
    }

    // GD unavailable (or could not read the file): keep the original image.
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        return ['error' => 'The image could not be saved.'];
    }
    return ['path' => 'uploads/' . $filename];
}
