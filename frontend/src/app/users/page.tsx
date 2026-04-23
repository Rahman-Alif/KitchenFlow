import '@/components/admin/layouts/admin-layout.css';
import './users.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import UsersList from '@/components/admin/ui/UsersList';

export default function UsersPage() {
  return (
    <AdminLayout title="Users">
      <UsersList />
    </AdminLayout>
  );
}
