<?php

namespace go1\util\lo\event_publishing;

use Doctrine\DBAL\Connection;
use go1\core\util\client\federation_api\v1\UserMapper;
use go1\core\util\client\UserDomainHelper;
use go1\util\AccessChecker;
use go1\util\edge\EdgeHelper;
use go1\util\edge\EdgeTypes;
use go1\util\portal\PortalHelper;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

class LoCreateEventEmbedder
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

    protected function embedAuthors(array &$embedded, int $loId)
    {
        $hasAuthorEdges = EdgeHelper::edgesFromSource($this->go1, $loId, [EdgeTypes::HAS_AUTHOR_EDGE]);
        if ($hasAuthorEdges) {
            foreach ($hasAuthorEdges as $hasAuthorEdge) {
                $userIds[] = (int) $hasAuthorEdge->target_id;
            }
        }

        if (!empty($userIds)) {
            $users = $this->userDomainHelper->loadMultipleUsers($userIds);
            if ($users) {
                foreach ($users as &$user) {
                    $embedded['authors'][] = UserMapper::toLegacyStandardFormat('', $user);
                }
            }
        }
    }

    public function embedded(stdClass $lo, Request $req): array
    {
        $embedded = [];

        $portal = PortalHelper::load($this->go1, $lo->instance_id);
        if ($portal) {
            $embedded['portal'] = $portal;
        }

        $user = $this->access->validUser($req, $portal ? $portal->title : null);
        if ($user) {
            $embedded['jwt']['user'] = $user;
        }

        $this->embedAuthors($embedded, $lo->id);

        return $embedded;
    }
}
