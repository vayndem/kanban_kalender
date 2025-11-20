@extends('layouts.masters.master')

@section('title', 'Selamat Datang')

@section('content')
    <div class="flex items-center justify-center" style="height: 60vh;">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-800 dark:text-white mb-4">
                Selamat Datang di Aplikasi Jadwal
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300">
                Silakan kelola jadwal Anda melalui tombol Kanban di atas.
            </p>
        </div>
    </div>
@endsection
