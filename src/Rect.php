<?php

declare(strict_types=1);

namespace BenConda\PhpPdfium;

final class Rect
{
    public function __construct(
        private readonly float $left,
        private readonly float $bottom,
        private readonly float $right,
        private readonly float $top
    ) {
    }

    public function getLeft(): float
    {
        return $this->left;
    }

    public function getBottom(): float
    {
        return $this->bottom;
    }

    public function getRight(): float
    {
        return $this->right;
    }

    public function getTop(): float
    {
        return $this->top;
    }

    public function getWidth(): float
    {
        return abs($this->right - $this->left);
    }

    public function getHeight(): float
    {
        return abs($this->top - $this->bottom);
    }
}
