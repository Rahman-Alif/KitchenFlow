'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminUser, UserRole, createUser, getUsers, updateUser } from '@/lib/services/users';

interface UserFormProps {
  mode: 'create' | 'edit';
  userId?: number;
}

const ROLE_OPTIONS: UserRole[] = ['admin', 'kitchen_staff', 'user'];

export default function UserForm({ mode, userId }: UserFormProps) {
  const router = useRouter();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [role, setRole] = useState<UserRole>('user');
  const [loading, setLoading] = useState(mode === 'edit');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function bootstrapEditForm() {
      if (mode !== 'edit' || !userId) return;

      setLoading(true);
      setError(null);
      const response = await getUsers();
      if (response.error || !response.data) {
        setError(response.error ?? 'Failed to load user details.');
        setLoading(false);
        return;
      }

      const targetUser = response.data.find((user: AdminUser) => user.id === userId);
      if (!targetUser) {
        setError('User not found.');
        setLoading(false);
        return;
      }

      setName(targetUser.name);
      setEmail(targetUser.email);
      setRole(targetUser.role);
      setLoading(false);
    }

    bootstrapEditForm();
  }, [mode, userId]);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    if (saving) return;

    setSaving(true);
    setError(null);

    const payload = { name: name.trim(), email: email.trim(), role };
    const response =
      mode === 'create'
        ? await createUser(payload)
        : await updateUser(userId as number, payload);

    if (response.error) {
      setError(response.error);
      setSaving(false);
      return;
    }

    router.push('/users');
  }

  return (
    <section className="adm-user-form-wrap">
      <form className="adm-user-form" onSubmit={handleSubmit}>
        <div className="adm-user-form-header">
          <h2>{mode === 'create' ? 'Create User' : 'Edit User'}</h2>
          <p>{mode === 'create' ? 'Password defaults to password123.' : 'Update user profile fields.'}</p>
        </div>

        {error && <div className="adm-users-error">{error}</div>}
        {loading ? (
          <p>Loading user details...</p>
        ) : (
          <>
            <div className="adm-user-form-grid">
              <label>
                <span>Name</span>
                <input
                  type="text"
                  value={name}
                  onChange={(event) => setName(event.target.value)}
                  required
                />
              </label>

              <label>
                <span>Email</span>
                <input
                  type="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  required
                />
              </label>

              <label>
                <span>Role</span>
                <select
                  value={role}
                  onChange={(event) => setRole(event.target.value as UserRole)}
                  required
                >
                  {ROLE_OPTIONS.map((option) => (
                    <option key={option} value={option}>
                      {option}
                    </option>
                  ))}
                </select>
              </label>
            </div>

            <div className="adm-user-form-actions">
              <button type="button" className="adm-users-inline-btn" onClick={() => router.push('/users')}>
                Cancel
              </button>
              <button type="submit" className="adm-users-create-btn" disabled={saving}>
                {saving ? 'Saving...' : mode === 'create' ? 'Create User' : 'Save Changes'}
              </button>
            </div>
          </>
        )}
      </form>
    </section>
  );
}
