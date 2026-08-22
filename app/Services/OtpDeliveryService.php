<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class OtpDeliveryService
{
    /**
     * Send an OTP message through Twilio SMS or WhatsApp.
     *
     * @throws \Throwable
     */
    public function send(string $phoneNumber, string $code): void
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $channel = strtolower((string) config('services.twilio.channel', 'sms'));

        if ($sid === '' || $token === '') {
            throw new InvalidArgumentException('Konfigurasi Twilio belum diisi di .env.');
        }

        $from = $channel === 'whatsapp'
            ? (string) config('services.twilio.whatsapp_from')
            : (string) config('services.twilio.from');

        if ($from === '') {
            throw new InvalidArgumentException('Nomor pengirim Twilio belum diisi di .env.');
        }

        $payload = [
            'To' => $this->formatRecipient($phoneNumber, $channel),
            'From' => $this->formatSender($from, $channel),
            'Body' => "Kode verifikasi reset password Anda: {$code}. Berlaku 10 menit.",
        ];

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $payload);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new InvalidArgumentException(
                'Gagal mengirim kode verifikasi melalui Twilio: ' . $this->extractTwilioError($response->json())
            );
        }
    }

    private function formatRecipient(string $phoneNumber, string $channel): string
    {
        $normalized = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if ($normalized === '') {
            throw new InvalidArgumentException('Nomor telepon tidak valid.');
        }

        if (str_starts_with($normalized, '0')) {
            $normalized = '62' . substr($normalized, 1);
        }

        if ($channel === 'whatsapp') {
            return 'whatsapp:+' . ltrim($normalized, '+');
        }

        return '+' . ltrim($normalized, '+');
    }

    private function formatSender(string $sender, string $channel): string
    {
        $sender = trim($sender);

        if ($channel === 'whatsapp' && !str_starts_with($sender, 'whatsapp:')) {
            return 'whatsapp:' . $sender;
        }

        return $sender;
    }

    private function extractTwilioError(mixed $payload): string
    {
        if (is_array($payload)) {
            $message = $payload['message'] ?? $payload['detail'] ?? null;

            if (is_string($message) && $message !== '') {
                return $message;
            }
        }

        return 'Respons tidak valid dari server Twilio.';
    }
}
