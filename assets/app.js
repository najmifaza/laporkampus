// LaporKampus — validasi klien, pratinjau foto, filter tabel
(function () {
  'use strict';

  // Validasi form laporan (server tetap memvalidasi ulang)
  const form = document.querySelector('form[data-validasi]');
  if (form) {
    form.addEventListener('submit', function (ev) {
      const err = [];
      const judul = form.querySelector('#judul');
      const deskripsi = form.querySelector('#deskripsi');
      const lokasi = form.querySelector('#lokasi');
      const kategori = form.querySelector('#kategori_id');

      if (kategori && !kategori.value) err.push('Pilih kategori kerusakan.');
      if (judul && judul.value.trim().length < 10) err.push('Judul minimal 10 karakter.');
      if (judul && judul.value.trim().length > 120) err.push('Judul maksimal 120 karakter.');
      if (deskripsi && deskripsi.value.trim().length < 20) err.push('Deskripsi minimal 20 karakter.');
      if (lokasi && lokasi.value.trim().length < 3) err.push('Lokasi wajib diisi.');

      const foto = form.querySelector('#foto');
      if (foto && foto.files && foto.files[0]) {
        const f = foto.files[0];
        if (!['image/jpeg', 'image/png'].includes(f.type)) err.push('Foto harus JPG atau PNG.');
        if (f.size > 2 * 1024 * 1024) err.push('Ukuran foto maksimal 2 MB.');
      }

      if (err.length) {
        ev.preventDefault();
        tampilError(err);
      }
    });
  }

  function tampilError(pesan) {
    let box = document.querySelector('#error-klien');
    if (!box) {
      box = document.createElement('div');
      box.id = 'error-klien';
      box.className = 'flash flash-error mb-4';
      const target = document.querySelector('form[data-validasi]');
      target.parentNode.insertBefore(box, target);
    }
    box.innerHTML = pesan.map(function (p) { return '&bull; ' + p; }).join('<br>');
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  // Pratinjau foto sebelum unggah
  const inputFoto = document.querySelector('#foto');
  const pratinjau = document.querySelector('#pratinjau-foto');
  if (inputFoto && pratinjau) {
    inputFoto.addEventListener('change', function () {
      const f = inputFoto.files && inputFoto.files[0];
      if (!f) { pratinjau.innerHTML = ''; return; }
      const img = document.createElement('img');
      img.className = 'mt-2 h-40 w-auto rounded-lg border border-line object-cover';
      img.alt = 'Pratinjau foto laporan';
      img.src = URL.createObjectURL(f);
      pratinjau.innerHTML = '';
      pratinjau.appendChild(img);
    });
  }

  // Hitung karakter deskripsi
  const deskripsi = document.querySelector('#deskripsi');
  const hitung = document.querySelector('#hitung-deskripsi');
  if (deskripsi && hitung) {
    const perbarui = function () { hitung.textContent = deskripsi.value.length + ' karakter'; };
    deskripsi.addEventListener('input', perbarui);
    perbarui();
  }

  // Filter tabel admin tanpa reload
  const filterCari = document.querySelector('#filter-cari');
  if (filterCari) {
    filterCari.addEventListener('input', function () {
      const q = filterCari.value.toLowerCase();
      document.querySelectorAll('[data-cari]').forEach(function (row) {
        row.style.display = row.dataset.cari.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }
})();
