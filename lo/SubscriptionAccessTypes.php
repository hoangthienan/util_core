<?php

namespace go1\util\lo;

class SubscriptionAccessTypes
{
    const NO_SUBSCRIPTION_NEEDED = 0;
    const SUBSCRIPTION_NEEDED = 1;
    const LICENSE_NEEDED = 2;
    const LICENSED = 3;
    const LICENSE_AVAILABLE = 4;

    public static function all()
    {
        return [
            self::NO_SUBSCRIPTION_NEEDED,
            self::SUBSCRIPTION_NEEDED,
            self::LICENSE_NEEDED,
            self::LICENSED,
            self::LICENSE_AVAILABLE
        ];
    }
}
