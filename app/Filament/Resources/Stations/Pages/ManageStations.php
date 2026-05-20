<?php

namespace App\Filament\Resources\Stations\Pages;

use App\Filament\Resources\Stations\StationResource;
use App\Models\Station;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;

class ManageStations extends ManageRecords
{
    protected static string $resource = StationResource::class;

    public ?string $newStationPin = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['id'] = (string) Str::uuid();

                    do {
                        $apiKey = Str::random(64);
                    } while (Station::where('api_key', $apiKey)->exists());
                    $data['api_key'] = $apiKey;

                    do {
                        $plainPin  = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
                        $pinLookup = hash('sha256', $plainPin);
                    } while (Station::where('pin_lookup', $pinLookup)->exists());

                    $data['pin']        = bcrypt($plainPin);
                    $data['pin_lookup'] = $pinLookup;

                    $this->newStationPin = $plainPin;

                    return $data;
                })
                ->after(function (): void {
                    if ($this->newStationPin) {
                        Notification::make()
                            ->title('Station created — save the PIN')
                            ->body("Tablet PIN: **{$this->newStationPin}**\n\nThis PIN cannot be recovered later.")
                            ->success()
                            ->persistent()
                            ->send();

                        $this->newStationPin = null;
                    }
                }),
        ];
    }
}
