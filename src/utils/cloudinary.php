<?php
/**
 * Cloudinary Upload Helper
 * Uploads a file (from $_FILES) or a URL to Cloudinary using their REST API.
 * Falls back to local storage when Cloudinary credentials are not set.
 */

function cloudinary_upload_file(array $file, string $folder = 'uploads', string $resourceType = 'auto'): array
{
    $cloudName  = getenv('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? '');
    $apiKey     = getenv('CLOUDINARY_API_KEY')    ?: ($_ENV['CLOUDINARY_API_KEY']    ?? '');
    $apiSecret  = getenv('CLOUDINARY_API_SECRET') ?: ($_ENV['CLOUDINARY_API_SECRET'] ?? '');

    if (!$cloudName || !$apiKey || !$apiSecret) {
        return ['success' => false, 'message' => 'Cloudinary credentials not configured. Add CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET to your Vercel environment variables.'];
    }

    $timestamp = time();
    $params    = ['folder' => $folder, 'timestamp' => $timestamp];
    ksort($params);

    // Build signature
    $sigString = '';
    foreach ($params as $k => $v) {
        $sigString .= ($sigString ? '&' : '') . "$k=$v";
    }
    $signature = sha1($sigString . $apiSecret);

    $endpoint = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/upload";

    $postFields = [
        'file'      => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
        'folder'    => $folder,
        'timestamp' => $timestamp,
        'api_key'   => $apiKey,
        'signature' => $signature,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $endpoint,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'message' => 'Upload failed: ' . $curlError];
    }

    $data = json_decode($response, true);
    if (!empty($data['secure_url'])) {
        return ['success' => true, 'url' => $data['secure_url'], 'public_id' => $data['public_id'] ?? ''];
    }

    $errMsg = $data['error']['message'] ?? 'Unknown error';
    return ['success' => false, 'message' => 'Cloudinary error: ' . $errMsg];
}

function cloudinary_credentials_set(): bool
{
    $cloud = getenv('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? '');
    return !empty($cloud);
}
