import type { Metadata } from 'next';
import { Toaster } from 'sonner';
import './globals.css';
 
export const metadata: Metadata = {
  title:       'KitchenFlow',
  description: 'Catering and inventory management platform',
};
 
export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body>
        {children}
        <Toaster position="bottom-right" richColors />
      </body>
    </html>
  );
}
 