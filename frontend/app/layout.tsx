import './globals.css';
import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Final Pro Frontend',
  description: 'Next.js frontend for the Final Pro monorepo',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
