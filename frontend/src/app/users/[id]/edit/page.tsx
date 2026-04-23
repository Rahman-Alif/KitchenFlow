import '@/components/admin/layouts/admin-layout.css';
import '../../users.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import UserForm from '@/components/admin/ui/UserForm';

interface EditUserPageProps {
  params: Promise<{ id: string }>;
}

export default async function EditUserPage({ params }: EditUserPageProps) {
  const { id } = await params;
  const userId = Number(id);

  return (
    <AdminLayout title="Edit User">
      <UserForm mode="edit" userId={Number.isNaN(userId) ? undefined : userId} />
    </AdminLayout>
  );
}
