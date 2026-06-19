<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use Illuminate\Console\Command;

class ManageCoupons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coupon:manage {action=list : Action to perform: list, create, delete, toggle} {--code= : Coupon code} {--type=fixed : Coupon type (fixed or percentage)} {--value= : Discount value} {--limit= : Usage limit} {--expiry= : Expiration date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and manage discount coupons';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'create':
                $this->createCoupon();
                break;
            case 'delete':
                $this->deleteCoupon();
                break;
            case 'toggle':
                $this->toggleCoupon();
                break;
            case 'list':
            default:
                $this->listCoupons();
                break;
        }
    }

    private function createCoupon()
    {
        $code = $this->option('code') ?? strtoupper(str()->random(8));
        $value = $this->option('value');

        if (!$value) {
            $this->error('Value is required for creating a coupon.');
            return;
        }

        $coupon = Coupon::create([
            'code' => $code,
            'type' => $this->option('type'),
            'value' => $value,
            'usage_limit' => $this->option('limit'),
            'expires_at' => $this->option('expiry'),
        ]);

        $this->info("Coupon {$coupon->code} created successfully!");
    }

    private function deleteCoupon()
    {
        $code = $this->option('code');
        if (!$code) {
            $this->error('Code is required to delete a coupon.');
            return;
        }

        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) {
            $this->error('Coupon not found.');
            return;
        }

        $coupon->delete();
        $this->info("Coupon {$code} deleted.");
    }

    private function toggleCoupon()
    {
        $code = $this->option('code');
        if (!$code) {
            $this->error('Code is required to toggle a coupon.');
            return;
        }

        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) {
            $this->error('Coupon not found.');
            return;
        }

        $coupon->is_active = !$coupon->is_active;
        $coupon->save();

        $status = $coupon->is_active ? 'activated' : 'deactivated';
        $this->info("Coupon {$code} has been {$status}.");
    }

    private function listCoupons()
    {
        $coupons = Coupon::all(['code', 'type', 'value', 'is_active', 'used_count', 'usage_limit', 'expires_at']);

        if ($coupons->isEmpty()) {
            $this->info('No coupons found.');
            return;
        }

        $this->table(
            ['Code', 'Type', 'Value', 'Active', 'Used', 'Limit', 'Expiry'],
            $coupons->toArray()
        );
    }
}
