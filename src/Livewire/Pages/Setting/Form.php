<?php

namespace Bale\Pelatihan\Livewire\Pages\Setting;

use Bale\Cms\Services\TenantConnectionService;
use Bale\Pelatihan\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('cms::layouts.app')]
#[Title('Pengaturan Pelatihan')]
class Form extends Component
{
    public ?string $gemini_model = null;

    public string $gemini_api_key = '';

    public ?string $email_subject = null;

    public ?string $email_body = null;

    public ?string $wa_body = null;

    public bool $whatsapp_enabled = false;

    public function mount(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.settings.view')) {
            abort(403, 'Unauthorized action.');
        }

        $setting = Setting::current();

        $this->gemini_model = $setting->gemini_model;
        $this->email_subject = $setting->email_subject;
        $this->email_body = $setting->email_body;
        $this->wa_body = $setting->wa_body;
        $this->whatsapp_enabled = (bool) $setting->whatsapp_enabled;
    }

    public function save(): void
    {
        TenantConnectionService::ensureActive();

        if (! auth()->user()->can('pelatihan.settings.update')) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'gemini_model' => 'required|string|max:100',
            'gemini_api_key' => 'nullable|string|max:255',
            'email_subject' => 'required|string|max:255',
            'email_body' => 'required|string',
            // Template WhatsApp hanya wajib saat kanalnya dinyalakan.
            'wa_body' => 'nullable|string|required_if:whatsapp_enabled,true',
            'whatsapp_enabled' => 'boolean',
        ], [
            'required' => __(':attribute wajib diisi.'),
            'required_if' => __(':attribute wajib diisi saat notifikasi WhatsApp aktif.'),
            'max' => __(':attribute maksimal :max karakter.'),
            'boolean' => __(':attribute tidak valid.'),
        ], [
            'gemini_model' => __('Model Gemini'),
            'gemini_api_key' => __('Gemini API Key'),
            'email_subject' => __('Subjek Email'),
            'email_body' => __('Isi Email'),
            'wa_body' => __('Isi WhatsApp'),
            'whatsapp_enabled' => __('Notifikasi WhatsApp'),
        ]);

        $setting = Setting::current();

        $data = [
            'gemini_model' => $this->gemini_model,
            'email_subject' => $this->email_subject,
            'email_body' => $this->email_body,
            'wa_body' => $this->wa_body,
            'whatsapp_enabled' => $this->whatsapp_enabled,
        ];

        // Kosong berarti tidak diubah.
        if ($this->gemini_api_key !== '') {
            $data['gemini_api_key'] = $this->gemini_api_key;
        }

        $setting->update($data);

        $this->gemini_api_key = '';
        $this->dispatch('toast', message: __('Pengaturan disimpan.'), type: 'success');
    }

    public function render()
    {
        $setting = Setting::current();

        return view('pelatihan::livewire.pages.setting.form', [
            'hasApiKey' => filled($setting->gemini_api_key),
            'updatedAt' => $setting->updated_at,
            'canUpdate' => auth()->user()->can('pelatihan.settings.update'),
        ]);
    }
}
