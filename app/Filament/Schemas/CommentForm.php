<?php

namespace App\Filament\Schemas;

use App\Models\Comment;
use Filament\Actions\Action;
use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommentForm
{
    public static function commentFormFields(): F\Repeater
    {
        return F\Repeater::make('comments')
            ->label(__('Comments'))
            ->relationship(
                modifyQueryUsing: fn(Builder $query) => $query
                // ->where('user_id', auth()->id())
            )
            ->hiddenLabel()
            ->simple(
                F\Textarea::make('comment')
                    ->label(__('Comment'))
                    // ->label(fn(?Comment $record): string => $record?->user_id ?? __('Comment'))
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->maxLength(10000)
                    ->disabled(fn(?Comment $record): bool => $record?->user_id !== auth()->id() && !auth()->user()->isAdmin())
                    ->required(),
            )
            ->defaultItems(0)
            ->addActionLabel(__('Add Comment'))
            ->mutateRelationshipDataBeforeCreateUsing(fn(array $data): array => [
                ...$data,
                'user_id' => auth()->id(),
            ])
            ->deleteAction(function (Action $action, ?Model $record) {
                $action
                    ->before(function (array $arguments) use ($record, $action) {
                        $id = str_replace('record-', '', $arguments['item']);
                        // Nếu không phải id hợp lệ => không làm gì
                        if (!ctype_digit($id)) return;

                        $userId = auth()->id();
                        $comment = $record->comments()->find($id);
                        // Check quyền: user là chủ comment hoặc admin (id=1)
                        if ($comment->user_id !== $userId && $userId !== 1) {
                            Notification::make()
                                ->title(__('You do not have permission'))
                                ->warning()
                                ->send();

                            $action->cancel();
                            return;
                        }
                    })
                    ->requiresConfirmation(fn(array $arguments, callable $get): bool
                    => !empty($get("comments.{$arguments['item']}.comment") ?? null)
                        || Comment::whereKey(str_replace('record-', '', $arguments['item']))->exists());
            })
            ->grid();
    }
}
