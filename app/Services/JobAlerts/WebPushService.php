<?php

namespace App\Services\JobAlerts;

use App\Models\JobNotification;
use App\Models\JobPushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WebPushService
{
    private const array ENDPOINT_HOSTS = ['fcm.googleapis.com', 'push.services.mozilla.com', 'web.push.apple.com', 'wns.windows.com'];

    public function publicKey(): ?string
    {
        return config('services.job_alerts.vapid_public_key') ?: null;
    }

    /** @param array{endpoint: string, keys: array{p256dh: string, auth: string}} $subscription */
    public function saveSubscription(array $subscription, ?int $userId): JobPushSubscription
    {
        abort_unless($this->isAllowedEndpoint($subscription['endpoint']), 422, 'This push service endpoint is not supported.');
        $endpoint = $subscription['endpoint'];

        return JobPushSubscription::query()->updateOrCreate(['endpoint_hash' => hash('sha256', $endpoint)], [
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'public_key' => $subscription['keys']['p256dh'],
            'auth_token' => $subscription['keys']['auth'],
            'content_encoding' => 'aes128gcm',
        ]);
    }

    public function deleteSubscription(string $endpoint, ?int $userId): void
    {
        $query = JobPushSubscription::query()->where('endpoint_hash', hash('sha256', $endpoint));
        if ($userId !== null) {
            $query->where(function ($builder) use ($userId): void {
                $builder->where('user_id', $userId)->orWhereNull('user_id');
            });
        } else {
            $query->whereNull('user_id');
        }
        $query->delete();
    }

    public function send(JobNotification $notification): void
    {
        if (! $this->publicKey() || ! config('services.job_alerts.vapid_private_key')) {
            return;
        }

        foreach (JobPushSubscription::query()->cursor() as $subscription) {
            try {
                $this->sendToSubscription($subscription, $notification);
                $subscription->forceFill(['last_notified_at' => now()])->save();
            } catch (Throwable $exception) {
                Log::warning('Government job web push failed.', ['subscription_id' => $subscription->id, 'message' => $exception->getMessage()]);
                if ($exception->getCode() === 404 || $exception->getCode() === 410) {
                    $subscription->delete();
                }
            }
        }
    }

    private function sendToSubscription(JobPushSubscription $subscription, JobNotification $notification): void
    {
        abort_unless($this->isAllowedEndpoint($subscription->endpoint), 422, 'Unsupported push endpoint.');
        $subscriptionPublicKey = $this->decodeBase64Url($subscription->public_key);
        $authSecret = $this->decodeBase64Url($subscription->auth_token);
        abort_unless(strlen($subscriptionPublicKey) === 65 && $subscriptionPublicKey[0] === "\x04" && strlen($authSecret) >= 16, 422, 'Invalid push subscription keys.');

        $peerKey = openssl_pkey_get_public($this->subscriptionPublicKeyPem($subscriptionPublicKey));
        $serverKey = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        abort_unless($peerKey !== false && $serverKey !== false, 500, 'OpenSSL could not create a push key.');
        $serverDetails = openssl_pkey_get_details($serverKey);
        $serverPublicKey = "\x04".$this->padCoordinate($serverDetails['ec']['x']).$this->padCoordinate($serverDetails['ec']['y']);
        $sharedSecret = openssl_pkey_derive($peerKey, $serverKey, 32);
        abort_unless(is_string($sharedSecret), 500, 'OpenSSL could not derive the push secret.');

        $keyInfo = "WebPush: info\0".$subscriptionPublicKey.$serverPublicKey;
        $inputKeyMaterial = $this->hkdfExpand($this->hkdfExtract($authSecret, $sharedSecret), $keyInfo, 32);
        $salt = random_bytes(16);
        $contentKey = $this->hkdfExpand($this->hkdfExtract($salt, $inputKeyMaterial), "Content-Encoding: aes128gcm\0", 16);
        $nonce = $this->hkdfExpand($this->hkdfExtract($salt, $inputKeyMaterial), "Content-Encoding: nonce\0", 12);
        $payload = json_encode(['title' => $notification->title, 'body' => $notification->summary ?: 'A new official government notice is available.', 'url' => route('job-notices.show', ['year' => $notification->created_at->format('Y'), 'slug' => $notification->slug])], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\x02";
        $tag = '';
        $ciphertext = openssl_encrypt($payload, 'aes-128-gcm', $contentKey, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        abort_unless(is_string($ciphertext), 500, 'OpenSSL could not encrypt the push payload.');

        $body = $salt.pack('N', 4096).chr(strlen($serverPublicKey)).$serverPublicKey.$ciphertext.$tag;
        $authorization = $this->vapidAuthorization($subscription->endpoint);
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            'Content-Encoding' => 'aes128gcm',
            'Content-Type' => 'application/octet-stream',
            'TTL' => '86400',
        ])->withBody($body, 'application/octet-stream')->timeout(10)->send('POST', $subscription->endpoint);

        if (in_array($response->status(), [404, 410], true)) {
            throw new \RuntimeException('Push subscription is no longer active.', $response->status());
        }
        $response->throw();
    }

    private function vapidAuthorization(string $endpoint): string
    {
        $privateKey = openssl_pkey_get_private(config('services.job_alerts.vapid_private_key'));
        abort_unless($privateKey !== false, 500, 'Invalid VAPID private key.');
        $url = parse_url($endpoint);
        $audience = $url['scheme'].'://'.$url['host'].(isset($url['port']) ? ':'.$url['port'] : '');
        $now = now()->timestamp;
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode(['aud' => $audience, 'exp' => $now + 43200, 'sub' => config('services.job_alerts.vapid_subject')], JSON_THROW_ON_ERROR));
        $message = $header.'.'.$claims;
        openssl_sign($message, $derSignature, $privateKey, OPENSSL_ALGO_SHA256);

        return 'vapid t='.$message.'.'.$this->base64UrlEncode($this->derSignatureToRaw($derSignature)).', k='.$this->publicKey();
    }

    private function isAllowedEndpoint(string $endpoint): bool
    {
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));
        if (parse_url($endpoint, PHP_URL_SCHEME) !== 'https' || $host === '') {
            return false;
        }

        foreach (self::ENDPOINT_HOSTS as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.'.$allowedHost)) {
                return true;
            }
        }

        return false;
    }

    private function subscriptionPublicKeyPem(string $publicKey): string
    {
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$publicKey;

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private function hkdfExtract(string $salt, string $inputKeyMaterial): string
    {
        return hash_hmac('sha256', $inputKeyMaterial, $salt, true);
    }

    private function hkdfExpand(string $pseudoRandomKey, string $info, int $length): string
    {
        $output = '';
        $previousBlock = '';
        for ($counter = 1; strlen($output) < $length; $counter++) {
            $previousBlock = hash_hmac('sha256', $previousBlock.$info.chr($counter), $pseudoRandomKey, true);
            $output .= $previousBlock;
        }

        return substr($output, 0, $length);
    }

    private function decodeBase64Url(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        abort_unless(is_string($decoded), 422, 'Invalid base64url value.');

        return $decoded;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function padCoordinate(string $coordinate): string
    {
        return str_pad($coordinate, 32, "\0", STR_PAD_LEFT);
    }

    private function derSignatureToRaw(string $signature): string
    {
        $offset = 2;
        if (ord($signature[1]) > 0x80) {
            $offset += ord($signature[1]) - 0x80;
        }
        $integerLength = ord($signature[$offset + 1]);
        $r = substr($signature, $offset + 2, $integerLength);
        $offset += 2 + $integerLength;
        $sLength = ord($signature[$offset + 1]);
        $s = substr($signature, $offset + 2, $sLength);

        return str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT).str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);
    }
}
