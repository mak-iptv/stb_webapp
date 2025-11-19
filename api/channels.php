} catch (Exception $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'message' => 'Gabim në marrjen e kanaleve nga provideri',
        'error' => $e->getMessage(),
        'data' => [
            'channels' => [],
            'total_count' => 0,
            'categories' => [],
            'is_fallback' => false,
            'error' => true
        ]
    ]);
}
