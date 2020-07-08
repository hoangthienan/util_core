<?php

namespace go1\util\user;

class UserStatus
{
    const INACTIVE = 0;
    const ACTIVE   = 1;
    // @deprecated by no longer use virtual account
    const VIRTUAL  = 2; // Linked to user by HAS_ACCOUNT_VIRTUAL
}
