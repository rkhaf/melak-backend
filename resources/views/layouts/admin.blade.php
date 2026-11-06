<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/melak.svg') }}">

    <title>@yield('title', 'Admin Dashboard') - Melak Admin</title>

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Leaflet.js CSS (For map visualizations) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- Style configuration -->
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        /* Hide scrollbar for Chrome, Safari and Opera */
        .sidebar-nav-scroll::-webkit-scrollbar {
            display: none;
        }
        /* Hide scrollbar for IE, Edge and Firefox */
        .sidebar-nav-scroll {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>

    <!-- Vite Styles and Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F3F4F6] text-[#1F2937] h-screen overflow-hidden flex flex-col">

    <!-- Mobile Backdrop -->
    <div id="sidebar-backdrop" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-30 lg:hidden"></div>

    <div class="flex flex-1 min-h-0 overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 lg:static bg-[#0F767E] text-white flex-shrink-0 flex flex-col shadow-2xl z-[9999]">
            <!-- Sidebar Header -->
            <div class="sb-header h-16 flex items-center justify-between px-4 bg-white/5 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('images/melak.svg') }}" alt="Logo Melak" class="w-9 h-9 rounded-lg bg-white p-1 object-contain shadow-md shrink-0">
                    <div class="sb-logo-text min-w-0">
                        <h1 class="font-extrabold text-lg leading-tight tracking-wider">Melak</h1>
                        <span class="text-[9px] text-[#E0F2F1]/80 uppercase tracking-widest font-semibold">Admin Panel</span>
                    </div>
                </a>
                <button type="button" id="sidebar-close-mobile" class="lg:hidden text-white/60 hover:text-white hover:bg-white/10 p-2 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Sidebar Nav Links -->
            <nav class="sidebar-nav-scroll flex-1 px-3 py-4 space-y-0.5 overflow-y-auto overflow-x-hidden">

                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" title="Dashboard" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.dashboard') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-chart-line w-5 text-center text-lg"></i>
                    <span class="sb-text">Dashboard</span>
                </a>

                <!-- Moderasi -->
                <div class="sb-section px-3 pt-5 pb-1.5">
                    <span class="text-[9px] uppercase tracking-widest text-white/35 font-bold">Moderasi</span>
                </div>

                <a href="{{ route('admin.forum.index') }}" title="Moderasi Forum" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.forum.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.forum.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-comments w-5 text-center text-lg"></i>
                    <span class="sb-text">Moderasi Forum</span>
                </a>

                <a href="{{ route('admin.reports.index') }}" title="Laporan Forum" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.reports.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.reports.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-flag w-5 text-center text-lg"></i>
                    <span class="sb-text">Laporan Forum</span>
                </a>

                <!-- Master Data -->
                <div class="sb-section px-3 pt-5 pb-1.5">
                    <span class="text-[9px] uppercase tracking-widest text-white/35 font-bold">Master Data</span>
                </div>

                <a href="{{ route('admin.diseases.index') }}" title="Data Penyakit" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.diseases.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.diseases.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-virus w-5 text-center text-lg"></i>
                    <span class="sb-text">Data Penyakit</span>
                </a>

                <a href="{{ route('admin.tips.index') }}" title="Tips Harian" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.tips.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.tips.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-lightbulb w-5 text-center text-lg"></i>
                    <span class="sb-text">Tips Harian</span>
                </a>

                <a href="{{ route('admin.settings.index') }}" title="Pengaturan Aplikasi" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.settings.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.settings.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-sliders w-5 text-center text-lg"></i>
                    <span class="sb-text">Pengaturan App</span>
                </a>

                <!-- Manajemen -->
                <div class="sb-section px-3 pt-5 pb-1.5">
                    <span class="text-[9px] uppercase tracking-widest text-white/35 font-bold">Manajemen</span>
                </div>

                <a href="{{ route('admin.users.index') }}" title="Pengguna" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.users.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.users.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-users w-5 text-center text-lg"></i>
                    <span class="sb-text">Pengguna</span>
                </a>

                <a href="{{ route('admin.models.index') }}" title="Model ML" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.models.*') || request()->routeIs('admin.datasets.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.models.*') || request()->routeIs('admin.datasets.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-microchip w-5 text-center text-lg"></i>
                    <span class="sb-text">Model ML</span>
                </a>

                <!-- Laporan & Dukungan -->
                <div class="sb-section px-3 pt-5 pb-1.5">
                    <span class="text-[9px] uppercase tracking-widest text-white/35 font-bold">Laporan & Dukungan</span>
                </div>

                <a href="{{ route('admin.audit-logs.index') }}" title="Audit Log" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.audit-logs.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.audit-logs.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-clock-rotate-left w-5 text-center text-lg"></i>
                    <span class="sb-text">Audit Log</span>
                </a>

                <a href="{{ route('admin.contacts.index') }}" title="Kontak Penting" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.contacts.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.contacts.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-phone-flip w-5 text-center text-lg"></i>
                    <span class="sb-text">Kontak Penting</span>
                </a>

                <a href="{{ route('admin.faqs.index') }}" title="FAQ" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.faqs.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.faqs.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-circle-question w-5 text-center text-lg"></i>
                    <span class="sb-text">FAQ</span>
                </a>

                <!-- Pengaturan -->
                <div class="sb-section px-3 pt-5 pb-1.5">
                    <span class="text-[9px] uppercase tracking-widest text-white/35 font-bold">Pengaturan</span>
                </div>

                <a href="{{ route('admin.app-info.edit') }}" title="Konfigurasi Aplikasi" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.app-info.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.app-info.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-gears w-5 text-center text-lg"></i>
                    <span class="sb-text">Konfigurasi Aplikasi</span>
                </a>

                <a href="{{ route('admin.profile.edit') }}" title="Profil Saya" class="group sb-link relative flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.profile.*') ? 'bg-[#0E8C94] text-white shadow-lg shadow-[#0F767E]/30' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                    <span class="sb-indicator absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full bg-cyan-300 transition-opacity {{ request()->routeIs('admin.profile.*') ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }}"></span>
                    <i class="sb-icon fa-solid fa-user-gear w-5 text-center text-lg"></i>
                    <span class="sb-text">Profil Saya</span>
                </a>
            </nav>

            <!-- Sidebar Footer User Profile Info -->
            <div class="sb-footer p-3 bg-white/5 border-t border-white/10 flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0F767E] to-[#0E8C94] flex items-center justify-center text-white font-bold shrink-0 shadow-md">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="sb-profile-text min-w-0">
                        <p class="text-xs font-semibold truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-[#E0F2F1]/80 capitalize truncate">{{ Auth::user()->role }}</p>
                    </div>
                </div>
                <!-- Logout Trigger -->
                <form action="{{ route('admin.logout') }}" method="POST" class="sb-footer-logout inline">
                    @csrf
                    <button type="submit" title="Keluar" class="text-white/70 hover:text-red-400 p-2 hover:bg-white/10 rounded-lg transition-colors">
                        <i class="fa-solid fa-right-from-bracket text-base"></i>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0 overflow-hidden">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:px-8 shadow-sm z-10">
                <div class="flex items-center gap-3 md:gap-4">
                    <button type="button" id="sidebar-toggle" title="Buka/Tutup menu" class="text-[#0F767E] hover:bg-gray-100 p-2 rounded-lg transition-colors focus:outline-none">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <h2 class="text-base md:text-xl font-bold text-[#0F767E] truncate">@yield('header_title', 'Dashboard')</h2>
                </div>
                
                <div class="flex items-center gap-6">
                    <!-- Notification Icon -->
                    <div class="relative">
                        <button type="button" id="notif-btn" class="relative cursor-pointer hover:bg-gray-100 p-2 rounded-full transition-colors focus:outline-none">
                            <i class="fa-regular fa-bell text-xl text-gray-650"></i>
                            @if (($adminUnreadCount ?? 0) > 0)
                                <span class="absolute top-1.5 right-1.5 w-4 h-4 bg-[#F97316] text-white text-[9px] font-bold rounded-full border border-white flex items-center justify-center">
                                    {{ $adminUnreadCount }}
                                </span>
                            @endif
                        </button>

                        <!-- Notification Dropdown -->
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-2xl shadow-xl z-50 overflow-hidden">
                            <!-- Dropdown Header -->
                            <div class="px-5 py-3.5 border-b border-gray-100 bg-[#F9FAFB] flex items-center justify-between">
                                <span class="font-bold text-sm text-[#0F767E]">Antrean Moderasi</span>
                            </div>

                            <!-- Dropdown Body -->
                            <div class="max-h-[300px] overflow-y-auto divide-y divide-gray-100">
                                <div class="py-8 text-center text-gray-400 text-xs flex flex-col items-center gap-2">
                                    <i class="fa-solid fa-circle-check text-2xl text-emerald-500"></i>
                                    <span>Tidak ada konten yang menunggu moderasi.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 min-h-0 overflow-y-auto p-8">
                <!-- Alert Flash Messages -->
                @if (session('success'))
                    <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3 text-emerald-800 text-sm">
                            <i class="fa-solid fa-circle-check text-lg text-emerald-500"></i>
                            <span class="font-medium">{{ session('success') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @if (session('info'))
                    <div class="mb-6 p-4 bg-[#E0F2F1] border-l-4 border-[#0F767E] rounded-r-lg flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-3 text-[#0F767E] text-sm">
                            <i class="fa-solid fa-circle-info text-lg text-[#0F767E]"></i>
                            <span class="font-medium">{{ session('info') }}</span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-[#0F767E] hover:text-[#0a5258]">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-lg flex items-center justify-between shadow-sm">
                        <div class="flex flex-col text-rose-800 text-sm gap-1">
                            @foreach ($errors->all() as $error)
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-circle-exclamation text-lg text-rose-500"></i>
                                    <span class="font-medium">{{ $error }}</span>
                                </div>
                            @endforeach
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Leaflet.js Library (For maps) -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    @yield('scripts')
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const notifBtn = document.getElementById('notif-btn');
            const notifDropdown = document.getElementById('notif-dropdown');

            if (notifBtn && notifDropdown) {
                notifBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('hidden');
                });

                document.addEventListener('click', function (e) {
                    if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                        notifDropdown.classList.add('hidden');
                    }
                });
            }

            // ── Sidebar: desktop collapse + mobile off-canvas ─────────────
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const closeMobileBtn = document.getElementById('sidebar-close-mobile');

            const isMobile = () => window.innerWidth < 1024;
            const savedCollapseState = localStorage.getItem('melak_sidebar_collapsed');
            const collapseState = savedCollapseState === null
                ? localStorage.getItem('ngingu_sidebar_collapsed')
                : savedCollapseState;

            // Restore desktop collapse state
            if (sidebar && !isMobile() && collapseState === '1') {
                document.body.classList.add('sidebar-collapsed');
            }

            function openMobileSidebar() {
                sidebar.classList.add('mobile-open');
                backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeMobileSidebar() {
                sidebar.classList.remove('mobile-open');
                backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            if (sidebar && toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    if (isMobile()) {
                        if (sidebar.classList.contains('mobile-open')) {
                            closeMobileSidebar();
                        } else {
                            openMobileSidebar();
                        }
                    } else {
                        document.body.classList.toggle('sidebar-collapsed');
                        localStorage.setItem(
                            'melak_sidebar_collapsed',
                            document.body.classList.contains('sidebar-collapsed') ? '1' : '0'
                        );
                    }
                });
            }

            if (sidebar && closeMobileBtn) {
                closeMobileBtn.addEventListener('click', closeMobileSidebar);
            }

            if (backdrop) {
                backdrop.addEventListener('click', closeMobileSidebar);
            }

            window.addEventListener('resize', function () {
                if (!isMobile()) {
                    closeMobileSidebar();
                }
            });
        });
    </script>
</body>
</html>
