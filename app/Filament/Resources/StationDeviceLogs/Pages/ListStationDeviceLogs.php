<?php

namespace App\Filament\Resources\StationDeviceLogs\Pages;

use App\Filament\Resources\StationDeviceLogs\StationDeviceLogResource;
use Filament\Resources\Pages\ListRecords;

class ListStationDeviceLogs extends ListRecords
{
    protected static string $resource = StationDeviceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
