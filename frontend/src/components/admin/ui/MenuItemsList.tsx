'use client';

import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';
import AdminConfirmDialog from '@/components/admin/shared/AdminConfirmDialog';
import { AdminMenuItem, deleteMenuItem, getMenuItems, restockMenuItem } from '@/lib/services/menuItems';

type SortKey = 'name' | 'category' | 'price' | 'stock' | 'status';
type SortDirection = 'asc' | 'desc';

export default function MenuItemsList() {
  const [items, setItems] = useState<AdminMenuItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState('');
  const [sortKey, setSortKey] = useState<SortKey>('name');
  const [sortDirection, setSortDirection] = useState<SortDirection>('asc');
  const [actionItemId, setActionItemId] = useState<number | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<AdminMenuItem | null>(null);
  const [restockTarget, setRestockTarget] = useState<AdminMenuItem | null>(null);
  const [restockQuantity, setRestockQuantity] = useState('10');

  async function loadMenuItems() {
    setLoading(true);
    setError(null);
    const response = await getMenuItems();

    if (response.error || !response.data) {
      setItems([]);
      setError(response.error ?? 'Failed to load menu items.');
      setLoading(false);
      return;
    }

    setItems(response.data);
    setLoading(false);
  }

  useEffect(() => {
    loadMenuItems();
  }, []);

  const visibleItems = useMemo(() => {
    const normalized = search.trim().toLowerCase();
    const filtered = items.filter((item) => {
      if (!normalized) return true;
      return (
        item.name.toLowerCase().includes(normalized) ||
        item.category.name.toLowerCase().includes(normalized)
      );
    });

    const sorted = [...filtered].sort((a, b) => {
      const direction = sortDirection === 'asc' ? 1 : -1;
      if (sortKey === 'name') return a.name.localeCompare(b.name) * direction;
      if (sortKey === 'category') return a.category.name.localeCompare(b.category.name) * direction;
      if (sortKey === 'price') return (Number(a.price) - Number(b.price)) * direction;
      if (sortKey === 'stock') return (a.stock_quantity - b.stock_quantity) * direction;
      const statusA = a.is_available ? 'available' : 'unavailable';
      const statusB = b.is_available ? 'available' : 'unavailable';
      return statusA.localeCompare(statusB) * direction;
    });

    return sorted;
  }, [items, search, sortDirection, sortKey]);

  function toggleSort(key: SortKey) {
    if (sortKey === key) {
      setSortDirection((previous) => (previous === 'asc' ? 'desc' : 'asc'));
      return;
    }

    setSortKey(key);
    setSortDirection('asc');
  }

  function handleDelete(item: AdminMenuItem) {
    if (actionItemId !== null) return;
    setDeleteTarget(item);
  }

  async function confirmDelete() {
    if (!deleteTarget) return;

    setActionItemId(deleteTarget.id);
    setError(null);
    const response = await deleteMenuItem(deleteTarget.id);
    if (response.error) {
      setError(response.error);
      setActionItemId(null);
      return;
    }

    setDeleteTarget(null);
    await loadMenuItems();
    setActionItemId(null);
  }

  function handleRestock(item: AdminMenuItem) {
    if (actionItemId !== null) return;
    setRestockQuantity('10');
    setRestockTarget(item);
  }

  async function confirmRestock() {
    if (!restockTarget) return;
    const quantity = Number(restockQuantity);
    if (Number.isNaN(quantity) || quantity < 1) {
      setError('Please provide a valid quantity (minimum 1).');
      return;
    }

    setActionItemId(restockTarget.id);
    setError(null);
    const response = await restockMenuItem(restockTarget.id, quantity);
    if (response.error) {
      setError(response.error);
      setActionItemId(null);
      return;
    }

    setRestockTarget(null);
    await loadMenuItems();
    setActionItemId(null);
  }

  return (
    <section className="adm-menu">
      <div className="adm-menu-toolbar">
        <input
          type="search"
          className="adm-menu-search"
          placeholder="Search by item or category"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
        />

        <Link href="/menu/create" className="adm-menu-primary-btn">
          Create Menu Item
        </Link>
      </div>

      {error && <p className="adm-menu-error">{error}</p>}

      <div className="adm-menu-table-wrap">
        <table className="adm-menu-table">
        <thead>
          <tr>
            <th>
              <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('name')}>
                Name {sortKey === 'name' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
              </button>
            </th>
            <th>
              <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('category')}>
                Category {sortKey === 'category' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
              </button>
            </th>
            <th>
              <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('price')}>
                Price {sortKey === 'price' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
              </button>
            </th>
            <th>
              <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('stock')}>
                Stock {sortKey === 'stock' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
              </button>
            </th>
            <th>
              <button type="button" className="adm-menu-sort-btn" onClick={() => toggleSort('status')}>
                Status {sortKey === 'status' ? (sortDirection === 'asc' ? '↑' : '↓') : ''}
              </button>
            </th>
            <th>Actions</th>
          </tr>
        </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan={6}>Loading menu items...</td>
              </tr>
            ) : visibleItems.length === 0 ? (
              <tr>
                <td colSpan={6}>No menu items found.</td>
              </tr>
            ) : (
              visibleItems.map((item) => {
                const lowStock = item.stock_quantity <= item.low_stock_threshold;
                return (
                  <tr key={item.id}>
                    <td>{item.name}</td>
                    <td>{item.category.name}</td>
                    <td>${Number(item.price).toFixed(2)}</td>
                    <td>
                      {item.stock_quantity}
                      {lowStock ? <span className="adm-menu-low-stock">Low</span> : null}
                    </td>
                    <td>
                      <span className={item.is_available ? 'adm-menu-badge adm-menu-badge--active' : 'adm-menu-badge'}>
                        {item.is_available ? 'Available' : 'Unavailable'}
                      </span>
                    </td>
                    <td className="adm-menu-row-actions">
                      <Link href={`/menu/${item.id}/edit`} className="adm-menu-inline-btn">
                        Edit
                      </Link>
                      <button
                        type="button"
                        className="adm-menu-inline-btn"
                        onClick={() => handleRestock(item)}
                        disabled={actionItemId === item.id}
                      >
                        {actionItemId === item.id ? 'Saving...' : 'Restock'}
                      </button>
                      <button
                        type="button"
                        className="adm-menu-inline-btn adm-menu-inline-btn--danger"
                        onClick={() => handleDelete(item)}
                        disabled={actionItemId === item.id}
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      <AdminConfirmDialog
        open={deleteTarget !== null}
        title="Delete Menu Item"
        message={deleteTarget ? `Delete menu item "${deleteTarget.name}"? This action cannot be undone.` : ''}
        confirmText="Delete"
        danger
        onCancel={() => setDeleteTarget(null)}
        onConfirm={confirmDelete}
      />

      <AdminConfirmDialog
        open={restockTarget !== null}
        title="Restock Item"
        message={restockTarget ? `Enter restock quantity for "${restockTarget.name}".` : ''}
        confirmText="Apply Restock"
        inputLabel="Quantity"
        inputType="number"
        inputMin={1}
        inputValue={restockQuantity}
        onInputChange={setRestockQuantity}
        onCancel={() => setRestockTarget(null)}
        onConfirm={confirmRestock}
      />
    </section>
  );
}
