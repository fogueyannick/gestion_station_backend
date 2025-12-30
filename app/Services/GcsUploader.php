<?php

namespace App\Services;

use Google\Cloud\Storage\StorageClient;

class GcsUploader
{
    public static function upload($file, string $folder): string
    {
        $storage = new StorageClient([
            'projectId' => env('GOOGLE_CLOUD_PROJECT'),
            'keyFilePath' => storage_path('app/gcs-key.json'),
        ]);

        $bucket = $storage->bucket(env('GOOGLE_CLOUD_BUCKET'));

        $fileName = $folder . '/' . uniqid() . '.' . $file->getClientOriginalExtension();

        $bucket->upload(
            fopen($file->getRealPath(), 'r'),
            [
                'name' => $fileName,
            ]
        );

        return "https://storage.googleapis.com/" . env('GOOGLE_CLOUD_BUCKET') . "/" . $fileName;
    }
}
