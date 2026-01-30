<?php

declare(strict_types=1);

namespace BenCondaTest\PhpPdfium;

use PHPUnit\Framework\TestCase;

final class MultipleDocumentsTest extends TestCase
{
    use TestDocumentLoaderHelper;

    public function testMultipleDocumentsFromResources(): void
    {
        // 1. Open first document
        $doc1 = $this->loadDocumentFromResource('cerfa_13750-05');

        // 2. Open second document (different or same file, doesn't matter, using same for simplicity but separate streams)
        $doc2 = $this->loadDocumentFromResource('notice');

        // 3. Force GC to shake out any weak reference implementations
        gc_collect_cycles();

        // 4. Interleaved usage
        $page1 = $doc1->loadPage(0);
        $page2 = $doc2->loadPage(0); // This is from 'notice', should have different dimensions

        // Check Doc 1 (cerfa) details
        // Cerfa is usually A4-ish
        $width1 = $page1->getWidth();

        // Check Doc 2 (notice) details
        $width2 = $page2->getWidth();

        self::assertGreaterThan(0, $width1);
        self::assertGreaterThan(0, $width2);

        // Ensure they are not strictly tied if they are different docs
        // (Assuming notice and cerfa might have different widths or content)
        // Just ensuring they don't crash is the main point.

        // 5. Explicitly destroy doc 1 and check doc 2
        unset($doc1, $page1);
        gc_collect_cycles();

        $usage2 = $page2->getHeight(); // page2 still alive? 
        // Note: Page object holds reference to Document object, so Document is alive.
        // We need to reload page to be sure we are hitting the file/callback again?
        // Actually accessing getters on Page might use cached values in FFI?
        // FPDF_GetPageWidthF uses the page handle.

        // Let's load another page from doc2 to trigger file access
        $page2b = $doc2->loadPage(1);
        self::assertNotNull($page2b);
    }
}
