<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pelaporan Kendala - RRI Malang</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fdf2f2',
                            100: '#fbe8e8',
                            500: '#e31b23',
                            600: '#c81019',
                            700: '#a70d14',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 dark:bg-gradient-to-br dark:from-slate-900 dark:via-slate-800 dark:to-zinc-900 min-h-screen flex items-center justify-center p-4 text-slate-800 dark:text-slate-100 transition-colors duration-300">

    <!-- Background Glow Effects (Dark Mode Only) -->
    <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-brand-600/10 rounded-full blur-3xl pointer-events-none hidden dark:block"></div>
    <div class="absolute bottom-1/4 right-1/4 w-72 h-72 bg-blue-600/10 rounded-full blur-3xl pointer-events-none hidden dark:block"></div>

    <div class="relative max-w-lg w-full bg-white dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl dark:shadow-2xl overflow-hidden p-8 transition-colors duration-300">
        
        <!-- Top Indicator Bar -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-brand-600 via-rose-500 to-amber-500"></div>

        <!-- Header Section & Theme Toggle -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-xl bg-brand-50 dark:bg-brand-600/20 border border-brand-200 dark:border-brand-500/30 flex items-center justify-center text-brand-600 dark:text-brand-500 text-xl font-bold">
                    <i class="fa-solid fa-tower-broadcast"></i>
                </div>
                <div>
                    <span class="text-xs font-semibold tracking-wider uppercase text-brand-600 dark:text-brand-500 bg-brand-50 dark:bg-brand-500/10 px-2.5 py-1 rounded-full">Layanan Teknis</span>
                    <h1 class="text-2xl font-bold tracking-tight mt-1">RRI Malang</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pusat Pelaporan Kendala Sistem & Operasional</p>
                </div>
            </div>

            <!-- Theme Toggle Button -->
            <button onclick="toggleTheme()" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all duration-200 shadow-sm" title="Ganti Tema">
                <i id="themeIcon" class="fa-solid fa-moon text-sm"></i>
            </button>
        </div>

        <!-- Formulir -->
        <form id="reportForm" onsubmit="handleSubmit(event)" class="space-y-5">
            <!-- Nama Pelapor -->
            <div class="space-y-1.5">
                <label for="nama" class="block text-xs font-semibold tracking-wider uppercase text-slate-500 dark:text-slate-400">Nama Pelapor</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                        <i class="fa-regular fa-user text-sm"></i>
                    </span>
                    <input type="text" id="nama" name="nama" required
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm placeholder-slate-400 dark:placeholder-slate-500 transition-all duration-200"
                        placeholder="Masukkan nama lengkap">
                </div>
            </div>

            <!-- Tempat -->
            <div class="space-y-1.5">
                <label for="tempat" class="block text-xs font-semibold tracking-wider uppercase text-slate-500 dark:text-slate-400">Tempat / Lokasi Kendala</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 dark:text-slate-500">
                        <i class="fa-solid fa-location-dot text-sm"></i>
                    </span>
                    <input type="text" id="tempat" name="tempat" required
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm placeholder-slate-400 dark:placeholder-slate-500 transition-all duration-200"
                        placeholder="Contoh: Studio Siaran / Ruang Transmisi">
                </div>
            </div>

            <!-- Kendala -->
            <div class="space-y-1.5">
                <label for="kendala" class="block text-xs font-semibold tracking-wider uppercase text-slate-500 dark:text-slate-400">Deskripsi Kendala</label>
                <div class="relative">
                    <span class="absolute top-3.5 left-4 text-slate-400 dark:text-slate-500">
                        <i class="fa-regular fa-comment-dots text-sm"></i>
                    </span>
                    <textarea id="kendala" name="kendala" rows="4" required
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm placeholder-slate-400 dark:placeholder-slate-500 resize-none transition-all duration-200"
                        placeholder="Jelaskan kendala teknis atau layanan yang dialami..."></textarea>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center space-x-3 pt-3">
                <button type="submit"
                    class="flex-1 bg-brand-600 hover:bg-brand-500 active:scale-[0.98] text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 text-sm shadow-lg shadow-brand-600/30 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Kirim Laporan</span>
                </button>
                <button type="button" onclick="clearInput()"
                    class="flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold py-3 px-4 rounded-xl transition-all duration-200 text-sm border border-slate-200 dark:border-slate-700/80 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-rotate-right text-xs"></i>
                    <span>Clear Input</span>
                </button>
            </div>
        </form>

        <!-- Pesan Sukses -->
        <div id="successMessage" class="hidden mt-6 p-4 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-sm rounded-xl flex items-center space-x-3">
            <i class="fa-solid fa-circle-check text-lg"></i>
            <div>
                <p class="font-semibold">Berhasil Dikirim!</p>
                <p class="text-xs opacity-80">Laporan kendala Anda telah diteruskan ke tim terkait.</p>
            </div>
        </div>
    </div>

    <!-- Script Fungsionalitas -->
    <script>
        // Cek preferensi tema saat halaman dimuat
        const htmlElement = document.documentElement;
        const themeIcon = document.getElementById('themeIcon');

        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlElement.classList.add('dark');
            themeIcon.className = 'fa-solid fa-sun text-sm';
        } else {
            htmlElement.classList.remove('dark');
            themeIcon.className = 'fa-solid fa-moon text-sm';
        }

        // Fungsi toggle tema Light/Dark
        function toggleTheme() {
            if (htmlElement.classList.contains('dark')) {
                htmlElement.classList.remove('dark');
                localStorage.theme = 'light';
                themeIcon.className = 'fa-solid fa-moon text-sm';
            } else {
                htmlElement.classList.add('dark');
                localStorage.theme = 'dark';
                themeIcon.className = 'fa-solid fa-sun text-sm';
            }
        }

        function handleSubmit(event) {
            event.preventDefault();
            
            const successBox = document.getElementById('successMessage');
            successBox.classList.remove('hidden');

            setTimeout(() => {
                clearInput();
            }, 3500);
        }

        function clearInput() {
            document.getElementById('reportForm').reset();
            document.getElementById('successMessage').classList.add('hidden');
        }
    </script>

</body>
</html>