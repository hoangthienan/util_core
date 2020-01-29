<?php

namespace go1\util\lo;

class PackageLicenseTypes
{
    const SEAT   = 'seat_license';
    const PORTAL = 'portal_license';

    public static function all()
    {
        return [self::SEAT, self::PORTAL];
    }
}
