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

    public function mount(): void
    {
        $this->form->fill([
            'home_banner_video' => Metadato::get('home_banner_video'),
        ]);
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (isset($data['home_banner_video'])) {
            Metadato::set('home_banner_video', $data['home_banner_video'] ?: null);
        }

        Notification::make()
            ->title('Metadatos guardados correctamente')
            ->success()
            ->send();
    }
}
