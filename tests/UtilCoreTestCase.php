<?php

namespace go1\util\tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use go1\clients\MqClient;
use go1\core\util\client\federation_api\v1\Marshaller;
use go1\core\util\client\federation_api\v1\schema\object\PortalAccount;
use go1\core\util\client\federation_api\v1\schema\object\User;
use go1\core\util\client\UserDomainHelper;
use go1\util\DateTime;
use go1\util\DB;
use go1\util\edge\EdgeTypes;
use go1\util\schema\AwardSchema;
use go1\util\schema\CollectionSchema;
use go1\util\schema\InstallTrait;
use go1\util\schema\mock\UserMockTrait;
use go1\util\UtilCoreServiceProvider;
use PDO;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Log\LoggerInterface;

class UtilCoreTestCase extends TestCase
{
    use InstallTrait;
    use UserMockTrait;
    use QueueMockTrait;

    /** @var  Connection */
    protected $go1;
    protected $log;

    /** @var MqClient */
    protected $queue;
    protected $queueMessages = [];

    protected $schemaClasses = [
        AwardSchema::class,
        CollectionSchema::class,
    ];

    public function setUp(): void
    {
        $this->go1 = DriverManager::getConnection(['url' => 'sqlite://sqlite::memory:']);
        $this->installGo1Schema($this->go1, false, 'accounts.test');

        DB::install($this->go1, [
            function (Schema $schema) {
                $this->setupDatabaseSchema($schema);
            },
        ]);

        $this->queue = $this
            ->getMockBuilder(MqClient::class)
            ->setMethods(['publish', 'queue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this
            ->queue
            ->expects($this->any())
            ->method('publish')
            ->willReturnCallback(
                $callback = function ($payload, $subject, $context = null) {
                    if ($context && is_array($payload)) {
                        $payload['_context'] = $context;
                    }

                    $this->queueMessages[$subject][] = $payload;
                }
            );

        $this
            ->queue
            ->expects($this->any())
            ->method('queue')
            ->willReturnCallback($callback);
    }

    protected function setupDatabaseSchema(Schema $schema)
    {
        foreach ($this->schemaClasses as $schemaClass) {
            call_user_func([$schemaClass, 'install'], $schema);
        }
    }

    protected function getContainer(bool $rebuild = false): Container
    {
        static $container;

        if (null === $container || $rebuild) {
            $container = new Container(['accounts_name' => 'accounts.test']);
            $container->register(new UtilCoreServiceProvider, [
                'logger' => function () {
                    $logger = $this
                        ->getMockBuilder(LoggerInterface::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['error'])
                        ->getMockForAbstractClass();

                    $logger
                        ->expects($this->any())
                        ->method('error')
                        ->willReturnCallback(function ($message) {
                            $this->log['error'][] = $message;
                        });

                    return $logger;
                },
            ]);

            $this->setupContainer($container);
        }

        return $container;
    }

    public function setupContainer(Container &$c)
    {
        $c['go1.client.user-domain-helper'] = function () use ($c) {
            $helper = $this
                ->getMockBuilder(UserDomainHelper::class)
                ->disableOriginalConstructor()
                ->setMethods(['loadUser', 'loadMultipleUsers', 'loadPortalAccount'])
                ->getMock();

            $helper
                ->method('loadMultipleUsers')
                ->willReturnCallback(
                    function (array $userIds) use ($c) {
                        $legacyUsers = $this
                            ->go1
                            ->executeQuery('SELECT * FROM gc_user WHERE id IN (?)', [$userIds], [DB::INTEGERS])
                            ->fetchAll(PDO::FETCH_OBJ);

                        return array_map(
                            function ($legacyUser) {
                                return (new Marshaller)->parse(
                                    (object) [
                                        'id'        => md5($legacyUser->uuid),
                                        'legacyId'  => $legacyUser->id,
                                        'profileId' => $legacyUser->profile_id,
                                        'email'     => $legacyUser->mail,
                                        'firstName' => $legacyUser->first_name,
                                        'lastName'  => $legacyUser->last_name,
                                        'status'    => $legacyUser->status ? 'ACTIVE' : 'INACTIVE',
                                    ],
                                    new User
                                );
                            },
                            $legacyUsers,
                        );
                    }
                );

            $helper
                ->method('loadPortalAccount')
                ->willReturnCallback(
                    function (int $id, string $portalName) {
                        $legacyAccount = !$portalName ? null : $this
                            ->go1
                            ->executeQuery('SELECT * FROM gc_accounts WHERE instance = ? AND id = ?', [$portalName, $id])
                            ->fetch(PDO::FETCH_OBJ);

                        return !$legacyAccount ? null : (new Marshaller)->parse(
                            (object) [
                                'id'        => md5($legacyAccount->uuid),
                                'legacyId'  => $legacyAccount->id,
                                'profileId' => $legacyAccount->profile_id,
                                'email'     => $legacyAccount->mail,
                                'firstName' => $legacyAccount->first_name,
                                'lastName'  => $legacyAccount->last_name,
                                'status'    => $legacyAccount->status ? 'ACTIVE' : 'INACTIVE',
                                'createdAt' => DateTime::formatDate($legacyAccount->created),
                                'roles'     => array_map(
                                    function ($edge) use ($legacyAccount) {
                                        $row = $this
                                            ->go1
                                            ->executeQuery('SELECT name FROM gc_role WHERE id = ?', [$edge->target_id])
                                            ->fetch(PDO::FETCH_OBJ);

                                        return (object) [
                                            'legacyId' => $row->id,
                                            'name'     => $row->name === 'manager' ? 'MANAGER' : $row->name,
                                        ];
                                    },
                                    $this
                                        ->go1
                                        ->executeQuery(
                                            'SELECT * FROM gc_ro WHERE type = ? AND source_id = ?',
                                            [EdgeTypes::HAS_ROLE, $legacyAccount->id]
                                        )
                                        ->fetchAll(DB::OBJ)
                                ),
                            ],
                            new PortalAccount,
                        );
                    }
                );

            $helper
                ->method('loadUser')
                ->willReturnCallback(
                    function (int $userId, ?string $portalName) use ($c) {
                        $legacyUser = $this
                            ->go1
                            ->executeQuery('SELECT * FROM gc_user WHERE id = ?', [$userId])
                            ->fetch(PDO::FETCH_OBJ);

                        if (!$legacyUser) {
                            return null;
                        }

                        $legacyAccount = !$portalName ? null : $this
                            ->go1
                            ->executeQuery('SELECT * FROM gc_accounts WHERE instance = ? AND mail = ?', [$portalName, $legacyUser->mail])
                            ->fetch(PDO::FETCH_OBJ);

                        return (new Marshaller)->parse(
                            (object) [
                                'id'        => md5($legacyUser->uuid),
                                'legacyId'  => $legacyUser->id,
                                'profileId' => $legacyUser->profile_id,
                                'email'     => $legacyUser->mail,
                                'firstName' => $legacyUser->first_name,
                                'lastName'  => $legacyUser->last_name,
                                'status'    => $legacyUser->status ? 'ACTIVE' : 'INACTIVE',
                                'account'   => !$legacyAccount ? null : (object) [
                                    'id'        => md5($legacyAccount->uuid),
                                    'legacyId'  => $legacyAccount->id,
                                    'profileId' => $legacyAccount->profile_id,
                                    'email'     => $legacyAccount->mail,
                                    'firstName' => $legacyAccount->first_name,
                                    'lastName'  => $legacyAccount->last_name,
                                    'status'    => $legacyAccount->status ? 'ACTIVE' : 'INACTIVE',
                                    'createdAt' => DateTime::formatDate($legacyAccount->created),
                                ],
                            ],
                            new User
                        );
                    }
                );

            return $helper;
        };
    }
}
