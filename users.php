<?php
 
class UserInfoBuilder
{
    /**
     * @var Db
     */
    private $dbProvider;
 
    /**
     * @var Memcached
     */
    private $memcachedProvider;
 
    /**
     * @var DIContainer
     */
    protected $diContainer;
 
    /**
     * @param DIContainer $dIContainer
     */
    public function __construct(DIContainer $dIContainer)
    {
        $this->diContainer = $dIContainer;
    }
 
    const CACHE_KEY = 'UserInfoBuilderMemcachedKey::';
 
    /**
     * @param $userId
     * @return string
     */
    public static final function buildMemcacheKey($userId): string
    {
        return static::CACHE_KEY.$userId->getValue();
    }
 
    /**
     * @param UserId $userId
     */
    public function findUserInfo(UserId $userId): array
    {
        try {
            $result = $this->memcachedProvider->get(self::buildMemcacheKey($userId));
 
            if (empty($result)) {
                $rows = $this->dbProvider->execute("
                SELECT * FROM UserInfo
                WHERE id = {$userId->getValue()};
            ");
            $result = array_pop($rows);
 
            $this->memcachedProvider->set(self::buildMemcacheKey(), $result);
            }
        } catch (\Exception $exception) {
            $this->diContainer->get('Logger')->logError($exception, $this);
        }
 
        return $result;
    }
 
    /**
     * @param array $userIds
     */
    public function findUserInfos(array $userIds): array
    {
        $result = [];
        foreach ($userIds as $userId) {
            $result[] = $this->findUserInfo($userId);
        }
 
        return $result;
    }
 
    /**
     * @param array $userData
     * @return int
     */
    public function countUserVisits(array $userData): int
    {
        $result = 0;
         
        foreach ($userData as $data) {
            $result+=$data['viewCount'];
        }
         
        return $result;
    }
 
    /**
     * @param Db $db
     */
    public function setDbProvider(Db $db)
    {
        $this->dbProvider = $db;
    }
 
    /**
     * @param Memcached $memcached
     */
    public function setMemcachedProvider(Memcached $memcached)
    {
        $this->memcachedProvider = $memcached;
    }
}
