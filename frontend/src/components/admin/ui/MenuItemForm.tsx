'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { AdminCategory, getCategories } from '@/lib/services/categories';
import {
  createMenuItem,
  getMenuItem,
  MenuItemPayload,
  updateMenuItem,
} from '@/lib/services/menuItems';

interface MenuItemFormProps {
  mode: 'create' | 'edit';
  menuItemId?: number;
}

export default function MenuItemForm({ mode, menuItemId }: MenuItemFormProps) {
  const router = useRouter();
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [loading, setLoading] = useState(mode === 'edit');
  const [bootstrapping, setBootstrapping] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [categoryId, setCategoryId] = useState('');
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [price, setPrice] = useState('');
  const [stockQuantity, setStockQuantity] = useState('');
  const [lowStockThreshold, setLowStockThreshold] = useState('10');
  const [isAvailable, setIsAvailable] = useState(true);
  const [imageFile, setImageFile] = useState<File | null>(null);

  useEffect(() => {
    async function bootstrapForm() {
      setBootstrapping(true);
      setError(null);

      const categoriesResponse = await getCategories();
      if (categoriesResponse.error || !categoriesResponse.data) {
        setError(categoriesResponse.error ?? 'Failed to load categories.');
        setBootstrapping(false);
        return;
      }

      setCategories(categoriesResponse.data);
      if (categoriesResponse.data.length > 0) {
        setCategoryId(String(categoriesResponse.data[0].id));
      }

      if (mode === 'edit' && menuItemId) {
        setLoading(true);
        const itemResponse = await getMenuItem(menuItemId);
        if (itemResponse.error || !itemResponse.data) {
          setError(itemResponse.error ?? 'Failed to load menu item.');
          setLoading(false);
          setBootstrapping(false);
          return;
        }

        setCategoryId(String(itemResponse.data.category.id));
        setName(itemResponse.data.name);
        setDescription(itemResponse.data.description ?? '');
        setPrice(String(itemResponse.data.price));
        setStockQuantity(String(itemResponse.data.stock_quantity));
        setLowStockThreshold(String(itemResponse.data.low_stock_threshold));
        setIsAvailable(Boolean(itemResponse.data.is_available));
        setLoading(false);
      }

      setBootstrapping(false);
    }

    bootstrapForm();
  }, [mode, menuItemId]);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    if (saving || !categoryId) return;

    setSaving(true);
    setError(null);

    const payload: MenuItemPayload = {
      category_id: Number(categoryId),
      name: name.trim(),
      description: description.trim(),
      price: Number(price),
      stock_quantity: Number(stockQuantity),
      low_stock_threshold: Number(lowStockThreshold),
      is_available: isAvailable,
      image: imageFile,
    };

    const response =
      mode === 'create'
        ? await createMenuItem(payload)
        : await updateMenuItem(menuItemId as number, payload);

    if (response.error) {
      setError(response.error);
      setSaving(false);
      return;
    }

    router.push('/menu');
  }

  return (
    <section className="adm-menu-form-wrap">
      <form className="adm-menu-form" onSubmit={handleSubmit}>
        <div className="adm-menu-form-head">
          <h2>{mode === 'create' ? 'Create Menu Item' : 'Edit Menu Item'}</h2>
          <p>Set pricing, stock, availability, and image from one form.</p>
        </div>

        {error && <p className="adm-menu-error">{error}</p>}
        {bootstrapping || loading ? (
          <p>Loading form data...</p>
        ) : (
          <>
            <div className="adm-menu-form-grid">
              <label>
                <span>Category</span>
                <select
                  value={categoryId}
                  onChange={(event) => setCategoryId(event.target.value)}
                  required
                >
                  {categories.map((category) => (
                    <option key={category.id} value={category.id}>
                      {category.name}
                    </option>
                  ))}
                </select>
              </label>

              <label>
                <span>Name</span>
                <input value={name} onChange={(event) => setName(event.target.value)} required maxLength={150} />
              </label>

              <label>
                <span>Description</span>
                <textarea
                  value={description}
                  onChange={(event) => setDescription(event.target.value)}
                  rows={3}
                />
              </label>

              <label>
                <span>Price</span>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  value={price}
                  onChange={(event) => setPrice(event.target.value)}
                  required
                />
              </label>

              <label>
                <span>Stock Quantity</span>
                <input
                  type="number"
                  min="0"
                  value={stockQuantity}
                  onChange={(event) => setStockQuantity(event.target.value)}
                  required
                />
              </label>

              <label>
                <span>Low Stock Threshold</span>
                <input
                  type="number"
                  min="0"
                  value={lowStockThreshold}
                  onChange={(event) => setLowStockThreshold(event.target.value)}
                  required
                />
              </label>

              <label>
                <span>Image</span>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(event) => setImageFile(event.target.files?.[0] ?? null)}
                />
              </label>

              <label className="adm-menu-checkbox">
                <input
                  type="checkbox"
                  checked={isAvailable}
                  onChange={(event) => setIsAvailable(event.target.checked)}
                />
                <span>Available for ordering</span>
              </label>
            </div>

            <div className="adm-menu-form-actions">
              <button type="button" className="adm-menu-inline-btn" onClick={() => router.push('/menu')}>
                Cancel
              </button>
              <button type="submit" className="adm-menu-primary-btn" disabled={saving}>
                {saving ? 'Saving...' : mode === 'create' ? 'Create Item' : 'Save Changes'}
              </button>
            </div>
          </>
        )}
      </form>
    </section>
  );
}
