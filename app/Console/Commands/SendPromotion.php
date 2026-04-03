<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

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

        //  $previewData = [
        //     'discountCode' => $discountCode,
        //     'discountPercentage' => $discountPercentage,
        //     'audience' => $audience,
        // ];

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

        if (! $this->confirm('Do you want to send this promotion?', false)) {
            $this->warn('Promotion cancelled.');
            return self::SUCCESS;
        }

        // ===============================================================
        // Replace this block with your real email sending logic.
        // Example:
        // Mail::to($recipients)->send(new PromotionMail($previewData));
        // ===============================================================

        $this->info('Promotion email sent successfully.');

        return self::SUCCESS;
    }

    private function askDiscountPercentage(): int
    {
        while (true) {
            $input = $this->ask('Enter discount percentage (1-100)', '20');

            if (! is_numeric($input)) {
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
}
