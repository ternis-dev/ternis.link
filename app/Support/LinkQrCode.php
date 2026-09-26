<?php

namespace App\Support;

use App\Models\Link;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

/**
 * QR codes for short links. Encodes the canonical public short URL
 * (always https — a scanned code must work from any phone).
 */
class LinkQrCode
{
    public static function shortUrl(Link $link): string
    {
        $host = $link->domain?->hostname ?? 'href.nz';

        return "https://{$host}/{$link->slug}";
    }

    public static function svgDataUri(Link $link): string
    {
        $qr = new QrCode(
            data: static::shortUrl($link),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 10,
        );

        return (new SvgWriter())->write($qr)->getDataUri();
    }

    public static function png(Link $link, int $size = 600): string
    {
        $qr = new QrCode(
            data: static::shortUrl($link),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 10,
        );

        return (new PngWriter())->write($qr)->getString();
    }
}
