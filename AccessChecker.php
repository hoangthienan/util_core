<?php

namespace go1\util;

use Doctrine\DBAL\Connection;
use go1\util\award\AwardHelper;
use go1\util\edge\EdgeHelper;
use go1\util\edge\EdgeTypes;
use go1\util\enrolment\EnrolmentHelper;
use go1\util\lo\LoChecker;
use go1\util\portal\PortalHelper;
use go1\util\user\Roles;
use PDO;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

class AccessChecker
{
    const ACCESS_PUBLIC        = 0;
    const ACCESS_AUTHENTICATED = 100;
    const ACCESS_ADMIN         = 200;
    const ACCESS_ROOT          = 300;
    const ACCESS_OWNER         = 400;

    public function isContentAdministrator(Request $req, $instance, bool $inheritance = true)
    {
        if ($inheritance && $this->isPortalAdmin($req, $instance, Roles::ADMIN, $inheritance)) {
            return true;
        }

        return $this->isPortalAdmin($req, $instance, Roles::ADMIN_CONTENT, $inheritance);
    }

    /**
     * @param Request $req
     * @param string  $portalIdOrName
     * @return null|bool|stdClass
     */
    public function isPortalAdmin(Request $req, $portalIdOrName, $role = Roles::ADMIN, bool $inheritance = true)
    {
        if (!$user = $this->validUser($req)) {
            return null;
        }

        if ($inheritance && $this->isAccountsAdmin($req)) {
            return 1;
        }

        $accounts = isset($user->accounts) ? $user->accounts : [];
        foreach ($accounts as &$account) {
            $actual = is_numeric($portalIdOrName) ? $account->portal_id : $account->instance;
            if ($portalIdOrName === $actual) {
                if (!empty($account->roles) && in_array($role, $account->roles)) {
                    return $account;
                }
            }
        }

        return false;
    }

    public function isPortalTutor(Request $req, $portalIdOrName, $role = Roles::TUTOR, bool $strict = true)
    {
        if ($strict && $this->isPortalAdmin($req, $portalIdOrName)) {
            return 1;
        }

        if (!$user = $this->validUser($req)) {
            return null;
        }

        $accounts = isset($user->accounts) ? $user->accounts : [];
        foreach ($accounts as &$account) {
            $actual = is_numeric($portalIdOrName) ? $account->portal_id : $account->instance;
            if ($portalIdOrName == $actual) {
                if (!empty($account->roles) && in_array($role, $account->roles)) {
                    return $account;
                }
            }
        }

        return false;
    }

    public function isPortalManager(Request $req, $portalIdOrName, bool $strict = true)
    {
        return $this->isPortalTutor($req, $portalIdOrName, Roles::MANAGER, $strict);
    }

    public function isAccountsAdmin(Request $req)
    {
        if (!$user = $this->validUser($req)) {
            return null;
        }

        return $this->hasAccountsAdminRole($user) ? $user : false;
    }

    public function hasAccountsAdminRole($user)
    {
        return in_array(Roles::ROOT, isset($user->roles) ? $user->roles : []);
    }

    public function validAccount(Request $req, $portalIdOrName)
    {
        $payload = $req->attributes->get('jwt.payload');
        if ($payload && !empty($payload->object->type) && ('user' === $payload->object->type)) {
            $user = &$payload->object->content;
            $user = !empty($user->mail) ? $user : null;
        }

        if (!empty($user)) {
            $accounts = isset($user->accounts) ? $user->accounts : [];
            foreach ($accounts as $account) {
                $match = $portalIdOrName == (is_numeric($portalIdOrName) ? ($account->portal_id ?? 0) : $account->instance);
                if ($match) {
                    return $account;
                }
            }
        }

        return false;
    }

    public function jwt(Request $req): ?string
    {
        return $req->attributes->get('jwt.raw');
    }

    public function sessionToken(Request $req): ?string
    {
        $payload = $req->attributes->get('jwt.payload');

        return $payload->sid ?? null;
    }

    public function usedCredentials(Request $req = null): bool
    {
        $payload = $req->attributes->get('jwt.payload');

        return $payload->usedCreds ?? false;
    }

    public function validUser(Request $req, $portalName = null)
    {
        $payload = $req->attributes->get('jwt.payload');
        if ($payload && !empty($payload->object->type) && ('user' === $payload->object->type)) {
            $user = &$payload->object->content;
            $user = !empty($user->mail) ? $user : null;
        }

        if (!empty($user)) {
            if (!$portalName || empty($user->instance) || ($user->instance == $portalName)) {
                return $user;
            }

            $accounts = isset($user->accounts) ? $user->accounts : [];
            foreach ($accounts as $account) {
                if ($portalName == $account->instance) {
                    return $account;
                }
            }
        }

        return false;
    }

    public function isOwner(Request $req, $profileId, $property = 'profile_id')
    {
        if (!$user = $this->validUser($req)) {
            return false;
        }

        return isset($user->{$property}) && ($user->{$property} == $profileId);
    }

    public function hasAccount(Request $req, string $portalName)
    {
        if (!$user = $this->validUser($req)) {
            return false;
        }

        if ($this->isPortalTutor($req, $portalName)) {
            return true;
        }

        $accounts = isset($user->accounts) ? $user->accounts : [];
        foreach ($accounts as &$account) {
            if ($portalName === $account->instance) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated Use UserDomainHelper::isManager($portalName, $managerPortalAccountLegacyId, $portalAccountLegacyId)
     */
    public function isStudentManager(Connection $db, Request $req, string $studentMail, string $portalName)
    {
        if (!$user = $this->validUser($req)) {
            return false;
        }
        
        return $db->fetchColumn(
            'SELECT 1 FROM gc_ro'
            . ' WHERE type = ?'
            . '   AND source_id = (SELECT id FROM gc_user WHERE instance = ? AND mail = ?)'
            . '   AND target_id = ?',
            [EdgeTypes::HAS_MANAGER, $portalName, $studentMail, $user->id],
            0,
            [PDO::PARAM_INT, PDO::PARAM_STR, PDO::PARAM_STR, PDO::PARAM_INT]
        ) ? true : false;
    }

    public function accessLevel(Request $req, $instance = null)
    {
        if ($this->isAccountsAdmin($req)) {
            return static::ACCESS_ROOT;
        }

        if ($instance && $this->isPortalAdmin($req, $instance)) {
            return static::ACCESS_ADMIN;
        }

        if (!$user = $this->validUser($req)) {
            return static::ACCESS_PUBLIC;
        }

        // THE LOGIC is not yet stable, only applying for #ECK for now
        if (($entityType = $req->attributes->get('entityType')) && ($entityId = $req->attributes->get('entityId'))) {
            if (0 === strpos($entityType, 'user')) {
                if ($user->id == $entityId) {
                    return static::ACCESS_OWNER;
                }
            }

            if (0 === strpos($entityType, 'account')) {
                $account = $instance ? $this->validUser($req, $instance) : false;
                if ($account && $account->id == $entityId) {
                    return static::ACCESS_OWNER;
                }
            }
        }

        return static::ACCESS_AUTHENTICATED;
    }

    public function isMasquerading(Request $req)
    {
        $payload = $req->attributes->get('jwt.payload');

        return ($payload && !empty($payload->object->content->masquerading)) ? true : false;
    }

    public static function isAssessor(
        Connection $db,
        int $courseId,
        int $assessorId,
        int $studentProfileId = null,
        Request $req = null): bool
    {
        $checker = new self;

        if ($checker->isAccountsAdmin($req)) {
            return true;
        }

        if ((new LoChecker)->isAuthor($db, $courseId, $assessorId)) {
            return true;
        }

        // Is portal admin
        $instance = PortalHelper::titleFromLoId($db, $courseId);
        if ($instance && $checker->isPortalAdmin($req, $instance)) {
            return true;
        }

        if ($studentProfileId) {
            if ($enrolmentId = EnrolmentHelper::enrolmentId($db, $courseId, $studentProfileId)) {
                if (EdgeHelper::hasLink($db, EdgeTypes::HAS_TUTOR_ENROLMENT_EDGE, $assessorId, $enrolmentId)) {
                    return true;
                }
            }
        }

        return EdgeHelper::hasLink($db, EdgeTypes::COURSE_ASSESSOR, $courseId, $assessorId);
    }

    public function isAwardAssessor(
        Connection $go1,
        Connection $awardDb,
        int $awardId,
        int $assessorId,
        bool $checkParent = true,
        Request $req = null
    ): bool {
        if ($req && $this->isAccountsAdmin($req)) {
            return true;
        }

        $award = AwardHelper::load($awardDb, $awardId);
        if ($req && $award) {
            $instance = PortalHelper::load($go1, $award->instance_id);
            if ($instance && $this->isPortalAdmin($req, $instance->title)) {
                return true;
            }
        }

        if (EdgeHelper::hasLink($go1, EdgeTypes::AWARD_ASSESSOR, $awardId, $assessorId)) {
            return true;
        }

        if ($checkParent) {
            $currentChildAwardIds = [$awardId];
            while ($awardParentIds = AwardHelper::awardParentIds($awardDb, $currentChildAwardIds)) {
                foreach ($awardParentIds as $awardParentId) {
                    if (EdgeHelper::hasLink($go1, EdgeTypes::AWARD_ASSESSOR, $awardParentId, $assessorId)) {
                        return true;
                    }
                }
                $currentChildAwardIds = $awardParentIds;
            }
        }

        return false;
    }

    public static function isRequestVerified(Request $req, ?array $scope): bool
    {
        # `X-ACCESS` is forwarded from up stream API services, we allow to access the endpoint as 1Player previewToken,
        if (!$req->headers->has('X-ACCESS') || $req->headers->get('X-ACCESS') !== 'verified') {
            return false;
        }

        $payload = $req->attributes->get('jwt.payload');
        $currentScope = $payload->scope ?? null;
        if ($currentScope == json_decode(json_encode($scope, JSON_FORCE_OBJECT))) {
            return true;
        }

        return false;
    }
}
