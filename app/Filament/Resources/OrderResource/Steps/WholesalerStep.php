<?php

namespace App\Filament\Resources\OrderResource\Steps;

use App\Models\User;
use BaironBernal\ColombiaLocations\Models\Departamento;
use BaironBernal\ColombiaLocations\Models\Municipio;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WholesalerStep
{
    public static function make(): Step
    {
        return Step::make('Mayorista')
            ->label('Mayorista')
            ->icon('heroicon-o-user')
            ->description('Busque o registre el mayorista')
            ->schema([
                self::searchSection(),
                self::foundSection(),
                self::registerSection(),
            ])
            ->afterValidation(function (Get $get, Set $set): void {
                // User already exists — nothing to do
                if (filled($get('customer_id'))) {
                    return;
                }

                // New wholesaler — create the user now so they're saved
                // even if the admin abandons the remaining steps
                $name = trim($get('new_full_name') ?? '');
                if (blank($name)) {
                    return;
                }

                // Split full name into first/last to satisfy NOT NULL constraints
                $nameParts = explode(' ', $name, 2);
                $firstName = $nameParts[0];
                $lastName  = $nameParts[1] ?? $nameParts[0];

                // Avoid duplicate if identity_number already exists (race condition)
                if (User::where('identity_number', $get('identity_number'))->exists()) {
                    $existing = User::where('identity_number', $get('identity_number'))->first();
                    $set('customer_id',    $existing->id);
                    $set('customer_name',  $existing->name);
                    $set('customer_email', $existing->email);
                    $set('customer_phone', $existing->phone_number ?? '');
                    return;
                }

                // Resolve email: if provided but already taken, fall back to a unique placeholder
                $providedEmail = $get('customer_email');
                if (filled($providedEmail) && User::where('email', $providedEmail)->exists()) {
                    $providedEmail = null; // force placeholder below
                }
                $email = filled($providedEmail)
                    ? $providedEmail
                    : Str::slug($name) . '-' . Str::random(8) . '@mayorista.local';

                $user = User::create([
                    'name'             => $name,
                    'first_name'       => $firstName,
                    'last_name'        => $lastName,
                    'identity_number'  => $get('identity_number'),
                    'email'            => $email,
                    'password'         => Hash::make(Str::random(16)),
                    'phone_number'     => $get('customer_phone') ?: null,
                    'whatsapp_number'  => $get('new_whatsapp_number') ?: null,
                    'department_id'    => $get('department_id') ?: null,
                    'municipality_id'  => $get('municipality_id') ?: null,
                    'selling_channel'  => $get('new_selling_channel') ?: null,
                    'business_name'    => $get('new_business_name') ?: null,
                    'clothing_type'    => $get('new_clothing_type') ?: null,
                    'selling_location' => $get('new_selling_location') ?: null,
                    'is_active'        => true,
                ]);

                $user->assignRole('wholesaler');

                $set('customer_id',    $user->id);
                $set('customer_name',  $user->name);
                $set('customer_email', $user->email);
                $set('customer_phone', $user->phone_number ?? '');
            });
    }

    // -------------------------------------------------------------------------

    private static function searchSection(): Section
    {
        return Section::make('Buscar Mayorista')
            ->description('Ingrese la cédula o NIT para buscar en el sistema')
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                TextInput::make('identity_number')
                    ->label('Cédula / NIT')
                    ->placeholder('Ej: 1234567890')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if (blank($state)) {
                            $set('customer_id', null);
                            $set('customer_name', null);
                            $set('customer_email', null);
                            $set('customer_phone', null);

                            return;
                        }

                        $user = User::where('identity_number', $state)->first();

                        if ($user) {
                            $set('customer_id', $user->id);
                            $set('customer_name', $user->name);
                            $set('customer_email', $user->email);
                            $set('customer_phone', $user->phone_number ?? '');
                        } else {
                            $set('customer_id', null);
                        }
                    }),

                Hidden::make('customer_id'),
            ]);
    }

    private static function foundSection(): Section
    {
        return Section::make('Mayorista Encontrado')
            ->description('El mayorista ya está registrado en el sistema')
            ->icon('heroicon-o-check-circle')
            ->schema([
                TextInput::make('customer_name')
                    ->label('Nombre Completo')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('customer_email')
                    ->label('Email')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('customer_phone')
                    ->label('Teléfono')
                    ->disabled()
                    ->dehydrated(),
            ])
            ->columns(3)
            ->visible(fn (Get $get): bool => filled($get('customer_id')));
    }

    private static function registerSection(): Section
    {
        $isNew = fn (Get $get): bool => blank($get('customer_id')) && filled($get('identity_number'));

        return Section::make('Registrar Nuevo Mayorista')
            ->description('No se encontró el mayorista. Complete los datos para registrarlo.')
            ->icon('heroicon-o-user-plus')
            ->schema([
                TextInput::make('new_full_name')
                    ->label('Nombre completo')
                    ->required($isNew)
                    ->maxLength(150),
                TextInput::make('new_whatsapp_number')
                    ->label('Número de WhatsApp')
                    ->tel()
                    ->required($isNew)
                    ->maxLength(20),
                Select::make('department_id')
                    ->label('Departamento')
                    ->options(Departamento::orderBy('nombre')->pluck('nombre', 'id'))
                    ->required($isNew)
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('municipality_id', null))
                    ->searchable(),

                Select::make('municipality_id')
                    ->label('Municipio')
                    ->options(fn (Get $get): array => blank($get('department_id'))
                        ? []
                        : Municipio::where('departamento_id', $get('department_id'))
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id')
                            ->all()
                    )
                    ->required($isNew)
                    ->disabled(fn (Get $get): bool => blank($get('department_id')))
                    ->searchable()
                    ->live(),
                Select::make('new_selling_channel')
                    ->label('¿Cómo vendes los productos?')
                    ->options([
                        'Tienda física' => 'Tienda física',
                        'Instagram'     => 'Instagram',
                        'WhatsApp'      => 'WhatsApp',
                        'Personal'      => 'Personal',
                    ])
                    ->required($isNew),
                TextInput::make('new_business_name')
                    ->label('Nombre de tu negocio o marca (opcional)')
                    ->maxLength(150),
                Select::make('new_clothing_type')
                    ->label('¿Qué tipo de ropa vendes principalmente?')
                    ->options([
                        'Hombre' => 'Hombre',
                        'Dama'   => 'Dama',
                        'Niño'   => 'Niño',
                        'Mixto'  => 'Mixto',
                    ])
                    ->required($isNew),
                Select::make('new_selling_location')
                    ->label('¿Desde dónde vendes principalmente?')
                    ->options([
                        'Tienda física'  => 'Tienda física',
                        'Redes sociales' => 'Redes sociales',
                        'Catálogo'       => 'Catálogo',
                        'Otro'           => 'Otro',
                    ])
                    ->required($isNew),
                TextInput::make('customer_email')
                    ->label('Email')
                    ->email()
                    ->dehydrated(),
                TextInput::make('customer_phone')
                    ->label('Teléfono')
                    ->tel()
                    ->dehydrated(),
            ])
            ->columns(2)
            ->visible($isNew);
    }
}
