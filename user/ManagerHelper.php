<?php

namespace go1\util\user;

use Doctrine\DBAL\Connection;
use go1\core\util\client\federation_api\v1\schema\object\PortalAccountRole;
use go1\util\edge\EdgeHelper;
use go1\util\edge\EdgeTypes;
use PDO;

/**
 * @deprecated No useful methods.
 */
class ManagerHelper
{
    /**
     * <<<<<<< HEAD
     * @deprecated Use UserDomainHelper::isManager($portalName, $managerAccountId, $learnerAccountId)
     * =======
     * @deprecated Use UserDomainHelper::isManager($portalName, $managerPortalAccountLegacyId, $portalAccountLegacyId)
     * >>>>>>> 697213ddc05189f19b588c09922d2e2f5f906947
     */
    public static function isManagerOfUser(Connection $go1, string $portalName, int $managerUserId, int $studentId): bool
    {
        # From instance & user ID, we find account ID.
        $studentAccountId = 'SELECT u.mail FROM gc_user u WHERE u.id = ?';
        $studentAccountId = 'SELECT a.id FROM gc_user a WHERE a.instance = ? AND mail = (' . $studentAccountId . ')';
        $studentAccountId = (int) $go1->fetchColumn($studentAccountId, [$portalName, $studentId]);
        if (!$studentAccountId) {
            return false;
        }

        return EdgeHelper::hasLink($go1, EdgeTypes::HAS_MANAGER, $studentAccountId, $managerUserId);
    }

    /**
     * @deprecated Use UserDomainHelper::isManager($portalName, $managerPortalAccountLegacyId, $portalAccountLegacyId)
     */
    public static function isManagerUser(Connection $go1, int $managerAccountId, string $portalName): bool
    {
        if (!$roleId = UserHelper::roleId($go1, Roles::MANAGER, $portalName)) {
            return false;
        }

        $managerRoles = array_filter($account->roles ?? [], fn(PortalAccountRole $role) => in_array($role->name, ['ASSESSOR', 'MANAGER']));

        return count($managerRoles) ? true : false;
    }

    /**
     * @deprecated Use UserDomainHelper::managerUserIds($learnerAccountId)
     */
    public static function userManagerIds(Connection $go1, int $accountId): array
    {
        $sql = 'SELECT ro.target_id FROM gc_ro ro ';
        $sql .= 'WHERE ro.source_id = ? AND ro.type = ?';

        return $go1
            ->executeQuery($sql, [$accountId, EdgeTypes::HAS_MANAGER])
            ->fetchAll(PDO::FETCH_COLUMN);
    }
}
