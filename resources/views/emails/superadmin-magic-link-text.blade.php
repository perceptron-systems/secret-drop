{!! __('messages.email_superadmin_intro') !!}

{!! __('messages.email_superadmin_button') !!}: {{ $verifyUrl }}

{!! __('messages.email_magic_link_warning', ['minutes' => config('secrets.magic_link_ttl')]) !!}

--
{!! __('messages.email_footer', ['app' => config('app.name')]) !!}
