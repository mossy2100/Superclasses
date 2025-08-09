<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Superclasses\Color;

final class ColorTest extends TestCase
{
    public function testConstructHexAndProperties(): void
    {
        $color = new Color('#0f8');
        $this->assertSame(0, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(136, $color->blue);
        $this->assertEqualsWithDelta(1.0, $color->alpha, 0.0001);
    }

    public function testConstructColorNameCaseInsensitive(): void
    {
        $color = new Color('ReD');
        $this->assertSame(255, $color->red);
        $this->assertSame(0, $color->green);
        $this->assertSame(0, $color->blue);
    }

    public function testHexWithAlpha(): void
    {
        $color = new Color('#1234');
        $this->assertSame(0x11, $color->red);
        $this->assertSame(0x22, $color->green);
        $this->assertSame(0x33, $color->blue);
        $this->assertEqualsWithDelta(0x44/255, $color->alpha, 0.0001);
    }

    public function testFromRgbaAndVirtualProperties(): void
    {
        $color = Color::fromRgba(10, 20, 30, 0.5);
        $this->assertSame(10, $color->red);
        $this->assertEqualsWithDelta(0.5, $color->alpha, 0.01);
        $color->blue = 40;
        $this->assertSame(40, $color->blue);
    }

    public function testFromHsla(): void
    {
        $color = Color::fromHsla(120, 1, 0.5, 0.25);
        $this->assertSame(0, $color->red);
        $this->assertSame(255, $color->green);
        $this->assertSame(0, $color->blue);
        $this->assertEqualsWithDelta(0.25, $color->alpha, 0.01);
    }
}
