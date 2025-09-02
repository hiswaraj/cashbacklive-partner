<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Telegram\TelegramService;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jenssegers\Agent\Agent;
use Request;

final readonly class LogSuccessfulLogin implements ShouldQueue
{
    public function __construct(
        private TelegramService $telegramService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // It's good practice to get the IP once and store it in a variable.
        $ip = Request::ip();

        // Check if the IP is public before sending the notification.
        if ($this->isPublicIp($ip)) {
            $agent = new Agent();

            $message = '🚨 <b>New Login Detected</b> 🚨'."\n\n";
            // Use the $ip variable for consistency.
            $message .= 'IP Address: '.$ip."\n";
            $message .= 'Browser: '.$agent->browser()."\n";
            $message .= 'Device Type: '.$agent->deviceType()."\n";
            $message .= 'User Agent: '.$agent->getUserAgent()."\n";

            $this->telegramService->sendMessageToAdmin($message);
        }
    }

    /**
     * Determine if the given IP address is a public IP.
     *
     * @param  string|null  $ip  The IP address to check.
     */
    private function isPublicIp(?string $ip): bool
    {
        // An empty IP is not public.
        if (! $ip) {
            return false;
        }

        // Use filter_var to check if the IP is NOT a private or reserved range.
        // The function returns the IP if it's valid and passes the flags,
        // or false otherwise. We cast the result to a boolean.
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
