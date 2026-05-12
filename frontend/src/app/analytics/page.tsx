import '@/components/admin/layouts/admin-layout.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import './analytics.css';
import AnalyticsDashboard from '@/components/admin/ui/AnalyticsDashboard';

export default function AnalyticsPage() {
  return (
    <AdminLayout title="AI Analytics">
      <AnalyticsDashboard />
    </AdminLayout>
  );
}