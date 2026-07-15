<?php

namespace App\Filament\Pages;

use App\Models\Metadato;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MetadatosPage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Gestión de Contenido';

    protected static ?string $navigationLabel = 'Metadatos';

    protected static ?string $title = 'Metadatos del Sitio';

    protected static string $view = 'filament.pages.metadatos';

    public ?array $data = [];

    /**
     * Claves de información de empresa / redes sociales gestionadas en esta
     * página. Los valores por defecto viven en config/company.php.
     */
    protected function companyKeys(): array
    {
        return array_keys(config('company', []));
    }

    public function mount(): void
    {
        $info = Metadato::siteInfo();

        $this->form->fill(array_merge(
            ['home_banner_video' => Metadato::get('home_banner_video')],
            $info,
        ));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Banner Principal (Home)')
                    ->description('Video que se muestra en el banner del home. Reemplaza el video actual.')
                    ->schema([
                        Forms\Components\FileUpload::make('home_banner_video')
                            ->label('Video del Banner')
                            ->disk('public')
                            ->directory('banners')
                            ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                            ->maxSize(102400)
                            ->helperText('Máximo 100 MB. Formatos aceptados: MP4, WebM, OGG.'),
                    ]),

                Forms\Components\Section::make('Datos de la Empresa')
                    ->description('Se usan en la factura (impresión de pedidos) y en el frontend. Si un campo se deja vacío se usa el valor por defecto.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Nombre de la empresa'),
                        Forms\Components\TextInput::make('company_nit')
                            ->label('NIT'),
                        Forms\Components\TextInput::make('company_address')
                            ->label('Dirección'),
                        Forms\Components\TextInput::make('company_city')
                            ->label('Ciudad / País'),
                        Forms\Components\TextInput::make('company_phone')
                            ->label('Teléfono / Contacto'),
                        Forms\Components\TextInput::make('company_website')
                            ->label('Sitio web'),
                    ]),

                Forms\Components\Section::make('Redes Sociales')
                    ->description('Enlaces y nombres de usuario. Se usan en la factura y en el pie de página del frontend.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('social_instagram_url')
                            ->label('Instagram — URL')
                            ->url(),
                        Forms\Components\TextInput::make('social_instagram_handle')
                            ->label('Instagram — Usuario'),
                        Forms\Components\TextInput::make('social_facebook_url')
                            ->label('Facebook — URL')
                            ->url(),
                        Forms\Components\TextInput::make('social_facebook_handle')
                            ->label('Facebook — Usuario'),
                        Forms\Components\TextInput::make('social_tiktok_url')
                            ->label('TikTok — URL')
                            ->url(),
                        Forms\Components\TextInput::make('social_tiktok_handle')
                            ->label('TikTok — Usuario'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (isset($data['home_banner_video'])) {
            Metadato::set('home_banner_video', $data['home_banner_video'] ?: null);
        }

        foreach ($this->companyKeys() as $clave) {
            Metadato::set($clave, isset($data[$clave]) && $data[$clave] !== '' ? $data[$clave] : null);
        }

        Notification::make()
            ->title('Metadatos guardados correctamente')
            ->success()
            ->send();
    }
}
