<?php

namespace App\Filament\Resources\OrderResource\Steps;

use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;

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
            ]);
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
                TextInput::make('new_city')
                    ->label('Ciudad donde vendes')
                    ->required($isNew)
                    ->maxLength(100),
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
