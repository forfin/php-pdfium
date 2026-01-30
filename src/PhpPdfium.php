<?php

declare(strict_types=1);

namespace BenConda\PhpPdfium;

use FFI\CData;

final class PhpPdfium
{
    private \FFI $ffi;

    private static ?self $ownInstance = null;

    public static function lib(): self
    {
        return self::$ownInstance ??= new self();
    }

    private function __construct()
    {
        // Load the library in an FFI instance
        $this->ffi = \FFI::load(dirname(__DIR__) . '/include/pdfium.h');

        // Create a new FPDF_LIBRARY_CONFIG struct / object
        $config = $this->ffi->new('FPDF_LIBRARY_CONFIG');

        // Set up the configuration
        $config->version = 2;
        $config->m_pUserFontPaths = null;
        $config->m_pIsolate = null;
        $config->m_v8EmbedderSlot = 0;

        // Initialize the library with the configuration
        $this->ffi->FPDF_InitLibraryWithConfig(\FFI::addr($config));
    }

    public function FFI(): \FFI
    {
        return $this->ffi;
    }

    public function loadDocument(string $documentPath): ?Document
    {
        # Second parameter is the document password, null if no password needed
        $docHandler = $this->ffi->FPDF_LoadDocument($documentPath, null);
        if (null === $docHandler) {
            return null;
        }

        return new Document($docHandler);
    }

    /**
     * @param resource $resource
     */
    public function loadDocumentFromResource($resource): ?Document
    {
        $stat = fstat($resource);
        if (false === $stat) {
            return null;
        }
        $fileLen = $stat['size'];

        $fileAccess = $this->ffi->new('FPDF_FILEACCESS');
        $fileAccess->m_FileLen = $fileLen;
        $fileAccess->m_Param = null;

        $callback = function ($param, $position, $pBuf, $size) use ($resource): int {
            if (-1 === fseek($resource, $position)) {
                return 0;
            }
            $data = fread($resource, $size);
            if (false === $data) {
                return 0;
            }
            $readSize = strlen($data);
            if ($readSize > 0) {
                \FFI::memcpy($pBuf, $data, $readSize);
            }

            return 1;
        };

        $fileAccess->m_GetBlock = $callback;

        $docHandler = $this->ffi->FPDF_LoadCustomDocument(\FFI::addr($fileAccess), null);
        if (null === $docHandler) {
            return null;
        }

        // Keep the resource, callback, and fileAccess alive for the document's lifetime
        $owningObject = (object) [
            'resource' => $resource,
            'callback' => $callback,
            'fileAccess' => $fileAccess,
        ];

        return new Document($docHandler, $owningObject);
    }

    public function decodeUTF16toUT8(string $utf16String): string
    {
        $text = mb_convert_encoding($utf16String, 'UTF-8', 'UTF-16LE');

        return mb_strcut($text, 0, strlen($text) - 1);
    }

    public function encodeUTF8toUTF16(string $utf8string): string
    {
        return mb_convert_encoding($utf8string . "\x00", 'UTF-16LE', 'UTF-8');
    }

    public function convertToWideString(string $text): CData
    {
        $encodedValue = PhpPdfium::lib()->encodeUTF8toUTF16($text);

        $wideChar = $this->ffi->new("FPDF_WCHAR");
        \FFI::memcpy(\FFI::addr($wideChar), $encodedValue, strlen($encodedValue));

        return $wideChar;
    }

    /**
     * @param \Closure(null|CData, int): int $closure
     */
    public function callStringRelatedMethod(\Closure $closure): string
    {
        $length = $closure(null, 0);
        $buffer = $this->ffi->new("FPDF_WCHAR");
        $pointer = \FFI::addr($buffer);
        $closure($pointer, $length);
        $str = \FFI::string($pointer, $length);

        return PhpPdfium::lib()->decodeUTF16toUT8($str);
    }

    public function __destruct()
    {
        $this->ffi->FPDF_DestroyLibrary();
    }
}
