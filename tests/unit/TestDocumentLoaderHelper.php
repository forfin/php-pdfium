<?php

declare(strict_types=1);

namespace BenCondaTest\PhpPdfium;

use BenConda\PhpPdfium\Document;
use BenConda\PhpPdfium\PhpPdfium;

trait TestDocumentLoaderHelper
{
    private function loadDocument(string $name): Document
    {
        return PhpPdfium::lib()
            ->loadDocument(dirname(__DIR__) . "/resources/$name.pdf");
    }

    private function loadDocumentFromResource(string $name): Document
    {
        $path = dirname(__DIR__) . "/resources/$name.pdf";
        $resource = fopen($path, 'r');

        return PhpPdfium::lib()
            ->loadDocumentFromResource($resource);
    }

    private function loadDocumentFromS3(string $name): Document
    {
        $s3Client = new \Aws\S3\S3Client([
            'region' => 'us-west-2',
            'version' => '2006-03-01',
            'endpoint' => 'http://s3:9090',
            'use_path_style_endpoint' => true,
            'credentials' => false,
        ]);
        $path = dirname(__DIR__) . "/resources/$name.pdf";
        $s3Client->putObject([
            'Bucket' => 'test',
            'Key' => $name,
            'Body' => file_get_contents($path),
        ]);

        $s3Client->registerStreamWrapperV2();
        $context = stream_context_create([
            's3' => [
                'seekable' => true
            ]
        ]);
        $resource = fopen("s3://test/$name", 'r', false, $context);

        return PhpPdfium::lib()
            ->loadDocumentFromResource($resource);
    }
}
