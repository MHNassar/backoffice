<?php

namespace App\DB;

use PDO;
use Psr\Cache\CacheException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;


class DbGenie implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var array
     */
    private $masterConnection;

    /**
     * @var array
     */
    private $memoryCache = [];

    /**
     * @var int
     */
    private $queryCount = 0;

    /**
     * @var int
     */
    private $memoryCacheHitCount = 0;

    /**
     * @var int
     */
    private $cacheHitCount = 0;

    /**
     * @var int
     */
    private $queryTime = 0;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var CacheInterface
     */
    private $cachePool;

    /**
     * DbGenie constructor.
     */
    public function __construct(
        array $connections,
        LoggerInterface $logger,
        CacheInterface $dbResultPool
    )
    {
        $this->cachePool = $dbResultPool;
        $this->setLogger($logger);
        $this->masterConnection = array_shift($connections);
        $randomConnection = count($connections) > 0 ? $connections[array_rand($connections)] : $this->masterConnection;
        $this->pdo = new PDO($randomConnection['dsn'], $randomConnection['username'], $randomConnection['password']);
        $this->pdo->exec('set names utf8');
        $this->debug = $randomConnection['debug'] ?? false;

        if ($this->debug) {
            $this->logger->debug('Using pdo connection', ['dns' => $randomConnection['dsn']]);
        }
    }

    public function doFetchAllColumn(string $query, array $parameters = [], bool $cache = false, int $ttl = 0): array
    {
        $startTime = microtime(true);

        $hashKey = $this->getHashKey($query, $parameters, 'doFetchAllColumn');

        //In memory cache hit
        if (array_key_exists($hashKey, $this->memoryCache)) {
            ++$this->memoryCacheHitCount;
            $this->updateQueryTime($startTime);

            return $this->memoryCache[$hashKey];
        }

        //Cache pool caching
        if ($cache) {
            $this->handleCachePool($startTime, $hashKey, $query, $parameters, $ttl, PDO::FETCH_COLUMN);

            return $this->memoryCache[$hashKey];
        }

        $this->memoryCache[$hashKey] = $this->runFetchAll($query, $parameters, PDO::FETCH_COLUMN);
        $this->updateQueryTime($startTime);

        return $this->memoryCache[$hashKey];
    }

    private function getHashKey(string $query, array $parameters, string $queryMode): string
    {
        return md5($query . implode(',', $parameters) . $queryMode);
    }

    private function updateQueryTime(float $startTime)
    {
        if ($this->debug) {
            $endTime = microtime(true);
            $this->queryTime += ($endTime - $startTime);
        }
    }

    private function handleCachePool(int $startTime, string $hashKey, string $query, array $parameters, int $ttl, int $fetchStyle)
    {
        try {
            $result = $this->cachePool->get($hashKey, function (ItemInterface $item) use ($query, $parameters, $ttl, $fetchStyle) {
                $item->expiresAfter($ttl);

                return $this->runFetchAll($query, $parameters, $fetchStyle);
            });
        } catch (CacheException $e) {
            $result = $this->runFetchAll($query, $parameters, $fetchStyle);
        }

        $this->memoryCache[$hashKey] = $result;
        $this->updateQueryTime($startTime);
    }

    private function runFetchAll(string $query, array $parameters, int $fetchStyle): array
    {
        ++$this->queryCount;
        $statement = $this->pdo->prepare($query);
        $statement->execute($parameters);
        if ($this->debug) {
            $this->logger->debug('Executed DB query', [
                'query' => $statement->queryString,
            ]);
        }

        return $statement->fetchAll($fetchStyle);
    }

    public function doFetchAllAssoc(string $query, array $parameters = [], bool $cache = false, int $ttl = 0): array
    {
        $startTime = microtime(true);

        $hashKey = $this->getHashKey($query, $parameters, 'doFetchAllAssoc');

        //In memory cache hit
        if (array_key_exists($hashKey, $this->memoryCache)) {
            ++$this->memoryCacheHitCount;
            $this->updateQueryTime($startTime);

            return $this->memoryCache[$hashKey];
        }

        //Cache pool caching
        if ($cache) {
            $this->handleCachePool($startTime, $hashKey, $query, $parameters, $ttl, PDO::FETCH_ASSOC);

            return $this->memoryCache[$hashKey];
        }

        $this->memoryCache[$hashKey] = $this->runFetchAll($query, $parameters, PDO::FETCH_ASSOC);
        $this->updateQueryTime($startTime);

        return $this->memoryCache[$hashKey];
    }

    /**
     * On Kernel response event.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->debug && $event->isMasterRequest()) {
            $event->getResponse()->headers->set('X-Debug-Query-Count', $this->queryCount);
            $event->getResponse()->headers->set('X-Debug-Memory-Cache-Hit-Count', $this->memoryCacheHitCount);
            $event->getResponse()->headers->set('X-Debug-Query-Time', $this->queryTime);

            $this->logger->debug('DB query stats', [
                'Query-Count' => $this->queryCount,
                'Memory-Cache-Hit-Count' => $this->memoryCacheHitCount,
                'Query-Time' => $this->queryTime,
            ]);
        }
    }
}
