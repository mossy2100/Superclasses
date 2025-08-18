<?php

declare(strict_types=1);

namespace Superclasses\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Superclasses\Color;

#[CoversClass(Color::class)]
final class ColorTests extends TestCase
{
    #[Test]
    public function constructHexAndProperties(): void
    {
        $color = new Color('#0f8');
        $this->assertSame(0, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(136, $color->blue);
        $this->assertSame(255, $color->alpha);
    }

    #[Test]
    public function constructColorNameCaseInsensitive(): void
    {
        $color = new Color('ReD');
        $this->assertSame(255, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(0, $color->blue);
    }

    #[Test]
    public function constructHexWithAlpha(): void
    {
        $color = new Color('#1234');
        $this->assertSame(0x11, $color->red);
        $this->assertSame(0x22, $color->green);
        $this->assertSame(0x33, $color->blue);
        $this->assertSame(0x44, $color->alpha);
    }

    #[Test]
    public function fromRgbaAndVirtualProperties(): void
    {
        $color = Color::fromRgba(10, 20, 30, 127);
        $this->assertSame(10, $color->red);
        $this->assertSame(127, $color->alpha);
        $color->blue = 40;
        $this->assertSame(40, $color->blue);
    }

    #[Test]
    public function fromHsla(): void
    {
        $color = Color::fromHsla(120, 1, 0.5, 64);
        $this->assertSame(0, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(0, $color->blue);
        $this->assertSame(64, $color->alpha);
    }
}
