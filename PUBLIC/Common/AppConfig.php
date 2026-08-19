<?php
/**
 * AppConfig - Cấu hình ứng dụng
 * Database, Memcached, Session, Upload paths
 */
class AppConfig
{
    // === Database Config ===
    const DB_HOST = 'localhost';
    const DB_PORT = 3306;
    const DB_NAME = 'th_bao_gia';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';

    // === Memcached Config ===
    const MEMCACHED_ENABLED = false; // Bật/tắt cache (false khi dev)
    const MEMCACHED_HOST = '127.0.0.1';
    const MEMCACHED_PORT = 11211;
    const MEMCACHED_PREFIX = 'ql_user_';

    // === App Config ===
    const APP_NAME = 'Tổng Hợp Báo Giá';
    const APP_VERSION = '3.0.0';
    //const APP_URL = 'http://thbg.bv';
    const APP_URL = 'https://thbg.bvnghean.vn';
    const APP_TIMEZONE = 'Asia/Ho_Chi_Minh';
    const APP_DEBUG = true;

    // === Session ===
    const SESSION_NAME = 'THBG_SESSION1';
    const SESSION_LIFETIME = 7200; // 2 giờ

    // === Upload ===
    const UPLOAD_PATH = __DIR__ . '/../../assets/uploads/';
    const UPLOAD_URL = '/assets/uploads/';
    const UPLOAD_MAX_SIZE = 10485760; // 10MB
    const UPLOAD_ALLOWED_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'mp4'];

    // === Security ===
    const CSRF_TOKEN_NAME = '_csrf_token';
    const PASSWORD_ALGO = PASSWORD_BCRYPT;
    const PASSWORD_COST = 10;

    // === Pagination ===
    const DEFAULT_PAGE_SIZE = 20;
    const PAGE_SIZE_OPTIONS = [10, 20, 50, 100];

    /**
     * Lấy base URL của ứng dụng
     */
    public static function baseUrl(string $path = ''): string
    {
        return rtrim(self::APP_URL, '/') . '/' . ltrim($path, '/');
    }
}