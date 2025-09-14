<?php

namespace App\Filament\Resources\InformasiResource\Widgets;

use Filament\Tables;
use App\Models\Informasi;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Widgets\TableWidget as BaseWidget;

class InformasiList extends BaseWidget
{
    protected static ?string $heading = 'Informasi';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Informasi::query()
                    ->where('status', '!=', 'Archive')
                    ->where('status', '!=', 'Draft')
                    ->orderBy('tanggal', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable(Informasi::all()->count() > 25)
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->judul;
                    })
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('isi')
                    ->label('Konten')
                    ->limit(25)
                    ->html()
                    ->tooltip(function ($record) {
                        return strip_tags($record->isi);
                    }),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->dateTime('l, d F Y H:i:s'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('Detail')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn($record) => 'Detail Informasi')
                        ->modalDescription(fn($record) => $record->judul)
                        ->form(function ($record) {
                            return [
                                Section::make('Detail Informasi')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('judul')
                                                    ->label('Judul')
                                                    ->content(fn($record) => new HtmlString('<span class="font-semibold text-gray-900">' . $record->judul . '</span>')),

                                                Placeholder::make('status')
                                                    ->label('Status')
                                                    ->content(function ($record) {
                                                        $colors = [
                                                            'Draft' => 'bg-gray-100 text-gray-800',
                                                            'Publish' => 'bg-green-100 text-green-800',
                                                            'Archive' => 'bg-red-100 text-red-800'
                                                        ];
                                                        $color = $colors[$record->status] ?? 'bg-gray-100 text-gray-800';
                                                        return new HtmlString(
                                                            '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $color . '">'
                                                                . $record->status .
                                                                '</span>'
                                                        );
                                                    }),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('tanggal')
                                                    ->label('Tanggal Publikasi')
                                                    ->content(fn($record) => $record->tanggal),

                                                Placeholder::make('created_at')
                                                    ->label('Dibuat')
                                                    ->content(fn($record) => $record->created_at),
                                            ]),
                                    ]),

                                Section::make('Konten')
                                    ->schema([
                                        Placeholder::make('isi')
                                            ->label('')
                                            ->content(function ($record) {
                                                return new HtmlString(
                                                    '<div class="prose prose-sm max-w-none">' .
                                                        nl2br(e($record->isi)) .
                                                        '</div>'
                                                );
                                            }),
                                    ]),

                                Section::make('Lampiran')
                                    ->schema([
                                        Placeholder::make('lampiran')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record->lampiran) {
                                                    return new HtmlString('<p class="text-gray-500 italic">Tidak ada lampiran</p>');
                                                }

                                                $filename = basename($record->lampiran);
                                                $fileSize = Storage::exists($record->lampiran)
                                                    ? number_format(Storage::size($record->lampiran) / 1024, 1) . ' KB'
                                                    : 'Unknown size';

                                                return new HtmlString(
                                                    '<div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">' .
                                                        '<svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
                                                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>' .
                                                        '</svg>' .
                                                        '<div class="flex-1">' .
                                                        '<div class="text-sm font-medium text-gray-900">' . $filename . '</div>' .
                                                        '<div class="text-xs text-gray-500">Ukuran: ' . $fileSize . '</div>' .
                                                        '</div>' .
                                                        '<a href="' . Storage::url($record->lampiran) . '" target="_blank" ' .
                                                        'class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">' .
                                                        'Download' .
                                                        '</a>' .
                                                        '</div>'
                                                );
                                            }),
                                    ])
                                    ->visible(fn($record) => !empty($record->lampiran)),

                                Section::make('Informasi Tambahan')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('id')
                                                    ->label('ID')
                                                    ->content(fn($record) => $record->id),

                                                Placeholder::make('updated_at')
                                                    ->label('Terakhir Diperbarui')
                                                    ->content(fn($record) => $record->updated_at->format('d M Y H:i')),

                                                Placeholder::make('deleted_at')
                                                    ->label('Status')
                                                    ->content(fn($record) => $record->deleted_at ? 'Dihapus: ' . $record->deleted_at->format('d M Y H:i') : 'Aktif')
                                            ]),
                                    ])
                                    ->collapsed()
                            ];
                        })
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->fillForm(fn($record) => $record->toArray()),
                ])
            ])
            ->recordAction('view')
            ->recordUrl(null)
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
