<?php
/**
 * MemcachedHelper - Quản lý cache
 * Nếu Memcached không khả dụng → bypass (trả null khi get, không lỗi khi set)
 */
class MemcachedHelper
{
    private static $instance = null;
    private static bool $available = false;
    private static bool $initialized = false;
    private static array $keyRegistry = []; // Theo dõi keys đã set (fallback cho deleteByPrefix)

    private static function init(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;

        if (!AppConfig::MEMCACHED_ENABLED || !class_exists('Memcached')) {
            self::$available = false;
            return;
        }
        try {
            $mc = new Memcached();
            $mc->addServer(AppConfig::MEMCACHED_HOST, AppConfig::MEMCACHED_PORT);
            // Test kết nối bằng cách set 1 key
            $mc->set('__ping__', 1, 5);
            self::$instance = $mc;
            self::$available = true;
        } catch (Throwable $e) {
            self::$available = false;
        }
    }

    private static function key(string $key): string
    {
        return AppConfig::MEMCACHED_PREFIX . $key;
    }

    public static function get(string $key)
    {
        self::init();
        if (!self::$available) return null;
        $v = self::$instance->get(self::key($key));
        return $v === false ? null : $v;
    }

    public static function set(string $key, $value, int $ttl = 300): bool
    {
        self::init();
        if (!self::$available) return false;
        self::$keyRegistry[$key] = true;
        return self::$instance->set(self::key($key), $value, $ttl);
    }

    public static function delete(string $key): bool
    {
        self::init();
        if (!self::$available) return false;
        unset(self::$keyRegistry[$key]);
        return self::$instance->delete(self::key($key));
    }

    /**
     * Xóa tất cả key bắt đầu bằng prefix.
     * Memcached không hỗ trợ native → dùng registry để track.
     * Để đơn giản + an toàn, flush registry keys trùng prefix.
     */
    public static function deleteByPrefix(string $prefix): void
    {
        self::init();
        if (!self::$available) return;
        foreach (array_keys(self::$keyRegistry) as $k) {
            if (strpos($k, $prefix) === 0) {
                self::$instance->delete(self::key($k));
                unset(self::$keyRegistry[$k]);
            }
        }
    }

    public static function flush(): void
    {
        self::init();
        if (!self::$available) return;
        self::$instance->flush();
        self::$keyRegistry = [];
    }
}