<?php

/**
 * Project:       Hymie PHP MVC framework
 * File:          Psr6Adapter.php
 * Created Date:  2019-08-11.
 *
 * Github:        https://github.com/mahaixing/hymie-mvc
 * Gitee:         https://gitee.com/mahaixing/hymie-mvc
 * Composer:      https://packagist.org/packages/hymie/mvc
 *
 * @author:       mahaixing(mahaixing@gmail.com)
 * @license:      MIT
 */

namespace hymie\cache;

use \Psr\Cache\CacheException as PsrCacheException;
use \Psr\Cache\CacheItemPoolInterface;
use \Psr\SimpleCache\CacheException as SimpleCacheException;
use \Psr\SimpleCache\CacheInterface;

/**
 * 适配 psr-6 接口到 psr-16接口。
 *
 * 代码实现主要参考: Symfony\Component\Cache\Psr16Cache
 *
 * @author mahaixing <mahaixing@gmail.com>
 */
class Psr6Adapter implements CacheInterface
{
    /**
     * Psr\Cache\CacheItemPoolInterface instance.
     *
     * @var Psr\Cache\CacheItemPoolInterfac
     */
    private $pool;

    public static function adapt($target)
    {
        if ($target instanceof CacheItemPoolInterface) {
            return new \hymie\cache\Psr6Adapter($target);
        } elseif ($target instanceof CacheInterface) {
            return $target;
        } else {
            throw new CacheException(
                sprintf(
                    "%s: need 'CacheItemPoolInterface' or 'CacheInterface', '%s' given",
                    self::class,
                    get_type($target)
                )
            );
        }
    }

    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        try {
            $item = $this->pool->getItem($key);
        } catch (SimpleCacheException $e) {
            throw new CacheException($e);
        } catch (PsrCacheException $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        return $item->isHit() ? $item->get() : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        try {
            $item = $this->pool->getItem($key)->set($value);
        } catch (SimpleCacheException $e) {
            throw new CacheException($e);
        } catch (PsrCacheException $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }
        if (null !== $ttl) {
            $item->expiresAfter($ttl);
        }

        return $this->pool->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
            return $this->pool->deleteItem($key);
        } catch (SimpleCacheException $e) {
            throw new CacheException($e);
        } catch (PsrCacheException $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->pool->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!\is_array($keys)) {
            throw new \InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given', \is_object($keys) ? \get_class($keys) : \gettype($keys)));
        }
        try {
            $items = $this->pool->getItems($keys);
        } catch (SimpleCacheException $e) {
            throw new CacheException($e);
        } catch (PsrCacheException $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }
        $values = [];
        foreach ($items as $key => $item) {
            if (!$item->isHit()) {
                $values[$key] = $default;
                continue;
            }
            $values[$key] = $item->get();
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new \InvalidArgumentException(sprintf('Cache values must be array or Traversable, "%s" given', \is_object($values) ? \get_class($values) : \gettype($values)));
        }
        $ok = true;
        $items = [];
        try {
            foreach ($values as $key => $value) {
                if (\is_int($key)) {
                    $key = (string) $key;
                }
                $items[$key] = $this->pool->getItem($key)->set($value);
                if (null !== $ttl) {
                    $items[$key]->expiresAfter($ttl);
                }

                $ok = $this->pool->saveDeferred($items[$key]) && $ok;
            }
        } catch (SimpleCacheException $e) {
            throw new CacheException($e);
        } catch (PsrCacheException $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->pool->commit() && $ok;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!\is_array($keys)) {
            throw new \InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given', \is_object($keys) ? \get_class($keys) : \gettype($keys)));
        }
        try {
            return $this->pool->deleteItems($keys);
        } catch (SimpleCacheException $e) {
            throw new CacheException($e);
        } catch (PsrCacheException $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        try {
            return $this->pool->hasItem($key);
        } catch (SimpleCacheException $e) {
            throw new CacheException($e);
        } catch (PsrCacheException $e) {
            throw new CacheException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
