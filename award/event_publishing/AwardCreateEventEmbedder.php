<?php

namespace go1\util\award\event_publishing;

use Doctrine\DBAL\Connection;
use go1\core\util\client\federation_api\v1\UserMapper;
use go1\core\util\client\UserDomainHelper;
use go1\util\AccessChecker;
use go1\util\portal\PortalHelper;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

class AwardCreateEventEmbedder
{
    protected Connection        $go1;
    protected AccessChecker     $access;
    protected UserDomainHelper  $userDomainHelper;

    public function __construct(Connection $go1, AccessChecker $access, UserDomainHelper $userDomainHelper)
    {
        $this->go1 = $go1;
        $this->access = $access;
        $this->userDomainHelper = $userDomainHelper;
    }

    public function embedded(stdClass $award, Request $req = null): array
    {
        $embedded = [];

        $portal = PortalHelper::load($this->go1, $award->instance_id);
        if ($portal) {
            $embedded['portal'] = $portal;
        }

        if ($req) {
            $user = $this->access->validUser($req, $portal ? $portal->title : null);
            if ($user) {
                $embedded['jwt']['user'] = $user;
            }
        }

        if ($author = $this->userDomainHelper->loadUser($award->user_id)) {
            $embedded['authors'][] = UserMapper::toLegacyStandardFormat('', $author);
        }

        return $embedded;
    }
}
