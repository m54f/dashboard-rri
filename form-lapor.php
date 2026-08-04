<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pelaporan Kendala - RRI Malang</title>
    <!-- Tailwind CSS untuk styling modern -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-f-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-6 md:p-8">
        <!-- Header -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800">RRI Malang</h1>
            <p class="text-sm text-slate-500 mt-1">Formulir Pelaporan Kendala Teknis / Layanan</p>
        </div>

        <!-- Formulir -->
        <form id="reportForm" onsubmit="handleSubmit(event)" class="space-y-4">
            <!-- Nama Pelapor -->
            <div>
                <label for="nama" class="block text-sm font-medium text-slate-700 mb-1">Nama Pelapor</label>
                <input type="text" id="nama" name="nama" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                    placeholder="Masukkan nama lengkap">
            </div>

            <!-- Tempat -->
            <div>
                <label for="tempat" class="block text-sm font-medium text-slate-700 mb-1">Tempat / Lokasi Kendala</label>
                <input type="text" id="tempat" name="tempat" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm"
                    placeholder="Contoh: Studio 1 / Bagian Pemberitaan">
            </div>

            <!-- Kendala -->
            <div>
                <label for="kendala" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi Kendala</label>
                <textarea id="kendala" name="kendala" rows="4" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm resize-none"
                    placeholder="Jelaskan kendala yang dialami secara singkat..."></textarea>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center space-x-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm shadow-sm">
                    Submit
                </button>
                <button type="button" onclick="clearInput()"
                    class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium py-2 px-4 rounded-lg transition duration-200 text-sm">
                    Clear Input
                </button>
            </div>
        </form>

        <!-- Pesan Sukses (Opsional) -->
        <div id="successMessage" class="hidden mt-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg text-center">
            Laporan berhasil dikirim!
        </div>
    </div>

    <!-- Script Sederhana -->
    <script>
        function handleSubmit(event) {
            event.preventDefault(); // Mencegah reload halaman
            
            // Ambil nilai dari input (bisa diintegrasikan ke backend/API jika diperlukan)
            const nama = document.getElementById('nama').value;
            const tempat = document.getElementById('tempat').value;
            const kendala = document.getElementById('kendala').value;

            // Tampilkan pesan sukses sementara
            const successBox = document.getElementById('successMessage');
            successBox.classList.remove('hidden');

            // Reset form setelah beberapa detik
            setTimeout(() => {
                clearInput();
                successBox.classList.add('hidden');
            }, 3000);
        }

        function clearInput() {
            document.getElementById('reportForm').reset();
            document.getElementById('successMessage').classList.add('hidden');
        }
    </script>

</body>
</html>