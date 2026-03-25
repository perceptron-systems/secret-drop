@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_invalid_link_title'))

@section('content')
<x-auth-invalid-link
    :retryRoute="route('superadmin.index')"
    :retryLabel="__('messages.btn_retry')"
    color="amber"
    iconColor="red"
    iconName="icon.warning"
/>
@endsection
