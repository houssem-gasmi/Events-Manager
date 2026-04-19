<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;

class StripeGateway
{
    public function __construct(
        private readonly string $secretKey,
        private readonly bool $disableSslVerification,
    ) {}

    public function createCheckoutSession(Event $event, User $user, string $successUrl, string $cancelUrl): array
    {
        return $this->request('POST', '/checkout/sessions', [
            'mode' => 'payment',
            'customer_email' => $user->getEmail(),
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $event->getPrice(),
                    'product_data' => [
                        'name' => $event->getTitle(),
                        'description' => sprintf('Access to %s on %s', $event->getTitle(), $event->getEventDate()->format('Y-m-d H:i')),
                    ],
                ],
            ]],
            'metadata' => [
                'event_id' => (string) $event->getId(),
                'user_id' => (string) $user->getId(),
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->request('GET', '/checkout/sessions/' . rawurlencode($sessionId));
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $url = 'https://api.stripe.com/v1' . $path;

        if ('GET' === $method && [] !== $payload) {
            $url .= '?' . http_build_query($payload);
        }

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        if (function_exists('curl_init')) {
            $curl = curl_init($url);

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 15,
            ]);

            if ($this->disableSslVerification) {
                curl_setopt_array($curl, [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                ]);
            }

            if ('POST' === $method) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload));
            }

            $responseBody = curl_exec($curl);
            $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

            if (false === $responseBody) {
                $errorMessage = curl_error($curl) ?: 'Stripe API request failed.';
                curl_close($curl);

                throw new \RuntimeException($errorMessage);
            }

            curl_close($curl);

            return $this->decodeResponse((string) $responseBody, $statusCode);
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ];

        if ($this->disableSslVerification) {
            $options['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ];
        }

        if ('POST' === $method) {
            $options['http']['content'] = http_build_query($payload);
        }

        $responseBody = @file_get_contents($url, false, stream_context_create($options));
        $statusCode = 0;
        $statusLine = $http_response_header[0] ?? '';

        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches)) {
            $statusCode = (int) $matches[1];
        }

        return $this->decodeResponse((string) $responseBody, $statusCode);
    }

    private function decodeResponse(string $responseBody, int $statusCode): array
    {
        $decodedResponse = json_decode($responseBody, true);

        if (!is_array($decodedResponse)) {
            throw new \RuntimeException('Invalid response from Stripe.');
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $decodedResponse['error']['message'] ?? 'Stripe API request failed.';

            throw new \RuntimeException($message);
        }

        return $decodedResponse;
    }
}
