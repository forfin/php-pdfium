<?php

declare(strict_types=1);

namespace BenCondaTest\PhpPdfium;

use PHPUnit\Framework\TestCase;

final class MemoryTest extends TestCase
{
    use TestDocumentLoaderHelper;

    public function testGarbageCollectionDoesNotCauseSegfault(): void
    {
        // 1. Load document from resource
        $document = $this->loadDocumentFromResource('cerfa_13750-05');

        // 2. Force garbage collection.
        // If the callback/struct is held by FFI internal structures, this is safe.
        gc_collect_cycles();

        // 3. Perform an operation that requires reading from the file.
        $page = $document->loadPage(0);
        $width = $page->getWidth();

        self::assertGreaterThan(0, $width);
    }
}
