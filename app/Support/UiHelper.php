<?php

namespace App\Support;

class UiHelper
{
    public const SEVERITY = [
        'low' => ['label' => 'Rendah', 'text' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'hex' => '#22C55E'],
        'medium' => ['label' => 'Waspada', 'text' => 'text-amber-600', 'bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'hex' => '#FACC15'],
        'high' => ['label' => 'Siaga', 'text' => 'text-orange-600', 'bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'hex' => '#F97316'],
        'critical' => ['label' => 'Awas / Kritis', 'text' => 'text-red-600', 'bg' => 'bg-red-50', 'border' => 'border-red-200', 'hex' => '#DC2626'],
    ];

    public const STATUS = [
        'pending' => ['label' => 'Pending', 'text' => 'text-amber-600', 'bg' => 'bg-amber-50', 'border' => 'border-amber-200'],
        'verified' => ['label' => 'Terverifikasi', 'text' => 'text-blue-600', 'bg' => 'bg-blue-50', 'border' => 'border-blue-200'],
        'resolved' => ['label' => 'Selesai', 'text' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200'],
        'rejected' => ['label' => 'Ditolak', 'text' => 'text-rose-600', 'bg' => 'bg-rose-50', 'border' => 'border-rose-200'],
    ];

    public const RISK = [
        'low' => ['label' => 'Rendah', 'text' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200'],
        'medium' => ['label' => 'Sedang', 'text' => 'text-amber-600', 'bg' => 'bg-amber-50', 'border' => 'border-amber-200'],
        'high' => ['label' => 'Tinggi', 'text' => 'text-orange-600', 'bg' => 'bg-orange-50', 'border' => 'border-orange-200'],
        'critical' => ['label' => 'Kritis', 'text' => 'text-red-600', 'bg' => 'bg-red-50', 'border' => 'border-red-200'],
    ];

    public const SHELTER_STATUS = [
        'active' => ['label' => 'Aktif', 'text' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-200'],
        'inactive' => ['label' => 'Nonaktif', 'text' => 'text-gray-600', 'bg' => 'bg-gray-100', 'border' => 'border-gray-200'],
        'full' => ['label' => 'Penuh', 'text' => 'text-red-600', 'bg' => 'bg-red-50', 'border' => 'border-red-200'],
    ];

    public const CONTACT_TYPE = [
        'phone' => 'Telepon',
        'email' => 'Email',
        'website' => 'Website',
        'whatsapp' => 'WhatsApp',
        'other' => 'Lainnya',
    ];

    public const ROLE = [
        'superadmin' => ['label' => 'Super Admin', 'text' => 'text-purple-600', 'bg' => 'bg-purple-50', 'border' => 'border-purple-200'],
        'admin' => ['label' => 'Admin', 'text' => 'text-blue-600', 'bg' => 'bg-blue-50', 'border' => 'border-blue-200'],
        'user' => ['label' => 'Warga', 'text' => 'text-gray-600', 'bg' => 'bg-gray-100', 'border' => 'border-gray-200'],
    ];

    public static function severity(string $key): array
    {
        return self::SEVERITY[$key] ?? self::SEVERITY['low'];
    }

    public static function status(string $key): array
    {
        return self::STATUS[$key] ?? self::STATUS['pending'];
    }

    public static function risk(string $key): array
    {
        return self::RISK[$key] ?? self::RISK['medium'];
    }

    public static function shelterStatus(string $key): array
    {
        return self::SHELTER_STATUS[$key] ?? self::SHELTER_STATUS['inactive'];
    }

    public static function role(string $key): array
    {
        return self::ROLE[$key] ?? self::ROLE['user'];
    }

    public static function contactType(string $key): string
    {
        return self::CONTACT_TYPE[$key] ?? $key;
    }
}
