<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Models\Role;
use Filament\Support\Icons\Heroicon;

use App\Filament\BaseResource as Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Unique;

use Filament\Actions as A;
use Filament\Facades\Filament;
use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Filament\Tables\Columns as T;

class RoleResource extends Resource
{
    use HasShieldFormComponents;

    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 22;

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('User Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Grid::make()
                    ->schema([
                        S\Section::make()
                            ->schema([
                                F\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(
                                        ignoreRecord: true,
                                        /** @phpstan-ignore-next-line */
                                        modifyRuleUsing: fn(Unique $rule): Unique => Utils::isTenancyEnabled()
                                            ? $rule->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id)
                                            : $rule
                                    )
                                    ->required()
                                    ->maxLength(255),

                                F\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                F\Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    ->default(Filament::getTenant()?->id)
                                    ->options(fn(): array => in_array(Utils::getTenantModel(), [null, '', '0'], true)
                                        ? [] : Utils::getTenantModel()::pluck('name', 'id')->toArray())
                                    ->visible(fn(): bool => static::shield()->isCentralApp()
                                        && Utils::isTenancyEnabled())
                                    ->dehydrated(fn(): bool => static::shield()->isCentralApp()
                                        && Utils::isTenancyEnabled()),

                                static::getSelectAllFormComponent(),

                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                static::getShieldFormComponents(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                T\TextColumn::make('name')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn(string $state): string => str($state)->headline())
                    ->searchable(),
                T\TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                T\TextColumn::make('team.name')
                    ->default('Global')
                    ->badge()
                    ->color(fn(mixed $state): string => str($state)->contains('Global') ? 'gray' : 'primary')
                    ->label(__('filament-shield::filament-shield.column.team'))
                    ->searchable()
                    ->visible(fn(): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                T\TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->color('primary'),
                T\TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\EditAction::make()
                    ->mutateDataUsing(function (array $data, A\Action $action): array {
                        $action->after(fn(Role $record) => static::afterSave($record, $data));
                        return static::mutateData($data);
                    })
                    ->modal()->slideOver()
                    ->modalWidth(\Filament\Support\Enums\Width::ScreenTwoExtraLarge),

                A\DeleteAction::make(),
            ])
            ->toolbarActions([
                A\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }

    /**
     * Create
     */
    public static function mutateData(array $data): array
    {
        if (
            Utils::isTenancyEnabled() && Arr::has($data, Utils::getTenantModelForeignKey())
            && filled($data[Utils::getTenantModelForeignKey()])
        ) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    public static function afterSave(Role $record, array $originalData): void
    {
        $permissions = collect($originalData)
            ->filter(fn(mixed $permission, string $key): bool
            => ! in_array($key, ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()]))
            ->values()
            ->flatten()
            ->unique();

        // dd($record, $originalData, $permissions);

        $permissionModels = collect();
        $permissions->each(function (string $permission) use ($permissionModels, $originalData): void {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                'name' => $permission,
                'guard_name' => $originalData['guard_name'],
            ]));
        });

        // @phpstan-ignore-next-line
        $record->syncPermissions($permissionModels);
    }
}
