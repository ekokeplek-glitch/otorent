import type {Metadata} from 'next';
import './globals.css'; // Global styles

export const metadata: Metadata = {
  title: 'Ryokourent - Rental Sepeda Motor Malang Raya & Kota Wisata Batu',
  description: 'Rental Sepeda Motor Malang Raya & Kota Wisata Batu - Website & Core Booking Plugin Architecture',
  openGraph: {
    title: 'Ryokourent - Rental Sepeda Motor Malang Raya & Kota Wisata Batu',
    description: 'Rental Sepeda Motor Malang Raya & Kota Wisata Batu - Website & Core Booking Plugin Architecture',
    type: 'website',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Ryokourent - Rental Sepeda Motor Malang Raya & Kota Wisata Batu',
    description: 'Rental Sepeda Motor Malang Raya & Kota Wisata Batu - Website & Core Booking Plugin Architecture',
  },
};

export default function RootLayout({children}: {children: React.ReactNode}) {
  return (
    <html lang="en">
      <body suppressHydrationWarning>{children}</body>
    </html>
  );
}
