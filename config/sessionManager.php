<?php

namespace SessionManager;

class SessionManager {
    private int $expireTime;
    private bool $justExpired = false;

    public function __construct(int $expireTime = 1800) {
        $this->expireTime = $expireTime;
    }

    public function start(): void {
        if (session_status() === PHP_SESSION_NONE) {

            // Secure session settings (must be before session_start)
            ini_set('session.use_strict_mode', 1);

            session_set_cookie_params([
                'lifetime' => $this->expireTime,
                'path' => '/',
                'domain' => '', // Change your domain if needed
                'secure' => isset($_SERVER['HTTPS']), // true if HTTPS
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            session_start();
        }

        // Initializing session metadata if not set
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
            $_SESSION['last_regeneration'] = time();
            $_SESSION['expire'] = time() + $this->expireTime;
        }
		if (isset($_SESSION['expire']) && time() > $_SESSION['expire']) {
			$this->justExpired = true;
			$this->destroy();
			
			// Start a fresh, empty session so the rest of this request
            // (and the app's login check) sees a logged-out state.
			session_start();
			$_SESSION['created'] = time();
			$_SESSION['last_regeneration'] = time();
			$_SESSION['expire'] = time() + $this->expireTime;
		}
		
        // Sliding expiration (refresh on activity)
        $_SESSION['expire'] = time() + $this->expireTime;

        // Regenerating ID every 5 minutes (configurable)
        $this->maybeRegenerateId(300);
    }

    private function maybeRegenerateId(int $interval): void {
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
            return;
        }

        if (time() - $_SESSION['last_regeneration'] >= $interval) {
            session_regenerate_id(false);
            $_SESSION['last_regeneration'] = time();
        }
    }

    public function set(string $key, $value): void {
        $this->ensureSession();
        $_SESSION[$key] = $value;
    }

    public function get(string $key) {
        $this->ensureSession();
        return $_SESSION[$key] ?? null;
    }

    public function isExpired(): bool {
        $this->ensureSession();
        return $this->justExpired;
    }

    public function remove(string $key): void {
        $this->ensureSession();
        unset($_SESSION[$key]);
    }

    public function destroy(): void {
        $this->ensureSession();

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    private function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $this->start();
        }
    }

    // OPTIONAL: Bind session to IP + User Agent (anti-hijacking)
    public function bindToClient(): void {
        $this->ensureSession();

        $fingerprint = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = $fingerprint;
        } elseif ($_SESSION['fingerprint'] !== $fingerprint) {
            // Possible hijack attempt
            $this->destroy();
        }
    }
}

?>