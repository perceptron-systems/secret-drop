@extends('emails.layouts.magic-link')

@section('title', __('messages.email_superadmin_subject'))

@section('gradient-start', '#d97706')
@section('gradient-end', '#ea580c')
@section('button-shadow', 'rgba(217, 119, 6, 0.35)')
@section('header-bg-start', 'rgba(217, 119, 6, 0.06)')
@section('header-bg-end', 'rgba(234, 88, 12, 0.02)')
@section('header-bg-dark-start', 'rgba(217, 119, 6, 0.12)')
@section('header-bg-dark-end', 'rgba(234, 88, 12, 0.04)')

@section('logo', 'icon-192-amber.png')

@section('badge')
            <span class="badge">{{ __('messages.superadmin_title') }}</span>
@endsection

@section('intro', __('messages.email_superadmin_intro'))
@section('button-text', __('messages.email_superadmin_button'))
