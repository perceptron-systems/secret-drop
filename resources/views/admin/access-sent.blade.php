@extends('layouts.app')

@section('noindex', true)
@section('title', __('messages.admin_link_sent_title'))

@section('content')
<x-auth-access-sent :description="__('messages.admin_link_sent_description')" color="violet" />
@endsection
