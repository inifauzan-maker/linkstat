<?php

namespace App\Support;

use App\Contracts\CustomDomainVerifier;
use App\Models\LandingPage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class NetworkCustomDomainVerifier implements CustomDomainVerifier
{
    public function sync(LandingPage $landingPage): LandingPage
    {
        if (! $landingPage->hasCustomDomain()) {
            return $this->clear($landingPage);
        }

        $domain = (string) $landingPage->custom_domain;
        $dns = $this->inspectDns($domain);
        $ssl = $this->inspectSsl($domain);

        $landingPage->forceFill([
            'custom_domain_dns_status' => $dns['status'],
            'custom_domain_dns_target' => $dns['target'],
            'custom_domain_dns_checked_at' => $dns['checked_at'],
            'custom_domain_dns_message' => $dns['message'],
            'custom_domain_ssl_status' => $ssl['status'],
            'custom_domain_ssl_issuer' => $ssl['issuer'],
            'custom_domain_ssl_expires_at' => $ssl['expires_at'],
            'custom_domain_ssl_checked_at' => $ssl['checked_at'],
            'custom_domain_ssl_message' => $ssl['message'],
            'custom_domain_connected_at' => $dns['status'] === 'verified'
                ? ($landingPage->custom_domain_connected_at ?? now())
                : null,
        ])->save();

        return $landingPage->refresh();
    }

    public function clear(LandingPage $landingPage): LandingPage
    {
        $landingPage->forceFill([
            'custom_domain_dns_status' => null,
            'custom_domain_dns_target' => null,
            'custom_domain_dns_checked_at' => null,
            'custom_domain_dns_message' => null,
            'custom_domain_ssl_status' => null,
            'custom_domain_ssl_issuer' => null,
            'custom_domain_ssl_expires_at' => null,
            'custom_domain_ssl_checked_at' => null,
            'custom_domain_ssl_message' => null,
            'custom_domain_connected_at' => null,
        ])->save();

        return $landingPage->refresh();
    }

    protected function inspectDns(string $domain): array
    {
        $checkedAt = now();
        $records = $this->dnsRecords($domain);
        $targetHost = LandingPage::normalizeCustomDomain(config('landing.custom_domain_target'));

        if ($records->isEmpty()) {
            return [
                'status' => 'missing',
                'target' => null,
                'checked_at' => $checkedAt,
                'message' => 'DNS record untuk custom domain belum terdeteksi.',
            ];
        }

        $cnameTargets = $records
            ->where('type', 'CNAME')
            ->pluck('target')
            ->map(fn ($value) => LandingPage::normalizeCustomDomain($value))
            ->filter()
            ->values();

        $ipTargets = $records
            ->filter(fn (array $record) => in_array($record['type'] ?? null, ['A', 'AAAA'], true))
            ->map(function (array $record) {
                return $record['ip'] ?? $record['ipv6'] ?? null;
            })
            ->filter()
            ->unique()
            ->values();

        if ($targetHost === null) {
            return [
                'status' => 'verified',
                'target' => $cnameTargets->first() ?? $ipTargets->implode(', '),
                'checked_at' => $checkedAt,
                'message' => 'DNS custom domain berhasil terdeteksi.',
            ];
        }

        if ($cnameTargets->contains($targetHost)) {
            return [
                'status' => 'verified',
                'target' => $targetHost,
                'checked_at' => $checkedAt,
                'message' => 'CNAME domain sudah mengarah ke target aplikasi.',
            ];
        }

        $expectedIpTargets = $this->dnsRecords($targetHost)
            ->filter(fn (array $record) => in_array($record['type'] ?? null, ['A', 'AAAA'], true))
            ->map(fn (array $record) => $record['ip'] ?? $record['ipv6'] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($expectedIpTargets->isNotEmpty() && $ipTargets->intersect($expectedIpTargets)->isNotEmpty()) {
            return [
                'status' => 'verified',
                'target' => $ipTargets->intersect($expectedIpTargets)->implode(', '),
                'checked_at' => $checkedAt,
                'message' => 'Record A/AAAA domain sudah cocok dengan target aplikasi.',
            ];
        }

        return [
            'status' => 'mismatch',
            'target' => $cnameTargets->first() ?? $ipTargets->implode(', '),
            'checked_at' => $checkedAt,
            'message' => 'DNS domain sudah ada, tetapi belum mengarah ke target aplikasi.',
        ];
    }

    protected function inspectSsl(string $domain): array
    {
        $checkedAt = now();

        if (! function_exists('stream_socket_client') || ! function_exists('openssl_x509_parse')) {
            return [
                'status' => 'unavailable',
                'issuer' => null,
                'expires_at' => null,
                'checked_at' => $checkedAt,
                'message' => 'Pemeriksaan SSL tidak tersedia di server ini.',
            ];
        }

        $timeout = max(2, (int) config('landing.custom_domain_verify_timeout', 6));
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'SNI_enabled' => true,
                'peer_name' => $domain,
            ],
        ]);

        $client = @stream_socket_client(
            'ssl://'.$domain.':443',
            $errorNumber,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (! is_resource($client)) {
            return [
                'status' => 'unreachable',
                'issuer' => null,
                'expires_at' => null,
                'checked_at' => $checkedAt,
                'message' => $errorMessage !== '' ? $errorMessage : 'Tidak dapat membuka koneksi SSL ke domain.',
            ];
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;

        if ($certificate === null) {
            return [
                'status' => 'unavailable',
                'issuer' => null,
                'expires_at' => null,
                'checked_at' => $checkedAt,
                'message' => 'Sertifikat SSL tidak ditemukan pada domain.',
            ];
        }

        $parsed = openssl_x509_parse($certificate);

        if (! is_array($parsed)) {
            return [
                'status' => 'unavailable',
                'issuer' => null,
                'expires_at' => null,
                'checked_at' => $checkedAt,
                'message' => 'Sertifikat SSL tidak dapat dibaca.',
            ];
        }

        $issuer = $parsed['issuer']['CN']
            ?? $parsed['issuer']['O']
            ?? $parsed['issuer']['OU']
            ?? null;

        $expiresAt = isset($parsed['validTo_time_t'])
            ? Carbon::createFromTimestamp((int) $parsed['validTo_time_t'])
            : null;

        $status = $expiresAt instanceof Carbon && $expiresAt->isFuture()
            ? 'valid'
            : 'expired';

        return [
            'status' => $status,
            'issuer' => $issuer,
            'expires_at' => $expiresAt,
            'checked_at' => $checkedAt,
            'message' => $status === 'valid'
                ? 'Sertifikat SSL aktif dan dapat digunakan.'
                : 'Sertifikat SSL sudah kedaluwarsa atau belum valid.',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function dnsRecords(string $host): Collection
    {
        if (! function_exists('dns_get_record')) {
            return collect();
        }

        $typeMask = 0;

        foreach ([DNS_CNAME, DNS_A, DNS_AAAA] as $type) {
            $typeMask |= $type;
        }

        $records = @dns_get_record($host, $typeMask);

        return collect(is_array($records) ? $records : []);
    }
}
