<?php

namespace go1\util\lo;

class PackageLicenseTypes
{
    const SEAT   = 1;
    const PORTAL = 2;

    public static function all()
    {
        return [self::SEAT, self::PORTAL];
    }
}
