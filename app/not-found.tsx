import Link from 'next/link';

export default function NotFound() {
  return (
    <div className="min-h-screen bg-[#0b1120] text-slate-100 flex flex-col items-center justify-center p-4">
      <div className="text-center max-w-md">
        <span className="text-5xl font-black text-amber-500 mb-4 block">404</span>
        <h1 className="text-2xl font-bold text-white mb-2">Halaman Tidak Ditemukan</h1>
        <p className="text-sm text-slate-400 mb-6">
          Halaman yang Anda cari tidak ditemukan atau telah dipindahkan.
        </p>
        <Link
          href="/"
          className="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2.5 rounded-lg text-sm transition-colors"
        >
          Kembali ke Beranda
        </Link>
      </div>
    </div>
  );
}
