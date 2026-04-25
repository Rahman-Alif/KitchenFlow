'use client';

import { useEffect, useMemo, useState } from 'react';
import AdminConfirmDialog from '@/components/admin/shared/AdminConfirmDialog';
import {
  AdminCategory,
  createCategory,
  deleteCategory,
  getCategories,
} from '@/lib/services/categories';

type SortKey = 'name' | 'created';
type SortDirection = 'asc' | 'desc';

export default function CategoriesPanel() {
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [name, setName] = useState('');
  const [search, setSearch] = useState('');
  const [sortKey, setSortKey] = useState<SortKey>('name');
  const [sortDirection, setSortDirection] = useState<SortDirection>('asc');
  const [submitting, setSubmitting] = useState(false);
  const [deletingId, setDeletingId] = useState<number | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<AdminCategory | null>(null);

  async function loadCategories() {
    setLoading(true);
    setError(null);
    const response = await getCategories();

    if (response.error || !response.data) {
      setCategories([]);
      setError(response.error ?? 'Failed to load categories.');
      setLoading(false);
      return;
    }

    setCategories(response.data);
    setLoading(false);
  }

  useEffect(() => {
    loadCategories();
  }, []);

  const visibleCategories = useMemo(() => {
    const normalized = search.trim().toLowerCase();
    if (!normalized) return categories;
    return categories.filter((category) => category.name.toLowerCase().includes(normalized));
  }, [categories, search]);

  async function handleCreateCategory(event: React.FormEvent) {
    event.preventDefault();
    const trimmed = name.trim();
    if (!trimmed || submitting) return;

    setSubmitting(true);
    setError(null);
    setSuccess(null);

    const response = await createCategory(trimmed);
    if (response.error) {
      setError(response.error);
      setSubmitting(false);
      return;
    }

    setName('');
    setSuccess('Category created successfully.');
    await loadCategories();
    setSubmitting(false);
  }

  function toggleSort(key: SortKey) {
    if (sortKey === key) {
      setSortDirection((previous) => (previous === 'asc' ? 'desc' : 'asc'));
      return;
    }

    setSortKey(key);
    setSortDirection('asc');
  }

  function handleDeleteCategory(category: AdminCategory) {
    if (deletingId !== null) return;
    setDeleteTarget(category);
  }

  async function confirmDeleteCategory() {
    if (!deleteTarget) return;

    setDeletingId(deleteTarget.id);
    setError(null);
    setSuccess(null);

    const response = await deleteCategory(deleteTarget.id);
    if (response.error) {
      setError(response.error);
      setDeletingId(null);
      return;
    }

    setDeleteTarget(null);
    setSuccess('Category deleted successfully.');
    await loadCategories();
    setDeletingId(null);
  }

  return (
    <section className="adm-cat">
      <form className="adm-cat-create" onSubmit={handleCreateCategory}>
        <h2>Create Category</h2>
        <div className="adm-cat-create-row">
          <input
            type="text"
            className="adm-cat-input"
            placeholder="Category name"
            value={name}
            onChange={(event) => setName(event.target.value)}
            required
            maxLength={100}
          />
          <button type="submit" className="adm-cat-primary-btn" disabled={submitting}>
            {submitting ? 'Creating...' : 'Add Category'}
          </button>
        </div>
      </form>

      <div className="adm-cat-list">
        <div className="adm-cat-list-head">
          <h2>Categories</h2>
          <input
            type="search"
            className="adm-cat-input"
            placeholder="Search category"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
          />
        </div>

        {error && <p className="adm-cat-error">{error}</p>}
        {success && <p className="adm-cat-success">{success}</p>}

        <div className="adm-cat-table-wrap">
          <table className="adm-cat-table">
          <thead>
            <tr>
              <th>
                <button type="button" className="adm-cat-sort-btn" onClick={() => toggleSort('name')}>
                  Name {sortKey === 'name' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
                </button>
              </th>
              <th>Created</th>
              <th>Action</th>
            </tr>
          </thead>
            <tbody>
              {loading ? (
                <tr>
                  <td colSpan={3}>Loading categories...</td>
                </tr>
              ) : visibleCategories.length === 0 ? (
                <tr>
                  <td colSpan={3}>No categories found.</td>
                </tr>
              ) : (
                visibleCategories.map((category) => (
                  <tr key={category.id}>
                    <td>{category.name}</td>
                    <td>{new Date(category.created_at).toLocaleDateString()}</td>
                    <td>
                      <button
                        type="button"
                        className="adm-cat-danger-btn"
                        onClick={() => handleDeleteCategory(category)}
                        disabled={deletingId === category.id}
                      >
                        {deletingId === category.id ? 'Deleting...' : 'Delete'}
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      <AdminConfirmDialog
        open={deleteTarget !== null}
        title="Delete Category"
        message={deleteTarget ? `Delete category "${deleteTarget.name}"? This action cannot be undone.` : ''}
        confirmText="Delete"
        danger
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDeleteCategory}
      />
    </section>
  );
}
