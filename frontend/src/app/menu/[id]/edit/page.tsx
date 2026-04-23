import '@/components/admin/layouts/admin-layout.css';
import '../../menu.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import MenuItemForm from '@/components/admin/ui/MenuItemForm';

interface EditMenuItemPageProps {
  params: Promise<{ id: string }>;
}

export default async function EditMenuItemPage({ params }: EditMenuItemPageProps) {
  const { id } = await params;
  const menuItemId = Number(id);

  return (
    <AdminLayout title="Edit Menu Item">
      <MenuItemForm mode="edit" menuItemId={Number.isNaN(menuItemId) ? undefined : menuItemId} />
    </AdminLayout>
  );
}
