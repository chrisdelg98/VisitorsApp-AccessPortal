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

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    // Generar UUID
                    $data['id'] = (string) Str::uuid();

                    // Generar API key única
                    do {
                        $apiKey = Str::random(64);
                    } while (Station::where('api_key', $apiKey)->exists());
                    $data['api_key'] = $apiKey;

                    // Generar PIN único de 8 dígitos y almacenarlo en sesión para notificarlo
                    do {
                        $plainPin = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
                        $pinLookup = hash('sha256', $plainPin);
                    } while (Station::where('pin_lookup', $pinLookup)->exists());

                    $data['pin']        = bcrypt($plainPin);
                    $data['pin_lookup'] = $pinLookup;

                    // Guardar el PIN en sesión para mostrarlo tras crear
                    session(['new_station_pin' => $plainPin]);

                    return $data;
                })
                ->after(function (): void {
                    $pin = session()->pull('new_station_pin');
                    if ($pin) {
                        Notification::make()
                            ->title('Estación creada')
                            ->body("PIN de la tablet: **{$pin}**  \nGuarda este PIN — no se puede recuperar después.")
                            ->success()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
