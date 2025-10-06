<?php

namespace App\Filament\Schemas;

use App\Models\Comment;
use Filament\Forms\Components as F;
use Illuminate\Database\Eloquent\Builder;

class CommentForm
{
    public static function commentFormFields(): F\Repeater
    {
        return F\Repeater::make('comments')
            ->label(__('Comments'))
            ->relationship(
                modifyQueryUsing: fn(Builder $query) => $query
            )
            ->hiddenLabel()
            ->simple(
                F\Textarea::make('comment')
                    ->label(__('Comment'))
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(10000)
                    ->disabled(fn(?Comment $record): bool => $record
                        && $record?->user_id !== auth()->id()),
            )
            ->addActionLabel(__('Add Comment'))
            ->mutateRelationshipDataBeforeCreateUsing(fn(array $data): array => [
                ...$data,
                'user_id' => auth()->id(),
            ])
            ->grid();
    }
}
