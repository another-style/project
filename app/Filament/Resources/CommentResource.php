<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommentResource\Pages;
use App\Filament\Resources\CommentResource\RelationManagers\ImagesRelationManager;
use App\Models\Comment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $modelLabel = 'Комментарий';

    protected static ?string $pluralModelLabel = 'Комментарии';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Имя')
                    ->maxLength(50),
                Forms\Components\Textarea::make('message')
                    ->label('Сообщение')
                    ->required()
                    ->maxLength(5000)
                    ->rows(5),
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP-адрес')
                    ->disabled()
                    ->visible(fn (): bool => auth()->user()->can('viewIp', Comment::class)),
                Forms\Components\TextInput::make('parent_id')
                    ->label('ID родителя')
                    ->disabled(),
                Forms\Components\Toggle::make('is_pinned')
                    ->label('Закреплён')
                    ->visible(fn (): bool => auth()->user()->can('pin', new Comment()))
                    ->disabled(fn (): bool => ! auth()->user()->can('pin', new Comment())),
                Forms\Components\Select::make('tags')
                    ->label('Теги')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(50),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->default('Аноним')
                    ->searchable(),
                Tables\Columns\TextColumn::make('message')
                    ->label('Сообщение')
                    ->limit(100)
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP-адрес')
                    ->visible(fn (): bool => auth()->user()->can('viewIp', Comment::class)),
                Tables\Columns\TextColumn::make('parent_id')
                    ->label('Родитель')
                    ->default('Тема')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Теги')
                    ->badge(),
                Tables\Columns\TextColumn::make('images_count')
                    ->label('Фото')
                    ->counts('images')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('Закреплён')
                    ->boolean()
                    ->trueIcon('heroicon-o-bookmark')
                    ->falseIcon('heroicon-o-bookmark-slash')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->label('Закреплённые'),
            ])
            ->actions([
                Tables\Actions\Action::make('togglePin')
                    ->label(fn (Comment $record): string => $record->is_pinned ? 'Открепить' : 'Закрепить')
                    ->icon(fn (Comment $record): string => $record->is_pinned ? 'heroicon-o-bookmark-slash' : 'heroicon-o-bookmark')
                    ->action(function (Comment $record): void {
                        $record->update(['is_pinned' => ! $record->is_pinned]);
                    })
                    ->authorize('pin'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
