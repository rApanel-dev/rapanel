<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeExternalUrl implements ValidationRule
{
    // Rangos de IP que nunca deben ser alcanzables desde el servidor
    private const BLOCKED_CIDRS = [
        ['10.0.0.0',      8],   // RFC 1918 — red privada
        ['172.16.0.0',   12],   // RFC 1918 — red privada
        ['192.168.0.0',  16],   // RFC 1918 — red privada
        ['127.0.0.0',     8],   // Loopback
        ['169.254.0.0',  16],   // Link-local / AWS metadata (169.254.169.254)
        ['0.0.0.0',       8],   // Red "this"
        ['100.64.0.0',   10],   // Shared address space (RFC 6598)
        ['192.0.0.0',    24],   // IETF protocol assignments
        ['198.18.0.0',   15],   // Benchmark testing
        ['240.0.0.0',     4],   // Reservado
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $host = parse_url($value, PHP_URL_HOST);

        if (! $host) {
            $fail(__('validation.url'))->translate();
            return;
        }

        // Quitar corchetes de IPv6 (ej: [::1])
        $host = trim($host, '[]');

        // Si ya es una IP válida, usarla directamente; si no, resolver el hostname
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ip = $host;
        } else {
            $ip = gethostbyname($host);

            // gethostbyname devuelve el input sin cambios si no puede resolver
            if ($ip === $host) {
                $fail(__('The :attribute hostname could not be resolved.', ['attribute' => $attribute]));
                return;
            }
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $fail(__('The :attribute resolved to an invalid IP address.', ['attribute' => $attribute]));
            return;
        }

        // Bloquear IPv6 privadas / loopback
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($this->isBlockedIPv6($ip)) {
                $fail(__('The :attribute must not point to an internal or reserved network address.', ['attribute' => $attribute]));
            }
            return;
        }

        // Bloquear rangos IPv4 privados
        foreach (self::BLOCKED_CIDRS as [$network, $prefix]) {
            if ($this->ipInCidr($ip, $network, $prefix)) {
                $fail(__('The :attribute must not point to an internal or reserved network address.', ['attribute' => $attribute]));
                return;
            }
        }
    }

    private function isBlockedIPv6(string $ip): bool
    {
        // ::1 loopback, fc00::/7 ULA, fe80::/10 link-local
        if ($ip === '::1') return true;

        $packed = inet_pton($ip);
        if ($packed === false) return true;

        $firstByte  = ord($packed[0]);
        $firstTwo   = ($firstByte << 8) | ord($packed[1]);

        $isULA       = ($firstByte & 0xFE) === 0xFC;           // fc00::/7
        $isLinkLocal = ($firstTwo & 0xFFC0) === 0xFE80;        // fe80::/10

        return $isULA || $isLinkLocal;
    }

    private function ipInCidr(string $ip, string $network, int $prefix): bool
    {
        $ipLong  = ip2long($ip);
        $netLong = ip2long($network);

        if ($ipLong === false || $netLong === false) {
            return false;
        }

        $mask = $prefix === 0 ? 0 : (~0 << (32 - $prefix));

        return ($ipLong & $mask) === ($netLong & $mask);
    }
}
