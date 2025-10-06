<?php

namespace App\Filament\Clusters\Settings\Resources\Users;

use App\Filament\Clusters\Settings\Resources\Users\Pages\ManageUsers;
use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Columns as T;
use Filament\Actions as A;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Illuminate\Support\Facades\DB;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 21;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('User Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                F\TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),

                F\TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(fn($state): bool => !empty($state))
                    ->password()
                    ->revealable()
                    ->autocomplete('new-password')
                    ->disabled(fn(string $operation) => $operation === 'edit' && auth()->id() !== 1)
                    ->required(fn(string $operation): bool => $operation === 'create'),

                F\TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->unique()
                    ->autocomplete(false)
                    ->required(),

                F\TextInput::make('phone')
                    ->label(__('Phone'))
                    ->tel()
                    ->maxLength(20)
                    ->mask(\Filament\Support\RawJs::make(<<<'JS'
                        '9999 999 999'
                    JS))
                    ->stripCharacters([' '])
                    ->trim()
                    ->unique(),

                F\DatePicker::make('dob')
                    ->label(__('Date of Birth'))
                    ->maxDate(today()->subYears(18))
                    ->minDate(today()->subYears(60)),

                F\Select::make('companies')
                    ->label(__('Company'))
                    ->relationship(
                        name: 'companies',
                        titleAttribute: 'company_code',
                        modifyQueryUsing: fn(Builder $query): Builder => $query
                    )
                    ->multiple()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query): Builder
            => $query->with('companies')->whereNot('id', 1))
            ->columns([
                __index(),

                T\TextColumn::make('status')
                    ->label(__('Status'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->since()
                    ->sortable(),

                T\TextColumn::make('companies.company_code')
                    ->label(__('Company'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make(),
                    A\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\BulkActionGroup::make([
                        static::bulkStatus(),
                        static::bulkCompanies(),
                    ])
                        ->dropdown(false),

                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    // Helpers

    public static function bulkCompanies(): A\BulkAction
    {
        return A\BulkAction::make('assign_company')
            ->label(__('Assign Company'))
            ->icon(Heroicon::OutlinedHomeModern)
            ->color('success')
            ->schema([
                F\CheckboxList::make('companies')
                    ->options(fn() => \App\Models\Company::all()->pluck('company_code', 'id'))
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(['default' => 2]),
            ])
            ->action(function (array $data, mixed $records) {
                $companyIds = $data['companies'] ?? [];
                $userIds = $records->toArray();

                $table = User::getCompanyPivotTable();

                DB::transaction(function () use ($table, $userIds, $companyIds) {
                    // Delete existing relations
                    DB::table($table)
                        ->whereIn('user_id', $userIds)
                        ->delete();

                    // Exit if No companies selected
                    if (empty($companyIds)) {
                        return;
                    }

                    $pivotData = collect($userIds)
                        ->crossJoin($companyIds)
                        ->map(fn($pair) => [
                            'user_id' => $pair[0],
                            'company_id' => $pair[1],
                        ])
                        ->all();
                    // Bulk insert new relations
                    DB::table($table)->insert($pivotData);
                });
            });
    }

    public static function bulkStatus(): A\BulkAction
    {
        return A\BulkAction::make('toggle_status')
            ->label(__('Toggle Status'))
            ->icon(Heroicon::OutlinedUserCircle)
            ->color('warning')
            ->schema([
                F\ToggleButtons::make('status')
                    ->label(__('Status'))
                    ->options(\App\Enums\UserStatusEnum::class)
                    ->grouped()
                    ->required(),
            ])
            ->action(function (array $data, mixed $records) {
                $status = $data['status'] ?? null;
                if ($status) {
                    User::query()->whereIn('id', $records)
                        ->update(['status' => $status]);
                }
            });
    }
}
