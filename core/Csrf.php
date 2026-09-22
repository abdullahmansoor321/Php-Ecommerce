<?php

/**
 * CSRF (Cross-Site Request Forgery) protection.
 *
 * How it works:
 * - Csrf::token()  creates a random secret ONCE per session and stores it
 *                  server-side in $_SESSION['csrf_token'].
 * - Csrf::field()  returns a hidden <input> to drop inside every admin form,
 *                  so each legit form "stamps" the secret into its request.
 * - Csrf::verify() compares the submitted value against the session copy.
 *
 * Why it stops attacks:
 * An evil website CAN trick the browser into sending a request to us along
 * with the session cookie (browsers auto-attach cookies). But it CANNOT read
 * our pages' HTML (same-origin policy), so it can never learn the token value
 * to put into its forged request. No token -> request rejected.
 */
class Csrf
{
    /** Lifetime of one token in seconds (rotated on expiry for hygiene). */
    private const TOKEN_LIFETIME = 7200; // 2 hours

    /**
     * Get the session's CSRF token, creating it on first use.
     */
    public static function token(): string
    {
        Session::start();

        $expired = !isset($_SESSION['csrf_token_time'])
            || (time() - (int)$_SESSION['csrf_token_time']) > self::TOKEN_LIFETIME;

        if (!isset($_SESSION['csrf_token']) || $expired) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Hidden input HTML to include in every state-changing form.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Verify a submitted token against the session copy.
     * Uses hash_equals() for timing-safe comparison.
     */
    public static function verify(?string $submitted): bool
    {
        Session::start();

        if (!is_string($submitted) || $submitted === '') {
            return false;
        }
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $submitted);
    }

    /**
     * Force a fresh token (call after a successful sensitive action).
     */
    public static function regenerate(): void
    {
        Session::start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
}
