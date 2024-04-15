<?php

namespace Framework\Kernel\Http\Responses;

use Framework\Kernel\Http\Requests\Contracts\RequestInterface;
use Framework\Kernel\Http\Responses\Contracts\ResponseInterface;
use Framework\Kernel\Http\Responses\Headers\ResponseHeaderBag;

class BaseResponse implements ResponseInterface
{
    public static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',                                           // RFC-ietf-httpbis-semantics
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Content',                                       // RFC-ietf-httpbis-semantics
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Too Early',                                                   // RFC-ietf-httpbis-replay-04
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',                                     // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

    public ?ResponseHeaderBag $headers = null;

    protected ?string $statusText = null;

    protected ?string $charset = null;

    protected string $version = '1.0';

    protected int $statusCode = 200;

    protected string $content = '';

    private array $sentHeaders = [];

    public function setStatusCode(int $code, ?string $text = null): static
    {
        $this->statusCode = $code;
        if ($this->isInvalid()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
        }

        if ($text === null) {
            $this->statusText = self::$statusTexts[$code] ?? 'unknown status';

            return $this;
        }

        $this->statusText = $text;

        return $this;
    }

    public function sendHeaders(/* int $statusCode = null */): static
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        $statusCode = \func_num_args() > 0 ? func_get_arg(0) : null;
        $informationalResponse = $statusCode >= 100 && $statusCode < 200;
        if ($informationalResponse && !\function_exists('headers_send')) {
            // skip informational responses if not supported by the SAPI
            return $this;
        }

        // headers
        foreach ($this->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            $newValues = $values;
            $replace = false;

            // As recommended by RFC 8297, PHP automatically copies headers from previous 103 responses, we need to deal with that if headers changed
            if ($statusCode === 103) {
                $previousValues = $this->sentHeaders[$name] ?? null;
                if ($previousValues === $values) {
                    // Header already sent in a previous response, it will be automatically copied in this response by PHP
                    continue;
                }

                $replace = strcasecmp($name, 'Content-Type') === 0;

                if ($previousValues !== null && array_diff($previousValues, $values)) {
                    header_remove($name);
                    $previousValues = null;
                }

                $newValues = $previousValues === null ? $values : array_diff($values, $previousValues);
            }

            foreach ($newValues as $value) {
                header($name . ': ' . $value, $replace, $this->statusCode);
            }

            if ($informationalResponse) {
                $this->sentHeaders[$name] = $values;
            }
        }

        // cookies
        foreach ($this->headers->getCookies() as $cookie) {
            header('Set-Cookie: ' . $cookie, false, $this->statusCode);
        }

        if ($informationalResponse) {
            headers_send($statusCode);

            return $this;
        }

        $statusCode ??= $this->statusCode;

        // status
        header(sprintf('HTTP/%s %s %s', $this->version, $statusCode, $this->statusText), true, $statusCode);

        return $this;
    }

    public function sendContent(): static
    {
        echo $this->content;

        return $this;
    }

    public function send(): static
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    public function prepare(RequestInterface $request): static
    {
        $headers = $this->headers;

        if ($this->isInformational() || $this->isEmpty()) {
            $this->setContent(null);
            $headers->remove('Content-Type');
            $headers->remove('Content-Length');

            ini_set('default_mimetype', '');
        } else {

            if (!$headers->has('Content-Type')) {

                $format = $request->getRequestFormat(null);

                if ($format !== null && $mimeType = $request->getMimeType($format)) {
                    $headers->set('Content-Type', $mimeType);
                }
            }

            // Fix Content-Type
            $charset = $this->charset ?: 'UTF-8';
            if (!$headers->has('Content-Type')) {
                $headers->set('Content-Type', 'text/html; charset=' . $charset);
            } elseif (stripos($headers->get('Content-Type') ?? '', 'text/') === 0 && stripos($headers->get('Content-Type') ?? '', 'charset') === false) {
                // add the charset
                $headers->set('Content-Type', $headers->get('Content-Type') . '; charset=' . $charset);
            }

            // Fix Content-Length
            if ($headers->has('Transfer-Encoding')) {
                $headers->remove('Content-Length');
            }
        }

        if ($request->server->get('SERVER_PROTOCOL') != 'HTTP/1.0') {
            $this->setProtocolVersion('1.1');
        }

        if ($this->getProtocolVersion() == '1.0' && str_contains($headers->get('Cache-Control', ''), 'no-cache')) {
            $headers->set('pragma', 'no-cache');
            $headers->set('expires', -1);
        }

        $this->ensureIEOverSSLCompatibility($request);

        return $this;
    }

    public function setProtocolVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    protected function ensureIEOverSSLCompatibility(RequestInterface $request): void
    {
        if (stripos($this->headers->get('Content-Disposition') ?? '', 'attachment') !== false && preg_match('/MSIE (.*?);/i', $request->server->get('HTTP_USER_AGENT') ?? '', $match) == 1 && $request->isSecure() === true) {
            if ((int)preg_replace('/(MSIE )(.*?);/', '$2', $match[0]) < 9) {
                $this->headers->remove('Cache-Control');
            }
        }
    }

    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    public function isInvalid(): bool
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }

    public function isEmpty(): bool
    {
        return in_array($this->statusCode, [204, 304]);
    }

    public function setContent(?string $content): static
    {

        $this->content = $content ?? '';

        return $this;
    }

    public function getContent(): false|string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
