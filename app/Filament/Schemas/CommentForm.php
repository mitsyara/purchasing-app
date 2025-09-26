<?php

namespace App\Filament\Schemas;

use App\Models\Comment;
use Filament\Forms\Components as F;


class CommentForm
{
    public static function commentFormFields(): F\Repeater
    {
        return F\Repeater::make('comments')
            ->label(__('Comments'))
            ->relationship()
            ->hiddenLabel()
            ->simple(
                F\Textarea::make('comment')
                    ->label(__('Comment'))
                    ->hiddenLabel()
                    ->columnSpanFull()
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
