'use client';

import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import {
  Bike,
  Shield,
  Clock,
  MapPin,
  CheckCircle2,
  AlertTriangle,
  ArrowRight,
  Phone,
  Sparkles,
  Search,
  SlidersHorizontal,
  FileCode2,
  X,
  ExternalLink,
  ChevronRight,
  Info
} from 'lucide-react';

interface MotorUnit {
  id: string;
  name: string;
  category: 'beat-series' | 'scoopy-vario' | 'trail-adventure';
  categoryName: string;
  engineCc: number;
  transmission: string;
  routeCharacter: string;
  isBromoReady: boolean;
  statusLabel: 'Tersedia' | 'Booking Menipis' | 'Penuh';
  priceDaily: number;
  priceWeekly: number;
  priceMonthly: number;
  description: string;
  plateNumbers: string[];
}

const FLEET_DATA: MotorUnit[] = [
  {
    id: 'beat-deluxe',
    name: 'Honda BeAT Deluxe',
    category: 'beat-series',
    categoryName: 'Honda BeAT Series',
    engineCc: 110,
    transmission: 'Otomatis (CVT)',
    routeCharacter: 'Lincah & Sangat Irit',
    isBromoReady: false,
    statusLabel: 'Tersedia',
    priceDaily: 85000,
    priceWeekly: 500000,
    priceMonthly: 1600000,
    description: 'Bodi ramping memudahkan bermanuver di gang kuliner, kawasan kampus Dinoyo-Suhat, hingga pusat oleh-oleh tanpa khawatir macet atau boros BBM.',
    plateNumbers: ['N 2481 ABC', 'N 3192 DEF', 'N 4820 GHI']
  },
  {
    id: 'beat-cbs',
    name: 'Honda BeAT CBS',
    category: 'beat-series',
    categoryName: 'Honda BeAT Series',
    engineCc: 110,
    transmission: 'Otomatis (CVT)',
    routeCharacter: 'Lincah & Irit Dalam Kota',
    isBromoReady: false,
    statusLabel: 'Tersedia',
    priceDaily: 80000,
    priceWeekly: 480000,
    priceMonthly: 1500000,
    description: 'Pilihan paling hemat untuk mobilitas harian keliling kota Malang. Mesin eSP generasi terbaru sangat responsif dan efisien.',
    plateNumbers: ['N 1823 JKL', 'N 5912 MNO']
  },
  {
    id: 'beat-street',
    name: 'Honda BeAT Street',
    category: 'beat-series',
    categoryName: 'Honda BeAT Series',
    engineCc: 110,
    transmission: 'Otomatis (CVT)',
    routeCharacter: 'Lincah & Naked Handlebar',
    isBromoReady: false,
    statusLabel: 'Booking Menipis',
    priceDaily: 85000,
    priceWeekly: 500000,
    priceMonthly: 1600000,
    description: 'Tampilan sporty dengan stang telanjang (naked handlebar) yang ergonomis dan gagah untuk eksplorasi wisata perkotaan.',
    plateNumbers: ['N 6204 PQR']
  },
  {
    id: 'scoopy',
    name: 'Honda Scoopy',
    category: 'scoopy-vario',
    categoryName: 'Honda Scoopy & Vario',
    engineCc: 110,
    transmission: 'Otomatis (CVT)',
    routeCharacter: 'Nyaman & Stylish Retro',
    isBromoReady: false,
    statusLabel: 'Tersedia',
    priceDaily: 95000,
    priceWeekly: 570000,
    priceMonthly: 1800000,
    description: 'Desain retro modern yang sangat fotogenik untuk liburan. Pijakan kaki luas dan bagasi lega pas untuk jalan-jalan santai.',
    plateNumbers: ['N 4108 STU', 'N 7321 VWX']
  },
  {
    id: 'vario-125',
    name: 'Honda Vario 125',
    category: 'scoopy-vario',
    categoryName: 'Honda Scoopy & Vario',
    engineCc: 125,
    transmission: 'Otomatis (CVT)',
    routeCharacter: 'Nyaman & Bagasi Lega',
    isBromoReady: false,
    statusLabel: 'Tersedia',
    priceDaily: 100000,
    priceWeekly: 600000,
    priceMonthly: 1900000,
    description: 'Kapasitas 125 cc berpendingin cairan sangat bertenaga diajak menanjak menuju Alun-Alun Batu, Selecta, maupun Coban Rondo.',
    plateNumbers: ['N 3982 YZA', 'N 5120 BCD']
  },
  {
    id: 'vario-160',
    name: 'Honda Vario 160',
    category: 'scoopy-vario',
    categoryName: 'Honda Scoopy & Vario',
    engineCc: 160,
    transmission: 'Otomatis (CVT)',
    routeCharacter: 'Nyaman, Bertenaga, Stabil',
    isBromoReady: false,
    statusLabel: 'Booking Menipis',
    priceDaily: 130000,
    priceWeekly: 780000,
    priceMonthly: 2400000,
    description: 'Mesin 160 cc 4-katup dengan rangka eSAF yang stabil untuk berboncengan jarak jauh melintasi kontur perbukitan Malang Raya.',
    plateNumbers: ['N 6742 EFG']
  },
  {
    id: 'crf-150l',
    name: 'Trail CRF 150L',
    category: 'trail-adventure',
    categoryName: 'Trail Adventure (Bromo)',
    engineCc: 150,
    transmission: 'Manual 5-Speed',
    routeCharacter: 'Adventure (Wajib Bromo)',
    isBromoReady: true,
    statusLabel: 'Tersedia',
    priceDaily: 250000,
    priceWeekly: 1500000,
    priceMonthly: 4500000,
    description: 'Armada resmi dan WAJIB untuk trip petualangan ke Lautan Pasir Kaldera Gunung Bromo. Suspensi Showa upside-down dan ban dual-purpose siap melibas medan ekstrem.',
    plateNumbers: ['N 8910 HIJ', 'N 9021 KLM']
  }
];

export default function HomePage() {
  const [selectedCategory, setSelectedCategory] = useState<'all' | 'beat-series' | 'scoopy-vario' | 'trail-adventure'>('all');
  const [activeModalMotor, setActiveModalMotor] = useState<MotorUnit | null>(null);
  const [showCodeInspector, setShowCodeInspector] = useState(false);

  // Booking Form State (TASK-011)
  const [tripDestination, setTripDestination] = useState<'malang_batu' | 'bromo'>('malang_batu');
  const [selectedMotorId, setSelectedMotorId] = useState<string>('beat-deluxe');
  const [pickupLocation, setPickupLocation] = useState<string>('Pool Dinoyo');
  const [startDateTime, setStartDateTime] = useState<string>('2026-10-02T08:30');
  const [endDateTime, setEndDateTime] = useState<string>('2026-10-04T17:00');
  const [customerName, setCustomerName] = useState<string>('');
  const [customerWa, setCustomerWa] = useState<string>('');
  const [customerEmergency, setCustomerEmergency] = useState<string>('');
  const [customerKtpAddress, setCustomerKtpAddress] = useState<string>('');
  const [customerStayAddress, setCustomerStayAddress] = useState<string>('');
  const [rentalNotes, setRentalNotes] = useState<string>('Butuh 2 helm ukuran L dan jas hujan setelan.');
  const [bookingSuccessNotice, setBookingSuccessNotice] = useState<boolean>(false);

  const filteredFleet = FLEET_DATA.filter((motor) => {
    if (selectedCategory === 'all') return true;
    return motor.category === selectedCategory;
  });

  // Handle route change: Bromo locks selection to Trail CRF 150L
  const handleDestinationChange = (dest: 'malang_batu' | 'bromo') => {
    setTripDestination(dest);
    if (dest === 'bromo') {
      setSelectedMotorId('crf-150l');
    }
  };

  // Find currently selected motor in form
  const currentMotor = FLEET_DATA.find((m) => m.id === selectedMotorId) || FLEET_DATA[0];

  // Duration & Pricing Calculation
  const calculateDurationAndPrice = () => {
    const start = new Date(startDateTime);
    const end = new Date(endDateTime);

    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
      return { days: 0, hours: 0, totalPrice: 0, isValid: false };
    }

    const diffMs = end.getTime() - start.getTime();
    const hours = Math.round((diffMs / (1000 * 60 * 60)) * 10) / 10;

    let days = 1;
    if (hours <= 26) {
      days = 1;
    } else {
      const extraHours = hours - 24;
      days = 1 + Math.ceil(Math.max(0, extraHours - 2) / 24);
    }

    const dailyRate = currentMotor.priceDaily;
    const totalPrice = days * dailyRate;

    return { days, hours, totalPrice, isValid: true };
  };

  const { days, hours, totalPrice, isValid } = calculateDurationAndPrice();

  // Construct structured WhatsApp booking text according to Blueprint §8
  const buildWhatsAppBookingUrl = () => {
    const text = `Halo Admin Ryokourent, saya ingin melakukan pemesanan sewa motor dengan rincian berikut:

📋 DATA PENYEWA
• Nama Lengkap   : ${customerName || 'Belum diisi'}
• Alamat KTP     : ${customerKtpAddress || 'Sesuai KTP'}
• Tempat Menginap: ${customerStayAddress || 'Malang/Batu'}
• No. WhatsApp   : ${customerWa || '08...'}
• No. Darurat    : ${customerEmergency || '08...'} (Keluarga)

🛵 UNIT & LOKASI
• Unit Motor     : ${currentMotor.name}
• Rute Tujuan    : ${tripDestination === 'bromo' ? 'Trip Kaldera Bromo (Trail CRF 150L)' : 'Malang Kota & Wisata Batu'}
• Lokasi Ambil   : ${pickupLocation}

⏱️ JADWAL SEWA
• Mulai Sewa     : ${startDateTime.replace('T', ' ')} WIB
• Selesai Sewa   : ${endDateTime.replace('T', ' ')} WIB
• Estimasi Durasi: ${days} Hari (~${Math.round(hours)} Jam)
• Estimasi Biaya : Rp ${totalPrice.toLocaleString('id-ID')}

📝 CATATAN TAMBAHAN:
${rentalNotes || '2 Helm SNI + Jas Hujan'}

Mohon konfirmasi ketersediaan slot armada dan instruksi pembayaran jaminan. Terima kasih!`;

    return `https://api.whatsapp.com/send?phone=62895384017772&text=${encodeURIComponent(text)}`;
  };

  const handleSelectMotorFromCard = (motor: MotorUnit) => {
    setSelectedMotorId(motor.id);
    if (motor.isBromoReady) {
      setTripDestination('bromo');
    } else {
      setTripDestination('malang_batu');
    }
    const formEl = document.getElementById('booking-form');
    if (formEl) {
      formEl.scrollIntoView({ behavior: 'smooth' });
    }
  };


  const getWaLink = (motorName?: string) => {
    const text = motorName
      ? `Halo Admin Ryokourent, saya ingin menyewa unit ${motorName} di Malang/Batu. Mohon info ketersediaan slot.`
      : 'Halo Admin Ryokourent, saya ingin tanya informasi sewa motor di Malang & Batu.';
    return `https://api.whatsapp.com/send?phone=62895384017772&text=${encodeURIComponent(text)}`;
  };

  return (
    <div className="min-h-screen bg-[#0b1120] text-slate-100 font-sans selection:bg-amber-500 selection:text-slate-900">
      {/* Top Bar Navigation */}
      <header className="sticky top-0 z-40 bg-[#0b1120]/90 backdrop-blur-md border-b border-[#223249]">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="text-2xl font-black tracking-tight text-white flex items-center gap-2">
              <span className="w-8 h-8 rounded-lg bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-lg">
                R
              </span>
              Ryokourent
            </span>
            <span className="hidden md:inline-flex text-xs uppercase tracking-wider font-semibold text-amber-500/90 bg-amber-500/10 px-2.5 py-0.5 rounded-full border border-amber-500/20">
              Malang & Batu
            </span>
          </div>

          <nav className="hidden lg:flex items-center gap-6 text-sm font-medium text-slate-300">
            <a href="#katalog-motor" className="hover:text-amber-400 transition-colors">
              Katalog Armada
            </a>
            <a href="#keunggulan" className="hover:text-amber-400 transition-colors">
              Keunggulan
            </a>
            <a href="#lokasi-pool" className="hover:text-amber-400 transition-colors">
              Lokasi Pool
            </a>
            <a href="#syarat-faq" className="hover:text-amber-400 transition-colors">
              Syarat & FAQ
            </a>
          </nav>

          <div className="flex items-center gap-3">
            <button
              onClick={() => setShowCodeInspector(true)}
              className="text-xs bg-[#162032] border border-[#223249] text-slate-300 hover:text-amber-400 px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors"
              title="Lihat status arsitektur kode WordPress plugin & child theme"
            >
              <FileCode2 className="w-4 h-4 text-amber-500" />
              <span className="hidden sm:inline">WordPress Core</span>
            </button>
            <a
              href={getWaLink()}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 bg-[#25d366] hover:bg-[#128c7e] text-white text-xs sm:text-sm font-bold px-3.5 sm:px-4 py-2 rounded-lg transition-all shadow-lg shadow-emerald-950/30"
            >
              <Phone className="w-4 h-4" />
              <span>Booking WA</span>
            </a>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <section className="relative overflow-hidden pt-12 pb-20 sm:pt-20 sm:pb-28 border-b border-[#223249]/80">
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-amber-500/10 via-[#0b1120] to-[#0b1120] pointer-events-none" />
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
          <div className="max-w-3xl">
            <div className="inline-flex items-center gap-2 bg-amber-500/10 border border-amber-500/30 text-amber-400 px-3 py-1 rounded-full text-xs font-bold tracking-wide uppercase mb-6">
              <Sparkles className="w-3.5 h-3.5" />
              Rental Motor Resmi Malang & Kota Wisata Batu
            </div>
            <h1 className="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-[1.15] mb-6">
              Eksplorasi Malang & Wisata Batu Lebih Bebas, Praktis, dan Tanpa Macet.
            </h1>
            <p className="text-base sm:text-lg text-slate-300 leading-relaxed mb-8">
              Solusi sewa motor terpercaya untuk wisatawan, mahasiswa, dan mobilitas harian. Dari skutik lincah hemat BBM untuk keliling kota hingga motor Trail CRF 150L khusus petualangan Bromo, siap diantar ke lokasi Anda.
            </p>

            <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 mb-12">
              <a
                href="#katalog-motor"
                className="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-6 py-3.5 rounded-xl transition-all shadow-lg shadow-amber-500/20"
              >
                <span>Lihat Katalog Armada</span>
                <ArrowRight className="w-4 h-4" />
              </a>
              <a
                href={getWaLink()}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center justify-center gap-2 bg-[#162032] hover:bg-[#1e2c44] border border-[#223249] text-white font-semibold px-6 py-3.5 rounded-xl transition-all"
              >
                <Phone className="w-4 h-4 text-emerald-400" />
                <span>Konsultasi via WhatsApp</span>
              </a>
            </div>

            {/* 3 Trust Badges */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-[#223249] pt-8">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                  <MapPin className="w-5 h-5" />
                </div>
                <div>
                  <div className="text-xs text-slate-400 font-medium">2 Pool Resmi</div>
                  <div className="text-sm font-bold text-white">Dinoyo & Kota Batu</div>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                  <Clock className="w-5 h-5" />
                </div>
                <div>
                  <div className="text-xs text-slate-400 font-medium">Jam Pelayanan</div>
                  <div className="text-sm font-bold text-white">07.00 – 23.00 WIB</div>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 shrink-0">
                  <Shield className="w-5 h-5" />
                </div>
                <div>
                  <div className="text-xs text-slate-400 font-medium">Fasilitas Lengkap</div>
                  <div className="text-sm font-bold text-white">2 Helm SNI + 2 Jas Hujan</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Fleet Catalog Section */}
      <section id="katalog-motor" className="py-16 sm:py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-3xl mx-auto mb-12">
            <span className="inline-block text-xs font-bold text-amber-400 tracking-widest uppercase bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full mb-3">
              Pilihan Armada Terbaik
            </span>
            <h2 className="text-2xl sm:text-4xl font-extrabold text-white mb-4">
              Katalog Armada Sepeda Motor Ryokourent
            </h2>
            <p className="text-slate-400 text-sm sm:text-base">
              Semua unit dalam kondisi prima, rutin servis berkala di bengkel resmi Honda, ban tebal, serta dilengkapi 2 helm SNI steril dan 2 jas hujan setelan.
            </p>
          </div>

          {/* Category Tabs */}
          <div className="flex items-center justify-start sm:justify-center gap-2 overflow-x-auto pb-4 mb-10 scrollbar-none">
            {[
              { id: 'all', label: 'Semua Unit' },
              { id: 'beat-series', label: 'Honda BeAT Series' },
              { id: 'scoopy-vario', label: 'Honda Scoopy & Vario' },
              { id: 'trail-adventure', label: 'Trail Adventure (Bromo)' }
            ].map((tab) => (
              <button
                key={tab.id}
                onClick={() => setSelectedCategory(tab.id as any)}
                className={`whitespace-nowrap px-4 py-2 rounded-full text-xs sm:text-sm font-bold transition-all ${
                  selectedCategory === tab.id
                    ? 'bg-amber-500 text-slate-950 shadow-md shadow-amber-500/30'
                    : 'bg-[#162032] border border-[#223249] text-slate-300 hover:text-white hover:border-slate-600'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>

          {/* Motor Grid */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredFleet.map((motor) => (
              <motion.article
                layout
                key={motor.id}
                initial={{ opacity: 0, y: 15 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: 10 }}
                className="bg-[#162032] border border-[#223249] hover:border-[#334868] rounded-2xl overflow-hidden flex flex-col group transition-all duration-300 hover:shadow-xl hover:shadow-black/40"
              >
                {/* Media Image & Badges */}
                <div className="relative aspect-[16/10] bg-slate-900 overflow-hidden flex items-center justify-center">
                  <div className="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-[#131c2d] to-[#1e2c44] text-slate-400 group-hover:scale-105 transition-transform duration-300">
                    <Bike className="w-16 h-16 text-amber-500/80 mb-2" />
                    <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
                      {motor.name}
                    </span>
                  </div>

                  {/* Top Badges */}
                  <div className="absolute top-3 left-3 right-3 flex items-center justify-between pointer-events-none">
                    <span
                      className={`text-[11px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1.5 backdrop-blur-md ${
                        motor.statusLabel === 'Tersedia'
                          ? 'bg-emerald-500/90 text-white'
                          : motor.statusLabel === 'Booking Menipis'
                          ? 'bg-amber-500/90 text-slate-950'
                          : 'bg-rose-500/90 text-white'
                      }`}
                    >
                      <span className="w-1.5 h-1.5 rounded-full bg-current" />
                      {motor.statusLabel}
                    </span>

                    {motor.isBromoReady ? (
                      <span className="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-amber-500 text-slate-950 border border-amber-300 flex items-center gap-1">
                        🌋 Bromo Ready
                      </span>
                    ) : (
                      <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-slate-900/80 text-slate-400 border border-slate-700">
                        Malang & Batu
                      </span>
                    )}
                  </div>
                </div>

                {/* Card Content */}
                <div className="p-5 flex flex-col flex-grow">
                  <div className="mb-3">
                    <span className="text-[11px] font-bold tracking-wider uppercase text-amber-400 block mb-1">
                      {motor.categoryName}
                    </span>
                    <h3 className="text-lg font-bold text-white group-hover:text-amber-400 transition-colors">
                      {motor.name}
                    </h3>
                  </div>

                  {/* Specs Box */}
                  <div className="grid grid-cols-2 gap-2 bg-[#0b1120]/70 border border-[#223249]/70 rounded-xl p-3 mb-3 text-xs">
                    <div>
                      <span className="text-[10px] text-slate-400 uppercase tracking-wider block">Mesin</span>
                      <strong className="text-slate-200">{motor.engineCc} cc</strong>
                    </div>
                    <div>
                      <span className="text-[10px] text-slate-400 uppercase tracking-wider block">Transmisi</span>
                      <strong className="text-slate-200 truncate block">{motor.transmission}</strong>
                    </div>
                    <div className="col-span-2 pt-1.5 border-t border-[#223249]/50">
                      <span className="text-[10px] text-slate-400 uppercase tracking-wider block">Karakter</span>
                      <strong className="text-slate-200">{motor.routeCharacter}</strong>
                    </div>
                  </div>

                  {/* Facilities Included */}
                  <div className="flex flex-wrap gap-1.5 mb-4 text-[11px] text-slate-300">
                    <span className="bg-white/5 border border-white/10 px-2 py-0.5 rounded">
                      🪖 2 Helm SNI
                    </span>
                    <span className="bg-white/5 border border-white/10 px-2 py-0.5 rounded">
                      🌧️ 2 Jas Hujan
                    </span>
                    <span className="bg-white/5 border border-white/10 px-2 py-0.5 rounded">
                      📱 Holder HP
                    </span>
                  </div>

                  {/* Pricing Breakdown */}
                  <div className="mt-auto pt-3 border-t border-[#223249] mb-4">
                    <div className="flex items-baseline justify-between mb-1">
                      <span className="text-xs text-slate-400">Tarif Harian (24 Jam)</span>
                      <div className="text-right">
                        <span className="text-base font-extrabold text-amber-400">
                          Rp {motor.priceDaily.toLocaleString('id-ID')}
                        </span>
                        <span className="text-[10px] text-slate-400 ml-1">/ 24 Jam</span>
                      </div>
                    </div>
                    <div className="flex justify-between text-[11px] text-slate-400">
                      <span>Mingguan: Rp {motor.priceWeekly.toLocaleString('id-ID')}</span>
                      <span>Bulanan: Rp {motor.priceMonthly.toLocaleString('id-ID')}</span>
                    </div>
                  </div>

                  {/* Dual Action CTA */}
                  <div className="grid grid-cols-2 gap-2">
                    <button
                      onClick={() => setActiveModalMotor(motor)}
                      className="inline-flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs py-2.5 px-3 rounded-lg transition-colors"
                    >
                      <Info className="w-3.5 h-3.5" />
                      <span>Detail Unit</span>
                    </button>
                    <a
                      href={getWaLink(motor.name)}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center justify-center gap-1.5 bg-[#25d366] hover:bg-[#128c7e] text-white font-bold text-xs py-2.5 px-3 rounded-lg transition-colors"
                    >
                      <Phone className="w-3.5 h-3.5" />
                      <span>Chat WA</span>
                    </a>
                  </div>
                </div>
              </motion.article>
            ))}
          </div>

          {/* Inclusions & Guarantees Banner */}
          <div className="mt-14 bg-[#162032]/80 border border-[#223249] rounded-2xl p-6 sm:p-8">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              <div className="flex items-start gap-3">
                <span className="text-2xl">🛡️</span>
                <div>
                  <h4 className="text-sm font-bold text-white mb-0.5">Unit Rutin Servis</h4>
                  <p className="text-xs text-slate-400 leading-relaxed">
                    Dicek oli, rem, ban, dan kelistrikan sebelum diserahkan ke pelanggan.
                  </p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <span className="text-2xl">🪖</span>
                <div>
                  <h4 className="text-sm font-bold text-white mb-0.5">Fasilitas Gratis</h4>
                  <p className="text-xs text-slate-400 leading-relaxed">
                    2 Helm SNI bersih/wangi + 2 jas hujan setelan tebal anti bocor.
                  </p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <span className="text-2xl">📍</span>
                <div>
                  <h4 className="text-sm font-bold text-white mb-0.5">2 Pool Strategis</h4>
                  <p className="text-xs text-slate-400 leading-relaxed">
                    Pool Dinoyo Malang (dekat kampus/stasiun) & Pool Diponegoro Batu.
                  </p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <span className="text-2xl">⏱️</span>
                <div>
                  <h4 className="text-sm font-bold text-white mb-0.5">Jam Kerja 07.00 – 23.00</h4>
                  <p className="text-xs text-slate-400 leading-relaxed">
                    Antar-ambil stasiun/hotel fleksibel menyesuaikan sikon lapangan.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Keunggulan Layanan (01–06) */}
      <section id="keunggulan" className="py-16 sm:py-24 bg-[#080d19] border-y border-[#223249]">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <span className="text-xs font-bold text-amber-400 tracking-widest uppercase bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full mb-3 inline-block">
              Kenapa Memilih Kami
            </span>
            <h2 className="text-2xl sm:text-4xl font-extrabold text-white mb-4">
              6 Keunggulan Layanan Sewa Motor Ryokourent
            </h2>
            <p className="text-slate-400 text-sm sm:text-base">
              Standar pelayanan profesional untuk kenyamanan liburan dan mobilitas harian Anda di wilayah Malang Raya & Kota Wisata Batu.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[
              {
                num: '01',
                title: 'Dua Pool Strategis (Dinoyo & Batu)',
                desc: 'Pool Dinoyo dekat kampus UB/UIN dan stasiun kota baru, sedangkan Pool Batu berada di pusat kota dekat Alun-Alun dan Jatim Park.'
              },
              {
                num: '02',
                title: 'Unit Prima & Rutin Servis Resmi',
                desc: 'Seluruh armada diservis berkala di bengkel resmi Honda. Bebas was-was saat melibas tanjakan Batu maupun jarak jauh.'
              },
              {
                num: '03',
                title: 'Jam Pelayanan Panjang (07.00 – 23.00)',
                desc: 'Melayani pengambilan pagi hari untuk sunrise Bromo hingga pengembalian larut malam setelah wisata kuliner.'
              },
              {
                num: '04',
                title: 'Layanan Antar & Ambil Unit Fleksibel',
                desc: 'Bisa diantar ke Stasiun Malang Kota Baru, hotel, homestay, atau terminal dengan konfirmasi jadwal yang fleksibel.'
              },
              {
                num: '05',
                title: 'Armada Khusus Bromo (Trail CRF 150L Wajib)',
                desc: 'Edukasi keamanan ketat: Unit matik dilarang ke pasir Bromo, kami sediakan Trail CRF 150L bertenaga dengan suspensi Showa.'
              },
              {
                num: '06',
                title: 'Transaksi Transparan via WhatsApp',
                desc: 'Tanpa form berbelit. Pesan terformat otomatis, verifikasi e-KTP aman, dan respon staf cepat via WhatsApp resmi.'
              }
            ].map((item) => (
              <div
                key={item.num}
                className="bg-[#162032] border border-[#223249] p-6 rounded-2xl relative overflow-hidden"
              >
                <span className="text-3xl font-black text-amber-500/20 absolute top-4 right-4">
                  {item.num}
                </span>
                <div className="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold text-sm mb-4">
                  {item.num}
                </div>
                <h3 className="text-base font-bold text-white mb-2">{item.title}</h3>
                <p className="text-xs sm:text-sm text-slate-400 leading-relaxed">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Booking Form Section (TASK-011 / shortcode [ryokou_booking_form]) */}
      <section id="booking-form" className="py-16 sm:py-24">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-2xl mx-auto mb-10">
            <span className="text-xs font-bold text-amber-400 tracking-widest uppercase bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full mb-3 inline-block">
              ZERO-FRICTION WHATSAPP BOOKING
            </span>
            <h2 className="text-2xl sm:text-4xl font-extrabold text-white mb-3">
              Formulir Pemesanan Sewa Motor
            </h2>
            <p className="text-slate-400 text-xs sm:text-sm">
              Lengkapi data sewa dan jadwal. Sistem menghitung durasi secara otomatis dan menyusun draf pesan WhatsApp siap kirim ke Admin resmi.
            </p>
          </div>

          <div className="bg-[#162032] border border-[#223249] rounded-2xl p-6 sm:p-8 shadow-xl">
            {/* Step 1: Route & Motor Selection */}
            <div className="pb-6 mb-6 border-b border-[#223249]">
              <div className="flex items-center gap-2 mb-4">
                <span className="w-6 h-6 rounded-full bg-amber-500 text-slate-950 font-bold text-xs flex items-center justify-center">
                  1
                </span>
                <h3 className="font-bold text-white text-base">Rute Perjalanan & Pilihan Armada</h3>
              </div>

              {/* Destination Radio */}
              <div className="mb-4">
                <label className="text-xs font-semibold text-slate-300 block mb-2">
                  Tujuan Rute Perjalanan <span className="text-rose-500">*</span>
                </label>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <button
                    type="button"
                    onClick={() => handleDestinationChange('malang_batu')}
                    className={`text-left p-3.5 rounded-xl border transition-all flex items-start gap-3 ${
                      tripDestination === 'malang_batu'
                        ? 'bg-amber-500/10 border-amber-500 shadow-md shadow-amber-500/10'
                        : 'bg-[#0b1120] border-[#223249] hover:border-slate-600'
                    }`}
                  >
                    <span className="text-2xl">🏙️</span>
                    <div>
                      <strong className="text-xs sm:text-sm text-white block mb-0.5">
                        Malang Kota & Wisata Batu
                      </strong>
                      <span className="text-[11px] text-slate-400 leading-tight block">
                        Rute dalam kota, kampus, kuliner, dan tanjakan wisata Batu.
                      </span>
                    </div>
                  </button>

                  <button
                    type="button"
                    onClick={() => handleDestinationChange('bromo')}
                    className={`text-left p-3.5 rounded-xl border transition-all flex items-start gap-3 ${
                      tripDestination === 'bromo'
                        ? 'bg-amber-500/10 border-amber-500 shadow-md shadow-amber-500/10'
                        : 'bg-[#0b1120] border-[#223249] hover:border-slate-600'
                    }`}
                  >
                    <span className="text-2xl">🌋</span>
                    <div>
                      <strong className="text-xs sm:text-sm text-white block mb-0.5">
                        Trip Kaldera Bromo (Wajib CRF)
                      </strong>
                      <span className="text-[11px] text-amber-400/90 leading-tight block font-medium">
                        Unit otomatis dikunci ke Trail CRF 150L.
                      </span>
                    </div>
                  </button>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-xs font-semibold text-slate-300 block mb-1.5">
                    Model Armada Motor <span className="text-rose-500">*</span>
                  </label>
                  <select
                    value={selectedMotorId}
                    onChange={(e) => setSelectedMotorId(e.target.value)}
                    className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                  >
                    {FLEET_DATA.map((m) => (
                      <option
                        key={m.id}
                        value={m.id}
                        disabled={tripDestination === 'bromo' && !m.isBromoReady}
                      >
                        {m.name} (Rp {m.priceDaily.toLocaleString('id-ID')}/hari)
                        {m.isBromoReady ? ' - [Wajib Bromo]' : ''}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="text-xs font-semibold text-slate-300 block mb-1.5">
                    Lokasi Penyerahan Unit <span className="text-rose-500">*</span>
                  </label>
                  <select
                    value={pickupLocation}
                    onChange={(e) => setPickupLocation(e.target.value)}
                    className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                  >
                    <option value="Pool Dinoyo">Pool Dinoyo (Lowokwaru, Kota Malang)</option>
                    <option value="Pool Batu">Pool Batu (Jl. Diponegoro, Kota Batu)</option>
                    <option value="Stasiun Malang">Diantar ke Stasiun Malang Kota Baru (Sikon)</option>
                    <option value="Hotel/Homestay">Diantar ke Hotel / Penginapan (Sikon)</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Step 2: Schedule & Duration */}
            <div className="pb-6 mb-6 border-b border-[#223249]">
              <div className="flex items-center gap-2 mb-4">
                <span className="w-6 h-6 rounded-full bg-amber-500 text-slate-950 font-bold text-xs flex items-center justify-center">
                  2
                </span>
                <h3 className="font-bold text-white text-base">Jadwal Sewa & Kalkulasi Tarif</h3>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label className="text-xs font-semibold text-slate-300 block mb-1.5">
                    Mulai Sewa (07.00 - 23.00 WIB) <span className="text-rose-500">*</span>
                  </label>
                  <input
                    type="datetime-local"
                    value={startDateTime}
                    onChange={(e) => setStartDateTime(e.target.value)}
                    className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                  />
                </div>

                <div>
                  <label className="text-xs font-semibold text-slate-300 block mb-1.5">
                    Selesai Sewa (07.00 - 23.00 WIB) <span className="text-rose-500">*</span>
                  </label>
                  <input
                    type="datetime-local"
                    value={endDateTime}
                    onChange={(e) => setEndDateTime(e.target.value)}
                    className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                  />
                </div>
              </div>

              {/* Live Preview Box */}
              <div className="bg-[#0b1120] border-l-4 border-l-amber-500 border border-[#223249] rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div>
                  <span className="text-slate-400 block text-[11px]">Estimasi Durasi Sewa:</span>
                  <strong className="text-white text-sm">
                    {isValid ? `${days} Hari (~${Math.round(hours)} Jam)` : 'Jadwal belum valid'}
                  </strong>
                  <span className="text-[10px] text-slate-400 block mt-0.5">
                    *Toleransi keterlambatan (overtime) s/d 2 jam
                  </span>
                </div>
                <div className="sm:text-right">
                  <span className="text-slate-400 block text-[11px]">Estimasi Total Tarif:</span>
                  <strong className="text-amber-400 text-lg sm:text-xl font-black">
                    Rp {totalPrice.toLocaleString('id-ID')}
                  </strong>
                </div>
              </div>
            </div>

            {/* Step 3: Identity Fields */}
            <div className="pb-6 mb-6 border-b border-[#223249]">
              <div className="flex items-center gap-2 mb-4">
                <span className="w-6 h-6 rounded-full bg-amber-500 text-slate-950 font-bold text-xs flex items-center justify-center">
                  3
                </span>
                <h3 className="font-bold text-white text-base">Data Identitas Pelanggan (Sesuai e-KTP)</h3>
              </div>

              <div className="space-y-4 text-xs sm:text-sm">
                <div>
                  <label className="text-xs font-semibold text-slate-300 block mb-1">
                    Nama Lengkap Sesuai e-KTP <span className="text-rose-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={customerName}
                    onChange={(e) => setCustomerName(e.target.value)}
                    placeholder="Contoh: Dimas Aditya Pratama"
                    className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                  />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="text-xs font-semibold text-slate-300 block mb-1">
                      No. WhatsApp Aktif <span className="text-rose-500">*</span>
                    </label>
                    <input
                      type="tel"
                      value={customerWa}
                      onChange={(e) => setCustomerWa(e.target.value)}
                      placeholder="081234567890"
                      className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-slate-300 block mb-1">
                      Kontak Darurat Keluarga <span className="text-rose-500">*</span>
                    </label>
                    <input
                      type="tel"
                      value={customerEmergency}
                      onChange={(e) => setCustomerEmergency(e.target.value)}
                      placeholder="081345678901 (Keluarga tidak ikut trip)"
                      className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="text-xs font-semibold text-slate-300 block mb-1">
                      Alamat Sesuai KTP <span className="text-rose-500">*</span>
                    </label>
                    <textarea
                      rows={2}
                      value={customerKtpAddress}
                      onChange={(e) => setCustomerKtpAddress(e.target.value)}
                      placeholder="Alamat asal sesuai KTP"
                      className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                    />
                  </div>

                  <div>
                    <label className="text-xs font-semibold text-slate-300 block mb-1">
                      Tempat Menginap di Malang/Batu <span className="text-rose-500">*</span>
                    </label>
                    <textarea
                      rows={2}
                      value={customerStayAddress}
                      onChange={(e) => setCustomerStayAddress(e.target.value)}
                      placeholder="Hotel / Homestay / Kost"
                      className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                    />
                  </div>
                </div>

                <div>
                  <label className="text-xs font-semibold text-slate-300 block mb-1">
                    Catatan Tambahan (Fasilitas Helm / Jas Hujan):
                  </label>
                  <input
                    type="text"
                    value={rentalNotes}
                    onChange={(e) => setRentalNotes(e.target.value)}
                    placeholder="Ukuran helm L, jas hujan setelan, dll."
                    className="w-full bg-[#0b1120] border border-[#223249] text-white text-xs sm:text-sm rounded-lg p-2.5 focus:outline-none focus:border-amber-500"
                  />
                </div>
              </div>
            </div>

            {/* Submit to WhatsApp */}
            <div className="space-y-3">
              <a
                href={buildWhatsAppBookingUrl()}
                target="_blank"
                rel="noopener noreferrer"
                className="w-full inline-flex items-center justify-center gap-2 bg-[#25d366] hover:bg-[#128c7e] text-white font-bold text-sm sm:text-base py-3.5 px-6 rounded-xl transition-all shadow-lg shadow-emerald-950/40"
              >
                <Phone className="w-5 h-5" />
                <span>Kirim Pesanan ke WhatsApp Admin Ryokourent</span>
              </a>
              <p className="text-[11px] text-slate-400 text-center leading-relaxed">
                🔒 Data identitas Anda aman dilindungi sesuai UU Perlindungan Data Pribadi (UU PDP). Form dilengkapi honeypot anti-spam dan nonce token WordPress.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Detail Modal Component (Emulating single-motor.php) */}
      <AnimatePresence>
        {activeModalMotor && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <motion.div
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="bg-[#162032] border border-[#223249] rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl relative"
            >
              {/* Modal Header */}
              <div className="sticky top-0 bg-[#162032]/95 backdrop-blur-md px-6 py-4 border-b border-[#223249] flex items-center justify-between z-10">
                <div>
                  <span className="text-[11px] font-bold uppercase tracking-wider text-amber-400">
                    {activeModalMotor.categoryName}
                  </span>
                  <h3 className="text-xl font-bold text-white">{activeModalMotor.name}</h3>
                </div>
                <button
                  onClick={() => setActiveModalMotor(null)}
                  className="w-8 h-8 rounded-lg bg-[#0b1120] text-slate-400 hover:text-white flex items-center justify-center border border-[#223249]"
                >
                  <X className="w-4 h-4" />
                </button>
              </div>

              {/* Modal Body */}
              <div className="p-6 space-y-6">
                {/* Advisory Notice */}
                {activeModalMotor.isBromoReady ? (
                  <div className="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 flex gap-3 text-amber-300">
                    <span className="text-2xl">🌋</span>
                    <div>
                      <h4 className="text-sm font-bold text-white mb-1">Armada Resmi & Disetujui Trip Bromo</h4>
                      <p className="text-xs text-slate-300 leading-relaxed">
                        Unit ini dibekali suspensi upside-down Showa dan ban dual-purpose yang tangguh untuk melibas lautan pasir berbisik dan tanjakan Penanjakan dengan aman.
                      </p>
                    </div>
                  </div>
                ) : (
                  <div className="bg-rose-500/10 border border-rose-500/30 rounded-xl p-4 flex gap-3 text-rose-300">
                    <AlertTriangle className="w-5 h-5 shrink-0 text-rose-400 mt-0.5" />
                    <div>
                      <h4 className="text-sm font-bold text-white mb-1">Ketentuan Rute: Khusus Malang & Batu</h4>
                      <p className="text-xs text-slate-300 leading-relaxed">
                        <strong>Dilarang keras dibawa ke lautan pasir Bromo.</strong> Transmisi otomatis skutik rawan mengalami slip pada medan pasir. Untuk trip Bromo wajib menyewa Trail CRF 150L.
                      </p>
                    </div>
                  </div>
                )}

                {/* Technical Specifications */}
                <div>
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                    Spesifikasi Teknis
                  </h4>
                  <div className="grid grid-cols-2 gap-3 text-xs">
                    <div className="bg-[#0b1120] border border-[#223249] p-3 rounded-xl">
                      <span className="text-slate-400 block mb-0.5">Kapasitas Mesin</span>
                      <strong className="text-white text-sm">{activeModalMotor.engineCc} cc eSP</strong>
                    </div>
                    <div className="bg-[#0b1120] border border-[#223249] p-3 rounded-xl">
                      <span className="text-slate-400 block mb-0.5">Tipe Transmisi</span>
                      <strong className="text-white text-sm">{activeModalMotor.transmission}</strong>
                    </div>
                    <div className="bg-[#0b1120] border border-[#223249] p-3 rounded-xl col-span-2">
                      <span className="text-slate-400 block mb-0.5">Karakteristik Rute</span>
                      <strong className="text-white text-sm">{activeModalMotor.routeCharacter}</strong>
                    </div>
                  </div>
                </div>

                {/* Pricing Tiers */}
                <div className="bg-[#0b1120] border border-[#223249] rounded-xl p-4">
                  <h4 className="text-xs font-bold uppercase tracking-wider text-amber-400 mb-3">
                    Rincian Tarif Resmi
                  </h4>
                  <div className="space-y-2 text-xs">
                    <div className="flex justify-between py-1 border-b border-[#223249]/60">
                      <span className="text-slate-300">Tarif Harian (24 Jam)</span>
                      <strong className="text-amber-400 text-sm">
                        Rp {activeModalMotor.priceDaily.toLocaleString('id-ID')}
                      </strong>
                    </div>
                    <div className="flex justify-between py-1 border-b border-[#223249]/60">
                      <span className="text-slate-300">Paket Mingguan (7 Hari)</span>
                      <strong className="text-white">
                        Rp {activeModalMotor.priceWeekly.toLocaleString('id-ID')}
                      </strong>
                    </div>
                    <div className="flex justify-between py-1">
                      <span className="text-slate-300">Paket Bulanan (30 Hari)</span>
                      <strong className="text-white">
                        Rp {activeModalMotor.priceMonthly.toLocaleString('id-ID')}
                      </strong>
                    </div>
                  </div>
                  <span className="text-[10px] text-slate-400 block mt-2">
                    *Toleransi keterlambatan sewa (overtime) hingga 2 jam.
                  </span>
                </div>

                {/* Description */}
                <div>
                  <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                    Deskripsi Unit
                  </h4>
                  <p className="text-xs text-slate-300 leading-relaxed">
                    {activeModalMotor.description}
                  </p>
                </div>
              </div>

              {/* Modal Footer CTA */}
              <div className="sticky bottom-0 bg-[#162032] border-t border-[#223249] p-4 flex gap-3">
                <button
                  onClick={() => setActiveModalMotor(null)}
                  className="flex-1 bg-[#0b1120] hover:bg-[#1e2c44] border border-[#223249] text-slate-300 text-xs font-semibold py-3 rounded-xl transition-colors"
                >
                  Tutup
                </button>
                <a
                  href={getWaLink(activeModalMotor.name)}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex-[2] inline-flex items-center justify-center gap-2 bg-[#25d366] hover:bg-[#128c7e] text-white text-xs font-bold py-3 rounded-xl transition-colors"
                >
                  <Phone className="w-4 h-4" />
                  <span>Sewa Sekarang via WhatsApp</span>
                </a>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* WordPress Core Architecture Inspector Modal */}
      <AnimatePresence>
        {showCodeInspector && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: 20 }}
              className="bg-[#162032] border border-[#223249] rounded-2xl max-w-3xl w-full max-h-[85vh] overflow-y-auto shadow-2xl"
            >
              <div className="sticky top-0 bg-[#162032] px-6 py-4 border-b border-[#223249] flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <FileCode2 className="w-5 h-5 text-amber-500" />
                  <h3 className="font-bold text-white text-base">
                    Ryokourent WordPress Core Architecture Inspector
                  </h3>
                </div>
                <button
                  onClick={() => setShowCodeInspector(false)}
                  className="text-slate-400 hover:text-white"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <div className="p-6 space-y-4 text-xs">
                <div className="bg-[#0b1120] p-4 rounded-xl border border-[#223249]">
                  <h4 className="text-amber-400 font-bold mb-2">Status Implementasi Modul Sesi:</h4>
                  <ul className="space-y-2 text-slate-300">
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span><strong>TASK-004:</strong> CPT <code>motor</code>, meta fields sanitasi, dan kolom admin.</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span><strong>TASK-005:</strong> Metabox spesifikasi teknis, harga sewa, dan stok kuota fisik.</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span><strong>TASK-006:</strong> Taxonomy <code>kategori_motor</code> & seeding 3 kategori default.</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span><strong>TASK-007:</strong> Shortcode <code>[ryokou_catalog]</code>, templates, CSS & filter JS.</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span><strong>TASK-008:</strong> Single post template (<code>single-motor.php</code>) GeneratePress Child Theme.</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span><strong>TASK-009:</strong> CPT <code>penyewaan</code> terproteksi RBAC <code>manage_ryokourent_bookings</code> (UU PDP).</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span><strong>TASK-010:</strong> 5 Status booking kustom & filter <code>wp_insert_post_data</code> anti-reset.</span>
                    </li>
                    <li className="flex items-center gap-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
                      <span><strong>TASK-011:</strong> Form booking HTML5, kuncian rute Bromo ke CRF, honeypot & shortcode <code>[ryokou_booking_form]</code>.</span>
                    </li>
                  </ul>
                </div>

                <div className="bg-[#0b1120] p-4 rounded-xl border border-[#223249]">
                  <h4 className="text-slate-200 font-bold mb-2">File Arsitektur WordPress yang Terhubung:</h4>
                  <div className="font-mono text-[11px] text-slate-400 space-y-1">
                    <div>• wp-content/plugins/ryokourent-core/ryokourent-core.php</div>
                    <div>• wp-content/plugins/ryokourent-core/includes/post-types.php (CPT motor, penyewaan, custom status)</div>
                    <div>• wp-content/plugins/ryokourent-core/includes/taxonomies.php (kategori_motor)</div>
                    <div>• wp-content/plugins/ryokourent-core/includes/meta-boxes.php (motor & penyewaan metaboxes)</div>
                    <div>• wp-content/plugins/ryokourent-core/includes/meta-fields.php (skema & sanitasi)</div>
                    <div>• wp-content/plugins/ryokourent-core/public/forms.php (form booking HTML5)</div>
                    <div>• wp-content/plugins/ryokourent-core/public/shortcodes.php ([ryokou_catalog], [ryokou_booking_form])</div>
                    <div>• wp-content/plugins/ryokourent-core/public/templates.php (render card & grid)</div>
                    <div>• wp-content/plugins/ryokourent-core/assets/css/ryokourent-public.css (styling dark responsive)</div>
                    <div>• wp-content/plugins/ryokourent-core/assets/js/ryokourent-filter.js (filter & Bromo lock)</div>
                    <div>• wp-content/themes/generatepress-child/single-motor.php</div>
                    <div>• wp-content/themes/generatepress-child/templates/single-motor.php</div>
                    <div>• wp-content/themes/generatepress-child/functions.php</div>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Locations Section */}
      <section id="lokasi-pool" className="py-16 sm:py-24 border-b border-[#223249]">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-3xl mx-auto mb-12">
            <span className="text-xs font-bold text-amber-400 tracking-widest uppercase bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full mb-3 inline-block">
              Area Layanan
            </span>
            <h2 className="text-2xl sm:text-4xl font-extrabold text-white mb-4">
              Dua Lokasi Pool Resmi di Malang & Batu
            </h2>
            <p className="text-slate-400 text-sm sm:text-base">
              Lokasi strategis memudahkan pengambilan langsung atau titik awal pengantaran ke tempat menginap Anda.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="bg-[#162032] border border-[#223249] p-6 rounded-2xl">
              <div className="flex items-center justify-between mb-4">
                <span className="text-xs font-bold uppercase tracking-wider text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-full">
                  Pool 1 (Kota Malang)
                </span>
                <span className="text-xs text-slate-400">07.00 – 23.00 WIB</span>
              </div>
              <h3 className="text-lg font-bold text-white mb-2">Pool Dinoyo - Lowokwaru</h3>
              <p className="text-xs sm:text-sm text-slate-300 mb-4 leading-relaxed">
                Jl. MT Haryono Gg. 21 No. 23, Dinoyo, Lowokwaru, Malang.
              </p>
              <div className="text-xs text-slate-400 space-y-1 mb-4">
                <div>• Akses mudah ke kawasan kampus UB, UIN, dan koridor kuliner Soekarno-Hatta.</div>
                <div>• Titik jemput cepat ke Stasiun Malang Kota Baru & Stasiun Kota Lama.</div>
              </div>
            </div>

            <div className="bg-[#162032] border border-[#223249] p-6 rounded-2xl">
              <div className="flex items-center justify-between mb-4">
                <span className="text-xs font-bold uppercase tracking-wider text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-full">
                  Pool 2 (Kota Wisata Batu)
                </span>
                <span className="text-xs text-slate-400">07.00 – 23.00 WIB</span>
              </div>
              <h3 className="text-lg font-bold text-white mb-2">Pool Diponegoro - Batu</h3>
              <p className="text-xs sm:text-sm text-slate-300 mb-4 leading-relaxed">
                Jl. Belakang Pompa Bensin, Jl. Diponegoro, Kota Wisata Batu.
              </p>
              <div className="text-xs text-slate-400 space-y-1 mb-4">
                <div>• Berada di jantung kota wisata Batu, dekat Alun-Alun Batu & Pasar Laron.</div>
                <div>• Akses langsung menuju kawasan Jatim Park 1-3, Museum Angkut, & Selecta.</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* FAQ & Requirements */}
      <section id="syarat-faq" className="py-16 sm:py-24 bg-[#080d19]">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <span className="text-xs font-bold text-amber-400 tracking-widest uppercase bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full mb-3 inline-block">
              Pertanyaan Umum
            </span>
            <h2 className="text-2xl sm:text-3xl font-extrabold text-white mb-3">
              Syarat Sewa & Pertanyaan Sering Diajukan
            </h2>
            <p className="text-slate-400 text-xs sm:text-sm">
              Seluruh ketentuan dirancang transparan demi kenyamanan bersama.
            </p>
          </div>

          <div className="space-y-4">
            {[
              {
                q: 'Apa saja dokumen persyaratan sewa motor di Ryokourent?',
                a: 'Penyewa wajib menunjukkan e-KTP Asli serta 2 dokumen identitas pendukung yang sah (misal: SIM A, Paspor, BPJS, NPWP, KTM Mahasiswa, atau ID Pegawai).'
              },
              {
                q: 'Mengapa motor matik dilarang keras ke Lautan Pasir Bromo?',
                a: 'Karakter medan pasir berbisik Bromo sangat berat dan panas. Transmisi skutik (CVT) rawan terbakar belt dan mengalami slip total di tengah lautan pasir. Untuk trip Bromo wajib menggunakan unit Trail CRF 150L.'
              },
              {
                q: 'Apakah bisa diantar ke stasiun atau hotel?',
                a: 'Bisa! Kami melayani antar-jemput unit ke Stasiun Malang Kota Baru atau penginapan di sekitar Malang dan Batu dengan konfirmasi jadwal operasional (07.00 – 23.00 WIB).'
              },
              {
                q: 'Berapa batas toleransi keterlambatan pengembalian?',
                a: 'Kami memberikan toleransi overtime hingga 2 jam dari jam serah terima. Keterlambatan melebihi toleransi akan dikenakan biaya overtime harian proporsional.'
              }
            ].map((faq, i) => (
              <div key={i} className="bg-[#162032] border border-[#223249] p-5 rounded-xl">
                <h4 className="text-sm font-bold text-white mb-2 flex items-center gap-2">
                  <span className="text-amber-400">Q:</span> {faq.q}
                </h4>
                <p className="text-xs text-slate-300 leading-relaxed pl-5 border-l border-amber-500/40">
                  {faq.a}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-[#0b1120] border-t border-[#223249] py-12 text-xs text-slate-400">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <span className="font-bold text-white text-sm">Ryokourent</span>
            <span>— Rental Sepeda Motor Malang Raya & Kota Wisata Batu</span>
          </div>
          <div className="text-slate-500">
            &copy; {new Date().getFullYear()} Ryokourent. All rights reserved.
          </div>
        </div>
      </footer>
    </div>
  );
}
