import '@/components/admin/layouts/admin-layout.css';
import '../users.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import UserForm from '@/components/admin/ui/UserForm';

export default function CreateUserPage() {
  return (
    <AdminLayout title="Create User">
      <UserForm mode="create" />
    </AdminLayout>
  );
}
