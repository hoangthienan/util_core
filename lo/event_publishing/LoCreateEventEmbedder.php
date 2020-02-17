<?php

namespace go1\util\lo\event_publishing;

use Doctrine\DBAL\Connection;
use go1\util\AccessChecker;
use go1\util\edge\EdgeHelper;
use go1\util\edge\EdgeTypes;
use go1\util\portal\PortalHelper;
use go1\util\user\UserHelper;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

class LoCreateEventEmbedder
{
    protected $go1;
    protected $access;

    public function __construct(Connection $go1, AccessChecker $access)
    {
        $this->go1 = $go1;
        $this->access = $access;
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
            $users = UserHelper::loadMultiple($this->go1, $userIds);
            if ($users) {
                foreach ($users as &$user) {
                    $embedded['authors'][] = $user;
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
