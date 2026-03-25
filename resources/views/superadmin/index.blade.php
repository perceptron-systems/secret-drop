@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.superadmin_title'))

@section('content')
<x-auth-login
    :title="__('messages.superadmin_title')"
    :description="__('messages.superadmin_description') ?: null"
    :formAction="route('superadmin.requestAccess')"
    color="amber"
    iconName="icon.shield-check"
/>
@endsection
