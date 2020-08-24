<?php

namespace go1\util\award\event_publishing;

use Doctrine\DBAL\Connection;
use go1\core\util\client\federation_api\v1\PortalAccountMapper;
use go1\core\util\client\federation_api\v1\UserMapper;
use go1\core\util\client\UserDomainHelper;
use go1\util\AccessChecker;
use go1\util\award\AwardHelper;
use go1\util\portal\PortalHelper;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

class AwardEnrolmentCreateEventEmbedder
{
    protected                   $go1;
    protected                   $award;
    protected                   $access;
    protected UserDomainHelper  $userDomainHelper;

    public function __construct(Connection $go1, Connection $award, AccessChecker $access, UserDomainHelper $userDomainHelper)
    {
        $this->go1 = $go1;
        $this->award = $award;
        $this->access = $access;
        $this->userDomainHelper = $userDomainHelper;
    }

    public function embedded(stdClass $awardEnrolment, Request $req = null): array
    {
        if ($award = AwardHelper::load($this->award, $awardEnrolment->award_id)) {
            $embedded['award'] = $award;
        }

        if ($portal = PortalHelper::load($this->go1, $awardEnrolment->instance_id)) {
            $embedded['portal'] = $portal;

            $user = $this->userDomainHelper->loadUser($awardEnrolment->user_id, $portal->title);
            if ($user->account) {
                $embedded['account'] = PortalAccountMapper::toLegacyStandardFormat($user, $user->account, $portal);
            }
        }

        if ($req) {
            $user = $this->access->validUser($req, $portal ? $portal->title : null);
            if ($user) {
                $embedded['jwt']['user'] = UserMapper::toLegacyStandardFormat('', $user);
            }
        }

        return $embedded;
    }
}
