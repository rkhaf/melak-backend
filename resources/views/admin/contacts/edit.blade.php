@extends('layouts.admin')

@section('title', 'Edit Kontak Darurat')
@section('header_title', 'Edit Kontak Darurat')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.contacts.index') }}" class="text-sm text-gray-400 hover:text-gray-600"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke daftar</a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-8">
    <h3 class="text-lg font-extrabold text-[#1F2937] mb-6"><i class="fa-solid fa-pen text-[#0F767E] mr-2"></i> Edit Kontak: {{ $contact->title }}</h3>
    <form action="{{ route('admin.contacts.update', $contact) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.contacts._form')
    </form>
</div>
@endsection
