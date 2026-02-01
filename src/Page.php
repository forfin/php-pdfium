<?php

declare(strict_types=1);

namespace BenConda\PhpPdfium;

use BenConda\PhpPdfium\Page\Annotation\Annotation;
use BenConda\PhpPdfium\Page\Annotation\AnnotationFactory;
use BenConda\PhpPdfium\Page\Annotation\FormField;
use BenConda\PhpPdfium\Page\PageBitmap;
use FFI\CData;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int,Annotation>
 */
final class Page implements IteratorAggregate
{
    private readonly \FFI $ffi;

    public function __construct(
        private readonly Document $document,
        private CData $handler,
        private readonly int $index,
    ) {
        $this->ffi = PhpPdfium::lib()->FFI();
    }

    public function getHandler(): CData
    {
        return $this->handler;
    }

    public function getNumber(): int
    {
        return $this->index + 1;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getAnnotationsCount(): int
    {
        return $this->ffi->FPDFPage_GetAnnotCount($this->handler);
    }

    public function loadAnnotation(int $index): ?Annotation
    {
        $annotationHandler = $this->ffi->FPDFPage_GetAnnot($this->handler, $index);

        if (null === $annotationHandler) {
            return null;
        }

        return AnnotationFactory::create($this, $annotationHandler, $index);
    }

    public function getIterator(): Traversable
    {
        $count = $this->getAnnotationsCount();
        for ($index = 0; $index < $count; ++$index) {
            yield $this->loadAnnotation($index);
        }
    }

    /**
     * @return Traversable<int, FormField>
     */
    public function getFormFieldsIterator(): Traversable
    {
        foreach ($this as $annotation) {
            if ($annotation instanceof FormField) {
                yield $annotation;
            }
        }
    }

    public function getBitmap(
        ?int $width = null,
        ?int $height = null,
        ?int $x = 0,
        ?int $y = 0
    ): PageBitmap {
        return new PageBitmap($this, $width, $height, $x, $y);
    }

    public function flatten(): self
    {
        $this->ffi->FPDFPage_Flatten($this->handler, 1);
        $this->reload();

        return $this;
    }

    public function getWidth(): int
    {
        return (int) $this->ffi->FPDF_GetPageWidth($this->handler);
    }

    public function getHeight(): int
    {
        return (int) $this->ffi->FPDF_GetPageHeight($this->handler);
    }

    public function getMediaBox(): ?Rect
    {
        return $this->getBox('FPDFPage_GetMediaBox');
    }

    public function getArtBox(): ?Rect
    {
        return $this->getBox('FPDFPage_GetArtBox');
    }

    public function getCropBox(): ?Rect
    {
        return $this->getBox('FPDFPage_GetCropBox');
    }

    public function getBleedBox(): ?Rect
    {
        return $this->getBox('FPDFPage_GetBleedBox');
    }

    public function getTrimBox(): ?Rect
    {
        return $this->getBox('FPDFPage_GetTrimBox');
    }

    private function getBox(string $functionName): ?Rect
    {
        $left = $this->ffi->new('float[1]');
        $bottom = $this->ffi->new('float[1]');
        $right = $this->ffi->new('float[1]');
        $top = $this->ffi->new('float[1]');

        $result = $this->ffi->$functionName(
            $this->handler,
            \FFI::addr($left[0]),
            \FFI::addr($bottom[0]),
            \FFI::addr($right[0]),
            \FFI::addr($top[0]),
        );

        if (0 === $result) {
            return null;
        }

        return new Rect($left[0], $bottom[0], $right[0], $top[0]);
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function close(): void
    {
        $this->ffi->FORM_OnBeforeClosePage($this->handler, $this->document->getFormHandler());
        $this->ffi->FPDF_ClosePage($this->handler);
    }

    public function generateContent(): bool
    {
        return (bool) $this->ffi->FPDFPage_GenerateContent($this->handler);
    }

    public function reload(): void
    {
        $this->close();
        $this->handler = $this->ffi->FPDF_LoadPage($this->document->handler, $this->index);
    }

    public function __destruct()
    {
        $this->close();
    }
}
