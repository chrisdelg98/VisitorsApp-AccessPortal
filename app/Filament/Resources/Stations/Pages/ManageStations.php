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

    // Propiedad Livewire para pasar el PIN entre mutateFormDataUsing y after
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

                    // Guardar en propiedad Livewire para mostrarlo en after()
                    $this->newStationPin = $plainPin;

                    return $data;
                })
                ->after(function (): void {
                    if ($this->newStationPin) {
                        Notification::make()
                            ->title('Estación creada — guarda el PIN')
                            ->body("PIN de la tablet: **{$this->newStationPin}**\n\nEste PIN no se puede recuperar después.")
                            ->success()
                            ->persistent()
                            ->send();

                        $this->newStationPin = null;
                    }
                }),
        ];
    }
}
