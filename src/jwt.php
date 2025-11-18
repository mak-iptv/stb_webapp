<?php
namespace App;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Jwt {
    public static function generate($payload) {
        $secret = getenv('JWT_SECRET');
        return JWT::encode($payload, $secret, 'HS256');
    }

    public static function validate($token) {
        $secret = getenv('JWT_SECRET');
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}
