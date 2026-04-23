import '@/components/admin/layouts/admin-layout.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import './dashboard.css';
import DashboardOverview from '@/components/admin/ui/DashboardOverview';

export default function DashboardPage() {
  return (
    <AdminLayout title="Dashboard">
      <DashboardOverview />
    </AdminLayout>
  );
}
