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

type StatusFilter = 'all' | 'active' | 'inactive';
type SortKey = 'name' | 'role' | 'status';
type SortDirection = 'asc' | 'desc';

export default function UsersList() {
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [sortKey, setSortKey] = useState<SortKey>('name');
  const [sortDirection, setSortDirection] = useState<SortDirection>('asc');
  const [actionUserId, setActionUserId] = useState<number | null>(null);
  const [uploading, setUploading] = useState(false);
  const [bulkMessage, setBulkMessage] = useState<string | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<AdminUser | null>(null);

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

  useEffect(() => {
    loadUsers();
  }, []);

  const filteredUsers = useMemo(() => {
    const normalized = search.trim().toLowerCase();

    const filtered = users.filter((user) => {
      const searchMatch = normalized.length === 0
        || user.name.toLowerCase().includes(normalized)
        || user.email.toLowerCase().includes(normalized);

      const statusMatch =
        statusFilter === 'all' ||
        (statusFilter === 'active' && user.is_active) ||
        (statusFilter === 'inactive' && !user.is_active);

      return searchMatch && statusMatch;
    });

    const sorted = [...filtered].sort((a, b) => {
      const direction = sortDirection === 'asc' ? 1 : -1;

      if (sortKey === 'name') {
        return a.name.localeCompare(b.name) * direction;
      }

      if (sortKey === 'role') {
        return a.role.localeCompare(b.role) * direction;
      }

      const statusA = a.is_active ? 'active' : 'inactive';
      const statusB = b.is_active ? 'active' : 'inactive';
      return statusA.localeCompare(statusB) * direction;
    });

    return sorted;
  }, [users, search, statusFilter, sortDirection, sortKey]);

  function handleSortClick(key: SortKey) {
    if (sortKey === key) {
      setSortDirection((previous) => (previous === 'asc' ? 'desc' : 'asc'));
      return;
    }

    setSortKey(key);
    setSortDirection('asc');
  }

  async function handleToggleActive(user: AdminUser) {
    if (actionUserId !== null) return;
    setActionUserId(user.id);
    setError(null);

    const response = user.is_active ? await deactivateUser(user.id) : await activateUser(user.id);
    if (response.error) {
      setError(response.error);
      setActionUserId(null);
      return;
    }

    await loadUsers();
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

    setDeleteTarget(null);
    await loadUsers();
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
            onChange={(event) => setSearch(event.target.value)}
          />

          <select
            className="adm-users-select"
            value={statusFilter}
            onChange={(event) => setStatusFilter(event.target.value as StatusFilter)}
          >
            <option value="all">All status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div className="adm-users-actions">
          <label className="adm-users-upload-btn">
            {uploading ? 'Uploading...' : 'Upload CSV'}
            <input
              type="file"
              accept=".csv,text/csv"
              onChange={(event) => handleBulkUpload(event.target.files?.[0] ?? null)}
              hidden
            />
          </label>
          <Link href="/users/create" className="adm-users-create-btn">
            Create User
          </Link>
        </div>
      </div>

      {error && <p className="adm-users-error">{error}</p>}
      {bulkMessage && <p className="adm-users-success">{bulkMessage}</p>}

      <div className="adm-users-table-wrap">
        <table className="adm-users-table">
          <thead>
            <tr>
              <th>
                <button type="button" className="adm-users-sort-btn" onClick={() => handleSortClick('name')}>
                  Name
                </button>
              </th>
              <th>Email</th>
              <th>
                <button type="button" className="adm-users-sort-btn" onClick={() => handleSortClick('role')}>
                  Role
                </button>
              </th>
              <th>
                <button type="button" className="adm-users-sort-btn" onClick={() => handleSortClick('status')}>
                  Status
                </button>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan={5}>Loading users...</td>
              </tr>
            ) : filteredUsers.length === 0 ? (
              <tr>
                <td colSpan={5}>No users found.</td>
              </tr>
            ) : (
              filteredUsers.map((user) => (
                <tr key={user.id}>
                  <td>{user.name}</td>
                  <td>{user.email}</td>
                  <td>{user.role}</td>
                  <td>
                    <span className={user.is_active ? 'adm-badge adm-badge--active' : 'adm-badge'}>
                      {user.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td className="adm-users-row-actions">
                    <Link href={`/users/${user.id}/edit`} className="adm-users-inline-btn">
                      Edit
                    </Link>
                    <button
                      type="button"
                      className="adm-users-inline-btn"
                      onClick={() => handleToggleActive(user)}
                      disabled={actionUserId === user.id}
                    >
                      {actionUserId === user.id ? 'Saving...' : user.is_active ? 'Deactivate' : 'Activate'}
                    </button>
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
