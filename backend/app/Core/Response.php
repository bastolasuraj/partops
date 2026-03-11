<?php
namespace App\Core;

class Response
{
    public static function json($data, int $statusCode = 200): void
    {
        AuditLogger::captureResponse($statusCode, is_array($data) ? $data : []);
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function success($data, string $message = 'Success', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
    
    public static function error(string $message, int $statusCode = 400, $errors = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::json($response, $statusCode);
    }
    
    public static function created($data, string $message = 'Created successfully'): void
    {
        self::success($data, $message, 201);
    }
    
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }
}
