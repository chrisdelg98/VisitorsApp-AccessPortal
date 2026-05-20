<?php

namespace App\Filament\Exports;

use App\Models\Visit;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class VisitExporter extends Exporter
{
    protected static ?string $model = Visit::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),

            ExportColumn::make('visitor.full_name')->label('Visitante'),
            ExportColumn::make('visitor.document_number')->label('Documento'),
            ExportColumn::make('visitor.document_type')->label('Tipo documento'),
            ExportColumn::make('visitor.company')->label('Empresa'),
            ExportColumn::make('visitor.email')->label('Email visitante'),

            ExportColumn::make('station.code')->label('Estación'),
            ExportColumn::make('station.name')->label('Nombre estación'),
            ExportColumn::make('station.country.name')->label('País'),

            ExportColumn::make('visitor_type')->label('Tipo visitante'),
            ExportColumn::make('visit_reason')->label('Razón'),
            ExportColumn::make('visiting_person')->label('Visita a'),

            ExportColumn::make('check_in')->label('Entrada'),
            ExportColumn::make('check_out')->label('Salida'),
            ExportColumn::make('duration_in_minutes')->label('Duración (min)'),

            ExportColumn::make('status')->label('Estado'),
            ExportColumn::make('badge_printed')->label('Badge impreso'),
            ExportColumn::make('notes')->label('Notas'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return "Exportación completada: {$count} visitas exportadas.";
    }
}
