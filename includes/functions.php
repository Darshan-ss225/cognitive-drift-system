<?php

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
        exit;
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('get_flash')) {
    function get_flash(): ?array
    {
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }

        return null;
    }
}

if (!function_exists('generate_token')) {
    function generate_token(int $length = 32): string
    {
        return bin2hex(random_bytes((int)($length / 2)));
    }
}

if (!function_exists('generate_otp')) {
    function generate_otp(int $length = 6): string
    {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= random_int(0, 9);
        }
        return $otp;
    }
}

if (!function_exists('log_system_action')) {
    function log_system_action(PDO $pdo, ?int $adminId, string $action): void
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (admin_id, action, created_at)
                VALUES (:admin_id, :action, NOW())
            ");

            $stmt->execute([
                'admin_id' => $adminId,
                'action' => $action
            ]);
        } catch (Throwable $e) {
            // silent fail
        }
    }
}