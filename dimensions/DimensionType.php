<?php

namespace go1\util\dimensions;

class DimensionType
{
    const TOPIC                  = 1;
    const INDUSTRY               = 2;
    const REGION_RESTRICTION     = 3;
    const LOCATION               = 4;
    const BUSINESS_AREA          = 5;
    const EXTERNAL_ACTIVITY_TYPE = 6;
    const LEARNER_LEVEL          = 7;
    const LOCALE                 = 8;
    const REGION_RELEVANCE       = 9;
    const ROLE_SKILL             = 10;
    const PLAYBACK_TARGET        = 11;

    public static function all()
    {
        $rSelf = new \ReflectionClass(__CLASS__);
        $values = [];
        foreach ($rSelf->getConstants() as $const) {
            if (is_scalar($const)) {
                $values[] = $const;
            }
        }

        return $values;
    }
}
