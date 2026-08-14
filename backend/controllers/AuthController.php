<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../utils/response.php';

class AuthController {

    public static function register(): void {
        $in = json_decode(file_get_contents('php://input'), true) ?? [];

        $name     = trim($in['name']     ?? '');
        $email    = trim($in['email']    ?? '');
        $phone    = trim($in['phone']    ?? '');
        $password =       $in['password'] ?? '';
        $role     = trim($in['role']     ?? 'customer');

        if ($name === '' || $email === '' || $password === '') {
            send_error(422, 'Name, email and password are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            send_error(422, 'Invalid email address.');
        }
        if (strlen($password) < 6) {
            send_error(422, 'Password must be at least 6 characters.');
        }
        if (!in_array($role, ['customer', 'salon_admin'], true)) {
            send_error(422, 'Invalid role. Designer accounts are created by salon admins.');
        }
        if (User::email_exists($email)) {
            send_error(409, 'Email already exists.');
        }

        $id = User::create([
            'name' => $name, 'email' => $email,
            'phone' => $phone, 'password' => $password,
            'role' => $role,
        ]);

        $user = User::find($id);
        login_user($user);
        send_created('Registration successful.', ['user' => $user]);
    }

    public static function login(): void {
        $in = json_decode(file_get_contents('php://input'), true) ?? [];
        $email    = trim($in['email']    ?? '');
        $password =       $in['password'] ?? '';

        if ($email === '' || $password === '') {
            send_error(422, 'Email and password are required.');
        }

        $u = User::find_by_email($email);
        if (!$u || !password_verify($password, $u['password'])) {
            send_error(401, 'Invalid login credentials.');
        }

        login_user($u);
        send_ok('Login successful.', ['user' => $u]);
    }

    public static function logout(): void {
        logout_user();
        send_ok('Logged out.');
    }

    public static function me(): void {
        $u = require_login();
        send_ok('OK', ['user' => $u]);
    }
}
