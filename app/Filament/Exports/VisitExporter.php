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

            ExportColumn::make('visitor.full_name')->label('Visitor'),
            ExportColumn::make('visitor.document_number')->label('Document'),
            ExportColumn::make('visitor.document_type')->label('Document type'),
            ExportColumn::make('visitor.company')->label('Company'),
            ExportColumn::make('visitor.email')->label('Visitor email'),

            ExportColumn::make('station.code')->label('Station'),
            ExportColumn::make('station.name')->label('Station name'),
            ExportColumn::make('station.country.name')->label('Country'),

            ExportColumn::make('visitor_type')->label('Visitor type'),
            ExportColumn::make('visit_reason')->label('Reason'),
            ExportColumn::make('visiting_person')->label('Visiting'),

            ExportColumn::make('check_in')->label('Check in'),
            ExportColumn::make('check_out')->label('Check out'),
            ExportColumn::make('duration_in_minutes')->label('Duration (min)'),

            ExportColumn::make('status')->label('Status'),
            ExportColumn::make('badge_printed')->label('Badge printed'),
            ExportColumn::make('notes')->label('Notes'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $count = number_format($export->successful_rows);
        return "Export complete: {$count} visits exported.";
    }
}
