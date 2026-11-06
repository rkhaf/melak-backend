@extends('layouts.admin')

@section('title', 'Tambah Tips')
@section('header_title', 'Tambah Tips')

@section('content')
<div class="mb-6">
    <h3 class="text-lg font-extrabold text-[#1F2937]">Tambah Tips</h3>
    <p class="text-sm text-gray-400">Tips baru akan ikut dalam rotasi harian bila aktif</p>
</div>

<form action="{{ route('admin.tips.store') }}" method="POST">
    @include('admin.tips._form')
</form>
@endsection
