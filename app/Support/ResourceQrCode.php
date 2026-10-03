<?php

namespace App\Support;

use App\Models\Resource;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Renders QR codes that deep-link to a resource's mobile booking page.
 */
class ResourceQrCode
{
    public static function payload(Resource $resource): string
    {
        return route('scan.show', $resource->code);
    }

    /**
     * Render the resource QR code as a PNG data URI.
     */
    public static function dataUri(Resource $resource, int $size = 300, int $margin = 8): string
    {
        $qrCode = new EndroidQrCode(
            data: self::payload($resource),
            size: $size,
            margin: $margin,
        );

        return (new PngWriter)->write($qrCode)->getDataUri();
    }
}
