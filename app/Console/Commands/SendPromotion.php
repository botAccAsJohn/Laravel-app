<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\PromotionMail;
use App\Models\User;

class SendPromotion extends Command
{
    protected $signature = 'email:sendpromotion';

    protected $description = 'Sending the Promotion Email to the users with Discount Code';

    public function handle()
    {
        $discountCode = $this->ask("Enter the Discount Code : ");
        $percentage = $this->askDiscountPercentage();

        $audience = $this->choice('Select The Target Audiunce : ', ['all_users', 'new_customers', 'inactive', 'top_buyers'], 0);

        $this->newLine();

        $summary = [
            ['Discount Code', $discountCode],
            ['Discount Percentage', $percentage . '%'],
            ['Target Audience', $audience],
        ];

        $this->table(['Field', 'Value'], $summary);

        $previewData = [
            'discountCode' => $discountCode,
            'discountPercentage' => $percentage,
            'audience' => $audience,
        ];

        $this->newLine();
        $this->line('<info>Email Preview</info>');
        $this->line('Subject: ' . $this->buildSubject($discountCode, $percentage));
        $this->newLine();

        $this->line($this->renderPreview($previewData));

        if (!$this->confirm('Do you want to send this promotion?', false)) {
            $this->warn('Promotion cancelled.');
            return self::SUCCESS;
        }

        // Get recipients based on audience selection
        $recipients = $this->getRecipientsByAudience($audience);

        if ($recipients->isEmpty()) {
            $this->warn('No recipients found for the selected audience.');
            return self::SUCCESS;
        }

        $this->info("Sending promotion emails to {$recipients->count()} recipients...");

        // Prepare mail data with proper formatting
        $mailData = [
            'subject' => $this->buildSubject($discountCode, $percentage),
            'code' => $discountCode,
            'percentage' => $percentage,
            'audience' => Str::headline(str_replace('_', ' ', $audience)),
        ];

        // Send emails to all recipients
        $count = 0;
        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(new PromotionMail($mailData));
                $count++;
                $this->line("<fg=green>✓</> Email sent to {$user->email}");
            } catch (\Exception $e) {
                $this->line("<fg=red>✗</> Failed to send email to {$user->email}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Promotion email sent successfully to {$count} recipients.");

        return self::SUCCESS;
    }

    private function askDiscountPercentage(): int
    {
        while (true) {
            $input = $this->ask('Enter discount percentage (1-100)', '20');

            if (!is_numeric($input)) {
                $this->error('Discount percentage must be a number.');
                continue;
            }

            $percentage = (int) $input;

            if ($percentage < 1 || $percentage > 100) {
                $this->error('Discount percentage must be between 1 and 100.');
                continue;
            }

            return $percentage;
        }
    }
    private function buildSubject(string $code, int $percentage): string
    {
        return "Special Offer: {$percentage}% OFF with code {$code}";
    }

    private function renderPreview(array $data): string
    {
        $subject = $this->buildSubject($data['discountCode'], $data['discountPercentage']);

        return view('layouts.promotion-email', [
            'subject' => $subject,
            'code' => $data['discountCode'],
            'percentage' => $data['discountPercentage'],
            'audience' => Str::headline(str_replace('_', ' ', $data['audience'])),
        ])->render();
    }

    private function getRecipientsByAudience(string $audience)
    {
        return match ($audience) {
            'all_users' => User::where('email_verified_at', '!=', null)->get(),
            'new_customers' => User::orderBy('created_at', 'desc')
                ->where('email_verified_at', '!=', null)
                ->limit(100)
                ->get(),
            'inactive' => User::where('last_login_at', '<', now()->subDays(30))
                ->where('email_verified_at', '!=', null)
                ->get(),
            'top_buyers' => User::join('orders', 'users.id', '=', 'orders.user_id')
                ->select('users.*')
                ->groupBy('users.id')
                ->havingRaw('COUNT(orders.id) > ?', [5])
                ->where('email_verified_at', '!=', null)
                ->distinct()
                ->get(),
            default => collect(),
        };
    }
}
