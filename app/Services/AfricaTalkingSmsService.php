<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking as ATProvider;
use Illuminate\Support\Facades\Log;

class AfricaTalkingSmsService
{
    protected mixed $sms; // The SMS service instance from AT SDK
    protected ?string $defaultSenderId;

    public function __construct()
    {
        $username = config('services.africastalking.username');
        $apiKey   = config('services.africastalking.api_key');
        $this->defaultSenderId = config('services.africastalking.from');

        if (!$username || !$apiKey) {
            Log::error('Africa\'s Talking credentials are not configured.');
            // Optionally throw an exception or ensure sms is null/handled
            $this->sms = null; 
            return;
        }

        $AT       = new ATProvider($username, $apiKey);
        $this->sms = $AT->sms();
    }

    /**
     * Send an SMS message.
     *
     * @param string $to The recipient's phone number (e.g., "+2557XXXXXXXX").
     * @param string $message The message content.
     * @param string|null $from Optional sender ID (overrides default).
     * @return array|null The response from Africa's Talking API or null on configuration error.
     * @throws \Exception If SMS sending fails at the API level.
     */
    public function send(string $to, string $message, ?string $from = null): ?array
    {
        if (!$this->sms) {
            Log::error('AfricaTalkingSmsService not initialized due to missing credentials.');
            return null; // Or throw an exception
        }

        $options = [
            'to'      => $to,
            'message' => $message,
        ];

        $sender = $from ?: $this->defaultSenderId;
        if ($sender) {
            $options['from'] = $sender;
        }

        try {
            $response = $this->sms->send($options);
            Log::info('SMS sent via Africa\'s Talking SDK.', [
                'to' => $to,
                'from' => $options['from'] ?? 'Default',
                'response_status' => $response['status'] ?? 'Unknown', // AT V3 response structure
                'response_data' => $response['data']->SMSMessageData ?? ($response['data'] ?? 'No data')
            ]);
            return $response; // ['status' => 'success', 'data' => object]
        } catch (\Exception $e) {
            Log::error('Error sending SMS via Africa\'s Talking SDK.', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Be careful with verbose traces in production logs
            ]);
            throw $e; // Re-throw or handle more gracefully (e.g., return false or a specific error structure)
        }
    }
}