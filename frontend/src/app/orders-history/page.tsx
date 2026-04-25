// frontend/src/app/orders-history/page.tsx

import '@/components/admin/layouts/admin-layout.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import './orders-history.css';
import OrderHistory from '@/components/admin/ui/OrderHistory';

export default function OrderHistoryPage() {
  return (
    <AdminLayout title="Order History">
      <OrderHistory />
    </AdminLayout>
  );
}