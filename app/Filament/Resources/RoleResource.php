<?php

namespace App\Filament\Resources;

use App\Authorization\PermissionCatalog;
use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Usuarios y Roles';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Roles y Permisos';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roles';

    /** Built-in roles whose name cannot be changed and which cannot be deleted. */
    public const PROTECTED_ROLES = ['owner', 'admin', 'wholesaler'];

    /** Only users who can manage roles reach this resource (owner/admin via super-grant). */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('users.manage_roles') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del Rol')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre del rol')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Role $record): bool => $record && in_array($record->name, self::PROTECTED_ROLES, true))
                        ->helperText(fn (?Role $record): ?string => $record && in_array($record->name, self::PROTECTED_ROLES, true)
                            ? 'Rol del sistema: el nombre no puede modificarse.'
                            : 'Identificador del rol, p. ej. "supervisor_ventas".'),
                    Forms\Components\Hidden::make('guard_name')->default(PermissionCatalog::GUARD),
                ])
                ->columns(1),

            Forms\Components\Section::make('Permisos')
                ->description('Marca los permisos que tendrá este rol. Los roles owner y admin tienen acceso total automáticamente.')
                ->schema(self::permissionSections()),
        ]);
    }

    /**
     * One collapsible Section per module, each a checkbox list built from the
     * PermissionCatalog (labels + descriptions in Spanish). State is held under
     * `perms.<module>` and persisted by the Create/Edit pages via syncPermissions().
     */
    protected static function permissionSections(): array
    {
        $labels = PermissionCatalog::moduleLabels();
        $sections = [];

        foreach (PermissionCatalog::grouped() as $module => $permissions) {
            $options      = [];
            $descriptions = [];
            foreach ($permissions as $perm) {
                $options[$perm['name']]      = $perm['label'];
                $descriptions[$perm['name']] = $perm['description'];
            }

            $sections[] = Forms\Components\Section::make($labels[$module] ?? $module)
                ->schema([
                    Forms\Components\CheckboxList::make("perms.{$module}")
                        ->label('')
                        ->options($options)
                        ->descriptions($descriptions)
                        ->columns(2)
                        ->bulkToggleable()
                        ->gridDirection('row'),
                ])
                ->collapsible()
                ->collapsed()
                ->compact();
        }

        return $sections;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rol')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => in_array($state, self::PROTECTED_ROLES, true) ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permisos')
                    ->counts('permissions')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge(),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Role $record): bool => ! in_array($record->name, self::PROTECTED_ROLES, true)),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
