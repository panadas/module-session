<?php
namespace Panadas\SessionModule;

use Panadas\EventModule\Event;
use Panadas\EventModule\Publisher;
use Panadas\SessionModule\DataStructure\SessionParams;

class Session extends Publisher
{

    private $handler;
    private $params;
    private $flash;

    const KEY_NAMESPACE = "_panadas";
    const KEY_FLASH     = "_flash";

    public function __construct(\SessionHandlerInterface $handler = null, SessionParams $params = null)
    {
        parent::__construct();

        if ($this->isDisabled()) {
            throw new \RuntimeException("Sessions are not enabled on this server");
        }

        if ($this->isOpen()) {
            throw new \RuntimeException("Session is already open, ensure session.auto_start is disabled");
        }

        // Prevent session-fixation
        // See: http://en.wikipedia.org/wiki/Session_fixation
        ini_set("session.session.use_only_cookies", 1);

        // Use the SHA-1 hashing algorithm
        ini_set("session.hash_function", 1);

        // Increase character-range of the session ID to help prevent brute-force attacks
        ini_set("session.hash_bits_per_character", 6);

        if (null === $handler) {
            $handler = new \SessionHandler();
        }

        if (null === $params) {
            $params = new SessionParams();
        }

        register_shutdown_function(function () {
            if ($this->isOpen()) {
                $this->close();
            }
        });

        $this
            ->setHandler($handler)
            ->setParams($params);
    }

    public function getHandler()
    {
        return $this->handler;
    }

    protected function setHandler(\SessionHandlerInterface $handler)
    {
        $this->handler = $handler;

        session_set_save_handler($handler);

        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    protected function setParams(SessionParams $params)
    {
        $this->params = $params;

        return $this;
    }

    public function getFlash()
    {
        return $this->flash;
    }

    public function hasFlash()
    {
        return (null !== $this->getFlash());
    }

    protected function setFlash(array $flash = null)
    {
        $this->flash = $flash;

        return $this;
    }

    protected function removeFlash()
    {
        return $this->setFlash(null);
    }

    public function getStatus()
    {
        return session_status();
    }

    public function isDisabled()
    {
        return (PHP_SESSION_DISABLED === $this->getStatus());
    }

    public function isOpen()
    {
        return (PHP_SESSION_ACTIVE === $this->getStatus());
    }

    public function getName()
    {
        return session_name();
    }

    protected function setName($name)
    {
        if ($this->isOpen()) {
            throw new \RuntimeException("Session is already open");
        }

        session_name($name);

        return $this;
    }

    public function getId()
    {
        if (!$this->isOpen()) {
            throw new \RuntimeException("Session is not open");
        }

        return session_id();
    }

    public function setId($id)
    {
        if (!$this->isOpen()) {
            throw new \RuntimeException("Session is not open");
        }

        session_id($id);

        return $this;
    }

    public function regenerateId()
    {
        if (!$this->isOpen()) {
            throw new \RuntimeException("Session is not open");
        }

        session_regenerate_id(true);

        return $this;
    }

    public function getLifetime()
    {
        return ini_get("session.gc_maxlifetime");
    }

    protected function setLifetime($lifetime)
    {
        if ($this->isOpen()) {
            throw new \RuntimeException("Session is already open");
        }

        ini_set("session.gc_maxlifetime", $lifetime);

        return $this;
    }

    public function getCacheLimiter()
    {
        return session_cache_limiter();
    }

    public function setCacheLimiter($cacheLimiter)
    {
        if ($this->isOpen()) {
            throw new \RuntimeException("Session is already open");
        }

        session_cache_limiter($cacheLimiter);

        return $this;
    }

    public function getCacheExpire()
    {
        return session_cache_expire();
    }

    public function setCacheExpire($cacheExpire)
    {
        if ($this->isOpen()) {
            throw new \RuntimeException("Session is already open");
        }

        session_cache_expire($cacheExpire);

        return $this;
    }

    public function getCookieParams()
    {
        return session_get_cookie_params();
    }

    public function setCookieParams($lifetime, $path, $domain, $secure, $httpOnly)
    {
        if ($this->isOpen()) {
            throw new \RuntimeException("Session is already open");
        }

        session_set_cookie_params($lifetime, $path, $domain, $secure, $httpOnly);

        return $this;
    }

    protected function setCookieParamsArray(array $cookieParams)
    {
        return $this->setCookieParams(
            $cookieParams["lifetime"],
            $cookieParams["path"],
            $cookieParams["domain"],
            $cookieParams["secure"],
            $cookieParams["httponly"]
        );
    }

    public function getCookiePath()
    {
        return $this->getCookieParams()["path"];
    }

    public function hasCookiePath()
    {
        return (null !== $this->getCookiePath());
    }

    public function setCookiePath($cookiePath)
    {
        return $this->setCookieParamsArray(["path" => $cookiePath] + $this->getCookieParams());
    }

    public function removeCookiePath()
    {
        return $this->setCookiePath(null);
    }

    public function getCookieDomain()
    {
        return $this->getCookieParams()["domain"];
    }

    public function hasCookieDomain()
    {
        return (null !== $this->getCookieDomain());
    }

    public function setCookieDomain($cookieDomain)
    {
        return $this->setCookieParamsArray(["domain" => $cookieDomain] + $this->getCookieParams());
    }

    public function removeCookieDomain()
    {
        return $this->setCookieDomain(null);
    }

    public function isCookieSecure()
    {
        return (bool) $this->getCookieParams()["secure"];
    }

    public function setCookieSecure($cookieSecure)
    {
        return $this->setCookieParamsArray(["secure" => (bool) $cookieSecure] + $this->getCookieParams());
    }

    public function isCookieHttpOnly()
    {
        return (bool) $this->getCookieParams()["httponly"];
    }

    public function setCookieHttpOnly($cookieHttpOnly)
    {
        return $this->setCookieParamsArray(["httponly" => (bool) $cookieHttpOnly] + $this->getCookieParams());
    }

    public function open()
    {
        if ($this->isOpen()) {
            throw new \RuntimeException("Session is already open");
        }

        $this->publish("open", function (Event $event) {

            $eventParams = $event->getParams();

            if ($eventParams->has("id")) {
                $this->setId($eventParams->get("id"));
            }

            session_start();

            if (!array_key_exists(static::KEY_NAMESPACE, $_SESSION)) {
                $_SESSION[static::KEY_NAMESPACE] = [];
            }

            $this->getParams()->bind($_SESSION[static::KEY_NAMESPACE]);

            $this->updateFlash();

        });

        return $this;
    }

    public function destroy()
    {
        if (!$this->isOpen()) {
            throw new \RuntimeException("Session is not open");
        }

        $this->publish("destroy", function (Event $event) {

            session_destroy();

            $this->getParams()->clear();

        });

        return $this;
    }

    public function restart()
    {
        $this
            ->destroy()
            ->open()
            ->regenerateId();

        return $this;
    }

    public function close()
    {
        if (!$this->isOpen()) {
            throw new \RuntimeException("Session is not open");
        }

        $this->publish("close", "session_write_close");

        return $this;
    }

    protected function updateFlash()
    {
        $params = $this->getParams();

        if ($params->has(static::KEY_FLASH)) {
            $this->setFlash($params->get(static::KEY_FLASH));
            $params->remove(static::KEY_FLASH);
        } else {
            $this->removeFlash();
        }

        return $this;
    }

    public function getFlashType()
    {
        if (!$this->hasFlash()) {
            return null;
        }

        return $this->getFlash()["type"];
    }

    public function getFlashMessage()
    {
        if (!$this->hasFlash()) {
            return null;
        }

        return $this->getFlash()["message"];
    }

    public function setNextFlash($message, $type = null)
    {
        $this->getParams()->set(static::KEY_FLASH, [
            "message" => $message,
            "type" => $type
        ]);

        return $this;
    }
}
