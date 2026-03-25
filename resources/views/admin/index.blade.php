@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_title'))

@section('content')
<x-auth-login
    :title="__('messages.admin_title')"
    :description="__('messages.admin_description')"
    :formAction="route('admin.requestAccess')"
    color="violet"
    iconName="icon.settings"
/>
@endsection
