// frontend/src/app/messages/page.tsx
import '@/components/admin/layouts/admin-layout.css';
import AdminLayout from '@/components/admin/layouts/AdminLayout';
import './messages.css';
import MessagesPortal from '@/components/admin/ui/MessagesPortal';

export default function MessagesPage() {
  return (
    <AdminLayout title="Messages">
      <MessagesPortal />
    </AdminLayout>
  );
}
