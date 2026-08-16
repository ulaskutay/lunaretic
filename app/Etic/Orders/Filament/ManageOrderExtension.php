<?php

namespace App\Etic\Orders\Filament;

use App\Etic\Integrations\Shipping\ArasShipmentService;
use App\Etic\Integrations\Shipping\MngShipmentService;
use App\Etic\Integrations\Shipping\ShippingCredentials;
use App\Etic\Integrations\Shipping\SuratShipmentService;
use App\Etic\Integrations\Shipping\YurticiShipmentService;
use App\Etic\Orders\OrderStatusScenario;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Support\Extending\ViewPageExtension;
use Lunar\Models\Order;
use Throwable;

class ManageOrderExtension extends ViewPageExtension
{
    public function headerActions(array $actions): array
    {
        return [
            $this->createMngShipmentAction(),
            $this->createYurticiShipmentAction(),
            $this->createSuratShipmentAction(),
            $this->createArasShipmentAction(),
            ...$actions,
        ];
    }

    private function createMngShipmentAction(): Action
    {
        return Action::make('mng_shipment')
            ->label(__('etic.filament.shipping.mng_create'))
            ->icon('heroicon-o-truck')
            ->color('success')
            ->visible(fn (?Model $record) => $record instanceof Order && $this->canCreateMngShipment($record))
            ->requiresConfirmation()
            ->modalHeading(__('etic.filament.shipping.mng_create'))
            ->modalDescription(__('etic.filament.shipping.mng_create_help'))
            ->action(function (Order $record): void {
                try {
                    $result = app(MngShipmentService::class)->createFromOrder($record);

                    Notification::make()
                        ->title(__('etic.filament.shipping.mng_created'))
                        ->body(trim($result->trackingNumber.' — '.$result->message))
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title(__('etic.filament.shipping.mng_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function canCreateMngShipment(Order $order): bool
    {
        if (! app(ShippingCredentials::class)->mngConfigured()) {
            return false;
        }

        if (filled(data_get($order->meta, 'mng.integration_code'))) {
            return false;
        }

        return $this->canShipOrder($order);
    }

    private function createYurticiShipmentAction(): Action
    {
        return Action::make('yurtici_shipment')
            ->label(__('etic.filament.shipping.yurtici_create'))
            ->icon('heroicon-o-truck')
            ->color('gray')
            ->visible(fn (?Model $record) => $record instanceof Order && $this->canCreateYurticiShipment($record))
            ->requiresConfirmation()
            ->modalHeading(__('etic.filament.shipping.yurtici_create'))
            ->modalDescription(__('etic.filament.shipping.yurtici_create_help'))
            ->action(function (Order $record): void {
                try {
                    $result = app(YurticiShipmentService::class)->createFromOrder($record);

                    Notification::make()
                        ->title(__('etic.filament.shipping.yurtici_created'))
                        ->body(trim($result->trackingNumber.' — '.$result->message))
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title(__('etic.filament.shipping.yurtici_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function canCreateYurticiShipment(Order $order): bool
    {
        if (! app(ShippingCredentials::class)->yurticiConfigured()) {
            return false;
        }

        if (filled(data_get($order->meta, 'yurtici.integration_code'))) {
            return false;
        }

        return $this->canShipOrder($order);
    }

    private function createSuratShipmentAction(): Action
    {
        return Action::make('surat_shipment')
            ->label(__('etic.filament.shipping.surat_create'))
            ->icon('heroicon-o-truck')
            ->color('warning')
            ->visible(fn (?Model $record) => $record instanceof Order && $this->canCreateSuratShipment($record))
            ->requiresConfirmation()
            ->modalHeading(__('etic.filament.shipping.surat_create'))
            ->modalDescription(__('etic.filament.shipping.surat_create_help'))
            ->action(function (Order $record): void {
                try {
                    $result = app(SuratShipmentService::class)->createFromOrder($record);

                    Notification::make()
                        ->title(__('etic.filament.shipping.surat_created'))
                        ->body(trim($result->trackingNumber.' — '.$result->message))
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title(__('etic.filament.shipping.surat_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function canCreateSuratShipment(Order $order): bool
    {
        if (! app(ShippingCredentials::class)->suratConfigured()) {
            return false;
        }

        if (filled(data_get($order->meta, 'surat.integration_code'))) {
            return false;
        }

        return $this->canShipOrder($order);
    }

    private function createArasShipmentAction(): Action
    {
        return Action::make('aras_shipment')
            ->label(__('etic.filament.shipping.aras_create'))
            ->icon('heroicon-o-truck')
            ->color('info')
            ->visible(fn (?Model $record) => $record instanceof Order && $this->canCreateArasShipment($record))
            ->requiresConfirmation()
            ->modalHeading(__('etic.filament.shipping.aras_create'))
            ->modalDescription(__('etic.filament.shipping.aras_create_help'))
            ->action(function (Order $record): void {
                try {
                    $result = app(ArasShipmentService::class)->createFromOrder($record);

                    Notification::make()
                        ->title(__('etic.filament.shipping.aras_created'))
                        ->body(trim($result->trackingNumber.' — '.$result->message))
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title(__('etic.filament.shipping.aras_failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    private function canCreateArasShipment(Order $order): bool
    {
        if (! app(ShippingCredentials::class)->arasConfigured()) {
            return false;
        }

        if (filled(data_get($order->meta, 'aras.integration_code'))) {
            return false;
        }

        return $this->canShipOrder($order);
    }

    private function canShipOrder(Order $order): bool
    {
        return in_array($order->status, [
            OrderStatusScenario::PAYMENT_RECEIVED,
            OrderStatusScenario::PAYMENT_OFFLINE,
            OrderStatusScenario::PROCESSING,
        ], true);
    }
}
