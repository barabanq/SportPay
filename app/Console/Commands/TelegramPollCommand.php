<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Выполнить один запрос getUpdates и завершиться}';
    protected $description = 'Long-polling Telegram бота для локального тестирования привязки без Webhook';

    public function handle(TelegramBotService $telegram): int
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            $this->error('TELEGRAM_BOT_TOKEN не задан в .env!');
            return self::FAILURE;
        }

        $this->info('Слушаем апдейты Telegram-бота... (Нажмите Ctrl+C для остановки)');
        $offset = 0;

        do {
            $response = Http::timeout(25)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                'offset' => $offset,
                'timeout' => 15,
            ]);

            if ($response->successful()) {
                $updates = $response->json('result', []);
                foreach ($updates as $update) {
                    $offset = ((int) $update['update_id']) + 1;
                    if ($telegram->handleUpdate($update)) {
                        $this->info("Привязан пользователь по апдейту #{$update['update_id']}");
                    }
                }
            }

            if ($this->option('once')) {
                break;
            }

            usleep(500000);
        } while (true);

        return self::SUCCESS;
    }
}
