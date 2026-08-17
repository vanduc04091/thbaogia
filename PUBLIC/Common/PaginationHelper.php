<?php
/**
 * PaginationHelper - Tính offset, limit, tổng trang
 */
class PaginationHelper
{
    public static function normalize(int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(500, $pageSize));
        $offset = ($page - 1) * $pageSize;
        return [$page, $pageSize, $offset];
    }

    public static function totalPages(int $totalRecords, int $pageSize): int
    {
        if ($pageSize <= 0) return 0;
        return (int)ceil($totalRecords / $pageSize);
    }
}