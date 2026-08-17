<?php
/**
 * ResponseHelper - Chuẩn hóa JSON response cho AJAX
 */
class ResponseHelper
{
    public static function json(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(string $message = 'Thành công', $data = null, array $extra = []): void
    {
        $res = ['success' => true, 'message' => $message];
        if ($data !== null) $res['data'] = $data;
        if (!empty($extra)) $res = array_merge($res, $extra);
        self::json($res);
    }

    public static function error(string $message = 'Có lỗi xảy ra', int $httpCode = 400, array $extra = []): void
    {
        $res = ['success' => false, 'message' => $message];
        if (!empty($extra)) $res = array_merge($res, $extra);
        self::json($res, $httpCode);
    }

    public static function paged(array $data, int $currentPage, int $pageSize, int $totalRecords, string $message = 'OK'): void
    {
        $totalPages = $pageSize > 0 ? (int)ceil($totalRecords / $pageSize) : 0;
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'currentPage' => $currentPage,
                'pageSize' => $pageSize,
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages,
            ],
        ]);
    }
}