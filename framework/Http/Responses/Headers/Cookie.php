<?php

namespace Framework\Kernel\Http\Responses\Headers;

class Cookie
{
    public const SAMESITE_NONE = 'none';
    public const SAMESITE_LAX = 'lax';
    public const SAMESITE_STRICT = 'strict';

    protected string $path;

    protected int $expire;

    protected ?string $sameSite = null;

    private bool $secureDefault = false;

    private const RESERVED_CHARS_LIST = "=,; \t\r\n\v\f";
    private const RESERVED_CHARS_FROM = ['=', ',', ';', ' ', "\t", "\r", "\n", "\v", "\f"];
    private const RESERVED_CHARS_TO = ['%3D', '%2C', '%3B', '%20', '%09', '%0D', '%0A', '%0B', '%0C'];


    public function __construct(protected string $name, protected ?string $value = null, int|string|\DateTimeInterface $expire = 0, ?string $path = '/', protected ?string $domain = null, protected ?bool $secure = null, protected bool $httpOnly = true, protected bool $raw = false, ?string $sameSite = self::SAMESITE_LAX, protected bool $partitioned = false)
    {
        // from PHP source code
        if ($raw && false !== strpbrk($name, self::RESERVED_CHARS_LIST)) {
            throw new \InvalidArgumentException(sprintf('The cookie name "%s" contains invalid characters.', $name));
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('The cookie name cannot be empty.');
        }

        $this->expire = self::expiresTimestamp($expire);
        $this->path = empty($path) ? '/' : $path;
        $this->sameSite = $this->withSameSite($sameSite)->sameSite;
    }

    private static function expiresTimestamp(int|string|\DateTimeInterface $expire = 0): int
    {
        if ($expire instanceof \DateTimeInterface) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);

            if (false === $expire) {
                throw new \InvalidArgumentException('The cookie expiration time is not valid.');
            }
        }

        return 0 < $expire ? (int)$expire : 0;
    }

    public function withSameSite(?string $sameSite): static
    {
        if ('' === $sameSite) {
            $sameSite = null;
        } elseif (null !== $sameSite) {
            $sameSite = strtolower($sameSite);
        }

        if (!\in_array($sameSite, [self::SAMESITE_LAX, self::SAMESITE_STRICT, self::SAMESITE_NONE, null], true)) {
            throw new \InvalidArgumentException('The "sameSite" parameter value is not valid.');
        }

        $cookie = clone $this;
        $cookie->sameSite = $sameSite;

        return $cookie;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRaw(): bool
    {
        return $this->raw;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getExpiresTime(): int
    {
        return $this->expire;
    }

    public function getMaxAge(): int
    {
        $maxAge = $this->expire - time();

        return 0 >= $maxAge ? 0 : $maxAge;
    }

    public function isSecure(): bool
    {
        return $this->secure ?? $this->secureDefault;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function isPartitioned(): bool
    {
        return $this->partitioned;
    }

    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }

    public function __toString(): string
    {
        if ($this->isRaw()) {
            $str = $this->getName();
        } else {
            $str = str_replace(self::RESERVED_CHARS_FROM, self::RESERVED_CHARS_TO, $this->getName());
        }

        $str .= '=';

        if ('' === (string) $this->getValue()) {
            $str .= 'deleted; expires='.gmdate('D, d M Y H:i:s T', time() - 31536001).'; Max-Age=0';
        } else {
            $str .= $this->isRaw() ? $this->getValue() : rawurlencode($this->getValue());

            if (0 !== $this->getExpiresTime()) {
                $str .= '; expires='.gmdate('D, d M Y H:i:s T', $this->getExpiresTime()).'; Max-Age='.$this->getMaxAge();
            }
        }

        if ($this->getPath()) {
            $str .= '; path='.$this->getPath();
        }

        if ($this->getDomain()) {
            $str .= '; domain='.$this->getDomain();
        }

        if ($this->isSecure()) {
            $str .= '; secure';
        }

        if ($this->isHttpOnly()) {
            $str .= '; httponly';
        }

        if (null !== $this->getSameSite()) {
            $str .= '; samesite='.$this->getSameSite();
        }

        if ($this->isPartitioned()) {
            $str .= '; partitioned';
        }

        return $str;
    }
}