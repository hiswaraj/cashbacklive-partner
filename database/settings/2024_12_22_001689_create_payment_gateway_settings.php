<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('payment_gateway.active_payment_gateway', 'bulkpe');
        $this->migrator->add('payment_gateway.affxpay_key', '');
        $this->migrator->add('payment_gateway.bulkpe_auth_token', '');
        $this->migrator->add('payment_gateway.kritarth_api_id', '');
        $this->migrator->add('payment_gateway.kritarth_secret_key', '');
        $this->migrator->add('payment_gateway.openmoney_virtual_fund_account_id', '');
        $this->migrator->add('payment_gateway.openmoney_access_key', '');
        $this->migrator->add('payment_gateway.openmoney_secret_key', '');
    }

    public function down(): void
    {
        $this->migrator->delete('payment_gateway.active_payment_gateway');
        $this->migrator->delete('payment_gateway.affxpay_key');
        $this->migrator->delete('payment_gateway.bulkpe_auth_token');
        $this->migrator->delete('payment_gateway.kritarth_api_id');
        $this->migrator->delete('payment_gateway.kritarth_secret_key');
        $this->migrator->delete('payment_gateway.openmoney_virtual_fund_account_id');
        $this->migrator->delete('payment_gateway.openmoney_access_key');
        $this->migrator->delete('payment_gateway.openmoney_secret_key');
    }
};
