import '@/components/admin/layouts/admin-layout.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';

export default function DashboardPage() {
  return (
    <AdminLayout title="Dashboard">
      <section>
        <h2>Welcome to the admin dashboard</h2>
        <p>Dashboard widgets and metrics will be added next.</p>
      </section>
    </AdminLayout>
  );
}
