'use client';

import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';
import AdminConfirmDialog from '@/components/admin/shared/AdminConfirmDialog';
import {
  AdminUser,
  activateUser,
  bulkUploadUsers,
  deactivateUser,
  deleteUser,
  getUsers,
} from '@/lib/services/users';
import { User as UserIcon, Mail, Shield, Activity, Settings } from 'lucide-react';

type StatusFilter = 'all' | 'active' | 'inactive';
type RoleFilter   = 'all' | 'admin' | 'kitchen_staff' | 'user';
type SortKey      = 'name' | 'role' | 'status';
type SortDirection = 'asc' | 'desc';

export default function UsersList() {
  const [users,         setUsers]         = useState<AdminUser[]>([]);
  const [loading,       setLoading]       = useState(true);
  const [error,         setError]         = useState<string | null>(null);
  const [search,        setSearch]        = useState('');
  const [statusFilter,  setStatusFilter]  = useState<StatusFilter>('all');
  const [roleFilter,    setRoleFilter]    = useState<RoleFilter>('all');
  const [sortKey,       setSortKey]       = useState<SortKey>('name');
  const [sortDirection, setSortDirection] = useState<SortDirection>('asc');
  const [actionUserId,  setActionUserId]  = useState<number | null>(null);
  const [uploading,     setUploading]     = useState(false);
  const [bulkMessage,   setBulkMessage]   = useState<string | null>(null);
  const [deleteTarget,  setDeleteTarget]  = useState<AdminUser | null>(null);

  async function loadUsers() {
    setLoading(true);
    setError(null);
    const response = await getUsers();
    if (response.error || !response.data) {
      setUsers([]);
      setError(response.error ?? 'Failed to load users.');
      setLoading(false);
      return;
    }
    setUsers(response.data);
    setLoading(false);
  }

  useEffect(() => { loadUsers(); }, []);

  const filteredUsers = useMemo(() => {
    const normalized = search.trim().toLowerCase();

    const filtered = users.filter((user) => {
      const searchMatch =
        normalized.length === 0 ||
        user.name.toLowerCase().includes(normalized) ||
        user.email.toLowerCase().includes(normalized);

      const statusMatch =
        statusFilter === 'all' ||
        (statusFilter === 'active'   && user.is_active) ||
        (statusFilter === 'inactive' && !user.is_active);

      const roleMatch =
        roleFilter === 'all' || user.role === roleFilter;

      return searchMatch && statusMatch && roleMatch;
    });

    return [...filtered].sort((a, b) => {
      const dir = sortDirection === 'asc' ? 1 : -1;
      if (sortKey === 'name')   return a.name.localeCompare(b.name) * dir;
      if (sortKey === 'role')   return a.role.localeCompare(b.role) * dir;
      const sA = a.is_active ? 'active' : 'inactive';
      const sB = b.is_active ? 'active' : 'inactive';
      return sA.localeCompare(sB) * dir;
    });
  }, [users, search, statusFilter, roleFilter, sortDirection, sortKey]);

  function handleSortClick(key: SortKey) {
    if (sortKey === key) {
      setSortDirection((prev) => (prev === 'asc' ? 'desc' : 'asc'));
      return;
    }
    setSortKey(key);
    setSortDirection('asc');
  }

  // ── Optimistic toggle ──────────────────────────────────────
  // Update local state immediately. Revert if the API call fails.

  async function handleToggleActive(user: AdminUser) {
    if (actionUserId !== null) return;
    setActionUserId(user.id);
    setError(null);

    // Optimistically flip is_active in local state
    setUsers((prev) =>
      prev.map((u) => u.id === user.id ? { ...u, is_active: !u.is_active } : u)
    );

    const response = user.is_active
      ? await deactivateUser(user.id)
      : await activateUser(user.id);

    if (response.error) {
      // Revert on failure
      setUsers((prev) =>
        prev.map((u) => u.id === user.id ? { ...u, is_active: user.is_active } : u)
      );
      setError(response.error);
    }

    setActionUserId(null);
  }

  function handleDelete(user: AdminUser) {
    if (actionUserId !== null) return;
    setDeleteTarget(user);
  }

  async function confirmDelete() {
    if (!deleteTarget) return;
    setActionUserId(deleteTarget.id);
    setError(null);

    const response = await deleteUser(deleteTarget.id);
    if (response.error) {
      setError(response.error);
      setActionUserId(null);
      return;
    }

    // Remove from local state immediately — no reload needed
    setUsers((prev) => prev.filter((u) => u.id !== deleteTarget.id));
    setDeleteTarget(null);
    setActionUserId(null);
  }

  async function handleBulkUpload(file: File | null) {
    if (!file || uploading) return;
    setUploading(true);
    setBulkMessage(null);
    setError(null);

    const response = await bulkUploadUsers(file);
    if (response.error || !response.data) {
      setError(response.error ?? 'Bulk upload failed.');
      setUploading(false);
      return;
    }

    const failedRows = response.data.errors.length;
    setBulkMessage(
      `${response.data.created} users created.${failedRows > 0 ? ` ${failedRows} rows failed.` : ''}`
    );
    await loadUsers();
    setUploading(false);
  }

  return (
    <section className="adm-users">
      <div className="adm-users-toolbar">
        <div className="adm-users-filters">
          <input
            type="search"
            placeholder="Search by name or email"
            className="adm-users-search"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />

          <select
            className="adm-users-select"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as StatusFilter)}
          >
            <option value="all">All status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>

          <select
            className="adm-users-select"
            value={roleFilter}
            onChange={(e) => setRoleFilter(e.target.value as RoleFilter)}
          >
            <option value="all">All roles</option>
            <option value="admin">Admin</option>
            <option value="kitchen_staff">Kitchen Staff</option>
            <option value="user">User</option>
          </select>
        </div>

        <div className="adm-users-actions">
          <div className="adm-stat-badge">
            Total Users: <strong>{users.length}</strong>
          </div>
          <label className="adm-users-upload-btn">
            {uploading ? 'Uploading...' : 'Upload CSV'}
            <input
              type="file"
              accept=".csv,text/csv"
              onChange={(e) => handleBulkUpload(e.target.files?.[0] ?? null)}
              hidden
            />
          </label>
          <Link href="/users/create" className="adm-users-create-btn">
            Create User
          </Link>
        </div>
      </div>

      {error       && <p className="adm-users-error">{error}</p>}
      {bulkMessage && <p className="adm-users-success">{bulkMessage}</p>}

      <div className="adm-users-table-wrap">
        <table className="adm-users-table">
          <thead>
            <tr>
              <th>
                <button type="button" className="adm-users-sort-btn" onClick={() => handleSortClick('name')}>
                  <span className="adm-icon-wrapper"><UserIcon size={14} className="adm-icon" /> Name</span> {sortKey === 'name' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th>
                <span className="adm-icon-wrapper"><Mail size={14} className="adm-icon" /> Email</span>
              </th>
              <th>
                <button type="button" className="adm-users-sort-btn" onClick={() => handleSortClick('role')}>
                  <span className="adm-icon-wrapper"><Shield size={14} className="adm-icon" /> Role</span> {sortKey === 'role' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th>
                <button type="button" className="adm-users-sort-btn" onClick={() => handleSortClick('status')}>
                  <span className="adm-icon-wrapper"><Activity size={14} className="adm-icon" /> Status</span> {sortKey === 'status' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th colSpan={3}>
                <span className="adm-icon-wrapper"><Settings size={14} className="adm-icon" /> Actions</span>
              </th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr><td colSpan={7}>Loading users...</td></tr>
            ) : filteredUsers.length === 0 ? (
              <tr><td colSpan={7}>No users found.</td></tr>
            ) : (
              filteredUsers.map((user) => (
                <tr key={user.id} className="adm-users-row">
                  <td>{user.name}</td>
                  <td>{user.email}</td>
                  <td>{user.role}</td>
                  <td>
                    <span className={user.is_active ? 'adm-badge adm-badge--active' : 'adm-badge adm-badge--inactive'}>
                      {user.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td className="adm-users-action-col">
                    <Link href={`/users/${user.id}/edit`} className="adm-users-inline-btn">
                      Edit
                    </Link>
                  </td>
                  <td className="adm-users-action-col">
                    <button
                      type="button"
                      className={`adm-users-inline-btn adm-users-toggle-btn${actionUserId === user.id ? ' adm-users-toggle-btn--loading' : ''}`}
                      onClick={() => handleToggleActive(user)}
                      disabled={actionUserId === user.id}
                    >
                      {actionUserId === user.id
                        ? '...'
                        : user.is_active ? 'Deactivate' : 'Activate'
                      }
                    </button>
                  </td>
                  <td className="adm-users-action-col">
                    <button
                      type="button"
                      className="adm-users-inline-btn adm-users-inline-btn--danger"
                      onClick={() => handleDelete(user)}
                      disabled={actionUserId === user.id}
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      <AdminConfirmDialog
        open={deleteTarget !== null}
        title="Delete User"
        message={deleteTarget ? `Delete user "${deleteTarget.name}"? This action cannot be undone.` : ''}
        confirmText="Delete"
        danger
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />
    </section>
  );
}