<?php

class Session
{
    /**
     * Start the session
     * 
     * @return void
     */

    public static function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
            $params = session_get_cookie_params();

            session_set_cookie_params([
                'lifetime' => (int) ($params['lifetime'] ?? 0),
                'path' => (string) ($params['path'] ?? '/'),
                'domain' => (string) ($params['domain'] ?? ''),
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
        }

        self::ensureCsrfToken();
    }

    /**
     * Set a session key/value pair
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value by the key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */

    public static function get($key, $default = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * Checks to see if session keys exist
     * 
     * @param string $key
     * @return bool 
     */

    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Clear session by key
     * 
     * @param string $key
     * @return void
     */

    public static function clear($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clear all session data
     * 
     * @return void
     * 
     */

    public static function clearAll()
    {
        session_unset();
        session_destroy();
    }

    /**
     * Regenerate active session id
     *
     * @return void
     */
    public static function regenerate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Ensure CSRF token exists
     *
     * @return string
     */
    public static function ensureCsrfToken()
    {
        $token = (string) self::get('_csrf_token', '');
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            self::set('_csrf_token', $token);
        }

        return $token;
    }

    /**
     * Return current CSRF token
     *
     * @return string
     */
    public static function csrfToken()
    {
        return self::ensureCsrfToken();
    }

    /**
     * Verify incoming CSRF token
     *
     * @param string|null $token
     * @return bool
     */
    public static function verifyCsrfToken($token)
    {
        $incoming = trim((string) $token);
        $stored = (string) self::get('_csrf_token', '');

        if ($incoming === '' || $stored === '') {
            return false;
        }

        return hash_equals($stored, $incoming);
    }

    /**
     * Set a flash message
     * 
     * @param string $key
     * @param mixed $message
     * @return bool
     */

    public static function setFlashMesssge($key, $message)
    {
        self::set('flash_' . $key, $message);
    }

    /**
     * Set a flash message
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */

    public static function getFlashMesssge($key, $default = null)
    {
        $message = self::get('flash_' . $key, $default);
        self::clear('flash_' . $key);

        return $message;
    }
}
