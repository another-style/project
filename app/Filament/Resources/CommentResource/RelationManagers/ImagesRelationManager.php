<?php

namespace App\Filament\Resources\CommentResource\RelationManagers;

use App\Models\CommentImage;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Изображения';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('Превью')
                    ->height(80)
                    ->width(80)
                    ->extraImgAttributes(['class' => 'rounded object-cover']),
                Tables\Columns\TextColumn::make('filename')
                    ->label('Имя файла')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Загружено')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('delete_image')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить изображение')
                    ->modalDescription('Изображение будет откреплено от этого комментария. Если оно больше ни к чему не привязано — файл будет удалён с диска.')
                    ->authorize(fn (CommentImage $record): bool => auth()->user()->can('delete', $record))
                    ->action(function (CommentImage $record, ImagesRelationManager $livewire): void {
                        /** @var \App\Models\Comment $comment */
                        $comment = $livewire->getOwnerRecord();
                        $comment->images()->detach($record->id);

                        // Если изображение больше не привязано ни к одному комментарию — удаляем файл и запись
                        if ($record->comments()->count() === 0) {
                            $record->deleteWithFile();
                        }
                    }),
            ])
            ->headerActions([])
            ->paginated(false);
    }
}
