import '@/components/admin/layouts/admin-layout.css';
import '../menu.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import MenuItemForm from '@/components/admin/ui/MenuItemForm';

export default function CreateMenuItemPage() {
  return (
    <AdminLayout title="Create Menu Item">
      <MenuItemForm mode="create" />
    </AdminLayout>
  );
}
