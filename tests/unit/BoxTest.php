<?php

declare(strict_types=1);

namespace BenCondaTest\PhpPdfium;

use BenConda\PhpPdfium\Rect;
use PHPUnit\Framework\TestCase;

final class BoxTest extends TestCase
{
    use TestDocumentLoaderHelper;

    public function testGetBoxes(): void
    {
        $document = $this->loadDocument('version4pdf');
        $page = $document->loadPage(0);

        $boxes = [
            $page->getMediaBox(),
            $page->getArtBox(),
            $page->getCropBox(),
            $page->getBleedBox(),
            $page->getTrimBox(),
        ];
        foreach ($boxes as $box) {
            self::assertInstanceOf(Rect::class, $box);
            self::assertGreaterThan(0, $box->getWidth());
            self::assertGreaterThan(0, $box->getHeight());
        }
    }
}
