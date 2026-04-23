import '@/components/admin/layouts/admin-layout.css';
import './categories.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import CategoriesPanel from '@/components/admin/ui/CategoriesPanel';

export default function CategoriesPage() {
  return (
    <AdminLayout title="Categories">
      <CategoriesPanel />
    </AdminLayout>
  );
}
