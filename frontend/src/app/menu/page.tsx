import '@/components/admin/layouts/admin-layout.css';
import './menu.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import MenuItemsList from '@/components/admin/ui/MenuItemsList';

export default function MenuPage() {
  return (
    <AdminLayout title="Menu Items">
      <MenuItemsList />
    </AdminLayout>
  );
}
