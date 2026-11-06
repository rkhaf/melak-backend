@extends('errors.layout')

@section('title', 'Terjadi Kesalahan')
@section('code', $code ?? 'Oops')
@section('icon', 'fa-triangle-exclamation')
@section('heading', 'Terjadi Kesalahan')
@section('message', 'Terjadi kesalahan saat memproses permintaan Anda. Silakan coba lagi beberapa saat lagi.')
